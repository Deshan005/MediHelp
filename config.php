<?php
session_start();

// Database config
$host = 'localhost';
$dbname = 'prescription_system';
$username = 'root';
$password = '';

try {
    // Connect without DB first
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    // Create tables if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            phone VARCHAR(15) NOT NULL,
            dob DATE NOT NULL,
            user_type ENUM('user','pharmacy') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prescriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            note TEXT,
            address TEXT NOT NULL,
            time_slot VARCHAR(20) NOT NULL,
            status ENUM('pending','quoted','accepted','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prescription_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prescription_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            FOREIGN KEY (prescription_id) REFERENCES prescriptions(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quotations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prescription_id INT NOT NULL,
            pharmacy_id INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            status ENUM('pending','accepted','rejected') DEFAULT 'pending',
            notified TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (prescription_id) REFERENCES prescriptions(id),
            FOREIGN KEY (pharmacy_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quotation_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quotation_id INT NOT NULL,
            drug VARCHAR(255) NOT NULL,
            quantity VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (quotation_id) REFERENCES quotations(id)
        )
    ");

    // ✅ Create default pharmacy admin only if it does not exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@pharmacy.com']);
    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash("admin123", PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (name, email, password, address, phone, dob, user_type)
                       VALUES (?,?,?,?,?,?,?)")
            ->execute([
                'Pharmacy Admin',
                'admin@pharmacy.com',
                $hashed,
                'Pharmacy HQ',
                '0000000000',
                '2000-01-01',
                'pharmacy'
            ]);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Utility functions
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isPharmacy() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'pharmacy';
}

function sendEmail($to, $subject, $message) {
    file_put_contents("email_log.txt", "TO: $to\nSUBJECT: $subject\nMESSAGE: $message\n\n", FILE_APPEND);
    return true;
}
?>
