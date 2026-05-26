<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

define('DB_HOST',    'localhost');
define('DB_NAME',    'rescue_path');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$input_number   = trim($_POST['number']   ?? '');
$input_password = trim($_POST['password'] ?? '');

if ($input_number === '' || $input_password === '') {
    header('Location: login.php?error=' . urlencode('Mobile number and password are required.'));
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    header('Location: login.php?error=' . urlencode('Database connection failed. Check XAMPP MySQL is running.'));
    exit;
}


try {
    $stmt = $pdo->prepare('SELECT Id, Name, Password, Number FROM user WHERE Number = ? LIMIT 1');
    $stmt->execute([$input_number]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    header('Location: login.php?error=' . urlencode('Server error. Please try again.'));
    exit;
}


if (!$user) {
    header('Location: login.php?error=' . urlencode('No account found with this mobile number.'));
    exit;
}

if ($user['Password'] !== $input_password) {
    header('Location: login.php?error=' . urlencode('Incorrect password. Please try again.'));
    exit;
}


$_SESSION['logged_in'] = true;
$_SESSION['user_id']   = $user['Id'];
$_SESSION['user_name'] = $user['Name'];


header('Location: dashboard.php');
exit;