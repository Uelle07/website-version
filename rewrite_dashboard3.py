import re

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove require_staff()
content = content.replace('require_staff();', '')

# Replace welcome banner PHP vars
content = re.sub(
    r'<\?php if \(!empty\(\$_SESSION\[\'profile_photo\'\]\)\): \?>\s*<img src="<\?= e\(photo_url\(\$_SESSION\[\'profile_photo\'\]\)\) \?>" alt="Admin Profile Photo" class="admin-banner-avatar-img" id="dashAdminAvatarImg">\s*<\?php else: \?>\s*<i class="fa-solid fa-user avatar-icon" id="dashAdminAvatarIcon"></i>\s*<\?php endif; \?>',
    '<img src="" alt="Admin Profile Photo" class="admin-banner-avatar-img" id="dashAdminAvatarImg" style="display:none;">\n      <i class="fa-solid fa-user avatar-icon" id="dashAdminAvatarIcon"></i>',
    content
)
content = re.sub(
    r'<div class="badge">STAFF ID: STF-<\?= e\(str_pad\(\(string\)current_user_id\(\), 4, \'0\', STR_PAD_LEFT\)\) \?></div>',
    '<div class="badge">STAFF ID: <span id="dashStaffId">Loading...</span></div>',
    content
)
content = re.sub(
    r'<h1>Welcome, <\?= e\(\$_SESSION\[\'username\'\]\) \>!</h1>',
    '<h1>Welcome, <span id="dashWelcomeName">User</span>!</h1>',
    content
)
content = re.sub(
    r'<span><i class="fa-solid fa-id-badge"></i> Role: <\?= e\(ucfirst\(\$_SESSION\[\'role\'\] \?\? \'Staff\'\)\) \?></span>',
    '<span><i class="fa-solid fa-id-badge"></i> Role: <span id="dashRole">Staff</span></span>',
    content
)

# Wait, the Regex for welcome name needs to match properly:
content = re.sub(r'<h1>Welcome, .*?!</h1>', '<h1>Welcome, <span id="dashWelcomeName">User</span>!</h1>', content)

# Update the script to populate these on auth state change
script_start = content.find('<script type="module">')
script_end = content.find('</script>', script_start)

# Add logic to onAuthStateChanged
old_auth = """onAuthStateChanged(auth, (user) => {
    if (!user) {
        window.location.href = '../login.php';
    } else {
        loadDashboardData();
    }
});"""

new_auth = """import { doc, getDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = '../login.php';
    } else {
        // Fetch staff profile
        try {
            const userDoc = await getDoc(doc(db, 'staff', user.uid));
            if (userDoc.exists()) {
                const data = userDoc.data();
                document.getElementById('dashWelcomeName').textContent = data.username || data.name || user.email;
                document.getElementById('dashRole').textContent = data.role ? data.role.charAt(0).toUpperCase() + data.role.slice(1) : 'Staff';
                if (data.profile_photo) {
                    const img = document.getElementById('dashAdminAvatarImg');
                    img.src = '../uploads/' + data.profile_photo;
                    img.style.display = 'block';
                    document.getElementById('dashAdminAvatarIcon').style.display = 'none';
                }
            } else {
                document.getElementById('dashWelcomeName').textContent = user.email;
            }
            document.getElementById('dashStaffId').textContent = user.uid.substring(0, 8).toUpperCase();
        } catch (e) {
            console.error('Error fetching staff profile:', e);
        }
        
        loadDashboardData();
    }
});"""

content = content.replace(old_auth, new_auth)

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/dashboard.php', 'w', encoding='utf-8') as f:
    f.write(content)
