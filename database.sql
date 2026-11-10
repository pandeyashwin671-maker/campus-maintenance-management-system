CREATE DATABASE IF NOT EXISTS campus_maintenance;
USE campus_maintenance;

-- Only two login roles exist now: student and admin. Staff is no longer a
-- login role at all.
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','admin') NOT NULL DEFAULT 'student',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff are now just reference data (a name to assign a request to) with no
-- login, no password, no account of any kind.
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(80) NULL
);

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(50) NOT NULL,
    room VARCHAR(50) NOT NULL
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL,
    asset_name VARCHAR(120) NOT NULL,
    location_id INT NULL,
    status ENUM('Working','Under Maintenance','Damaged','Retired') DEFAULT 'Working',
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_code VARCHAR(30) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    asset_id INT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    status ENUM('New','Under Review','Assigned','In Progress','Waiting for Parts','Resolved','Verified','Closed','Rejected','Reopened') DEFAULT 'New',
    assigned_to INT NULL,
    resolution_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL
);

CREATE TABLE status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNIQUE NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Only admin and student demo accounts remain. No staff logins exist.
INSERT IGNORE INTO users (name,email,password,role,active) VALUES
('System Admin','admin@campus.com',MD5('admin123'),'admin',1),
('Demo Student','student@campus.com',MD5('student123'),'student',1);

-- Default technician reference data for the Assign dropdown / Active Staff
-- list. These are NOT accounts - they cannot log in.
INSERT INTO staff(name,specialty) VALUES
('Rahul Sharma','Electrical'),
('Amit Verma','IT Equipment'),
('Suresh Kumar','Plumbing'),
('Meena Joshi','Cleaning'),
('Deepak Yadav','Furniture'),
('Anita Rao','AC / HVAC'),
('Vikram Singh','Security'),
('Priya Nair','Electrical'),
('Ramesh Gupta','Plumbing'),
('Sunita Patil','Cleaning'),
('Manoj Tiwari','IT Equipment'),
('Kavita Reddy','AC / HVAC'),
('Arjun Malhotra','Furniture'),
('Neha Kapoor','Security'),
('Sanjay Iyer','Other');

INSERT INTO categories(name) VALUES
('Electrical'),('IT Equipment'),('Plumbing'),('Furniture'),('Cleaning'),('AC / HVAC'),('Security'),('Other');

INSERT INTO locations(building,floor,room) VALUES
('Main Building','Ground Floor','101'),
('Main Building','First Floor','201'),
('Computer Block','First Floor','Lab 1'),
('Computer Block','Second Floor','Lab 2'),
('Library','Ground Floor','Reading Hall'),
('Science Block','First Floor','Lab 3'),
('Auditorium','Ground Floor','Main Hall');

INSERT INTO assets(asset_code,asset_name,location_id,status) VALUES
('PRJ-L1-01','Projector',3,'Working'),
('AC-L1-03','Air Conditioner',3,'Working'),
('PC-L1-12','Desktop Computer',3,'Working'),
('PRJ-AUD-01','Auditorium Projector',7,'Working');

INSERT INTO requests
(request_code,user_id,category_id,location_id,asset_id,title,description,priority,status,assigned_to)
VALUES
('MR-1001',2,2,3,1,'Projector not turning on','Projector in Lab 1 does not start.','High','Assigned',2);
