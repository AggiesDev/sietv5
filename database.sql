CREATE DATABASE IF NOT EXISTS siet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siet_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','member','public') DEFAULT 'public',
  membership_grade VARCHAR(50),
  membership_status ENUM('active','pending','expired') DEFAULT 'pending',
  membership_no VARCHAR(50) UNIQUE,
  certification_no VARCHAR(50),
  certified_grade VARCHAR(100),
  membership_title VARCHAR(100),
  post_nominal VARCHAR(50),
  branch VARCHAR(100),
  specialisation VARCHAR(150),
  certification_status ENUM('Certified','In Progress','Pending Review','Expired','Renewal Due','Suspended/Withdrawn') DEFAULT 'Pending Review',
  is_directory_visible TINYINT(1) DEFAULT 1,
  phone VARCHAR(20),
  profile_image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  content LONGTEXT,
  excerpt TEXT,
  image VARCHAR(255),
  status ENUM('published','draft') DEFAULT 'draft',
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  description LONGTEXT,
  event_date DATE,
  event_time TIME,
  location VARCHAR(255),
  image VARCHAR(255),
  status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming',
  max_participants INT DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE partners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type ENUM('local','international','organisational') DEFAULT 'local',
  description TEXT,
  website VARCHAR(255),
  logo VARCHAR(255),
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE council (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  position VARCHAR(100),
  period VARCHAR(50),
  image VARCHAR(255),
  bio TEXT,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE membership_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100),
  email VARCHAR(100),
  phone VARCHAR(20),
  grade_applied VARCHAR(50),
  qualification TEXT,
  experience TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cpd_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  activity_title VARCHAR(255),
  cpd_type VARCHAR(100),
  cpd_hours DECIMAL(5,2),
  activity_date DATE,
  certificate VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100),
  subject VARCHAR(255),
  message TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE site_banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  scope ENUM('home','page') NOT NULL DEFAULT 'page',
  page_key VARCHAR(120) NULL,
  title VARCHAR(255),
  subtitle TEXT,
  image VARCHAR(255) NOT NULL,
  button_label VARCHAR(100),
  button_url VARCHAR(255),
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE site_pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  excerpt TEXT,
  content LONGTEXT,
  status ENUM('published','draft') DEFAULT 'draft',
  show_in_nav TINYINT(1) DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE nav_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(120) NOT NULL,
  url VARCHAR(255),
  parent_id INT NULL,
  page_id INT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  is_header TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES nav_items(id) ON DELETE CASCADE,
  FOREIGN KEY (page_id) REFERENCES site_pages(id) ON DELETE SET NULL
);

CREATE TABLE import_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  import_type VARCHAR(50),
  file_name VARCHAR(255),
  rows_total INT DEFAULT 0,
  rows_success INT DEFAULT 0,
  rows_failed INT DEFAULT 0,
  notes TEXT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (name, email, password, role, membership_status)
VALUES ('SIET Admin', 'admin@siet.org', '$2y$10$21Rx9pdLNiMXGd/zSbpk9eID3oAD2aEhvYEGZOtH2S02xsLLlNh0e', 'admin', 'active');

INSERT INTO users (name, email, password, role, membership_grade, membership_status, membership_no, certification_no, certified_grade, membership_title, post_nominal, branch, specialisation, certification_status, phone) VALUES
('Aisha Rahman', 'aisha.member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'Member', 'active', 'SIET-00002', 'TPC-00034', 'Certified Engineering Technologist', 'Member Title', 'MSIET', 'Mechanical', 'Industrial Automation', 'Certified', '0123456789'),
('Daniel Tan', 'daniel.member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'Graduate Member', 'active', 'SIET-00003', 'TPC-00051', 'Certified Engineering Technician', 'Graduate Member Title', 'GradSIET', 'Instrumentation', 'Measurement Systems', 'In Progress', '0122223333');

INSERT INTO news (title, slug, excerpt, content, image, status, created_by) VALUES
('Instrumentation Seminar Opens Registration', 'instrumentation-seminar-opens-registration', 'A placeholder update about an upcoming professional seminar.', 'This is dummy content for a news article. Replace it with approved SIET copy before launch.', 'https://placehold.co/800x500?text=SIET+News', 'published', 1),
('New Member Resources Published', 'new-member-resources-published', 'A placeholder announcement about resources for members.', 'This is dummy content for a news article. Replace it with approved SIET copy before launch.', 'https://placehold.co/800x500?text=Resources', 'published', 1);

INSERT INTO events (title, slug, description, event_date, event_time, location, image, status, max_participants, created_by) VALUES
('Control Systems Workshop', 'control-systems-workshop', 'Dummy event description for a hands-on control systems workshop.', DATE_ADD(CURDATE(), INTERVAL 21 DAY), '09:00:00', 'Training Hall A', 'https://placehold.co/800x500?text=Workshop', 'upcoming', 80, 1),
('Measurement Technology Forum', 'measurement-technology-forum', 'Dummy event description for a professional forum.', DATE_ADD(CURDATE(), INTERVAL 45 DAY), '14:00:00', 'Conference Room B', 'https://placehold.co/800x500?text=Forum', 'upcoming', 120, 1);

INSERT INTO partners (name, type, description, website, logo, status) VALUES
('Local Technical Partner', 'local', 'Placeholder local partner profile.', 'https://example.com', 'https://placehold.co/300x160?text=Partner', 'active'),
('International Engineering Body', 'international', 'Placeholder international partner profile.', 'https://example.com', 'https://placehold.co/300x160?text=Global', 'active');

INSERT INTO council (name, position, period, image, bio, display_order) VALUES
('Dr. Maya Lim', 'President', '2026-2028', 'https://placehold.co/400x300?text=Council', 'Placeholder council biography.', 1),
('Ir. Kumar Shah', 'Honorary Secretary', '2026-2028', 'https://placehold.co/400x300?text=Council', 'Placeholder council biography.', 2);

INSERT INTO site_banners (scope, page_key, title, subtitle, image, sort_order, is_active, created_by) VALUES
('home', NULL, 'SIET Professional Recognition', 'Placeholder home banner managed by admin.', 'https://placehold.co/1600x520/0d6efd/ffffff?text=SIET+Professional+Recognition', 1, 1, 1),
('home', NULL, 'Engineering Technology Community', 'Placeholder home banner managed by admin.', 'https://placehold.co/1600x520/06b6d4/ffffff?text=Engineering+Technology+Community', 2, 1, 1);

INSERT INTO site_pages (title, slug, excerpt, content, status, show_in_nav, created_by) VALUES
('Custom Placeholder Page', 'custom-placeholder-page', 'Dummy custom page excerpt.', 'This is a dummy custom page managed from the admin panel.', 'published', 0, 1);
