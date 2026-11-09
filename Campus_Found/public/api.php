<?php
declare(strict_types=1);
ini_set('display_errors', '0');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function respond($data, int $status=200): never { http_response_code($status); echo json_encode($data); exit; }
function fail(string $message, int $status=400): never { respond(['error'=>$message],$status); }
function query(string $sql, array $args=[]): PDOStatement { global $db; $q=$db->prepare($sql); $q->execute($args); return $q; }
function field(string $key, int $max=150, bool $required=true): string { global $input; $v=trim((string)($input[$key]??'')); if (($required && $v==='') || strlen($v)>$max) fail('Please check '.str_replace('_',' ',$key).'.'); return $v; }
function requireUser(): array { global $user; if (!$user) fail('Please sign in to continue.',401); return $user; }
function officer(): void { if (requireUser()['role']!=='security') fail('Security access required.',403); }
function event(int $id,string $label): void { global $user; query('INSERT INTO events(report_id,actor_id,label,created_at) VALUES(?,?,?,?)',[$id,$user['id'],$label,gmdate('c')]); }
function lockReport(int $id): array { global $db; $suffix=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':''; $r=query('SELECT * FROM reports WHERE id=?'.$suffix,[$id])->fetch(); if (!$r) fail('Report not found.',404); return $r; }
try {
 $path=dirname(__DIR__).'/config.php';
 if (!is_file($path)) fail('Complete the database setup in README.md first.',503);
 $cfg=require $path;
 $db=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite') $db->exec('PRAGMA foreign_keys=ON');
 $input=json_decode(file_get_contents('php://input'),true)??[];
 $action=$_GET['action']??'state';
 $_SESSION['csrf']??=bin2hex(random_bytes(24));
 $user=isset($_SESSION['uid'])?query('SELECT id,name,email,role FROM users WHERE id=?',[$_SESSION['uid']])->fetch():null;
 if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) fail('Session expired. Refresh and try again.',403);
 if ($action==='state') {
   $reports=query("SELECT r.id,r.user_id,r.type,r.title,r.category,r.color,r.location_id,r.event_date,r.description,r.status,r.custody,r.created_at,l.name AS location FROM reports r JOIN locations l ON l.id=r.location_id ORDER BY r.id DESC")->fetchAll();
   $claims=[]; $watches=[]; $details=[];
   if ($user) {
     $claims=query('SELECT c.*,r.title,u.name AS claimant FROM claims c JOIN reports r ON r.id=c.report_id JOIN users u ON u.id=c.user_id'.($user['role']==='security'?'':' WHERE c.user_id=?').' ORDER BY c.id DESC',$user['role']==='security'?[]:[$user['id']])->fetchAll();
     $watches=query('SELECT * FROM watches WHERE user_id=?',[$user['id']])->fetchAll();
     $details=query('SELECT id,private_detail FROM reports'.($user['role']==='security'?'':' WHERE user_id=?'),$user['role']==='security'?[]:[$user['id']])->fetchAll();
   }
   respond(['user'=>$user?:null,'csrf'=>$_SESSION['csrf'],'reports'=>$reports,'claims'=>$claims,'watches'=>$watches,'details'=>$details,'locations'=>query('SELECT * FROM locations')->fetchAll(),'events'=>query('SELECT report_id,label,created_at FROM events ORDER BY id')->fetchAll()]);
 }
 if ($_SERVER['REQUEST_METHOD']!=='POST') fail('Method not allowed.',405);
 if ($action==='register' || $action==='login') {
   $email=strtolower(field('email',190)); $password=field('password',200);
   if (!filter_var($email,FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.');
   if ($action==='register') {
     $name=field('name',80); if (strlen($password)<12) fail('Use at least 12 characters for your password.');
     if (query('SELECT id FROM users WHERE email=?',[$email])->fetch()) fail('This email is already registered.');
     query('INSERT INTO users(name,email,password,role,created_at) VALUES(?,?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'student',gmdate('c')]);
     $uid=(int)$db->lastInsertId();
   } else {
     $key=hash('sha256',$email.'|'.($_SERVER['REMOTE_ADDR']??''));
     $attempt=query('SELECT * FROM login_attempts WHERE identity_hash=?',[$key])->fetch();
     if ($attempt && time()-(int)$attempt['window_start']<900 && (int)$attempt['attempts']>=5) fail('Too many attempts. Try again in 15 minutes.',429);
     if (!$attempt || time()-(int)$attempt['window_start']>=900) {
       query('DELETE FROM login_attempts WHERE identity_hash=?',[$key]); query('INSERT INTO login_attempts(identity_hash,attempts,window_start) VALUES(?,0,?)',[$key,time()]);
     }
     $account=query('SELECT * FROM users WHERE email=?',[$email])->fetch();
     if (!$account || !password_verify($password,$account['password'])) { query('UPDATE login_attempts SET attempts=attempts+1 WHERE identity_hash=?',[$key]); fail('Email or password is incorrect.',401); }
     query('DELETE FROM login_attempts WHERE identity_hash=?',[$key]); $uid=(int)$account['id'];
   }
   session_regenerate_id(true); $_SESSION['uid']=$uid; $_SESSION['csrf']=bin2hex(random_bytes(24)); respond(['ok'=>true]);
 }
 requireUser();
 if ($action==='logout') { $_SESSION=[]; session_destroy(); respond(['ok'=>true]); }
 if ($action==='report') {
   $type=field('type'); $category=field('category'); $title=field('title',100); $description=field('description',1000); $color=strtolower(field('color',30)); $private=field('private_detail',1000); $date=field('event_date',10); $location=(int)field('location_id');
   if (!in_array($type,['lost','found'],true) || !in_array($category,['Electronics','Books & stationery','Keys','Bags','ID & documents','Other'],true)) fail('Choose a valid report type and category.');
   $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date); if (!$parsed || $parsed->format('Y-m-d')!==$date || $date>gmdate('Y-m-d')) fail('Choose a valid date up to today.');
   if (!query('SELECT id FROM locations WHERE id=?',[$location])->fetch()) fail('Choose a campus location.');
   $doc=field('document_number',80,false);
   if ($category==='ID & documents') { $title='ID or document'; $description='Document reported. Use the private ID search or contact campus security to verify ownership.'; $color='unspecified'; }
   $db->beginTransaction();
   query('INSERT INTO reports(user_id,type,title,category,color,location_id,event_date,description,private_detail,document_hash,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[$user['id'],$type,$title,$category,$color,$location,$date,$description,$private,$doc?hash('sha256',strtoupper(preg_replace('/\s+/','',$doc))):null,gmdate('c')]);
   $id=(int)$db->lastInsertId(); event($id,'Report created'); $db->commit(); respond(['ok'=>true,'id'=>$id]);
 }
 if ($action==='claim') {
   $id=(int)field('report_id'); $evidence=field('evidence',1000);
   $db->beginTransaction(); $r=lockReport($id);
   if ($r['type']!=='found' || $r['status']!=='open' || (int)$r['user_id']===(int)$user['id']) fail('This item is not available for your claim.');
   if (query('SELECT id FROM claims WHERE report_id=? AND user_id=?',[$id,$user['id']])->fetch()) fail('You already submitted a claim for this item.');
   query('INSERT INTO claims(report_id,user_id,evidence,created_at) VALUES(?,?,?,?)',[$id,$user['id'],$evidence,gmdate('c')]); event($id,'Ownership claim submitted'); $db->commit(); respond(['ok'=>true]);
 }
 if ($action==='watch') { $cat=field('category'); if (!in_array($cat,['Electronics','Books & stationery','Keys','Bags','ID & documents','Other'],true)) fail('Choose a valid category.'); query('INSERT INTO watches(user_id,category,color,created_at) VALUES(?,?,?,?)',[$user['id'],$cat,strtolower(field('color',30,false)),gmdate('c')]); respond(['ok'=>true]); }
 if ($action==='unwatch') { query('DELETE FROM watches WHERE id=? AND user_id=?',[(int)field('id'),$user['id']]); respond(['ok'=>true]); }
 if ($action==='id-search') {
   $key=hash('sha256','id-search|'.$user['id']);
   $attempt=query('SELECT * FROM login_attempts WHERE identity_hash=?',[$key])->fetch();
   if ($attempt && time()-(int)$attempt['window_start']<900 && (int)$attempt['attempts']>=10) fail('Search limit reached. Try again in 15 minutes.',429);
   if (!$attempt || time()-(int)$attempt['window_start']>=900) { query('DELETE FROM login_attempts WHERE identity_hash=?',[$key]); query('INSERT INTO login_attempts(identity_hash,attempts,window_start) VALUES(?,0,?)',[$key,time()]); }
   query('UPDATE login_attempts SET attempts=attempts+1 WHERE identity_hash=?',[$key]);
   $number=field('document_number',80); $hash=hash('sha256',strtoupper(preg_replace('/\s+/','',$number)));
   $rows=query("SELECT id FROM reports WHERE document_hash=? AND category='ID & documents' AND type='found' AND status='open'",[$hash])->fetchAll(); respond(['ids'=>array_column($rows,'id')]);
 }
 if ($action==='close') { $id=(int)field('id'); $db->beginTransaction(); $r=lockReport($id); if ((int)$r['user_id']!==(int)$user['id'] || $r['type']!=='lost' || $r['status']!=='open') fail('Only your open lost report can be closed.'); query("UPDATE reports SET status='closed' WHERE id=?",[$id]); event($id,'Owner closed lost report'); $db->commit(); respond(['ok'=>true]); }
 officer();
 if ($action==='custody') { $id=(int)field('id'); $db->beginTransaction(); $r=lockReport($id); if ($r['type']!=='found' || $r['status']!=='open' || $r['custody']) fail('Custody cannot be recorded for this item.'); query('UPDATE reports SET custody=1 WHERE id=?',[$id]); event($id,'Received at campus security'); $db->commit(); respond(['ok'=>true]); }
 if ($action==='review' || $action==='collect') {
   $claimId=(int)field('id'); $initial=query('SELECT report_id FROM claims WHERE id=?',[$claimId])->fetch(); if (!$initial) fail('Claim not found.',404);
   $db->beginTransaction(); $r=lockReport((int)$initial['report_id']); $claim=query('SELECT * FROM claims WHERE id=?',[$claimId])->fetch();
   if ($action==='review') {
     $decision=field('decision'); if (!in_array($decision,['approved','rejected'],true) || $claim['status']!=='pending' || $r['status']!=='open') fail('This claim has already changed. Refresh the page.');
     if ($decision==='approved' && !$r['custody']) fail('Record physical receipt at security before approval.');
     $note=field('review_note',500); $code=$decision==='approved'?strtoupper(bin2hex(random_bytes(6))):null;
     query('UPDATE claims SET status=?,review_note=?,collection_code=? WHERE id=?',[$decision,$note,$code,$claimId]);
     if ($decision==='approved') { query("UPDATE reports SET status='reserved' WHERE id=?",[$r['id']]); query("UPDATE claims SET status='rejected',review_note='Another claim was verified.' WHERE report_id=? AND status='pending'",[$r['id']]); }
     event((int)$r['id'],$decision==='approved'?'Ownership verified; ready for collection':'Claim reviewed');
   } else {
     if ($claim['status']!=='approved' || $r['status']!=='reserved') fail('This collection is already completed or unavailable.');
     if ((int)$claim['attempts']>=5) fail('Code attempts exceeded. Administrator intervention required.',429);
     if (!hash_equals((string)$claim['collection_code'],strtoupper(field('code',30)))) { query('UPDATE claims SET attempts=attempts+1 WHERE id=?',[$claimId]); $db->commit(); fail('Collection code does not match.'); }
     query("UPDATE claims SET status='collected',collection_code=NULL WHERE id=?",[$claimId]); query("UPDATE reports SET status='returned' WHERE id=?",[$r['id']]); event((int)$r['id'],'Item collected; handover recorded');
   }
   $db->commit(); respond(['ok'=>true]);
 }
 fail('Unknown action.',404);
} catch (Throwable $error) {
 if (isset($db) && $db->inTransaction()) $db->rollBack();
 error_log($error->getMessage()); fail('The request could not be completed. Please retry or contact the administrator.',500);
}
