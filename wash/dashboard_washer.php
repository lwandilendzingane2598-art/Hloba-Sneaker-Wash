<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'washer') {
    header("Location: login.php");
    exit;
}

$washer_id = $_SESSION['user_id'];
$message = "";

// 1. Handle Updating Shoe Wash Progress (e.g. In Wash -> Ready for Delivery)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_wash_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $conn->query("UPDATE orders SET status='$status' WHERE id=$order_id");
    
    // Automatically notify receptionist of wash progress update
    $wash_msg = "Washer updated Order #$order_id status to: $status";
    $conn->query("INSERT INTO role_communications (order_id, sender_id, recipient_role, message) 
                  VALUES ($order_id, $washer_id, 'receptionist', '$wash_msg')");
                  
    $message = "Order #$order_id status updated and reported to receptionist.";
}

// 2. Handle Material Usage / Deduction (Washer uses materials -> Subtracted from Inventory)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['use_material'])) {
    $item_id = intval($_POST['item_id']);
    $qty_used = intval($_POST['quantity_used']);

    if ($qty_used > 0) {
        $stock_check = $conn->query("SELECT stock_level, item_name FROM inventory WHERE id = $item_id")->fetch_assoc();
        
        if ($stock_check && $stock_check['stock_level'] >= $qty_used) {
            // Subtract stock
            $conn->query("UPDATE inventory SET stock_level = stock_level - $qty_used WHERE id = $item_id");
            
            // Notify receptionist about material usage
            $mat_msg = "Washer used " . $qty_used . "x " . $stock_check['item_name'] . ". Stock deducted.";
            $conn->query("INSERT INTO role_communications (sender_id, recipient_role, message) 
                          VALUES ($washer_id, 'receptionist', '$mat_msg')");

            $message = "Successfully recorded usage of " . $qty_used . "x " . $stock_check['item_name'] . ". Inventory updated!";
        } else {
            $message = "Error: Not enough stock available for " . ($stock_check['item_name'] ?? 'this item') . "!";
        }
    }
}

// 3. Handle Equipment Shortage Report
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_shortage'])) {
    $item_name = $conn->real_escape_string($_POST['shortage_item']);
    $note = $conn->real_escape_string($_POST['shortage_note']);
    $report_msg = "SHORTAGE ALERT: " . $item_name . " is running low. Note: " . $note;

    $conn->query("INSERT INTO role_communications (sender_id, recipient_role, message) VALUES ($washer_id, 'manager', '$report_msg')");
    $conn->query("INSERT INTO role_communications (sender_id, recipient_role, message) VALUES ($washer_id, 'receptionist', '$report_msg')");
    
    $message = "Shortage report sent successfully!";
}

$wash_orders = $conn->query("SELECT * FROM orders WHERE status IN ('Pending Pickup', 'In Wash', 'Ready for Delivery') ORDER BY id DESC");
$inventory = $conn->query("SELECT * FROM inventory");
$reception_comms = $conn->query("SELECT rc.*, u.full_name as sender_name FROM role_communications rc JOIN users u ON rc.sender_id = u.id WHERE rc.sender_id = $washer_id OR rc.recipient_role = 'washer' ORDER BY rc.id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Washer Dashboard | Hloba Ntanga</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fb; margin: 0; color: #1e293b; }
        header { background: #0a2540; color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9rem; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #0a2540; color: white; }
        select, input { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { background: #0a2540; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #d90429; }
    </style>
</head>
<body>

<header>
    <h2><i class="fa-solid fa-soap"></i> Washer Portal</h2>
    <div>Washer: <?= htmlspecialchars($_SESSION['name']) ?> | <a href="logout.php" style="color:#d90429; font-weight:bold; text-decoration:none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</header>

<div class="container">
    <?php if($message): ?><div style="padding:12px; background:#dcfce7; color:#166534; border-radius:6px; margin-bottom:20px; font-weight:600;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-shoe-prints"></i> Sneaker Washing Queue</h3>
        <table>
            <thead><tr><th>Order #</th><th>Customer</th><th>Service</th><th>Qty</th><th>Status</th><th>Update Wash Progress</th></tr></thead>
            <tbody>
                <?php while($ord = $wash_orders->fetch_assoc()): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                        <td>#<?= $ord['id'] ?></td>
                        <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                        <td><?= htmlspecialchars($ord['service_type']) ?></td>
                        <td><?= $ord['quantity'] ?></td>
                        <td><?= htmlspecialchars($ord['status']) ?></td>
                        <td>
                            <select name="status" style="margin-bottom:5px;">
                                <option value="In Wash" <?= $ord['status'] == 'In Wash' ? 'selected' : '' ?>>In Wash (Active)</option>
                                <option value="Ready for Delivery" <?= $ord['status'] == 'Ready for Delivery' ? 'selected' : '' ?>>Ready for Delivery</option>
                            </select>
                            <button type="submit" name="update_wash_status" style="font-size:0.8rem; width:100%;">Update & Report</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-bottle-droplet"></i> Log Material Usage (Auto-Subtraction)</h3>
        <table>
            <thead><tr><th>Item</th><th>Stock</th><th>Supplier</th><th>Use Qty</th></tr></thead>
            <tbody>
                <?php while($inv = $inventory->fetch_assoc()): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?= $inv['id'] ?>">
                        <td><strong><?= htmlspecialchars($inv['item_name']) ?></strong></td>
                        <td><?= $inv['stock_level'] ?> Units</td>
                        <td><?= htmlspecialchars($inv['supplier_store']) ?></td>
                        <td>
                            <div style="display:flex; gap:10px;">
                                <input type="number" name="quantity_used" placeholder="Qty" min="1" max="<?= $inv['stock_level'] ?>" style="width:100px;" required>
                                <button type="submit" name="use_material" style="background:#1890ff; padding:6px 12px; font-size:0.8rem;">Subtract</button>
                            </div>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="border-left: 4px solid #d90429;">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-triangle-exclamation"></i> Report Equipment Shortage</h3>
        <form method="POST" style="margin-top:15px;">
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:15px; margin-bottom:15px;">
                <select name="shortage_item" required>
                    <option value="Soft Cleaning Brush">Soft Cleaning Brush</option>
                    <option value="Sneaker Cleaning Solution">Sneaker Cleaning Solution</option>
                    <option value="Microfiber Cloth">Microfiber Cloth</option>
                    <option value="Shoe Deodorizer">Shoe Deodorizer</option>
                </select>
                <input type="text" name="shortage_note" placeholder="Shortage details..." required>
            </div>
            <button type="submit" name="report_shortage" style="background:#d90429;">Send Shortage Report</button>
        </form>
    </div>
</div>

</body>
</html>