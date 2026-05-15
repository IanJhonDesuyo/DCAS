-- Data Cleaning and Analytics System -- MySQL schema
CREATE DATABASE IF NOT EXISTS dcas_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE dcas_db;

-- Users table for authentication and roles
-- IMPORTANT: This must be created first because other tables reference users(id)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  role ENUM('user', 'admin') DEFAULT 'user',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Uploaded files
CREATE TABLE IF NOT EXISTS uploaded_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                      -- Link to users table
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,         -- filename on disk (original)
  cleaned_name VARCHAR(255) DEFAULT NULL,    -- filename of cleaned CSV
  file_size INT NOT NULL,
  rows_count INT DEFAULT 0,
  cols_count INT DEFAULT 0,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Dataset column metadata (after profiling)
CREATE TABLE IF NOT EXISTS dataset_metadata (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                      -- Link to users table
  file_id INT NOT NULL,
  column_name VARCHAR(255) NOT NULL,
  data_type VARCHAR(50) NOT NULL,            -- integer, float, date, string, boolean
  missing_count INT DEFAULT 0,
  unique_count INT DEFAULT 0,
  most_frequent VARCHAR(255) DEFAULT NULL,
  min_value VARCHAR(255) DEFAULT NULL,
  max_value VARCHAR(255) DEFAULT NULL,
  avg_value VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cleaning logs (every action explained)
CREATE TABLE IF NOT EXISTS cleaning_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                      -- Link to users table
  file_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,              -- e.g. remove_duplicates
  details TEXT,                              -- short explanation + numbers affected
  affected_rows INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Generated insights
CREATE TABLE IF NOT EXISTS insights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                      -- Link to users table
  file_id INT NOT NULL,
  insight_text TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User action history
CREATE TABLE IF NOT EXISTS action_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                      -- Link to users table
  file_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  description VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;