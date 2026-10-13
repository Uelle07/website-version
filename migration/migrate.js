const { initializeApp, cert } = require('firebase-admin/app');
const { getFirestore, FieldValue } = require('firebase-admin/firestore');
const mysql = require('mysql2/promise');
const serviceAccount = require('../firebase-admin-key.json');

initializeApp({
  credential: cert(serviceAccount)
});
const db = getFirestore();

async function migrateData() {
    console.log("Starting Migration Process...");
    
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'root',
        password: '',
        database: 'coop_member_portal'
    });

    // 1. Migrate Staff
    console.log("Migrating Staff...");
    const [staffRows] = await connection.execute('SELECT * FROM staff');
    const staffBatch = db.batch();
    for (const staff of staffRows) {
        const docRef = db.collection('staff').doc(staff.staff_id.toString());
        // Do not migrate passwords in plaintext! (Ideally we'd create Auth users, but we'll just migrate profiles here)
        const { password, ...staffData } = staff;
        staffBatch.set(docRef, staffData);
    }
    await staffBatch.commit();
    console.log(`Migrated ${staffRows.length} staff records.`);

    // 2. Migrate Members
    console.log("Migrating Members...");
    const [memberRows] = await connection.execute('SELECT * FROM member');
    for (const member of memberRows) {
        const memberId = member.member_id.toString();
        const docRef = db.collection('members').doc(memberId);
        
        // Fetch relations
        const [[spouse]] = await connection.execute('SELECT spouse_name, occupation, employer FROM spouse WHERE member_id = ?', [member.member_id]);
        const [[parents]] = await connection.execute('SELECT father_name, mother_name FROM parents WHERE member_id = ?', [member.member_id]);
        const [children] = await connection.execute('SELECT child_name, birthday FROM children WHERE member_id = ?', [member.member_id]);
        const [references] = await connection.execute('SELECT reference_member_name, reference_member_id, signature FROM member_references WHERE member_id = ?', [member.member_id]);
        
        const memberData = {
            ...member,
            spouse: spouse || null,
            parents: parents || null,
            children: children,
            references: references
        };
        
        await docRef.set(memberData);

        // Update History as Subcollection
        const [history] = await connection.execute('SELECT * FROM update_history WHERE member_id = ?', [member.member_id]);
        if (history.length > 0) {
            const historyBatch = db.batch();
            for (const h of history) {
                const hRef = docRef.collection('update_history').doc(h.update_id.toString());
                historyBatch.set(hRef, h);
            }
            await historyBatch.commit();
        }
    }
    console.log(`Migrated ${memberRows.length} members.`);

    // 3. Migrate Surveys
    console.log("Migrating Surveys...");
    const [surveyRows] = await connection.execute('SELECT * FROM surveys');
    for (const survey of surveyRows) {
        const surveyId = survey.survey_id.toString();
        const docRef = db.collection('surveys').doc(surveyId);
        
        // Get questions
        const [questions] = await connection.execute('SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY question_order ASC', [survey.survey_id]);
        
        // For each question, get choices
        for (let i = 0; i < questions.length; i++) {
            const [choices] = await connection.execute('SELECT * FROM survey_choices WHERE question_id = ? ORDER BY choice_order ASC', [questions[i].question_id]);
            questions[i].choices = choices;
        }

        const surveyData = {
            ...survey,
            questions: questions
        };
        
        await docRef.set(surveyData);
    }
    console.log(`Migrated ${surveyRows.length} surveys.`);

    // 4. Migrate Responses
    console.log("Migrating Responses...");
    // Let's create a single responses collection
    const [responseRows] = await connection.execute('SELECT * FROM responses');
    const responseBatch = db.batch();
    let count = 0;
    
    for (const response of responseRows) {
        const docRef = db.collection('responses').doc(response.response_id.toString());
        responseBatch.set(docRef, response);
        count++;
        
        // Firestore batches have a limit of 500, but we likely have few responses for testing
        // If > 490, we would need to commit and create a new batch. We'll handle it simply for now.
        if (count % 450 === 0) {
            await responseBatch.commit();
        }
    }
    if (count % 450 !== 0) {
        await responseBatch.commit();
    }
    console.log(`Migrated ${responseRows.length} responses.`);

    console.log("Migration Complete!");
    await connection.end();
}

migrateData().catch(console.error);
