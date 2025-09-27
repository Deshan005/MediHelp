<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    // fetch pharmacy user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'pharmacy'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = 'pharmacy';
        header("Location: ../dashboard.php");
        exit;
    } else {
        $error = "Invalid pharmacy login";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy Admin Login - MedSystem</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden; /* removes scrollbar */
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-card {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            width: 350px;
            text-align: center;
            box-sizing: border-box;
        }
        h2 {
            margin-bottom: 25px;
            color: #2c3e50;
        }
        input[type="email"], input[type="password"] {
            width: calc(100% - 24px); /* equal left/right spacing inside card */
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #bdc3c7;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        .error {
            background: #e74c3c;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 14px;
        }
        .footer-text {
            margin-top: 20px;
            font-size: 13px;
            color: #7f8c8d;
        }
        .footer-text a {
            color: #3498db;
            text-decoration: none;
        }
        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Pharmacy Admin Login</h2>
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Admin Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="footer-text">
            Back to <a href="../index.php">User Login</a>
        </div>
    </div>
</body>
</html>
