<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($user_type == 'user') {
    $stmt = $pdo->prepare("SELECT q.*, u.name as pharmacy_name 
                          FROM quotations q 
                          JOIN users u ON q.pharmacy_id = u.id 
                          JOIN prescriptions p ON q.prescription_id = p.id 
                          WHERE p.user_id = ?");
} else {
    $stmt = $pdo->prepare("SELECT q.*, u.name as user_name 
                          FROM quotations q 
                          JOIN prescriptions p ON q.prescription_id = p.id 
                          JOIN users u ON p.user_id = u.id 
                          WHERE q.pharmacy_id = ?");
}

$stmt->execute([$user_id]);
$quotations = $stmt->fetchAll();
if ($user_type == 'pharmacy') {
    // Mark all accepted/rejected as notified once viewed
    $mark_seen = $pdo->prepare("UPDATE quotations 
                                SET notified = 1 
                                WHERE pharmacy_id = ? 
                                AND status IN ('accepted','rejected')");
    $mark_seen->execute([$user_id]);
}


// Handle accept/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($user_type == 'user') {
        $stmt = $pdo->prepare("UPDATE quotations SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['action'], $_GET['id']]);
        header('Location: quotations.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quotations - MedSystem</title>
    <link rel="stylesheet" href="style.css">
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
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>My Quotations</h2>
        
        <?php if(empty($quotations)): ?>
            <p>No quotations found.</p>
        <?php else: ?>
            <?php foreach($quotations as $quote): ?>
            <div class="card">
                <h3>Quotation #<?php echo $quote['id']; ?></h3>
                <p><strong>Status:</strong> <span class="status-<?php echo $quote['status']; ?>">
                    <?php echo ucfirst($quote['status']); ?>
                </span></p>
                
                <?php if($user_type == 'user'): ?>
                    <p><strong>Pharmacy:</strong> <?php echo $quote['pharmacy_name']; ?></p>
                <?php else: ?>
                    <p><strong>User:</strong> <?php echo $quote['user_name']; ?></p>
                <?php endif; ?>
                
                <p><strong>Total Amount:</strong> $<?php echo number_format($quote['total'], 2); ?></p>
                
                <?php
                // Get items
                $items_stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
                $items_stmt->execute([$quote['id']]);
                $items = $items_stmt->fetchAll();
                ?>
                
                <table class="quote-table">
                    <tr>
                        <th>Drug</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                    </tr>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td><?php echo $item['drug']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong>$<?php echo number_format($quote['total'], 2); ?></strong></td>
                    </tr>
                </table>
                
                <?php if($user_type == 'user' && $quote['status'] == 'pending'): ?>
                    <div class="action-buttons">
                        <a href="quotations.php?action=accepted&id=<?php echo $quote['id']; ?>" class="btn btn-success">Accept</a>
                        <a href="quotations.php?action=rejected&id=<?php echo $quote['id']; ?>" class="btn btn-danger">Reject</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>