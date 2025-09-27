<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_POST) {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // 🚫 Prevent pharmacy login from here
        if ($user['user_type'] === "pharmacy") {
            $error = "Pharmacy accounts must log in via the Admin Login page.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = "Wrong email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - MedSystem</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <h1>MedSystem</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Login</h2>
            
            <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
            
            <form method="POST">
                <label>Email :</label>
                <input type="email" name="email" placeholder="Email" required>
                
                <label>Password :</label>
                <input type="password" name="password" placeholder="Password" required>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <p>No account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
