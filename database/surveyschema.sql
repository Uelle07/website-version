-- =========================================================
-- IT 305 ACTIVITY 5 - SET B
-- SURVEY MANAGEMENT SYSTEM
-- Integrated with existing SET A: Member Information System
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- SURVEYS
-- Staff members create and manage surveys.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS survey_results;
DROP TABLE IF EXISTS responses;
DROP TABLE IF EXISTS survey_choices;
DROP TABLE IF EXISTS survey_questions;
DROP TABLE IF EXISTS surveys;

CREATE TABLE surveys (
    survey_id       INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    description     TEXT NULL,
    start_date      DATETIME NOT NULL,
    end_date        DATETIME NOT NULL,
    status          ENUM('draft','active','inactive','closed')
                    NOT NULL DEFAULT 'draft',
    created_by_staff_id INT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_survey_staff
        FOREIGN KEY (created_by_staff_id)
        REFERENCES staff(staff_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SURVEY QUESTIONS
-- Each survey can contain many questions.
-- ---------------------------------------------------------
CREATE TABLE survey_questions (
    question_id     INT AUTO_INCREMENT PRIMARY KEY,
    survey_id       INT NOT NULL,
    question        TEXT NOT NULL,
    question_type   ENUM(
                        'multiple_choice',
                        'yes_no',
                        'rating',
                        'short_answer'
                    ) NOT NULL,
    question_order  INT NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_question_survey
        FOREIGN KEY (survey_id)
        REFERENCES surveys(survey_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_survey_question_order (survey_id, question_order)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SURVEY CHOICES
-- Used for Multiple Choice, Yes/No, and Rating questions.
-- ---------------------------------------------------------
CREATE TABLE survey_choices (
    choice_id       INT AUTO_INCREMENT PRIMARY KEY,
    question_id     INT NOT NULL,
    choice_text     VARCHAR(255) NOT NULL,
    choice_order    INT NOT NULL DEFAULT 1,

    CONSTRAINT fk_choice_question
        FOREIGN KEY (question_id)
        REFERENCES survey_questions(question_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_question_choice_order (question_id, choice_order)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- RESPONSES
-- Connects SET A's member table to SET B surveys.
--
-- member_id       -> member.member_id
-- survey_id       -> surveys.survey_id
-- question_id     -> survey_questions.question_id
-- choice_id       -> survey_choices.choice_id
--
-- A member can answer each survey only ONCE.
-- The unique key prevents duplicate answers to the same
-- question by the same member for the same survey.
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
        FOREIGN KEY (survey_id)
        REFERENCES surveys(survey_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_response_question
        FOREIGN KEY (question_id)
        REFERENCES survey_questions(question_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_response_member
        FOREIGN KEY (member_id)
        REFERENCES member(member_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_response_choice
        FOREIGN KEY (choice_id)
        REFERENCES survey_choices(choice_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    UNIQUE KEY uq_member_survey_question
        (member_id, survey_id, question_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SURVEY RESULTS
-- Stores the calculated result/statistics for selectable
-- choices. Results can also be recalculated from responses.
-- ---------------------------------------------------------
CREATE TABLE survey_results (
    result_id       INT AUTO_INCREMENT PRIMARY KEY,
    survey_id       INT NOT NULL,
    question_id     INT NOT NULL,
    choice_id       INT NULL,
    response_count  INT NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    calculated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_result_survey
        FOREIGN KEY (survey_id)
        REFERENCES surveys(survey_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_result_question
        FOREIGN KEY (question_id)
        REFERENCES survey_questions(question_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_result_choice
        FOREIGN KEY (choice_id)
        REFERENCES survey_choices(choice_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    UNIQUE KEY uq_result_choice
        (survey_id, question_id, choice_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- RELATIONSHIPS WITH YOUR SET A DATABASE
-- =========================================================
--
-- staff.staff_id
--       |
--       +---- surveys.created_by_staff_id
--
-- member.member_id
--       |
--       +---- responses.member_id
--
-- surveys.survey_id
--       |
--       +---- survey_questions.survey_id
--       |
--       +---- responses.survey_id
--       |
--       +---- survey_results.survey_id
--
-- survey_questions.question_id
--       |
--       +---- survey_choices.question_id
--       |
--       +---- responses.question_id
--       |
--       +---- survey_results.question_id
--
-- survey_choices.choice_id
--       |
--       +---- responses.choice_id
--       |
--       +---- survey_results.choice_id
--
-- =========================================================
-- IMPORTANT INTEGRATION NOTE
-- =========================================================
--
-- SET A already contains:
--   staff
--   member
--   member_account
--   spouse
--   parents
--   children
--   member_references
--   update_history
--
-- SET B adds only:
--   surveys
--   survey_questions
--   survey_choices
--   responses
--   survey_results
--
-- No duplicate member/staff tables are created.
-- =========================================================
