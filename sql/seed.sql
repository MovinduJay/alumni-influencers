-- =====================================================
-- Alumni Influencers Platform - Test Seed Data
-- =====================================================
-- This file populates the database with test data for
-- development and testing purposes.
--
-- Test Users:
--   1. john.doe@westminster.ac.uk    / TestPass1!  (verified, active)
--   2. jane.smith@westminster.ac.uk  / TestPass1!  (verified, active)
--   3. bob.wilson@westminster.ac.uk  / TestPass1!  (unverified)
--
-- All passwords are hashed with bcrypt (cost 12).
-- =====================================================

USE alumni_platform;

-- Clear existing data (in reverse FK order)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE rate_limits;
TRUNCATE TABLE api_access_logs;
TRUNCATE TABLE api_client_scopes;
TRUNCATE TABLE api_scopes;
TRUNCATE TABLE api_clients;
TRUNCATE TABLE featured_alumni;
TRUNCATE TABLE event_participations;
TRUNCATE TABLE sponsorships;
TRUNCATE TABLE bids;
TRUNCATE TABLE analytics_filter_presets;
TRUNCATE TABLE alumni_skills;
TRUNCATE TABLE skills;
TRUNCATE TABLE alumni_outcomes;
TRUNCATE TABLE industry_sectors;
TRUNCATE TABLE programmes;
TRUNCATE TABLE employment_history;
TRUNCATE TABLE courses;
TRUNCATE TABLE licences;
TRUNCATE TABLE certifications;
TRUNCATE TABLE degrees;
TRUNCATE TABLE alumni;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Alumni (Test Users)
-- Password for all: TestPass1!
-- Bcrypt hash: $2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm
-- =====================================================
INSERT INTO alumni (id, email, password, first_name, last_name, bio, linkedin_url, profile_image, role, email_verified, verification_token, verification_expires, is_active) VALUES
(1, 'john.doe@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'John', 'Doe',
 'Software engineer with 10 years of experience in full-stack development. Graduated from the University of Westminster with a First Class Honours in Computer Science. Currently leading a team at a major tech firm in London.',
 'https://linkedin.com/in/johndoe',
 NULL, 'admin', 1, NULL, NULL, 1),

(2, 'jane.smith@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Jane', 'Smith',
 'Data scientist and AI researcher. Passionate about using machine learning to solve real-world problems. Westminster alumna with a PhD in Artificial Intelligence.',
 'https://linkedin.com/in/janesmith',
 NULL, 'alumni', 1, NULL, NULL, 1),

-- For Bob Wilson, verification_token is stored as SHA-256 hash.
-- Raw token: abc123testtoken456
-- SHA-256:   SHA2('abc123testtoken456', 256)
(3, 'bob.wilson@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Bob', 'Wilson',
 'Cybersecurity specialist with CISSP certification.',
 'https://linkedin.com/in/bobwilson',
 NULL, 'alumni', 0, SHA2('abc123testtoken456', 256), DATE_ADD(NOW(), INTERVAL 24 HOUR), 1),

(4, 'amina.khan@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Amina', 'Khan',
 'Business graduate now working in analytics and product operations.',
 'https://linkedin.com/in/aminakhan',
 NULL, 'alumni', 1, NULL, NULL, 1),
(5, 'liam.brown@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Liam', 'Brown',
 'Cloud engineer focused on Kubernetes platforms and AWS migration.',
 'https://linkedin.com/in/liambrown',
 NULL, 'alumni', 1, NULL, NULL, 1),
(6, 'sofia.fernandez@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Sofia', 'Fernandez',
 'Data analyst combining SQL, Tableau, and stakeholder reporting.',
 'https://linkedin.com/in/sofiafernandez',
 NULL, 'alumni', 1, NULL, NULL, 1),
(7, 'ethan.chen@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Ethan', 'Chen',
 'AI engineer building TensorFlow and Python systems.',
 'https://linkedin.com/in/ethanchen',
 NULL, 'alumni', 1, NULL, NULL, 1),
(8, 'maya.patel@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Maya', 'Patel',
 'Cybersecurity consultant working across cloud security and governance.',
 'https://linkedin.com/in/mayapatel',
 NULL, 'alumni', 1, NULL, NULL, 1),
(9, 'oliver.green@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Oliver', 'Green',
 'Product manager using agile practices and analytics for SaaS teams.',
 'https://linkedin.com/in/olivergreen',
 NULL, 'alumni', 1, NULL, NULL, 1),
