<?php
require_once 'config.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'user') {
    header('Location: dashboard.php');
    exit;
}

if ($_POST) {
    $note = clean($_POST['note']);
    $address = clean($_POST['address']);
    $time = clean($_POST['time']);
    
    // Insert prescription
    $stmt = $pdo->prepare("INSERT INTO prescriptions (user_id, note, address, time_slot) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $note, $address, $time])) {
        $prescription_id = $pdo->lastInsertId();
        
        // Handle file upload
        if (!empty($_FILES['images']['name'][0])) {
            if (!is_dir('uploads')) mkdir('uploads');
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $file_name = time() . '_' . $_FILES['images']['name'][$key];
                    $file_path = 'uploads/' . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $pdo->prepare("INSERT INTO prescription_images (prescription_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$prescription_id, $file_path]);
                    }
                }
            }
        }
        
        $success = "Prescription uploaded successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Prescription - MedSystem</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <h1>MedSystem</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="quotations.php">Quotations</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Upload Prescription</h2>
            
            <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <textarea name="note" placeholder="Prescription Note"></textarea>
                <textarea name="address" placeholder="Delivery Address" required></textarea>
                
                <label>Delivery Time Slot:</label>
                <select name="time" required>
                    <option value="09:00-11:00">09:00-11:00</option>
                    <option value="11:00-13:00">11:00-13:00</option>
                    <option value="13:00-15:00">13:00-15:00</option>
                    <option value="15:00-17:00">15:00-17:00</option>
                </select>
                
                <label>Prescription Images (Max 5):</label>
                <input type="file" name="images[]" multiple accept="image/*">
                
                <button type="submit" class="btn">Upload Prescription</button>
            </form>
        </div>
    </div>
</body>
</html>