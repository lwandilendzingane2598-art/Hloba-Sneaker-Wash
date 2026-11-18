<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: login.php");
    exit;
}

$receptionist_id = $_SESSION['user_id'];
$message = "";

// 1. Handle Phone / Walk-in Order Entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['phone_order'])) {
    $c_name = $conn->real_escape_string($_POST['customer_name']);
    $c_phone = $conn->real_escape_string($_POST['customer_phone']);
    $service_data = explode('|', $_POST['service_type']);
    $service = $conn->real_escape_string($service_data[0]);
    $price = floatval($service_data[1]);
    $qty = intval($_POST['quantity']);
    $total = $price * $qty;
    $address = $conn->real_escape_string($_POST['pickup_address']);
    $pickup = $_POST['pickup_date'];
    $pay_mode = $_POST['payment_mode'];

    $conn->query("INSERT INTO orders (customer_name, customer_phone, service_type, price_per_unit, quantity, total_amount, pickup_address, pickup_date, payment_mode, order_type)
                  VALUES ('$c_name', '$c_phone', '$service', $price, $qty, $total, '$address', '$pickup', '$pay_mode', 'phone_walkin')");
    $message = "Phone/Walk-in order created successfully!";
}

// 2. Handle Order Updates & Feedback Dispatch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = intval($_POST['order_id']);
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : "NULL";
    $delivery_date = $conn->real_escape_string($_POST['estimated_delivery']);
    $notes = isset($_POST['receptionist_notes']) ? $conn->real_escape_string($_POST['receptionist_notes']) : '';
    $target_role = $conn->real_escape_string($_POST['target_role']);
    $feedback_msg = $conn->real_escape_string($_POST['feedback_message']);

    $conn->query("UPDATE orders SET driver_id=$driver_id, estimated_delivery='$delivery_date', receptionist_notes='$notes' WHERE id=$order_id");
    
    if (!empty($feedback_msg)) {
        $conn->query("INSERT INTO role_communications (order_id, sender_id, recipient_role, message) 
                      VALUES ($order_id, $receptionist_id, '$target_role', '$feedback_msg')");
    }
    $message = "Order #$order_id updated and message dispatched to $target_role successfully!";
}

// 3. Handle Direct General Message / Feedback Dispatch to Any Role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_general_message'])) {
    $target_role = $conn->real_escape_string($_POST['target_role']);
    $feedback_msg = $conn->real_escape_string($_POST['general_message']);

    if (!empty($feedback_msg)) {
        $conn->query("INSERT INTO role_communications (sender_id, recipient_role, message) 
                      VALUES ($receptionist_id, '$target_role', '$feedback_msg')");
        $message = "Direct message sent to " . ucfirst($target_role) . " successfully!";
    }
}

// 4. Handle Assigning Washing Material Pickup Task to Driver
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_material_pickup'])) {
    $desc = $conn->real_escape_string($_POST['item_description']);
    $supplier = $conn->real_escape_string($_POST['supplier_store']);
    $address = $conn->real_escape_string($_POST['pickup_address']);
    $assigned_driver = intval($_POST['driver_id']);

    $conn->query("INSERT INTO material_tasks (item_description, supplier_store, pickup_address, driver_id, assigned_by) 
                  VALUES ('$desc', '$supplier', '$address', $assigned_driver, $receptionist_id)");
    $message = "Material pickup task assigned to driver!";
}

// Fetch drivers list
$drivers = $conn->query("SELECT id, full_name FROM users WHERE role='driver'");
$driver_list = [];
while($d = $drivers->fetch_assoc()) { $driver_list[] = $d; }

// Fetch material tasks list
$material_tasks_list = $conn->query("SELECT mt.*, u.full_name as driver_name FROM material_tasks mt JOIN users u ON mt.driver_id = u.id ORDER BY mt.id DESC");