(10, 'nora.ali@westminster.ac.uk',
 '$2y$12$ZrGB.QOE0dtogLY2GRMMcOUoZ3X8yQlAJ4eq4w3w/DIXUKyR5sWLm',
 'Nora', 'Ali',
 'Data science graduate working in machine learning operations.',
 'https://linkedin.com/in/noraali',
 NULL, 'alumni', 1, NULL, NULL, 1);

-- =====================================================
-- Degrees
-- =====================================================
INSERT INTO degrees (alumni_id, title, institution, url, completion_date) VALUES
(1, 'BSc Computer Science (First Class Honours)', 'University of Westminster', 'https://www.westminster.ac.uk/courses/computer-science', '2015-06-30'),
(1, 'MSc Software Engineering', 'University of Westminster', 'https://www.westminster.ac.uk/courses/software-engineering', '2016-06-30'),
(2, 'BSc Mathematics', 'University of Westminster', 'https://www.westminster.ac.uk/courses/mathematics', '2014-06-30'),
(2, 'PhD Artificial Intelligence', 'University of Westminster', 'https://www.westminster.ac.uk/research/ai', '2019-06-30'),
(4, 'BA Business Management', 'University of Westminster', 'https://www.westminster.ac.uk/courses/business-management', '2020-06-30'),
(5, 'BSc Computer Science', 'University of Westminster', 'https://www.westminster.ac.uk/courses/computer-science', '2021-06-30'),
(6, 'BA Business Management', 'University of Westminster', 'https://www.westminster.ac.uk/courses/business-management', '2019-06-30'),
(7, 'MSc Data Science and Analytics', 'University of Westminster', 'https://www.westminster.ac.uk/courses/data-science', '2022-06-30'),
(8, 'BSc Cyber Security', 'University of Westminster', 'https://www.westminster.ac.uk/courses/cyber-security', '2021-06-30'),
(9, 'BA Business Management', 'University of Westminster', 'https://www.westminster.ac.uk/courses/business-management', '2018-06-30'),
(10, 'MSc Data Science and Analytics', 'University of Westminster', 'https://www.westminster.ac.uk/courses/data-science', '2023-06-30');

-- =====================================================
-- Certifications
-- =====================================================
INSERT INTO certifications (alumni_id, title, issuer, url, completion_date) VALUES
(1, 'AWS Solutions Architect Professional', 'Amazon Web Services', 'https://aws.amazon.com/certification/certified-solutions-architect-professional/', '2022-03-15'),
(1, 'Google Cloud Professional Data Engineer', 'Google', 'https://cloud.google.com/certification/data-engineer', '2023-01-20'),
(2, 'TensorFlow Developer Certificate', 'Google', 'https://www.tensorflow.org/certificate', '2021-08-10'),
(2, 'Microsoft Azure AI Engineer', 'Microsoft', 'https://learn.microsoft.com/en-us/certifications/azure-ai-engineer/', '2022-11-01'),
(4, 'Tableau Desktop Specialist', 'Tableau', 'https://www.tableau.com/learn/certification', '2021-04-12'),
(5, 'Certified Kubernetes Administrator', 'Cloud Native Computing Foundation', 'https://www.cncf.io/training/certification/cka/', '2022-07-10'),
(6, 'Google Data Analytics Certificate', 'Google', 'https://grow.google/certificates/data-analytics/', '2020-09-21'),
(7, 'TensorFlow Developer Certificate', 'Google', 'https://www.tensorflow.org/certificate', '2023-03-11'),
(8, 'CISSP', 'ISC2', 'https://www.isc2.org/certifications/cissp', '2022-05-19'),
(9, 'Professional Scrum Master I', 'Scrum.org', 'https://www.scrum.org/assessments/professional-scrum-master-i-certification', '2019-11-08'),
(10, 'AWS Machine Learning Specialty', 'Amazon Web Services', 'https://aws.amazon.com/certification/certified-machine-learning-specialty/', '2024-02-02');

-- =====================================================
-- Licences
-- =====================================================
INSERT INTO licences (alumni_id, title, awarding_body, url, completion_date) VALUES
(1, 'Chartered IT Professional (CITP)', 'BCS - The Chartered Institute for IT', 'https://www.bcs.org/qualifications-and-certifications/chartered-it-professional/', '2020-05-01'),
(2, 'Chartered Scientist (CSci)', 'Science Council', 'https://sciencecouncil.org/scientists-science-technicians/which-professional-registration-is-right-for-me/chartered-scientist-csci/', '2021-03-15');

