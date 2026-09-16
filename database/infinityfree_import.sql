-- =========================================================
-- INFINITYFREE FULL DATABASE IMPORT SCRIPT
-- Database: if0_42733013_coop_member_portal
-- System: SPACE Cooperative Member Information System & Survey Management
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- ---------------------------------------------------------
-- 1. STAFF TABLE
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2. MEMBER TABLE
-- ---------------------------------------------------------
DROP TABLE IF EXISTS member;
CREATE TABLE member (
    member_id               INT AUTO_INCREMENT PRIMARY KEY,
    account_number           VARCHAR(20)  NOT NULL UNIQUE,
    lastname                 VARCHAR(100) NOT NULL,
    firstname                VARCHAR(100) NOT NULL,
    middlename                VARCHAR(100) NULL,
    extension_name             VARCHAR(10) NULL,
    civil_status              ENUM('Single','Married','Widowed','Separated','Divorced') NULL,
    account_status            ENUM('Active','Inactive','Locked','Flagged','Deleted') NOT NULL DEFAULT 'Active',
    sex                       VARCHAR(20)  NULL,
    nationality               VARCHAR(50)  NULL,
    birthday                  DATE         NOT NULL,
    occupation                VARCHAR(100) NULL,
    employer                  VARCHAR(150) NULL,
    employer_address           VARCHAR(255) NULL,
    address                   VARCHAR(255) NOT NULL,
    contact_number             VARCHAR(20) NULL,
    passport_photo             VARCHAR(255) NULL,
    registered_by_staff_id      INT NULL,
    created_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_staff
        FOREIGN KEY (registered_by_staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3. MEMBER ACCOUNT (1:1 with MEMBER)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4. SPOUSE (1:0..1 with MEMBER)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS spouse;
CREATE TABLE spouse (
    spouse_id    INT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT NOT NULL UNIQUE,
    spouse_name   VARCHAR(150) NULL,
    occupation    VARCHAR(100) NULL,
    employer      VARCHAR(150) NULL,
    CONSTRAINT fk_spouse_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5. PARENTS (1:0..1 with MEMBER)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS parents;
CREATE TABLE parents (
    parent_id     INT AUTO_INCREMENT PRIMARY KEY,
    member_id      INT NOT NULL UNIQUE,
    father_name    VARCHAR(150) NULL,
    mother_name    VARCHAR(150) NULL,
    CONSTRAINT fk_parents_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 6. CHILDREN (1:many with MEMBER)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS children;
CREATE TABLE children (
    child_id     INT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT NOT NULL,
    child_name    VARCHAR(150) NOT NULL,
    birthday      DATE NULL,
    CONSTRAINT fk_children_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 7. MEMBER REFERENCES (1:many with MEMBER)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 8. UPDATE HISTORY (Audit Trail)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS update_history;
CREATE TABLE update_history (
    update_id             INT AUTO_INCREMENT PRIMARY KEY,
    member_id              INT NOT NULL,
    updated_by_staff_id      INT NULL,
    updated_by_member_id      INT NULL,
    update_type            VARCHAR(100) NOT NULL,
    update_date            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks                TEXT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 9. SURVEYS
-- ---------------------------------------------------------
DROP TABLE IF EXISTS survey_results;
DROP TABLE IF EXISTS responses;
DROP TABLE IF EXISTS survey_choices;
DROP TABLE IF EXISTS survey_questions;
DROP TABLE IF EXISTS surveys;

CREATE TABLE surveys (
    survey_id           INT AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(255) NOT NULL,
    description         TEXT NULL,
    start_date          DATETIME NOT NULL,
    end_date            DATETIME NOT NULL,
    status              ENUM('draft','active','inactive','closed') NOT NULL DEFAULT 'draft',
    created_by_staff_id INT NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_survey_staff
        FOREIGN KEY (created_by_staff_id) REFERENCES staff(staff_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 10. SURVEY QUESTIONS
-- ---------------------------------------------------------
CREATE TABLE survey_questions (
    question_id     INT AUTO_INCREMENT PRIMARY KEY,
    survey_id       INT NOT NULL,
    question        TEXT NOT NULL,
    question_type   ENUM('multiple_choice','yes_no','rating','short_answer') NOT NULL,
    question_order  INT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_question_survey
        FOREIGN KEY (survey_id) REFERENCES surveys(survey_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_survey_question_order (survey_id, question_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 11. SURVEY CHOICES
-- ---------------------------------------------------------
CREATE TABLE survey_choices (
    choice_id       INT AUTO_INCREMENT PRIMARY KEY,
    question_id     INT NOT NULL,
    choice_text     VARCHAR(255) NOT NULL,
    choice_order    INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_choice_question
        FOREIGN KEY (question_id) REFERENCES survey_questions(question_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_question_choice_order (question_id, choice_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 12. SURVEY RESPONSES
-- ---------------------------------------------------------
CREATE TABLE responses (
    response_id     INT AUTO_INCREMENT PRIMARY KEY,
    survey_id       INT NOT NULL,
    question_id     INT NOT NULL,
    member_id       INT NOT NULL,
    choice_id       INT NULL,
    answer          TEXT NULL,
    submitted_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_response_survey
        FOREIGN KEY (survey_id) REFERENCES surveys(survey_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_response_question
        FOREIGN KEY (question_id) REFERENCES survey_questions(question_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_response_member
        FOREIGN KEY (member_id) REFERENCES member(member_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_response_choice
        FOREIGN KEY (choice_id) REFERENCES survey_choices(choice_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_member_survey_question (member_id, survey_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 13. SURVEY RESULTS
-- ---------------------------------------------------------
CREATE TABLE survey_results (
    result_id       INT AUTO_INCREMENT PRIMARY KEY,
    survey_id       INT NOT NULL,
    question_id     INT NOT NULL,
    choice_id       INT NULL,
    response_count  INT NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    calculated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_result_survey
        FOREIGN KEY (survey_id) REFERENCES surveys(survey_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_result_question
        FOREIGN KEY (question_id) REFERENCES survey_questions(question_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_result_choice
        FOREIGN KEY (choice_id) REFERENCES survey_choices(choice_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_result_choice (survey_id, question_id, choice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- INITIAL TEST & SEED DATA
-- =========================================================

-- 1. Staff Admin Account (Username: admin | Password: admin123)
INSERT INTO staff (username, password, fullname, role)
VALUES (
    'admin',
    '$2y$10$JMBwGSUNj14LDM.8ZPjQ8uqbGe1w8KRRhu3SmZgbiE6rSF4jCobxu',
    'System Administrator',
    'admin'
);

-- 2. Member Account (Account No: 2026-0001 | Password: 2026-0001)
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