// Fetch incoming messages and feedback written by ALL other roles (Customer, Driver, Washer, Manager)
$incoming_messages = $conn->query("SELECT rc.*, u.full_name as sender_name, u.role as sender_role, o.service_type 
                                   FROM role_communications rc 
                                   JOIN users u ON rc.sender_id = u.id 
                                   LEFT JOIN orders o ON rc.order_id = o.id 
                                   WHERE rc.recipient_role = 'receptionist' OR rc.sender_id = $receptionist_id
                                   ORDER BY rc.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receptionist Dashboard | Hloba Ntanga</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fb; margin: 0; color: #1e293b; }
        header { background: #0a2540; color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1250px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { background: #0a2540; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #d90429; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.88rem; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #0a2540; color: white; }
        .badge-role { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; background: #e0f2fe; color: #0369a1; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-In { background: #e0f2fe; color: #075985; }
        .status-Ready { background: #dcfce7; color: #166534; }
        .status-Delivered { background: #bbf7d0; color: #14532d; }
    </style>
</head>
<body>

<header>
    <h2><i class="fa-solid fa-headset"></i> Reception Desk Portal</h2>
    <div>User: <?= htmlspecialchars($_SESSION['name']) ?> | <a href="logout.php" style="color:#d90429; font-weight:bold; text-decoration:none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</header>

<div class="container">
    <?php if($message): ?><div style="padding:12px; background:#dcfce7; color:#166534; border-radius:6px; margin-bottom:20px; font-weight:600;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <!-- Manager & Cross-Role Communication Center -->
    <div class="card" style="border-left: 4px solid #1890ff;">
        <h3 style="color:#0a2540; margin-bottom:10px;"><i class="fa-solid fa-comments"></i> Communication & Feedback Hub (All Roles)</h3>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:15px;">Send direct messages and view incoming feedback/notes from Manager, Customers, Drivers, and Washers.</p>
        
        <!-- Send Direct Message to Any Role (Manager, Driver, Washer, Customer) -->
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #0a2540;">Send Direct Feedback or Instruction</h4>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 10px;">
                    <select name="target_role" required>
                        <option value="manager">Manager</option>
                        <option value="customer">Customer</option>
                        <option value="driver">Driver</option>
                        <option value="washer">Washer</option>
                    </select>
                    <input type="text" name="general_message" placeholder="Type message, instruction or feedback..." required>
                    <button type="submit" name="send_general_message" style="background:#0a2540;"><i class="fa-solid fa-paper-plane"></i> Send</button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sender Role & Name</th>
                    <th>Related Order #</th>
                    <th>Message / Feedback Content</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if($incoming_messages->num_rows > 0): ?>
                    <?php while($msg = $incoming_messages->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="badge-role"><?= htmlspecialchars($msg['sender_role']) ?></span><br>
                            <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                        </td>
                        <td><?= $msg['order_id'] ? '#'.$msg['order_id'].' ('.$msg['service_type'].')' : 'General' ?></td>
                        <td><?= htmlspecialchars($msg['message']) ?></td>
                        <td><?= $msg['created_at'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#64748b;">No messages or feedback logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Live Driver Updates -->
    <div class="card" style="border-left: 4px solid #10b981;">
        <h3 style="color:#0a2540; margin-bottom:10px;"><i class="fa-solid fa-truck-fast"></i> Live Driver Updates & Logistics Status</h3>
        
        <h4 style="color:#0a2540; margin-top:15px;">1. Driver Order Progress Updates</h4>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer Name</th>
                    <th>Assigned Driver</th>
                    <th>Pickup Address</th>
                    <th>Live Status Updated by Driver</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $orders_driver_view = $conn->query("SELECT o.*, u.full_name as driver_name FROM orders o LEFT JOIN users u ON o.driver_id = u.id ORDER BY o.id DESC");
                while($ord_row = $orders_driver_view->fetch_assoc()): 
                ?>
                <tr>
                    <td><strong>#<?= $ord_row['id'] ?></strong></td>
                    <td><?= htmlspecialchars($ord_row['customer_name']) ?><br><small><?= htmlspecialchars($ord_row['customer_phone']) ?></small></td>
                    <td><strong style="color:#1890ff;"><?= $ord_row['driver_name'] ? htmlspecialchars($ord_row['driver_name']) : 'Unassigned' ?></strong></td>
                    <td><?= htmlspecialchars($ord_row['pickup_address']) ?></td>
                    <td><span class="status-badge status-<?= explode(' ', $ord_row['status'])[0] ?>"><?= htmlspecialchars($ord_row['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Washer Updates -->
    <div class="card" style="border-left: 4px solid #f59e0b;">
        <h3 style="color:#0a2540; margin-bottom:10px;"><i class="fa-solid fa-soap"></i> Washer Laboratory Updates</h3>
        <table>
            <thead><tr><th>Order #</th><th>Customer Name</th><th>Service Type</th><th>Current Wash Status</th></tr></thead>
            <tbody>
                <?php 
                $washer_orders = $conn->query("SELECT * FROM orders WHERE status IN ('In Wash', 'Ready for Delivery') ORDER BY id DESC");
                if($washer_orders->num_rows > 0):
                    while($wo = $washer_orders->fetch_assoc()):
                ?>
                <tr>
                    <td><strong>#<?= $wo['id'] ?></strong></td>
                    <td><?= htmlspecialchars($wo['customer_name']) ?></td>
                    <td><?= htmlspecialchars($wo['service_type']) ?></td>
                    <td><span class="status-badge status-<?= explode(' ', $wo['status'])[0] ?>"><?= htmlspecialchars($wo['status']) ?></span></td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#64748b;">No active washer updates at this moment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phone Order Form -->
    <div class="card">
        <h3 style="color:#0a2540; margin-bottom:15px;"><i class="fa-solid fa-phone-volume"></i> Record Phone / Walk-in Order</h3>
        <form method="POST">
            <div class="form-grid">
                <div><label>Customer Name</label><input type="text" name="customer_name" required></div>
                <div><label>Customer Phone</label><input type="text" name="customer_phone" required></div>
                <div>
                    <label>Service Type</label>
                    <select name="service_type" required>
                        <option value="Basic Sneaker Wash|80">Basic Sneaker Wash - E80</option>
                        <option value="Basic Shoe Cleaning (Polish)|80">Basic Shoe Cleaning (Polish) - E80</option>
                        <option value="Arbiter (Respray)|120">Arbiter (Respray) - E120</option>
                        <option value="Timberland Wash & Care|150">Timberland Wash & Care - E150</option>
                        <option value="Carvella (Respray)|180">Carvella (Respray) - E180</option>
                    </select>
                </div>
                <div><label>Quantity</label><input type="number" name="quantity" value="1" min="1" required></div>
                <div><label>Pickup Date</label><input type="date" name="pickup_date" value="<?= date('Y-m-d') ?>" required></div>
                <div>
                    <label>Payment Mode</label>
                    <select name="payment_mode"><option value="Cash on Pickup">Cash on Pickup</option><option value="Mobile Money / EFT">Mobile Money / EFT</option></select>
                </div>
            </div>
            <div style="margin-top:15px;"><label>Physical Address</label><input type="text" name="pickup_address" required></div>
            <button type="submit" name="phone_order" style="margin-top:15px;">Save Phone Order</button>
        </form>
    </div>

    <!-- Assign Material Pickup -->
    <div class="card">
        <h3 style="color:#0a2540; margin-bottom:15px;"><i class="fa-solid fa-truck-ramp-box"></i> Assign Material Pickup to Driver</h3>
        <form method="POST">
            <div class="form-grid">
                <div><label>Materials Description</label><input type="text" name="item_description" placeholder="e.g. Soft Brushes, Solution" required></div>
                <div><label>Supplier Store</label><input type="text" name="supplier_store" placeholder="Wash Hub, Ruchi, Build It" required></div>
                <div>
                    <label>Select Driver</label>
                    <select name="driver_id" required>
                        <option value="">-- Choose Driver --</option>
                        <?php foreach($driver_list as $dr): ?><option value="<?= $dr['id'] ?>"><?= htmlspecialchars($dr['full_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top:15px;"><label>Pickup Address</label><input type="text" name="pickup_address" required></div>
            <button type="submit" name="assign_material_pickup" style="margin-top:15px; background:#1890ff;">Assign Pickup Task</button>
        </form>
    </div>

    <!-- Active Orders & Feedback Dispatcher -->
    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-list-check"></i> Orders & Feedback Dispatcher</h3>
        <table>
            <thead>
                <tr>
                    <th>Order # & Customer</th>
                    <th>Service</th>
                    <th>Assign Driver</th>
                    <th>Est. Delivery</th>
                    <th>Feedback / Instruction to Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $orders_dispatch = $conn->query("SELECT o.*, u.full_name as driver_name FROM orders o LEFT JOIN users u ON o.driver_id = u.id ORDER BY o.id DESC");
                while($row = $orders_dispatch->fetch_assoc()): 
                ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                        <td><strong>#<?= $row['id'] ?></strong><br><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['service_type']) ?> (<?= $row['quantity'] ?>x)</td>
                        <td>
                            <select name="driver_id" style="font-size:0.8rem;">
                                <option value="">-- Driver --</option>
                                <?php foreach($driver_list as $dr): ?><option value="<?= $dr['id'] ?>" <?= $row['driver_id'] == $dr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dr['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="date" name="estimated_delivery" value="<?= htmlspecialchars($row['estimated_delivery'] ?? '') ?>"></td>
                        <td>
                            <select name="target_role" style="font-size:0.8rem; margin-bottom:4px;">
                                <option value="manager">Manager</option>
                                <option value="customer">Customer</option>
                                <option value="driver">Driver</option>
                                <option value="washer">Washer</option>
                            </select>
                            <input type="text" name="feedback_message" value="<?= htmlspecialchars($row['receptionist_notes'] ?? '') ?>" placeholder="Type feedback..." style="font-size:0.8rem;">
                        </td>
                        <td>
                            <button type="submit" name="update_order" style="padding:6px 10px; font-size:0.8rem; margin-bottom:4px;">Dispatch</button><br>
                            <a href="print_receipt.php?id=<?= $row['id'] ?>" target="_blank" style="background:#1890ff; color:white; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:0.8rem;">Receipt</a>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>