-- =====================================================
-- Professional Courses
-- =====================================================
INSERT INTO courses (alumni_id, title, provider, url, completion_date) VALUES
(1, 'Machine Learning Specialization', 'Coursera / Stanford', 'https://www.coursera.org/specializations/machine-learning-introduction', '2021-06-01'),
(1, 'Docker & Kubernetes Masterclass', 'Udemy', 'https://www.udemy.com/course/docker-kubernetes/', '2022-09-15'),
(2, 'Deep Learning Specialization', 'Coursera / deeplearning.ai', 'https://www.coursera.org/specializations/deep-learning', '2020-04-20'),
(2, 'Natural Language Processing with Transformers', 'Hugging Face', 'https://huggingface.co/learn/nlp-course', '2023-02-28'),
(4, 'Python for Everybody', 'Coursera', 'https://www.coursera.org/specializations/python', '2020-12-01'),
(4, 'SQL for Data Analysis', 'DataCamp', 'https://www.datacamp.com/', '2021-02-15'),
(5, 'AWS Cloud Practitioner Essentials', 'Amazon Web Services', 'https://aws.amazon.com/training/', '2021-10-01'),
(5, 'Docker Deep Dive', 'A Cloud Guru', 'https://www.pluralsight.com/cloud-guru', '2022-02-18'),
(6, 'Tableau for Business Intelligence', 'Udemy', 'https://www.udemy.com/', '2020-10-05'),
(7, 'MLOps Fundamentals', 'Coursera', 'https://www.coursera.org/', '2023-05-20'),
(8, 'Azure Security Engineer', 'Microsoft Learn', 'https://learn.microsoft.com/', '2022-08-14'),
(9, 'Agile Product Management', 'Product School', 'https://productschool.com/', '2019-04-25'),
(10, 'Kubernetes for Machine Learning', 'Udacity', 'https://www.udacity.com/', '2024-03-18');

-- =====================================================
-- Employment History
-- =====================================================
INSERT INTO employment_history (alumni_id, company, position, start_date, end_date) VALUES
(1, 'TechStart London', 'Junior Developer', '2016-09-01', '2018-08-31'),
(1, 'InnovateTech Ltd', 'Senior Software Engineer', '2018-09-01', '2021-12-31'),
(1, 'GlobalTech Solutions', 'Lead Software Architect', '2022-01-01', NULL),
(2, 'DataInsights Ltd', 'Data Analyst', '2019-07-01', '2020-12-31'),
(2, 'AI Research Lab', 'Senior Data Scientist', '2021-01-01', '2023-06-30'),
(2, 'DeepMind', 'Research Scientist', '2023-07-01', NULL),
(4, 'Retail Insights Group', 'Business Analyst', '2020-08-01', '2022-09-30'),
(4, 'FinData Works', 'Analytics Consultant', '2022-10-01', NULL),
(5, 'CloudScale Ltd', 'Platform Engineer', '2021-09-01', NULL),
(6, 'MarketMetrics', 'BI Analyst', '2019-09-01', NULL),
(7, 'VisionAI Studio', 'Machine Learning Engineer', '2022-09-01', NULL),
(8, 'SecureCloud Partners', 'Cloud Security Consultant', '2021-08-01', NULL),
(9, 'SaaSWorks', 'Product Manager', '2018-09-01', NULL),
(10, 'MLOps Labs', 'Data Scientist', '2023-09-01', NULL);

-- =====================================================
-- University Analytics Dimensions
-- =====================================================
INSERT INTO programmes (id, name, faculty) VALUES
(1, 'BSc Computer Science', 'Science and Technology'),
(2, 'BSc Mathematics', 'Science and Technology'),
(3, 'BA Business Management', 'Westminster Business School'),
(4, 'BSc Cyber Security', 'Science and Technology'),
(5, 'MSc Data Science and Analytics', 'Science and Technology');

INSERT INTO industry_sectors (id, name) VALUES
(1, 'Software Engineering'),
(2, 'Data Analytics'),
(3, 'Artificial Intelligence'),
(4, 'Cybersecurity'),
(5, 'Cloud Computing'),
(6, 'Product Management');

