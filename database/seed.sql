USE special_scientists_project;

-- USERS

INSERT INTO users (username, email, password_hash, role, is_active)
VALUES ('admin1', 'admin@test.com', '$2y$10$nVEL5jCwmPdYG.CL1RIZO.yICzgeZGrqmCYWYdOcqOpp2y8y2U6sa', 'admin', 1),
       ('candidate1', 'candidate1@test.com', '$2y$10$eSNLN0Ga.ClInfJFgVpcP.S8Sa5GMFjhyqhbMC6BDzM3abRWIAHvu',
        'candidate', 1),
       ('candidate2', 'candidate2@test.com', '$2y$10$JdUsPLJtqqjrYn14ZIw4nejtwE5jIsOgwd2elKxAJDcfOnyulOnCS',
        'candidate', 1),
       ('candidate3', 'candidate3@test.com', '$2y$10$eSNLN0Ga.ClInfJFgVpcP.S8Sa5GMFjhyqhbMC6BDzM3abRWIAHvu',
        'candidate', 1),
       ('evaluator1', 'evaluator1@test.com', '$2y$10$eSNLN0Ga.ClInfJFgVpcP.S8Sa5GMFjhyqhbMC6BDzM3abRWIAHvu',
        'evaluator', 1),
       ('evaluator2', 'evaluator2@test.com', '$2y$10$JdUsPLJtqqjrYn14ZIw4nejtwE5jIsOgwd2elKxAJDcfOnyulOnCS',
        'evaluator', 1),
       ('hr1', 'hr1@test.com', '$2y$10$eSNLN0Ga.ClInfJFgVpcP.S8Sa5GMFjhyqhbMC6BDzM3abRWIAHvu', 'hr', 1),
       ('ee1', 'ee1@test.com', '$2y$10$JdUsPLJtqqjrYn14ZIw4nejtwE5jIsOgwd2elKxAJDcfOnyulOnCS', 'ee', 1);

INSERT INTO faculties (name)
VALUES ('Faculty of Science'),
       ('Faculty of Engineering'),
       ('Faculty of Arts and Social Sciences');

INSERT INTO departments (faculty_id, name)
VALUES (1, 'Department of Mathematics'),
       (1, 'Department of Computer Science'),
       (1, 'Department of Physics'),
       (1, 'Department of Chemistry'),
       (2, 'Department of Electrical Engineering'),
       (2, 'Department of Mechanical Engineering'),
       (3, 'Department of Languages and Literature');

INSERT INTO courses (department_id, name, code)
VALUES (1, 'Calculus I', 'MATH101'),
       (1, 'Linear Algebra', 'MATH102'),
       (2, 'Introduction to Programming', 'CS101'),
       (2, 'Data Structures', 'CS201'),
       (2, 'Database Systems', 'CS301'),
       (3, 'Classical Mechanics', 'PHYS101'),
       (3, 'Quantum Physics', 'PHYS301'),
       (4, 'Organic Chemistry', 'CHEM201'),
       (5, 'Circuit Analysis', 'EE201'),
       (6, 'Thermodynamics', 'ME201'),
       (7, 'Academic Writing', 'LANG101');

INSERT INTO recruitment_periods (title, start_date, end_date, is_active)
VALUES ('Spring 2026 Recruitment', '2026-01-10', '2026-03-31', 0),
       ('Summer 2026 Recruitment', '2026-04-01', '2026-06-30', 1),
       ('Autumn 2026 Recruitment', '2026-09-01', '2026-11-30', 0);

INSERT INTO applications (user_id, course_id, period_id, title, status)
VALUES (2, 1, 1, 'Application for Calculus I', 'submitted'),
       (2, 3, 2, 'Application for Introduction to Programming', 'under_review'),
       (3, 4, 2, 'Application for Data Structures', 'approved'),
       (3, 6, 1, 'Application for Classical Mechanics', 'rejected'),
       (4, 5, 2, 'Application for Database Systems', 'draft'),
       (4, 8, 2, 'Application for Organic Chemistry', 'submitted'),
       (2, 9, 3, 'Application for Circuit Analysis', 'draft'),
       (3, 10, 3, 'Application for Thermodynamics', 'draft'),
       (4, 11, 2, 'Application for Academic Writing', 'under_review');

INSERT INTO application_evaluators (application_id, evaluator_id)
VALUES (1, 5),
       (2, 5),
       (3, 6),
       (4, 6),
       (6, 5),
       (9, 6);