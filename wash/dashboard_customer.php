<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// 1. Handle New Order Request Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_order'])) {
    $name = $conn->real_escape_string($_POST['full_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $service_data = explode('|', $_POST['service_type']);
    $service_name = $conn->real_escape_string($service_data[0]);
    $price_per_unit = floatval($service_data[1]);
    $quantity = intval($_POST['quantity']);
    $total = $price_per_unit * $quantity;
    $location = $conn->real_escape_string($_POST['location']);
    $pickup_date = $_POST['pickup_date'];
    $payment_mode = $conn->real_escape_string($_POST['payment_mode']);

    $sql = "INSERT INTO orders (customer_id, customer_name, customer_phone, service_type, price_per_unit, quantity, total_amount, pickup_address, pickup_date, payment_mode, order_type) 
            VALUES ($user_id, '$name', '$phone', '$service_name', $price_per_unit, $quantity, $total, '$location', '$pickup_date', '$payment_mode', 'online')";

    if ($conn->query($sql)) {
        $message = "Order placed successfully! Track status and communicate with the reception desk below.";
    } else {
        $message = "Order Error: " . $conn->error;
    }
}

// 2. Handle Customer Feedback / Message to Receptionist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_feedback'])) {
    $order_id = !empty($_POST['order_id']) ? intval($_POST['order_id']) : "NULL";
    $feedback_msg = $conn->real_escape_string($_POST['customer_message']);

    if (!empty($feedback_msg)) {
        $conn->query("INSERT INTO role_communications (order_id, sender_id, recipient_role, message) 
                      VALUES ($order_id, $user_id, 'receptionist', '$feedback_msg')");
        $message = "Feedback sent successfully to the reception desk!";
    }
}

// Fetch past customer orders
$orders = $conn->query("SELECT * FROM orders WHERE customer_id=$user_id ORDER BY id DESC");

// Fetch communication logs between this Customer and the Receptionist (both sent and received)
$comms = $conn->query("SELECT rc.*, u.full_name as sender_name, u.role as sender_role 
                       FROM role_communications rc 
                       JOIN users u ON rc.sender_id = u.id 
                       LEFT JOIN orders o ON rc.order_id = o.id 
                       WHERE (rc.sender_id = $user_id AND rc.recipient_role = 'receptionist') 
                          OR (rc.recipient_role = 'customer' AND (o.customer_id = $user_id OR rc.recipient_id = $user_id OR rc.recipient_id IS NULL))
                       ORDER BY rc.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard | Hloba Ntanga</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fb; margin: 0; color: #1e293b; }
        header { background: #0a2540; color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .banner { background: #e0f2fe; border-left: 5px solid #1890ff; padding: 15px; border-radius: 6px; margin-bottom: 20px; color: #0369a1; font-weight: 600; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; color: #0a2540; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .calc-box { background: #f8fafc; padding: 15px; border-radius: 6px; font-weight: bold; font-size: 1.2rem; color: #d90429; margin-top: 10px; border: 1px dashed #d90429; }
        button { background: #d90429; color: white; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: 0.3s; }
        button:hover { background: #b00020; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.9rem; }
        th { background: #0a2540; color: white; }
        .status { padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-In { background: #e0f2fe; color: #075985; }
        .status-Ready { background: #dcfce7; color: #166534; }
        
        /* Chat styling for receptionist communication */
        .chat-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-top: 15px; max-height: 350px; overflow-y: auto; }
        .msg-bubble { margin-bottom: 12px; padding: 10px 14px; border-radius: 8px; max-width: 80%; }
        .msg-receptionist { background: #e0f2fe; border-left: 4px solid #1890ff; color: #0369a1; }
        .msg-customer { background: #dcfce7; border-left: 4px solid #10b981; color: #14532d; margin-left: auto; }
    </style>
</head>
<body>

<header>
    <h2><i class="fa-solid fa-shoe-prints"></i> Hloba Ntanga - Customer Portal</h2>
    <div>
        Welcome, <?= htmlspecialchars($_SESSION['name']) ?> | 
        <a href="logout.php" style="color:#d90429; font-weight:bold; text-decoration:none;">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</header>

<div class="container">
    <div class="banner">
        <i class="fa-solid fa-clock"></i> <strong>Turnaround Notice:</strong> Washing & restoration takes approximately 2 - 3 days due to processing volume.
    </div>

    <?php if($message): ?>
        <div style="padding:15px; background:#dcfce7; color:#166534; border-radius:6px; margin-bottom:20px; font-weight:600;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Request Shoe Wash Form -->
    <div class="card">
        <h3 style="color:#0a2540; margin-bottom:20px;"><i class="fa-solid fa-plus-circle"></i> Request Shoe Wash</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($_SESSION['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="phone" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Select Shoe Service Type</label>
                    <select name="service_type" id="serviceSelect" onchange="calculateTotal()" required>
                        <option value="Basic Sneaker Wash|80">Basic Sneaker Wash - E80</option>
                        <option value="Basic Shoe Cleaning (Polish)|80">Basic Shoe Cleaning (Polish) - E80</option>
                        <option value="Arbiter (Respray)|120">Arbiter (Respray & Refinishing) - E120</option>
                        <option value="Timberland Wash & Care|150">Timberland Wash & Care - E150</option>
                        <option value="Carvella (Respray)|180">Carvella (Respray) - E180</option>
                        <option value="Clean & Polish Deep|200">Clean & Polish Deep - E200</option>
                        <option value="Repair & Restoration|400">Repair & Restoration - E400+</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity (Pairs)</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" onchange="calculateTotal()" onkeyup="calculateTotal()" required>
                </div>
            </div>

            <div class="calc-box">
                Calculated Total: E<span id="totalDisplay">80.00</span>
            </div>

            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group">
                    <label>Pickup Location Address</label>
                    <textarea name="location" rows="2" placeholder="Street, suburb or landmark" required></textarea>
                </div>
                <div class="form-group">
                    <label>Preferred Pickup Day</label>
                    <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Payment Mode</label>
                <select name="payment_mode" required>
                    <option value="Cash on Pickup">Cash on Pickup</option>
                    <option value="Mobile Money / EFT">Mobile Money / EFT</option>
                    <option value="Card on Delivery">Card on Delivery</option>
                </select>
            </div>

            <button type="submit" name="submit_order"><i class="fa-solid fa-paper-plane"></i> Submit Order</button>
        </form>
    </div>

    <!-- Active Orders & Receipts Table -->
    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-list-check"></i> My Wash Orders & Receipts</h3>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Service</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Pickup Date</th>
                    <th>Est. Delivery</th>
                    <th>Status</th>
                    <th>Digital Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if($orders->num_rows > 0): ?>
                    <?php while($row = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td><?= htmlspecialchars($row['service_type']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td>E<?= number_format($row['total_amount'], 2) ?></td>
                        <td><?= $row['pickup_date'] ?></td>
                        <td><?= $row['estimated_delivery'] ? $row['estimated_delivery'] : 'Pending Confirmation' ?></td>
                        <td><span class="status status-<?= explode(' ', $row['status'])[0] ?>"><?= $row['status'] ?></span></td>
                        <td>
                            <a href="print_receipt.php?id=<?= $row['id'] ?>" target="_blank" 
                               style="background:#1890ff; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; font-size:0.8rem; font-weight:bold;">
                                <i class="fa-solid fa-receipt"></i> View Receipt
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; color:#64748b;">No orders placed yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Communication & Feedback Section with Receptionist -->
    <div class="card" style="border-left: 4px solid #0a2540;">
        <h3 style="color:#0a2540; margin-bottom:10px;"><i class="fa-solid fa-comments"></i> Reception Desk Messages & Feedback</h3>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:20px;">View messages/updates sent by the receptionist and send your feedback directly to the reception desk.</p>

        <!-- Send Message Form -->
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Related Order # (Optional)</label>
                    <select name="order_id">
                        <option value="">-- General Message --</option>
                        <?php 
                        $ords_dropdown = $conn->query("SELECT id, service_type FROM orders WHERE customer_id=$user_id ORDER BY id DESC");
                        while($od = $ords_dropdown->fetch_assoc()): 
                        ?>
                            <option value="<?= $od['id'] ?>">Order #<?= $od['id'] ?> - <?= htmlspecialchars($od['service_type']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Message / Feedback</label>
                    <input type="text" name="customer_message" placeholder="Type instructions, questions or feedback for the receptionist..." required>
                </div>
            </div>
            <button type="submit" name="send_feedback" style="background:#0a2540;"><i class="fa-solid fa-paper-plane"></i> Send Feedback to Receptionist</button>
        </form>

        <!-- Receptionist Messages & Chat History Log -->
        <div style="margin-top: 25px;">
            <h4 style="color:#0a2540; margin-bottom:10px;">Message & Feedback Log</h4>
            <div class="chat-box">
                <?php if($comms && $comms->num_rows > 0): ?>
                    <?php while($c = $comms->fetch_assoc()): ?>
                        <?php $is_receptionist = ($c['sender_role'] == 'receptionist'); ?>
                        <div class="msg-bubble <?= $is_receptionist ? 'msg-receptionist' : 'msg-customer' ?>">
                            <div style="display:flex; justify-between; align-items:center; font-size:0.75rem; color:#64748b; margin-bottom:4px;">
                                <strong><?= $is_receptionist ? 'Reception Desk (' . htmlspecialchars($c['sender_name']) . ')' : 'You' ?></strong>
                                <span><?= $c['created_at'] ?> <?= $c['order_id'] ? '| Order #'.$c['order_id'] : '' ?></span>
                            </div>
                            <div style="font-size:0.92rem;"><?= htmlspecialchars($c['message']) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #64748b; font-size: 0.9rem; margin:0;">No messages or feedback logged yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function calculateTotal() {
        var serviceSelect = document.getElementById('serviceSelect').value;
        var price = parseFloat(serviceSelect.split('|')[1]);
        var qty = parseInt(document.getElementById('quantity').value) || 1;
        var total = price * qty;
        document.getElementById('totalDisplay').innerText = total.toFixed(2);
    }
</script>

</body>
</html>