INSERT INTO alumni_outcomes (alumni_id, programme_id, industry_sector_id, graduation_date, current_role, current_company) VALUES
(1, 1, 1, '2015-06-30', 'Lead Software Architect', 'GlobalTech Solutions'),
(2, 2, 3, '2014-06-30', 'Research Scientist', 'DeepMind'),
(3, 4, 4, '2021-06-30', 'Security Analyst', 'SecureOps Labs'),
(4, 3, 2, '2020-06-30', 'Analytics Consultant', 'FinData Works'),
(5, 1, 5, '2021-06-30', 'Platform Engineer', 'CloudScale Ltd'),
(6, 3, 2, '2019-06-30', 'BI Analyst', 'MarketMetrics'),
(7, 5, 3, '2022-06-30', 'Machine Learning Engineer', 'VisionAI Studio'),
(8, 4, 4, '2021-06-30', 'Cloud Security Consultant', 'SecureCloud Partners'),
(9, 3, 6, '2018-06-30', 'Product Manager', 'SaaSWorks'),
(10, 5, 3, '2023-06-30', 'Data Scientist', 'MLOps Labs');

INSERT INTO skills (id, name, category, curriculum_status) VALUES
(1, 'Docker', 'Cloud & DevOps', 'missing'),
(2, 'Kubernetes', 'Cloud & DevOps', 'missing'),
(3, 'AWS', 'Cloud & DevOps', 'partial'),
(4, 'Azure', 'Cloud & DevOps', 'partial'),
(5, 'Python', 'Programming', 'covered'),
(6, 'SQL', 'Data', 'covered'),
(7, 'Tableau', 'Data', 'missing'),
(8, 'TensorFlow', 'AI', 'partial'),
(9, 'Agile Scrum', 'Professional Practice', 'partial'),
(10, 'CISSP', 'Cybersecurity', 'missing');

INSERT INTO alumni_skills (alumni_id, skill_id, source_type, acquired_date) VALUES
(1, 1, 'course', '2022-09-15'),
(1, 2, 'course', '2022-09-15'),
(1, 3, 'certification', '2022-03-15'),
(1, 4, 'certification', '2023-01-20'),
(1, 5, 'course', '2021-06-01'),
(1, 9, 'employment', '2022-01-01'),
(2, 5, 'course', '2020-04-20'),
(2, 6, 'employment', '2019-07-01'),
(2, 8, 'certification', '2021-08-10'),
(2, 4, 'certification', '2022-11-01'),
(3, 10, 'certification', '2022-01-15'),
(3, 6, 'employment', '2021-07-01'),
(4, 5, 'course', '2020-12-01'),
(4, 6, 'course', '2021-02-15'),
(4, 7, 'certification', '2021-04-12'),
(4, 9, 'employment', '2022-10-01'),
(5, 1, 'course', '2022-02-18'),
(5, 2, 'certification', '2022-07-10'),
(5, 3, 'course', '2021-10-01'),
(5, 9, 'employment', '2021-09-01'),
(6, 6, 'employment', '2019-09-01'),
(6, 7, 'course', '2020-10-05'),
(6, 5, 'certification', '2020-09-21'),
(7, 5, 'employment', '2022-09-01'),
(7, 8, 'certification', '2023-03-11'),
(7, 3, 'course', '2023-05-20'),
(8, 4, 'course', '2022-08-14'),
(8, 10, 'certification', '2022-05-19'),
(8, 3, 'employment', '2021-08-01'),
(9, 9, 'certification', '2019-11-08'),
(9, 7, 'employment', '2018-09-01'),
(10, 2, 'course', '2024-03-18'),
(10, 3, 'certification', '2024-02-02'),
(10, 5, 'employment', '2023-09-01'),
(10, 8, 'employment', '2023-09-01');

-- =====================================================
-- Bids (for testing the bidding system)
-- =====================================================
INSERT INTO bids (alumni_id, amount, bid_date, status) VALUES
(1, 250.00, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'pending'),
(2, 300.00, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'pending'),
(1, 200.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'won'),
(2, 150.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'lost'),
(1, 175.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'lost'),
(2, 350.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'won');

-- =====================================================
-- Featured Alumni (past winners)
-- =====================================================
INSERT INTO featured_alumni (bid_id, featured_date) VALUES
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(6, CURDATE());

