# Cooperative Member Portal — Project Foundation

This is the shared foundation for the group's IT 305 Activity 5 project. It does **not** include the finished feature pages — those are the seven modules your team builds on top of this. Read this whole file before you start coding your module.

---

## 1. What I found when reviewing your files

Your `coop_member_portal.sql` schema is solid 3NF and I used it exactly as-is — no tables or columns were invented or renamed. A few things worth deciding as a team before you build on top of it:

1. **"Delete Member Record" (staff requirement) is a hard delete in the current schema.** `ON DELETE CASCADE` on `member_account`, `spouse`, `parents`, `children`, `member_references` means deleting a member permanently wipes all their related data, with no way to undo it. If you want it recoverable, add an `is_active BOOLEAN DEFAULT TRUE` column to `member` and make "Delete" a soft-delete (`UPDATE member SET is_active = FALSE`) instead of an actual `DELETE`. I did **not** add this myself since it changes your schema — it's your call as a team.
2. **`update_history.chk_history_actor` is a CHECK constraint.** These are only enforced on MySQL 8.0.16+ / MariaDB 10.2+. If your XAMPP's MySQL is older, the constraint will silently do nothing — not a bug in your file, just something to be aware of when testing "at least one actor" logic.
3. **No table stores "age"** for member or children — this is correct, since age should always be *calculated* from `birthday`, never stored (it would go stale). I added a `calculate_age()` helper for this — see `config/functions.php`.
4. **`member_account.username`** is a separate unique field from `member.account_number`. Since the spec says members log in with their Account Number, the cleanest approach (which the seed data and `auth.php` both use) is to always set `member_account.username` equal to `member.account_number` when a member is created. Worth confirming as a team convention.

Nothing else needed changing — the rest of the schema maps directly onto the functional requirements.

---

## 2. Folder structure (unchanged from what you uploaded)

```
cooperative-member-information-system/
├── assets/
│   ├── css/style.css         ← base styles (design tokens, layout, components)
│   ├── images/               ← default-avatar.svg lives here
│   └── js/script.js          ← base JS (flash auto-dismiss, validation, photo preview)
├── config/                   ← ALL shared/core PHP logic lives here
│   ├── constants.php         ← DB credentials, BASE_URL, paths, roles
│   ├── database.php          ← PDO connection ($pdo)
│   ├── session.php           ← secure session bootstrap
│   ├── auth.php              ← login/logout/role-check functions
│   ├── functions.php         ← generic helpers (e, redirect, csrf, age calc...)
│   ├── flash.php             ← one-time flash messages
│   ├── validation.php        ← reusable field validators
│   ├── upload.php            ← passport-photo upload handler
│   └── init.php              ← require THIS ONE file, it loads all the above in order
├── database/
│   ├── coop_member_portal.sql← your original schema, unchanged
│   └── seed_data.sql         ← OPTIONAL test accounts (see §5)
├── member/                   ← Modules 2, 3, 4 build here
├── staff/                    ← Modules 5, 6, 7 build here
├── pages/                    ← shared header/footer/navbar/sidebars
├── uploads/                  ← member passport photos land here (.htaccess blocks PHP execution)
├── index.php                 ← routes visitor to the right dashboard or login.php
├── login.php                 ← Module 1: member + staff login (functional, not a stub)
├── logout.php                ← functional
└── README.md
```

I didn't add any new top-level folders — everything shared lives inside your existing `config/` and `pages/` folders.

---

## 3. How every page should start and end

Every module page follows this exact pattern:

```php
<?php
require_once __DIR__ . '/../config/init.php';   // loads session, DB, helpers, auth
require_member();                                 // or require_staff(), or require_staff('admin')

// ... your PHP logic / DB queries / form handling ...

$pageTitle = 'Page Title';
require_once __DIR__ . '/../pages/header.php';   // opens <html>, navbar, sidebar
?>

<!-- your HTML here -->

<?php require_once __DIR__ . '/../pages/footer.php'; ?>  <!-- closes layout, loads JS -->
```

That's it — three requires and you're inside a fully-styled, logged-in layout with the correct sidebar already showing.

---

## 4. What's already built for you (don't rebuild these)

