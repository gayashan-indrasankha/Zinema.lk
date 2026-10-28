<?php
putenv('DB_PASS=' . trim(file_get_contents('/run/secrets/db_password')));
putenv('GOOGLE_CLIENT_SECRET=' . trim(file_get_contents('/run/secrets/oauth_secret')));
require_once '/var/www/html/includes/environment.php';
if (APP_ENV !== 'development' || DB_HOST !== '127.0.0.1' || DB_NAME !== 'zinema_recovery_development') exit(1);
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($db->query('SELECT DATABASE()')->fetch_row()[0] !== DB_NAME) exit(1);
// The target identity was checked above. This migration does not delete rows.
$db->multi_query(file_get_contents('/opt/zinema/migrations/002-token-claims.sql'));
do { if ($result = $db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
$hash = password_hash(trim(file_get_contents('/run/secrets/demo_password')), PASSWORD_DEFAULT);
$name = 'demo_admin';
$stmt = $db->prepare('INSERT IGNORE INTO admins(username,password_hash) VALUES(?,?)');
$stmt->bind_param('ss', $name, $hash); $stmt->execute();
$name = 'demo_verified'; $email = 'verified@example.invalid';
$stmt = $db->prepare('INSERT IGNORE INTO users(username,email,password_hash,is_verified) VALUES(?,?,?,1)');
$stmt->bind_param('sss', $name, $email, $hash); $stmt->execute();
echo "Fictional development accounts available; credentials remain in private secret files.\n";
