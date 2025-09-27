<?php
require_once 'config.php';

if ($_POST) {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $address = clean($_POST['address']);
    $phone = clean($_POST['phone']);
    $dob = $_POST['dob'];
    $user_type = "user"; // default for normal registration

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, address, phone, dob, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $address, $phone, $dob, $user_type])) {
            $success = "Registered successfully! You can now <a href='login.php'>login</a>.";
        } else {
            $error = "Something went wrong, please try again!";
        }
    } else {
        $error = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - MedSystem</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <h1>MedSystem</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Create Account</h2>
            
            <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
            <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <form method="POST">
                <label>Name :</label>
                <input type="text" name="name" placeholder="Full Name" required>
                
                <label>Email :</label>
                <input type="email" name="email" placeholder="Email" required>
                
                <label>Password :</label>
                <input type="password" name="password" placeholder="Password" required>
                
                <label>Address :</label>
                <textarea name="address" placeholder="Address" required></textarea>
                
                <label>Tele. No. :</label>
                <input type="text" name="phone" placeholder="Phone Number" required>
                
                <label>Date of Birth :</label>
                <input type="date" name="dob" required>
                
                <button type="submit" class="btn">Register</button>
            </form>
            
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
