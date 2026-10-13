const { initializeApp, cert } = require('firebase-admin/app');
const { getFirestore, FieldValue } = require('firebase-admin/firestore');
const mysql = require('mysql2/promise');
const serviceAccount = require('../firebase-admin-key.json');

// Initialize Firebase Admin
initializeApp({
  credential: cert(serviceAccount)
});

const db = getFirestore();

async function testConnection() {
    try {
        console.log(`Successfully connected to Firebase Project: ${serviceAccount.project_id}`);
        
        // Test Firestore Write
        const docRef = db.collection('migration_status').doc('test');
        await docRef.set({
            status: 'connected',
            timestamp: FieldValue.serverTimestamp(),
            message: 'Firebase Admin SDK is working perfectly!'
        });
        
        console.log('Successfully wrote test document to Firestore!');

        // Test MySQL connection
        const connection = await mysql.createConnection({
            host: 'localhost',
            user: 'root',
            password: '',
            database: 'coop_member_portal'
        });
        
        console.log('Successfully connected to local MySQL database!');
        const [rows] = await connection.execute('SELECT COUNT(*) as member_count FROM member');
        console.log(`Found ${rows[0].member_count} members in MySQL ready to migrate.`);
        
        await connection.end();
        process.exit(0);
    } catch (error) {
        console.error('Error during connection test:', error);
        process.exit(1);
    }
}

testConnection();
