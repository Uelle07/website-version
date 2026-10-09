import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

const firebaseConfig = {
  projectId: "activity-8-advance-web",
  appId: "1:532150151046:web:ebe42232a75bf375162e07",
  storageBucket: "activity-8-advance-web.firebasestorage.app",
  apiKey: "AIzaSyDw9e9ujAj9XmBjx1reZ41yPZxseVhLcto",
  authDomain: "activity-8-advance-web.firebaseapp.com",
  messagingSenderId: "532150151046",
  measurementId: "G-GGH72XJJ40"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

export { app, db, auth };

