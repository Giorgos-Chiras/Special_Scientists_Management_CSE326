CREATE
DATABASE IF NOT EXISTS special_scientists_project;
USE
special_scientists_project;

CREATE TABLE users (
                       id            INT AUTO_INCREMENT PRIMARY KEY,
                       username      VARCHAR(50)  NOT NULL UNIQUE,
                       email         VARCHAR(100) NOT NULL UNIQUE,
                       password_hash VARCHAR(255) NOT NULL,
                       role          ENUM('admin', 'candidate', 'evaluator', 'hr', 'ee') NOT NULL DEFAULT 'candidate',
                       is_active     TINYINT(1) NOT NULL DEFAULT 1,
                       created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE faculties (
                           id            INT AUTO_INCREMENT PRIMARY KEY,
                           name          VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE departments (
                             id            INT AUTO_INCREMENT PRIMARY KEY,
                             faculty_id    INT NOT NULL,
                             name          VARCHAR(100) NOT NULL,
                             FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);

CREATE TABLE courses (
                         id            INT AUTO_INCREMENT PRIMARY KEY,
                         department_id INT NOT NULL,
                         name          VARCHAR(150) NOT NULL,
                         code          VARCHAR(50),
                         FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE recruitment_periods (
                                     id            INT AUTO_INCREMENT PRIMARY KEY,
                                     title         VARCHAR(100) NOT NULL,
                                     start_date    DATE NOT NULL,
                                     end_date      DATE NOT NULL,
                                     is_active     TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE applications (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    course_id           INT NOT NULL,
    period_id           INT NOT NULL,

    title               VARCHAR(150) NOT NULL,
    status              ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'draft',

    cover_letter        TEXT NULL,
    qualifications      TEXT NULL,

    cv_file_path        VARCHAR(255) NULL,
    cv_original_name    VARCHAR(255) NULL,

    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES recruitment_periods(id) ON DELETE CASCADE
);

CREATE TABLE application_evaluators (
                                        id              INT AUTO_INCREMENT PRIMARY KEY,
                                        application_id  INT NOT NULL,
                                        evaluator_id    INT NOT NULL,
                                        assigned_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
                                        FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE system_settings (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 setting_key VARCHAR(100) NOT NULL UNIQUE,
                                 setting_value TEXT NULL
);

CREATE TABLE lms_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lms_access TINYINT(1) NOT NULL DEFAULT 0,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);