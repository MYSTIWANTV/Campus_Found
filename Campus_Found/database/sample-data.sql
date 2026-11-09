-- Fictional records only. The password value deliberately disables sample login.
INSERT INTO users(name,email,password,role,created_at) VALUES
('Demo Finder','finder@example.test','LOGIN_DISABLED','student','2026-11-09T10:00:00Z'),
('Demo Owner','owner@example.test','LOGIN_DISABLED','student','2026-11-09T10:00:00Z');
INSERT INTO reports(user_id,type,title,category,color,location_id,event_date,description,private_detail,status,custody,created_at)
SELECT u.id,'found','Scientific calculator','Electronics','black',l.id,'2026-11-09','Calculator found on a library desk.','A star scratched on the back.','open',0,'2026-11-09T10:00:00Z'
FROM users u CROSS JOIN locations l WHERE u.email='finder@example.test' AND l.name='Library';
INSERT INTO events(report_id,actor_id,label,created_at)
SELECT r.id,r.user_id,'Report created',r.created_at FROM reports r JOIN users u ON u.id=r.user_id WHERE u.email='finder@example.test';
