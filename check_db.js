const { initializeApp, cert } = require('firebase-admin/app');
const { getFirestore } = require('firebase-admin/firestore');
const serviceAccount = require('./firebase-admin-key.json');

const app = initializeApp({
  credential: cert(serviceAccount)
});

const db = getFirestore(app);

async function checkCollections() {
  const membersSnap = await db.collection('members').get();
  console.log(`Documents in 'members' (plural): ${membersSnap.size}`);
  const memberSnap = await db.collection('member').get();
  console.log(`Documents in 'member' (singular): ${memberSnap.size}`);
}

checkCollections().then(() => process.exit(0)).catch(err => {
  console.error(err);
  process.exit(1);
});
