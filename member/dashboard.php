<?php
/**
 * member/dashboard.php
 * -------------------------------------------------------
 * MODULE — Member Dashboard
 * Displays the member hero profile banner, account overview,
 * and navigation tile cards for member actions.
 * -------------------------------------------------------
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../pages/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Member ID Hero Banner Container -->
    <div class="hero-card">
        <div class="hero-profile">
            <div class="profile-avatar-wrapper">
                <img id="memberPhoto" src="../assets/images/default-avatar.png" alt="Member Photo" class="profile-avatar">
                <span class="status-badge" title="Active Member"></span>
            </div>
            <div class="hero-info">
                <div class="acc-number">
                    <i class="fa-solid fa-id-card"></i>
                    <span id="memberAccountNo">Loading...</span>
                </div>
                <h2 class="hero-welcome-title" id="memberWelcomeTitle">Welcome!</h2>
                <div class="meta-details">
                    <span><i class="fa-solid fa-shield-halved" style="color: #10b981; filter: drop-shadow(0 0 6px rgba(16,185,129,0.4));"></i> Verified Member</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Grid Header Section -->
    <div class="grid-header">
        <h3>What would you like to do?</h3>
    </div>

    <!-- Actions Navigation Tile Grid (3 items top row, 2 centered bottom row) -->
    <div class="custom-actions-grid five-cards-grid">
        <!-- 1. View My Information -->
        <a href="profile.php" class="tile-card">
            <div>
                <div class="tile-icon-box"><i class="fa-solid fa-user-gear"></i></div>
                <div class="tile-content">
                    <h4>View My Information</h4>
                    <p>See your personal, contact, and family details on file.</p>
                </div>
            </div>
            <div class="tile-arrow"><span>View Details</span> <i class="fa-solid fa-arrow-right"></i></div>
        </a>

        <!-- 2. Update My Information -->
        <a href="update_info.php" class="tile-card">
            <div>
                <div class="tile-icon-box"><i class="fa-solid fa-pen-to-square"></i></div>
                <div class="tile-content">
                    <h4>Update My Information</h4>
                    <p>Edit personal, contact, spouse, and children information.</p>
                </div>
            </div>
            <div class="tile-arrow"><span>Update Now</span> <i class="fa-solid fa-arrow-right"></i></div>
        </a>

        <!-- 3. Upload Photo -->
        <a href="upload_photo.php" class="tile-card">
            <div>
                <div class="tile-icon-box"><i class="fa-solid fa-camera"></i></div>
                <div class="tile-content">
                    <h4>Upload Photo</h4>
                    <p>Upload or replace your passport-size photo.</p>
                </div>
            </div>
            <div class="tile-arrow"><span>Upload</span> <i class="fa-solid fa-arrow-right"></i></div>
        </a>

        <!-- 4. Cooperative Surveys -->
        <a href="surveys.php" class="tile-card">
            <div>
                <div class="tile-icon-box"><i class="fa-solid fa-square-poll-vertical"></i></div>
                <div class="tile-content">
                    <h4>Cooperative Surveys</h4>
                    <p>Participate in active cooperative surveys and share feedback.</p>
                </div>
            </div>
            <div class="tile-arrow"><span>Take Survey</span> <i class="fa-solid fa-arrow-right"></i></div>
        </a>

        <!-- 5. Change Password -->
        <a href="change_password.php" class="tile-card">
            <div>
                <div class="tile-icon-box"><i class="fa-solid fa-key"></i></div>
                <div class="tile-content">
                    <h4>Change Password</h4>
                    <p>Update your account security password.</p>
                </div>
            </div>
            <div class="tile-arrow"><span>Change Password</span> <i class="fa-solid fa-arrow-right"></i></div>
        </a>
    </div>
</div>

<!-- Custom Layout and Responsive Grid Styles -->
<style>
.five-cards-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 24px;
    max-width: 1140px;
    margin: 0 auto;
}

.five-cards-grid .tile-card {
    flex: 0 1 calc(33.333% - 18px);
    width: calc(33.333% - 18px);
    min-width: 280px;
    max-width: 360px;
    box-sizing: border-box;
}

@media (max-width: 991px) {
    .five-cards-grid .tile-card {
        flex: 0 1 calc(50% - 14px);
        width: calc(50% - 14px);
        max-width: none;
    }
}

@media (max-width: 640px) {
    .five-cards-grid .tile-card {
        flex: 1 1 100%;
        width: 100%;
        max-width: 100%;
    }
}
</style>

<script type="module">
import { auth, db } from '../assets/js/firebase-init.js';
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
import { doc, getDoc, collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = '../login.php';
        return;
    }
    
    try {
        let memberDoc = await getDoc(doc(db, 'member', user.uid));
        let memberData = null;
        
        if (memberDoc.exists()) {
            memberData = memberDoc.data();
        } else {
            const q = query(collection(db, 'member'), where('member_id', '==', user.uid));
            const querySnapshot = await getDocs(q);
            if (!querySnapshot.empty) {
                memberData = querySnapshot.docs[0].data();
            }
        }
        
        if (memberData) {
            const photoUrl = memberData.passport_photo ? ('../uploads/' + memberData.passport_photo) : '../assets/images/default-avatar.png';
            document.getElementById('memberPhoto').src = photoUrl;
            
            const accNo = memberData.account_number || 'N/A';
            document.getElementById('memberAccountNo').innerText = 'ACCT NO. ' + accNo;
            
            const fullName = `${memberData.firstname || ''} ${memberData.lastname || ''}`.trim();
            document.getElementById('memberWelcomeTitle').innerText = 'Welcome, ' + fullName + '!';
        } else {
            if(typeof showToast === 'function') showToast('We could not find your member record.', 'error');
            setTimeout(() => { auth.signOut(); }, 2000);
        }
    } catch (error) {
        console.error("Error fetching member data:", error);
    }
});
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>
