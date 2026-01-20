-- Faculty Loading Table
CREATE TABLE IF NOT EXISTS faculty_loads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    faculty_id BIGINT UNSIGNED NOT NULL,
    department VARCHAR(255) NOT NULL,
    classes_assigned INT NOT NULL DEFAULT 0,
    load_hours DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'part-time', 'overloaded') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_faculty_loads_user FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- DSS Recommendations Table
CREATE TABLE IF NOT EXISTS dss_recommendations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(255) NOT NULL,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    issue TEXT NOT NULL,
    solution TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'implemented') DEFAULT 'pending',
    related_faculty_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dss_recommendations_user FOREIGN KEY (related_faculty_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Export Logs Table
CREATE TABLE IF NOT EXISTS export_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    format VARCHAR(50) NOT NULL,
    data_selected TEXT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size BIGINT,
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_export_logs_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Class Schedules Table (if not exists)
CREATE TABLE IF NOT EXISTS class_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    faculty_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    grade_section VARCHAR(100) NOT NULL,
    room_id BIGINT UNSIGNED,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    student_count INT DEFAULT 0,
    status ENUM('pending', 'active', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_class_schedules_faculty FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rooms Table (if not exists)
CREATE TABLE IF NOT EXISTS rooms (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    building VARCHAR(100),
    capacity INT DEFAULT 30,
    has_laboratory BOOLEAN DEFAULT FALSE,
    has_projector BOOLEAN DEFAULT TRUE,
    has_ac BOOLEAN DEFAULT TRUE,
    status ENUM('available', 'in-use', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Sample Data
INSERT INTO faculty_loads (faculty_id, department, classes_assigned, load_hours, status) 
SELECT id, 'High School', 5, 6.5, 'overloaded' FROM users WHERE email = 'maria@example.com' LIMIT 1;

INSERT INTO faculty_loads (faculty_id, department, classes_assigned, load_hours, status) 
SELECT id, 'Senior High', 4, 4.2, 'active' FROM users WHERE email = 'juan@example.com' LIMIT 1;

INSERT INTO dss_recommendations (type, priority, issue, solution, status) VALUES
('teacher_overload', 'high', 'Maria Santos has 6.5 hours/week load (exceeds 6.0 max)', 'Reassign 1 class to another available teacher with lower load', 'pending'),
('class_balance', 'medium', 'Grade 10A has 35 students while 10B has 28 students', 'Transfer 3-5 students from 10A to 10B to balance enrollment', 'pending'),
('room_utilization', 'medium', 'Lab Room 5 is only 40% utilized with 3 classes per day', 'Consolidate lab classes or schedule more activities to increase room usage', 'pending'),
('schedule_gap', 'high', '2-hour gap in Juan schedule on Tuesday afternoon', 'Schedule additional class or professional development session during this gap', 'pending'),
('facility_assignment', 'medium', 'Science class assigned to regular classroom instead of lab', 'Reassign to appropriate lab room with required equipment and facilities', 'pending'),
('teacher_preference', 'low', 'Ana requested morning classes but assigned mostly afternoon slots', 'Swap 2 afternoon classes with another teacher to accommodate preference', 'pending');
