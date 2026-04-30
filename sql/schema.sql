-- Alumni Influencers Platform Database Schema
-- Database: alumni_platform
-- Normalized to 3NF

CREATE DATABASE IF NOT EXISTS alumni_platform
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE alumni_platform;

-- Shared user table for authentication and common identity fields.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    user_type ENUM('alumni','admin') NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    verification_expires DATETIME,
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active_verified_created (is_active, email_verified, created_at),
    INDEX idx_verification_token (verification_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alumni profile subtype. id is also users.id for the alumni account.
CREATE TABLE IF NOT EXISTS alumni (
    id INT UNSIGNED PRIMARY KEY,
    bio TEXT,
    linkedin_url VARCHAR(500),
    profile_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Degrees table (1:N with alumni)
CREATE TABLE IF NOT EXISTS degrees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    institution VARCHAR(255) NOT NULL,
    url VARCHAR(500),
    completion_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_completion_date (alumni_id, completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Certifications table (1:N with alumni)
CREATE TABLE IF NOT EXISTS certifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    issuer VARCHAR(255) NOT NULL,
    url VARCHAR(500),
    completion_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_completion_date (alumni_id, completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Licences table (1:N with alumni)
CREATE TABLE IF NOT EXISTS licences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    awarding_body VARCHAR(255) NOT NULL,
    url VARCHAR(500),
    completion_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_completion_date (alumni_id, completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Professional courses table (1:N with alumni)
CREATE TABLE IF NOT EXISTS courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    provider VARCHAR(255) NOT NULL,
    url VARCHAR(500),
    completion_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_completion_date (alumni_id, completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employment history table (1:N with alumni)
CREATE TABLE IF NOT EXISTS employment_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    company VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_start_date (alumni_id, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Academic programmes offered by the university.
CREATE TABLE IF NOT EXISTS programmes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    faculty VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Industry sectors used for graduate destination analytics.
CREATE TABLE IF NOT EXISTS industry_sectors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One current graduate-outcome record per alumnus keeps programme/sector filters normalized.
CREATE TABLE IF NOT EXISTS alumni_outcomes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL UNIQUE,
    programme_id INT UNSIGNED NOT NULL,
    industry_sector_id INT UNSIGNED NOT NULL,
    graduation_date DATE NOT NULL,
    current_role VARCHAR(255),
    current_company VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE RESTRICT,
    FOREIGN KEY (industry_sector_id) REFERENCES industry_sectors(id) ON DELETE RESTRICT,
    INDEX idx_programme_graduation (programme_id, graduation_date),
    INDEX idx_sector_graduation (industry_sector_id, graduation_date),
    INDEX idx_graduation_date (graduation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Skills/technology catalog used to detect curriculum gaps from alumni profiles.
CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    category VARCHAR(120) NOT NULL,
    curriculum_status ENUM('covered','partial','missing') DEFAULT 'partial',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_status (category, curriculum_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alumni-to-skill evidence. Source describes where the skill was observed.
CREATE TABLE IF NOT EXISTS alumni_skills (
    alumni_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    source_type ENUM('certification','course','licence','employment','self_reported') NOT NULL,
    acquired_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (alumni_id, skill_id, source_type),
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    INDEX idx_skill_date (skill_id, acquired_date),
    INDEX idx_alumni_date (alumni_id, acquired_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Saved dashboard filter presets for repeatable reports.
CREATE TABLE IF NOT EXISTS analytics_filter_presets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    filters_json TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_preset (admin_id, name),
    INDEX idx_admin_created (admin_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bids table (blind bidding system)
CREATE TABLE IF NOT EXISTS bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    bid_date DATE NOT NULL,
    status ENUM('pending','won','lost') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_bid_date (bid_date),
    INDEX idx_status (status),
    UNIQUE KEY unique_alumni_bid_date (alumni_id, bid_date),
    INDEX idx_bid_date_status_amount (bid_date, status, amount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Featured alumni table (daily winners)
CREATE TABLE IF NOT EXISTS featured_alumni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bid_id INT UNSIGNED NOT NULL,
    featured_date DATE NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE,
    INDEX idx_featured_date (featured_date),
    UNIQUE KEY unique_bid_id (bid_id),
    INDEX idx_featured_date_bid_id (featured_date, bid_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Event participations (for 4th bid allowance per month)
CREATE TABLE IF NOT EXISTS event_participations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_event_date (alumni_id, event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API clients (for bearer token access control)
CREATE TABLE IF NOT EXISTS api_clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    bearer_token VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_bearer_token (bearer_token),
    INDEX idx_active_bearer_token (is_active, bearer_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API scopes catalog
CREATE TABLE IF NOT EXISTS api_scopes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API client scope assignments (M:N)
CREATE TABLE IF NOT EXISTS api_client_scopes (
    api_client_id INT UNSIGNED NOT NULL,
    api_scope_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (api_client_id, api_scope_id),
    FOREIGN KEY (api_client_id) REFERENCES api_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (api_scope_id) REFERENCES api_scopes(id) ON DELETE CASCADE,
    INDEX idx_scope_client (api_scope_id, api_client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API access logs (usage statistics)
CREATE TABLE IF NOT EXISTS api_access_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_client_id INT UNSIGNED NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45),
    access_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_client_id) REFERENCES api_clients(id) ON DELETE CASCADE,
    INDEX idx_api_client_id (api_client_id),
    INDEX idx_access_time (access_time),
    INDEX idx_client_access_time (api_client_id, access_time),
    INDEX idx_endpoint_access_time (endpoint(191), access_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sponsorships table
CREATE TABLE IF NOT EXISTS sponsorships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    sponsor_name VARCHAR(255) NOT NULL,
    amount_offered DECIMAL(10,2) NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_alumni_id (alumni_id),
    INDEX idx_alumni_status_created (alumni_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rate limits table (IP-based rate limiting for auth endpoints)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip_address, action),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_ip_action_attempted_at (ip_address, action, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Required API scope catalog values. These are lookup records, not demo seed data.
INSERT IGNORE INTO api_scopes (name) VALUES
('read:alumni'),
('read:analytics'),
('read:donations'),
('read:alumni_of_day'),
('write:alumni');
