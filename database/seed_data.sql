-- =========================================================
-- seed_data.sql  (OPTIONAL — for local development/testing only)
-- ---------------------------------------------------------
-- Run this AFTER coop_member_portal.sql has created the schema.
-- Gives the team one staff login and one member login to test
-- the auth foundation with, before Module 6 (Add New Member)
-- is built.
--
-- Passwords below are bcrypt-hashed (PHP password_hash() /
-- password_verify() compatible).
-- =========================================================

-- Select database if needed (uncomment for local XAMPP or update for hosting):
-- USE if0_42733013_coop_member_portal;

-- ---------------------------------------------------------
-- Test STAFF account
--   username: admin
--   password: admin123
-- ---------------------------------------------------------
INSERT INTO staff (username, password, fullname, role)
VALUES (
    'admin',
    '$2y$10$JMBwGSUNj14LDM.8ZPjQ8uqbGe1w8KRRhu3SmZgbiE6rSF4jCobxu',
    'System Administrator',
    'admin'
);

-- ---------------------------------------------------------
-- Test MEMBER account
--   account number / username: 2026-0001
--   password: 2026-0001  (matches the "default password = account number" rule)
--   first_login is TRUE, so logging in will route to the
--   change-password page, exactly like a brand-new member.
-- ---------------------------------------------------------
INSERT INTO member (
    account_number, lastname, firstname, middlename, civil_status, sex, nationality,
    birthday, occupation, address, contact_number, registered_by_staff_id
) VALUES (
    '2026-0001', 'Dela Cruz', 'Juan', 'Santos', 'Single', 'Male', 'Filipino',
    '1995-06-15', 'Farmer', 'Brgy. San Isidro, Bulacan', '09171234567', 1
);

INSERT INTO member_account (member_id, username, password, first_login, password_changed)
VALUES (
    LAST_INSERT_ID(),
    '2026-0001',
    '$2y$10$33ta4wkbgUk5523IfBM/u..KA3.j97p.drb5kC4l88OAK6RbkiKF6',
    TRUE,
    FALSE
);