-- =====================================================
-- Event Participations (for 4th bid bonus)
-- =====================================================
INSERT INTO event_participations (alumni_id, event_name, event_date) VALUES
(1, 'Westminster Career Fair 2026', CURDATE()),
(2, 'Alumni Networking Night', DATE_SUB(CURDATE(), INTERVAL 7 DAY));

-- =====================================================
-- Sponsorships
-- =====================================================
INSERT INTO sponsorships (alumni_id, sponsor_name, amount_offered, status) VALUES
(1, 'AWS Training & Certification', 200.00, 'accepted'),
(1, 'Google Cloud Skills Boost', 300.00, 'pending'),
(2, 'Microsoft Learn', 250.00, 'accepted'),
(2, 'Coursera for Enterprise', 150.00, 'rejected');

-- =====================================================
-- API Clients (for testing API access)
-- =====================================================
-- Values are stored as SHA-256 hashes (matching Api_client_model::validate_token())
-- Plain-text bearer tokens for curl testing:
--   Client 1 (Active):  Authorization: Bearer test-bearer-token-12345
--   Client 2 (Active):  Authorization: Bearer mobile-bearer-token-67890
--   Client 3 (Revoked): Authorization: Bearer revoked-token-00000
INSERT INTO api_clients (id, client_name, api_key, bearer_token, is_active) VALUES
(1, 'Analytics Dashboard (Development)', '0c8d48c0f50b513727be8cff1dcd66dbfe49419755a0dba68ccc503dc4ec439d', 'd45a64efc20f54b5007c8e209d6b79585de6dcbf0845194daa7a975176f832cc', 1),
(2, 'Mobile AR App (Development)', '3634a060c2d2c9531b19f221bea365ea63fc4ac67494372a91311091b8468bef', '08f1c0beb8ebae8efa5dc81318b8440da11e2b7fd648bfc4b1077c0e60757456', 1),
(3, 'Revoked Client', '4c393082218ded2d9ac62c478c2b6c04ee2d921d8cb8588700bcca0da4813be8', '17c0ba23fac95d2c55f6ed7797b2d3f82b6a62545d8744a40f29b1e8839e1436', 0);

INSERT INTO api_scopes (id, name) VALUES
(1, 'read:alumni_of_day'),
(2, 'read:alumni'),
(3, 'write:alumni'),
(4, 'read:analytics'),
(5, 'read:donations'),
(6, 'featured:read'),
(7, 'alumni:read'),
(8, 'alumni:write');

INSERT INTO api_client_scopes (api_client_id, api_scope_id) VALUES
(1, 1),
(1, 2),
(1, 4),
(2, 1),
(3, 1),
(3, 2),
(3, 4);

-- =====================================================
-- API Access Logs (sample entries)
-- =====================================================
INSERT INTO api_access_logs (api_client_id, endpoint, method, ip_address, access_time) VALUES
(1, 'api/v1/featured/today', 'GET', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'api/v1/alumni', 'GET', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 'api/v1/featured/today', 'GET', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'api/v1/alumni/1', 'GET', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 1 HOUR));

SELECT '✓ Seed data loaded successfully!' AS status;
SELECT CONCAT('  Alumni: ', COUNT(*)) AS info FROM alumni
UNION ALL
SELECT CONCAT('  Degrees: ', COUNT(*)) FROM degrees
UNION ALL
SELECT CONCAT('  Certifications: ', COUNT(*)) FROM certifications
UNION ALL
SELECT CONCAT('  Licences: ', COUNT(*)) FROM licences
UNION ALL
SELECT CONCAT('  Courses: ', COUNT(*)) FROM courses
UNION ALL
SELECT CONCAT('  Employment: ', COUNT(*)) FROM employment_history
UNION ALL
SELECT CONCAT('  Bids: ', COUNT(*)) FROM bids
UNION ALL
SELECT CONCAT('  Featured: ', COUNT(*)) FROM featured_alumni
UNION ALL
SELECT CONCAT('  API Clients: ', COUNT(*)) FROM api_clients
UNION ALL
SELECT CONCAT('  API Scopes: ', COUNT(*)) FROM api_scopes
UNION ALL
SELECT CONCAT('  API Client Scopes: ', COUNT(*)) FROM api_client_scopes
UNION ALL
SELECT CONCAT('  API Logs: ', COUNT(*)) FROM api_access_logs;