| Concern | Where | Function/File |
|---|---|---|
| DB connection | `config/database.php` | `$pdo` (PDO, prepared statements only) |
| Session start/timeout | `config/session.php` | auto-runs via `init.php` |
| Member login | `config/auth.php` | `authenticate_member($pdo, $accountNumber, $password)` |
| Staff login | `config/auth.php` | `authenticate_staff($pdo, $username, $password)` |
| Logout | `config/auth.php` | `logout_user()` |
| Route guards | `config/auth.php` | `require_member()`, `require_staff()`, `require_staff('admin')` |
| Who's logged in | `config/auth.php` | `is_member()`, `is_staff()`, `is_admin()`, `current_user_id()` |
| Password hashing | built-in PHP | `password_hash()` / `password_verify()` (used in auth.php, change_password.php) |
| XSS-safe output | `config/functions.php` | `e($value)` — wrap every DB value you print |
| Redirects | `config/functions.php` | `redirect('member/dashboard.php')` |
| CSRF protection | `config/functions.php` | `csrf_token()` in forms, `verify_csrf()` in POST handlers |
| Flash messages | `config/flash.php` | `set_flash('success', 'Saved!')`, auto-displayed by header.php |
| Field validation | `config/validation.php` | `validate_required()`, `validate_contact_number()`, `validate_date()`, etc. |
| Photo upload | `config/upload.php` | `handle_photo_upload($_FILES['passport_photo'], $memberId)` |
| Age calculation | `config/functions.php` | `calculate_age($birthday)` |
| Account number generator | `config/functions.php` | `generate_account_number($pdo)` → `"2026-0001"` style |
| Layout/navbar/sidebar | `pages/*.php` | included automatically by `header.php` |
| Base styling | `assets/css/style.css` | Bootstrap 5 + cooperative green/gold theme |
| Base JS | `assets/js/script.js` | flash auto-dismiss, `.needs-validation` forms, photo preview, `data-confirm` |

---

## 5. Running it locally (XAMPP)

1. Copy this whole folder into `htdocs/`.
2. Start Apache + MySQL in the XAMPP control panel.
3. In phpMyAdmin, import `database/coop_member_portal.sql` first, then (optional but recommended) `database/seed_data.sql` so you have test logins immediately:
   - **Staff:** username `admin`, password `admin123`
   - **Member:** account number `2026-0001`, password `2026-0001` (this account has `first_login = TRUE`, so it will demonstrate the forced password-change flow)
4. Visit `http://localhost/cooperative-member-information-system/` — it should route you to the login page.
5. If your DB credentials differ from the XAMPP defaults, edit `config/constants.php` (`DB_HOST`, `DB_USER`, `DB_PASS`).

`BASE_URL` is auto-detected from the request, so the project works regardless of what you name the folder inside `htdocs/` — nobody needs to hardcode a URL.

---

## 6. Module ownership map

Each module should only touch its own files, so nobody blocks anybody else.

| Module | Owner | Files | Status |
|---|---|---|---|
| **1. Authentication** | — | `login.php`, `logout.php`, `member/change_password.php`, `config/auth.php` | review, don't rebuild |
| **2. Member Dashboard** | (assign) | `member/dashboard.php` | 🟡 Stub — build the widgets |
| **3. Member Profile** | (assign) | `member/profile.php` | 🟡 Stub — build read-only view + print |
| **4. Member Info Update** | (assign) | `member/update_info.php` | 🟡 Stub — build the edit form |
| **5. Staff Dashboard** | (assign) | `staff/dashboard.php` | 🟡 Stub — build summary cards |
| **6. Member Management (CRUD)** | (assign) | `staff/members.php`, `staff/add_member.php`, `staff/search.php`,
							`staff/updated_records.php` | 🟡 Stubs — build view/add/edit/delete/search/reset-password |
| **7. Reports** | (assign) | `staff/reports.php` | 🟡 Stub — build report queries/charts |

Shared files (`config/*`, `pages/*`, `assets/*`, `index.php`) belong to everyone — if you need to change one 
(e.g. add a sidebar link for a new page), just say so in your group chat first, since it affects the whole team.

Every stub already includes `// TODO` comments with the exact query pattern to use, so you can start writing
 your module's HTML/logic immediately without needing to figure out plumbing.

---

## 7. Security notes for whoever builds each module

- Never build a raw SQL string with `.` or string interpolation — always use `$pdo->prepare()` with `?` or `:named` 
placeholders (every existing file already does this — copy the pattern).
- Every form that changes data (`POST`) must call `verify_csrf($_POST['csrf_token'] ?? '')` as its first
 line, and every form must include `<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">`.
- Every value coming from the database that you print into HTML must go through `e()`.
- Every value coming from `$_POST`/`$_GET` should go through `clean()` before you validate or store it.
