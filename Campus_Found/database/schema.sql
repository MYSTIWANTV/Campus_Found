CREATE TABLE users (
 id INTEGER PRIMARY KEY AUTO_INCREMENT,
 name VARCHAR(80) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role VARCHAR(15) NOT NULL DEFAULT 'student',
 created_at VARCHAR(25) NOT NULL
);
CREATE TABLE locations (
 id INTEGER PRIMARY KEY AUTO_INCREMENT,
 name VARCHAR(80) NOT NULL UNIQUE
);
CREATE TABLE reports (
 id INTEGER PRIMARY KEY AUTO_INCREMENT,
 user_id INTEGER NOT NULL,
 type VARCHAR(10) NOT NULL,
 title VARCHAR(100) NOT NULL,
 category VARCHAR(30) NOT NULL,
 color VARCHAR(30) NOT NULL,
 location_id INTEGER NOT NULL,
 event_date VARCHAR(10) NOT NULL,
 description TEXT NOT NULL,
 private_detail TEXT NOT NULL,
 document_hash VARCHAR(64),
 status VARCHAR(20) NOT NULL DEFAULT 'open',
 custody INTEGER NOT NULL DEFAULT 0,
 created_at VARCHAR(25) NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id),
 FOREIGN KEY(location_id) REFERENCES locations(id)
);
CREATE TABLE claims (
 id INTEGER PRIMARY KEY AUTO_INCREMENT,
 report_id INTEGER NOT NULL,
 user_id INTEGER NOT NULL,
 evidence TEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 collection_code VARCHAR(30),
 review_note TEXT,
 attempts INTEGER NOT NULL DEFAULT 0,
 created_at VARCHAR(25) NOT NULL,
 UNIQUE(report_id,user_id),
 FOREIGN KEY(report_id) REFERENCES reports(id),
 FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE events (
 id INTEGER PRIMARY KEY AUTO_INCREMENT,
 report_id INTEGER NOT NULL,
 actor_id INTEGER NOT NULL,
 label VARCHAR(150) NOT NULL,
 created_at VARCHAR(25) NOT NULL,
 FOREIGN KEY(report_id) REFERENCES reports(id),
 FOREIGN KEY(actor_id) REFERENCES users(id)
);
CREATE TABLE watches (
 id INTEGER PRIMARY KEY AUTO_INCREMENT,
 user_id INTEGER NOT NULL,
 category VARCHAR(30) NOT NULL,
 color VARCHAR(30) NOT NULL,
 created_at VARCHAR(25) NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE login_attempts (
 identity_hash VARCHAR(64) PRIMARY KEY,
 attempts INTEGER NOT NULL DEFAULT 0,
 window_start INTEGER NOT NULL
);
CREATE INDEX reports_search ON reports(category, status, location_id);
CREATE INDEX claims_queue ON claims(status, report_id);
CREATE INDEX events_report ON events(report_id);
INSERT INTO locations(name) VALUES ('Library'),('Lecture block'),('Hostel area'),('Main entrance'),('Cafeteria'),('Auditorium');
