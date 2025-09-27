<?php
require_once 'config.php';

if (!isLoggedIn() || !isPharmacy()) {
    header('Location: dashboard.php');
    exit;
}

// Get all pending prescriptions
$stmt = $pdo->prepare("SELECT p.*, u.name as user_name FROM prescriptions p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.status = 'pending'");
$stmt->execute();
$prescriptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Prescriptions - MedSystem</title>
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
        <h2>Pending Prescriptions</h2>
        
        <?php if(empty($prescriptions)): ?>
            <p>No pending prescriptions.</p>
        <?php else: ?>
            <?php foreach($prescriptions as $pres): ?>
            <div class="card">
                <h3>Prescription #<?php echo $pres['id']; ?></h3>
                <p><strong>User:</strong> <?php echo $pres['user_name']; ?></p>
                <p><strong>Note:</strong> <?php echo $pres['note'] ?: 'No note'; ?></p>
                <p><strong>Address:</strong> <?php echo $pres['address']; ?></p>
                <p><strong>Time:</strong> <?php echo $pres['time_slot']; ?></p>
                
                <?php
                // Get images
                $img_stmt = $pdo->prepare("SELECT * FROM prescription_images WHERE prescription_id = ?");
                $img_stmt->execute([$pres['id']]);
                $images = $img_stmt->fetchAll();
                ?>
                
                <?php if(!empty($images)): ?>
                    <div class="image-grid">
                        <?php foreach($images as $img): ?>
                            <img src="<?php echo $img['image_path']; ?>" width="100">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <a href="quote.php?id=<?php echo $pres['id']; ?>" class="btn">Create Quotation</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>