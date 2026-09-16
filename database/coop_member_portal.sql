-- =========================================================
-- Web-Based Member Personal Information Updating System
-- for a Multipurpose Cooperative
-- Normalized (3NF) schema
-- Compatible with local XAMPP and InfinityFree Web Hosting
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- STAFF
-- ---------------------------------------------------------
DROP TABLE IF EXISTS staff;
CREATE TABLE staff (
    staff_id        INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    fullname        VARCHAR(150) NOT NULL,
    profile_photo   VARCHAR(255) NULL,
    role            VARCHAR(50)  NOT NULL DEFAULT 'staff',
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- MEMBER
-- ---------------------------------------------------------
DROP TABLE IF EXISTS member;
CREATE TABLE member (
    member_id               INT AUTO_INCREMENT PRIMARY KEY,
    account_number           VARCHAR(20)  NOT NULL UNIQUE,
    lastname                 VARCHAR(100) NOT NULL,
    firstname                VARCHAR(100) NOT NULL,
    middlename                VARCHAR(100),
    extension_name             VARCHAR(10),
    civil_status              ENUM('Single','Married','Widowed','Separated','Divorced'),
    account_status            ENUM('Active','Inactive','Locked','Flagged','Deleted') NOT NULL DEFAULT 'Active',
    sex                       VARCHAR(20)  NULL,
    nationality               VARCHAR(50)  NULL,
    birthday                  DATE         NOT NULL,
    occupation                VARCHAR(100),
    employer                  VARCHAR(150),
    employer_address           VARCHAR(255),
    address                   VARCHAR(255) NOT NULL,
    contact_number             VARCHAR(20),
    passport_photo             VARCHAR(255),
    registered_by_staff_id      INT NULL,
    created_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_staff
        FOREIGN KEY (registered_by_staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- MEMBER_ACCOUNT  (1:1 with MEMBER — login credentials)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS member_account;
CREATE TABLE member_account (
    account_id          INT AUTO_INCREMENT PRIMARY KEY,
    member_id           INT NOT NULL UNIQUE,
    username            VARCHAR(50)  NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    first_login         BOOLEAN NOT NULL DEFAULT TRUE,
    last_login          TIMESTAMP NULL,
    password_changed    BOOLEAN NOT NULL DEFAULT FALSE,
    failed_attempts     INT NOT NULL DEFAULT 0,
    locked_until        DATETIME NULL DEFAULT NULL,
    CONSTRAINT fk_account_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SPOUSE  (1:0..1 with MEMBER)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS spouse;
CREATE TABLE spouse (
    spouse_id    INT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT NOT NULL UNIQUE,
    spouse_name   VARCHAR(150),
    occupation    VARCHAR(100),
    employer      VARCHAR(150),
    CONSTRAINT fk_spouse_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PARENTS  (1:0..1 with MEMBER)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS parents;
CREATE TABLE parents (
    parent_id     INT AUTO_INCREMENT PRIMARY KEY,
    member_id      INT NOT NULL UNIQUE,
    father_name    VARCHAR(150),
    mother_name    VARCHAR(150),
    CONSTRAINT fk_parents_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CHILDREN  (1:many with MEMBER)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS children;
CREATE TABLE children (
    child_id     INT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT NOT NULL,
    child_name    VARCHAR(150) NOT NULL,
    birthday      DATE,
    CONSTRAINT fk_children_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- MEMBER_REFERENCES  (1:many with MEMBER; optional self-link
-- if the reference is an existing co-op member)
-- Named member_references, not "references", since REFERENCES
-- is a reserved SQL keyword.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS member_references;
CREATE TABLE member_references (
    reference_id             INT AUTO_INCREMENT PRIMARY KEY,
    member_id                 INT NOT NULL,
    reference_member_name      VARCHAR(150) NOT NULL,
    reference_member_id         INT NULL,
    signature                  VARCHAR(255) NULL,
    CONSTRAINT fk_reference_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reference_referenced_member
        FOREIGN KEY (reference_member_id) REFERENCES member(member_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- UPDATE_HISTORY  (1:many with MEMBER; actor is either a
-- staff member or the member themself)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS update_history;
CREATE TABLE update_history (
    update_id             INT AUTO_INCREMENT PRIMARY KEY,
    member_id              INT NOT NULL,
    updated_by_staff_id      INT NULL,
    updated_by_member_id      INT NULL,
    update_type            VARCHAR(100) NOT NULL,
    update_date            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks                TEXT,
    CONSTRAINT fk_history_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_history_staff
        FOREIGN KEY (updated_by_staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_history_member_actor
        FOREIGN KEY (updated_by_member_id) REFERENCES member(member_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_history_actor
        CHECK (updated_by_staff_id IS NOT NULL OR updated_by_member_id IS NOT NULL)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;


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