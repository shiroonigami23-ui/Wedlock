CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profiles (
  user_id INT PRIMARY KEY,
  gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Other',
  seeking_gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Other',
  dob DATE NULL,
  city VARCHAR(80) NULL,
  religion VARCHAR(80) NULL,
  education VARCHAR(120) NULL,
  occupation VARCHAR(120) NULL,
  annual_income_lpa DECIMAL(8,2) NULL,
  about_me TEXT NULL,
  bio TEXT NULL,
  profile_photo_url VARCHAR(255) NULL,
  cover_photo_url VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_profile_gender(gender),
  INDEX idx_profile_city(city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plan_code VARCHAR(40) NOT NULL UNIQUE,
  plan_name VARCHAR(80) NOT NULL,
  price_inr DECIMAL(10,2) NOT NULL DEFAULT 0,
  duration_days INT NOT NULL DEFAULT 30,
  max_contact_views INT NOT NULL DEFAULT 10,
  has_priority_listing TINYINT(1) NOT NULL DEFAULT 0,
  has_advanced_filters TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  started_at DATETIME NOT NULL DEFAULT NOW(),
  expires_at DATETIME NOT NULL,
  payment_ref VARCHAR(120) NULL,
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
  INDEX idx_sub_user(user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS interests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  from_user_id INT NOT NULL,
  to_user_id INT NOT NULL,
  status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_interest(from_user_id, to_user_id),
  INDEX idx_interest_to(to_user_id, status),
  CONSTRAINT fk_i_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_i_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profile_vectors (
  user_id INT PRIMARY KEY,
  cluster_id INT NULL,
  v1 DECIMAL(8,4) NOT NULL DEFAULT 0,
  v2 DECIMAL(8,4) NOT NULL DEFAULT 0,
  v3 DECIMAL(8,4) NOT NULL DEFAULT 0,
  v4 DECIMAL(8,4) NOT NULL DEFAULT 0,
  CONSTRAINT fk_vec_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_vec_cluster(cluster_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

