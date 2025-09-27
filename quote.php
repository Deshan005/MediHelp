<?php
require_once 'config.php';

if (!isLoggedIn() || !isPharmacy()) {
    header('Location: dashboard.php');
    exit;
}

$prescription_id = $_GET['id'] ?? 0;

if ($_POST) {
    $drugs = $_POST['drug'];
    $quantities = $_POST['quantity'];
    $amounts = $_POST['amount'];
    
    $total = 0;
    $items = [];
    
    // Calculate total and prepare items
    for ($i = 0; $i < count($drugs); $i++) {
        if (!empty($drugs[$i])) {
            $items[] = [
                'drug' => clean($drugs[$i]),
                'quantity' => clean($quantities[$i]),
                'amount' => floatval($amounts[$i])
            ];
            $total += floatval($amounts[$i]);
        }
    }
    
    if (!empty($items)) {
        // Create quotation
        $stmt = $pdo->prepare("INSERT INTO quotations (prescription_id, pharmacy_id, total) VALUES (?, ?, ?)");
        if ($stmt->execute([$prescription_id, $_SESSION['user_id'], $total])) {
            $quote_id = $pdo->lastInsertId();
            
            // Add items
            foreach ($items as $item) {
                $stmt = $pdo->prepare("INSERT INTO quotation_items (quotation_id, drug, quantity, amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$quote_id, $item['drug'], $item['quantity'], $item['amount']]);
            }
            
            // Update prescription status
            $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'quoted' WHERE id = ?");
            $stmt->execute([$prescription_id]);

            // Fetch patient email
            $stmt = $pdo->prepare("SELECT u.email, u.name FROM prescriptions p 
                                JOIN users u ON p.user_id = u.id 
                                WHERE p.id = ?");
            $stmt->execute([$prescription_id]);
            $patient = $stmt->fetch();

            if ($patient) {
                $to = $patient['email'];
                $subject = "Your Prescription Quotation is Ready";
                $message = "Hello " . $patient['name'] . ",\n\n"
                        . "Your prescription quotation (ID: $prescription_id) has been prepared.\n"
                        . "Please log in to your MedSystem account to review and respond.\n\n"
                        . "Thank you,\nMedSystem Pharmacy Team";

                sendEmail($to, $subject, $message);
            }

            header('Location: quotations.php');
            exit;
        }
    }
}

// Get prescription details
$stmt = $pdo->prepare("SELECT p.*, u.name as user_name FROM prescriptions p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.id = ?");
$stmt->execute([$prescription_id]);
$prescription = $stmt->fetch();

// Get prescription images
$img_stmt = $pdo->prepare("SELECT * FROM prescription_images WHERE prescription_id = ?");
$img_stmt->execute([$prescription_id]);
$images = $img_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Quotation - MedSystem</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <h1>MedSystem</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="view.php">View Prescriptions</a>
            <a href="quotations.php">Quotations</a>
            <a href="logout.php">Logout (<?php echo $_SESSION['user_name']; ?>)</a>
        </div>
    </div>

    <div class="main-container">
        <div class="prescription-header">
            <h2>Create Quotation</h2>
            <p>Prescription #<?php echo $prescription_id; ?> - <?php echo $prescription['user_name']; ?></p>
        </div>
        
        <div class="quotation-layout">
            <!-- Left Column - Images -->
            <div class="images-column">
                <div class="image-section">
                    <div class="section-title">Prescription (Image)</div>
                    
                    <?php if(empty($images)): ?>
                        <div class="empty-state">
                            <p>No prescription images available</p>
                        </div>
                    <?php else: ?>
                        <!-- Big Main Image (First image) -->
                        <img id="mainImage" src="<?php echo $images[0]['image_path']; ?>" 
                             alt="Main Prescription Image" class="main-image">
                        
                        <!-- Thumbnail Grid (Only images 2-5, total 4 thumbnails) -->
                        <div class="thumbnail-grid">
                            <?php 
                            // Show only images 2-5 (skip the first one since it's displayed as main image)
                            for ($i = 1; $i < min(5, count($images)); $i++): 
                                $img = $images[$i];
                            ?>
                                <div class="thumbnail-container">
                                    <img src="<?php echo $img['image_path']; ?>" 
                                         alt="Img <?php echo $i + 1; ?>" 
                                         class="thumbnail <?php echo $i === 1 ? 'active' : ''; ?>"
                                         onclick="changeMainImage('<?php echo $img['image_path']; ?>', this)">
                                    <div class="thumbnail-label">Img <?php echo $i + 1; ?></div>
                                </div>
                            <?php endfor; ?>
                            
                            <!-- Fill remaining slots if less than 4 additional images -->
                            <?php for($i = count($images) - 1; $i < 4; $i++): ?>
                                <div class="thumbnail-container">
                                    <div class="thumbnail" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #bdc3c7;">
                                        No Image
                                    </div>
                                    <div class="thumbnail-label">Img <?php echo $i + 2; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column - Quotation (EXACT IMAGE LAYOUT) -->
            <div class="quotation-column">
                <div class="quotation-section">
                    <div class="quotation-title">Prepare Quotation</div>
                    
                    <form method="POST" id="quotationForm">
                        <!-- Drug List Section -->
                        <div class="drug-list" id="drugListContainer">
                            <!-- Drug items will be added here dynamically -->
                            <div class="empty-state">No drugs added yet</div>
                        </div>
                        
                        <!-- Total Row -->
                        <div class="total-row">
                            Total: <span id="totalAmount">$0.00</span>
                        </div>
                        
                        <!-- Add Section -->
                        <div class="add-section">
                            <div class="add-row">
                                <div class="add-label">Drug</div>
                                <input type="text" id="newDrug" class="add-input" placeholder="Amoxicillin 250mg">
                            </div>
                            
                            <div class="add-row">
                                <div class="add-label">Quantity</div>
                                <input type="text" id="newQuantity" class="add-input" placeholder="10.00 x 5">
                            </div>
                            
                            <button type="button" class="add-button" onclick="addDrug()">Add</button>
                        </div>
                        
                        <button type="button" class="send-button" onclick="submitQuotation()">Send Quotation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let drugCounter = 0;
        let drugs = [];
        
        // Change main image when thumbnail is clicked (with swap behavior)
        function changeMainImage(imageSrc, element) {
            const mainImage = document.getElementById('mainImage');
            const currentMainSrc = mainImage.src;
            
            // Swap the images
            mainImage.src = imageSrc;
            element.src = currentMainSrc;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
        }
        // Parse quantity and calculate amount (e.g., "10.00 x 5" = 50.00)
        function calculateAmount(quantityStr) {
            const parts = quantityStr.split('x').map(part => part.trim());
            if (parts.length === 2) {
                const quantity = parseFloat(parts[0]) || 0;
                const multiplier = parseFloat(parts[1]) || 0;
                return (quantity * multiplier).toFixed(2);
            }
            return '0.00';
        }
        
        // Add new drug to the quotation
        function addDrug() {
            const drugName = document.getElementById('newDrug').value.trim();
            const quantity = document.getElementById('newQuantity').value.trim();
            
            if (!drugName || !quantity) {
                alert('Please fill in both drug name and quantity.');
                return;
            }
            
            const amount = parseFloat(calculateAmount(quantity));
            
            if (amount <= 0) {
                alert('Please check the quantity format (e.g., 10.00 x 5)');
                return;
            }
            
            drugs.push({
                id: drugCounter++,
                name: drugName,
                quantity: quantity,
                amount: amount
            });
            
            updateDrugList();
            clearAddForm();
            updateTotal();
        }
        
        // Remove drug from quotation
        function removeDrug(id) {
            drugs = drugs.filter(drug => drug.id !== id);
            updateDrugList();
            updateTotal();
        }
        
        // Update the drug list display (EXACT IMAGE LAYOUT)
        function updateDrugList() {
            const container = document.getElementById('drugListContainer');
            
            if (drugs.length === 0) {
                container.innerHTML = '<div class="empty-state">No drugs added yet</div>';
                return;
            }
            
            // Create columns layout exactly like the image with remove button in Amount column
            container.innerHTML = `
                <div class="drug-item">
                    <div class="drug-column">
                        <div class="column-label">Drug</div>
                        ${drugs.map(drug => `<div class="column-value">${drug.name}</div>`).join('')}
                    </div>
                    <div class="drug-column">
                        <div class="column-label">Quantity</div>
                        ${drugs.map(drug => `<div class="column-value">${drug.quantity}</div>`).join('')}
                    </div>
                    <div class="drug-column">
                        <div class="column-label">Amount</div>
                        ${drugs.map(drug => `<div class="column-value">$${drug.amount.toFixed(2)}</div>`).join('')}
                    </div>
                    <div class="action-column">
                        <div class="action-label">Action</div>
                        ${drugs.map(drug => `<div class="column-value"><button type="button" class="remove-btn" onclick="removeDrug(${drug.id})">×</button></div>`).join('')}
                    </div>
                </div>
            `;
        }
        
        // Update total amount
        function updateTotal() {
            const total = drugs.reduce((sum, drug) => sum + drug.amount, 0);
            document.getElementById('totalAmount').textContent = `$${total.toFixed(2)}`;
        }
        
        // Clear the add form
        function clearAddForm() {
            document.getElementById('newDrug').value = '';
            document.getElementById('newQuantity').value = '';
            document.getElementById('newDrug').focus();
        }
        
        // Submit the quotation
        function submitQuotation() {
            if (drugs.length === 0) {
                alert('Please add at least one drug to the quotation.');
                return;
            }
            
            // Create hidden form inputs for each drug
            const form = document.getElementById('quotationForm');
            
            // Clear any existing inputs
            document.querySelectorAll('input[name="drug[]"], input[name="quantity[]"], input[name="amount[]"]').forEach(input => {
                input.remove();
            });
            
            // Add new inputs for current drugs
            drugs.forEach(drug => {
                const drugInput = document.createElement('input');
                drugInput.type = 'hidden';
                drugInput.name = 'drug[]';
                drugInput.value = drug.name;
                form.appendChild(drugInput);
                
                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'quantity[]';
                quantityInput.value = drug.quantity;
                form.appendChild(quantityInput);
                
                const amountInput = document.createElement('input');
                amountInput.type = 'hidden';
                amountInput.name = 'amount[]';
                amountInput.value = drug.amount;
                form.appendChild(amountInput);
            });
            
            // Submit the form
            form.submit();
        }
        
        // Initialize with sample data
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on first input
            document.getElementById('newDrug').focus();
            
            // Add sample drugs for demonstration (exactly like the image)
            setTimeout(() => {
                if (drugs.length === 0) {
                    drugs = [
                        { 
                            id: drugCounter++, 
                            name: 'Amoxicillin 250mg', 
                            quantity: '10.00 x 5', 
                            amount: 50.00 
                        },
                        { 
                            id: drugCounter++, 
                            name: 'Paracetamol 500mg', 
                            quantity: '5.00 x 5', 
                            amount: 25.00 
                        }
                    ];
                    updateDrugList();
                    updateTotal();
                }
            }, 500);
        });
    </script>
    <script>
    // Change main image when thumbnail is clicked (with swap behavior)
    function changeMainImage(imageSrc, element) {
        const mainImage = document.getElementById('mainImage');
        const currentMainSrc = mainImage.src;

        // Swap the images
        mainImage.src = imageSrc;
        element.src = currentMainSrc;

        // Update active thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }

    // Show main image in popup when clicked
    document.getElementById('mainImage').addEventListener('click', function() {
        // Create overlay
        const popup = document.createElement('div');
        popup.className = 'image-popup-overlay';
        popup.style.position = 'fixed';
        popup.style.top = 0;
        popup.style.left = 0;
        popup.style.width = '100%';
        popup.style.height = '100%';
        popup.style.background = 'rgba(0,0,0,0.8)';
        popup.style.display = 'flex';
        popup.style.alignItems = 'center';
        popup.style.justifyContent = 'center';
        popup.style.zIndex = 9999;
        popup.style.cursor = 'pointer';

        // Create image element
        const img = document.createElement('img');
        img.src = this.src;
        img.style.maxWidth = '90%';
        img.style.maxHeight = '90%';
        img.style.borderRadius = '10px';
        img.style.boxShadow = '0 0 20px rgba(0,0,0,0.5)';

        popup.appendChild(img);
        document.body.appendChild(popup);

        // Remove popup when clicked
        popup.addEventListener('click', function() {
            document.body.removeChild(popup);
        });
    });
</script>

</body>
</html>