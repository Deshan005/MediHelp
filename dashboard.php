<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get counts for dashboard
if ($user_type == 'user') {
    // User counts
    $prescriptions = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE user_id = ?");
    $prescriptions->execute([$user_id]);
    $prescription_count = $prescriptions->fetchColumn();
    
    $quotations = $pdo->prepare("SELECT COUNT(*) FROM quotations q 
                                JOIN prescriptions p ON q.prescription_id = p.id 
                                WHERE p.user_id = ?");
    $quotations->execute([$user_id]);
    $quotation_count = $quotations->fetchColumn();
    
    $pending_quotations = $pdo->prepare("SELECT COUNT(*) FROM quotations q 
                                       JOIN prescriptions p ON q.prescription_id = p.id 
                                       WHERE p.user_id = ? AND p.status = 'quoted'");
    $pending_quotations->execute([$user_id]);
    $pending_quotation_count = $pending_quotations->fetchColumn();
    
} else {
    // Pharmacy counts
    $prescriptions = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE status = 'pending'");
    $prescriptions->execute();
    $prescription_count = $prescriptions->fetchColumn();
    
    $quotations = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE pharmacy_id = ?");
    $quotations->execute([$user_id]);
    $quotation_count = $quotations->fetchColumn();
    
    $pending_quotations = $pdo->prepare("SELECT COUNT(*) FROM quotations q 
                                       JOIN prescriptions p ON q.prescription_id = p.id 
                                       WHERE q.pharmacy_id = ? AND p.status = 'quoted'");
    $pending_quotations->execute([$user_id]);
    $pending_quotation_count = $pending_quotations->fetchColumn();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - MedSystem</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid #3498db;
        }
        
        .stat-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .count {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin: 15px 0;
        }
        
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .stat-card.prescriptions {
            border-left-color: #e74c3c;
        }
        
        .stat-card.prescriptions .count {
            color: #e74c3c;
        }
        
        .stat-card.quotations {
            border-left-color: #27ae60;
        }
        
        .stat-card.quotations .count {
            color: #27ae60;
        }
        
        .stat-card.pending {
            border-left-color: #f39c12;
        }
        
        .stat-card.pending .count {
            color: #f39c12;
        }
        
        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            color: #7f8c8d;
            font-size: 16px;
        }

.notify-btn {
    position: relative;
    font-size: 20px;
    text-decoration: none;
}

.dot {
    position: absolute;
    top: -2px;
    right: -8px;
    height: 10px;
    width: 10px;
    background-color: red;
    border-radius: 50%;
    display: inline-block;
}


    </style>
</head>
<body>
    <div class="navbar">
        <h1>MedSystem</h1>
<div class="nav-links">
    <a href="dashboard.php">Dashboard</a>
    <?php if($user_type == 'user'): ?>
        <a href="upload.php">Upload Prescription</a>
    <?php else: ?>
        <a href="view.php">View Prescriptions</a>

        <?php
        // Check for accepted/rejected quotations
        $notify_stmt = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE pharmacy_id = ? AND status IN ('accepted','rejected') AND notified =0");
        $notify_stmt->execute([$user_id]);
        $notify_count = $notify_stmt->fetchColumn();
        ?>
        <a href="quotations.php" class="notify-btn">
            🔔
            <?php if($notify_count > 0): ?>
                <span class="dot"></span>
            <?php endif; ?>
        </a>

    <?php endif; ?>
    <a href="quotations.php">Quotations</a>
    <a href="logout.php">Logout (<?php echo $_SESSION['user_name']; ?>)</a>
</div>
    </div>

    <div class="container">
        <div class="card">
            <div class="welcome-message">
                <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
                <p>Here's your dashboard overview</p>
            </div>
            
            <div class="dashboard-stats">
                <?php if($user_type == 'user'): ?>
                    <!-- User Dashboard -->
                    <div class="stat-card prescriptions">
                        <h3>My Prescriptions</h3>
                        <div class="count"><?php echo $prescription_count; ?></div>
                        <p>Total prescriptions uploaded</p>
                        <a href="upload.php" class="btn">Upload New Prescription</a>
                    </div>
                    
                    <div class="stat-card quotations">
                        <h3>My Quotations</h3>
                        <div class="count"><?php echo $quotation_count; ?></div>
                        <p>Total quotations received</p>
                        <a href="quotations.php" class="btn">View My Quotations</a>
                    </div>
                    
                <?php else: ?>
                    <!-- Pharmacy Dashboard -->
                    <div class="stat-card prescriptions">
                        <h3>Pending Prescriptions</h3>
                        <div class="count"><?php echo $prescription_count; ?></div>
                        <p>Prescriptions needing quotations</p>
                        <a href="view.php" class="btn">View Prescriptions</a>
                    </div>
                    
                    <div class="stat-card quotations">
                        <h3>My Quotations</h3>
                        <div class="count"><?php echo $quotation_count; ?></div>
                        <p>Total quotations sent</p>
                        <a href="quotations.php" class="btn">View My Quotations</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>