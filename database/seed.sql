USE special_scientists_project;


USE special_scientists_project;

-- USERS

INSERT INTO users (username, email, password_hash, role)
VALUES 
('admin1', 'admin@test.com', '$2y$10$nVEL5jCwmPdYG.CL1RIZO.yICzgeZGrqmCYWYdOcqOpp2y8y2U6sa', 
'admin'),  -- AdminPass123

('user1', 'user1@test.com', '$2y$10$eSNLN0Ga.ClInfJFgVpcP.S8Sa5GMFjhyqhbMC6BDzM3abRWIAHvu', 
'user'),   -- User1Pass123

('user2', 'user2@test.com', '$2y$10$JdUsPLJtqqjrYn14ZIw4nejtwE5jIsOgwd2elKxAJDcfOnyulOnCS', 
'user');    -- User2Pass123


-- APPLICATIONS

INSERT INTO applications (user_id, title, department, status)
VALUES 
(1, 'Math Lecturer Application', 'Mathematics', 'pending'),
(2, 'Computer Science Assistant', 'Computer Science', 'approved'),
(3, 'Physics Research Position', 'Physics', 'pending'),
(2, 'Data Science Instructor', 'Computer Science', 'approved'),
(3, 'Chemistry Lab Assistant', 'Chemistry', 'rejected');