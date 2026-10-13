const { initializeApp, cert } = require('firebase-admin/app');
const { getStorage } = require('firebase-admin/storage');
const { getFirestore } = require('firebase-admin/firestore');
const fs = require('fs');
const path = require('path');
const serviceAccount = require('../firebase-admin-key.json');

initializeApp({
  credential: cert(serviceAccount),
  storageBucket: serviceAccount.project_id + '.appspot.com'
});

const db = getFirestore();
const bucket = getStorage().bucket();

async function migrateFiles() {
    console.log("Starting File Migration to Firebase Storage...");
    const uploadsDir = path.join(__dirname, '../uploads');
    
    if (!fs.existsSync(uploadsDir)) {
        console.log("Uploads directory not found.");
        return;
    }

    const files = fs.readdirSync(uploadsDir);
    let uploadedCount = 0;
    
    for (const file of files) {
        if (file.endsWith('.php') || file === '.htaccess') continue; // skip code files
        
        const filePath = path.join(uploadsDir, file);
        const destination = `uploads/${file}`;
        
        console.log(`Uploading ${file}...`);
        
        try {
            await bucket.upload(filePath, {
                destination: destination,
                metadata: {
                    cacheControl: 'public, max-age=31536000',
                }
            });
            await bucket.file(destination).makePublic();
            const publicUrl = `https://storage.googleapis.com/${bucket.name}/${destination}`;
            
            console.log(`Success: ${publicUrl}`);
            uploadedCount++;
        } catch (error) {
            console.error(`Error uploading ${file}:`, error.message);
        }
    }
    
    console.log(`Migration complete. Uploaded ${uploadedCount} files.`);
}

migrateFiles().catch(console.error);
