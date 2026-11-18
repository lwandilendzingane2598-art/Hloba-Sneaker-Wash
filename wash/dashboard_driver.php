<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE orders SET status='$status' WHERE id=$order_id AND driver_id=$driver_id");
    $message = "Order status updated!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_material_status'])) {
    $task_id = intval($_POST['task_id']);
    $m_status = $conn->real_escape_string($_POST['material_status']);
    $conn->query("UPDATE material_tasks SET status='$m_status' WHERE id=$task_id AND driver_id=$driver_id");
    $message = "Material task status updated!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_receptionist_feedback'])) {
    $order_id = !empty($_POST['order_id']) ? intval($_POST['order_id']) : "NULL";
    $feedback = $conn->real_escape_string($_POST['driver_message']);
    if (!empty($feedback)) {
        $conn->query("INSERT INTO role_communications (order_id, sender_id, recipient_role, message) VALUES ($order_id, $driver_id, 'receptionist', '$feedback')");
        $message = "Feedback sent to receptionist!";
    }
}

$assigned_orders = $conn->query("SELECT * FROM orders WHERE driver_id=$driver_id ORDER BY id DESC");
$material_tasks = $conn->query("SELECT mt.* FROM material_tasks mt WHERE mt.driver_id=$driver_id ORDER BY mt.id DESC");
$reception_comms = $conn->query("SELECT rc.*, u.full_name as sender_name FROM role_communications rc JOIN users u ON rc.sender_id = u.id WHERE rc.sender_id = $driver_id OR rc.recipient_role = 'driver' ORDER BY rc.id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard | Hloba Ntanga</title>
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
    <h2><i class="fa-solid fa-truck-fast"></i> Driver Portal</h2>
    <div>Driver: <?= htmlspecialchars($_SESSION['name']) ?> | <a href="logout.php" style="color:#d90429; font-weight:bold; text-decoration:none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</header>

<div class="container">
    <?php if($message): ?><div style="padding:12px; background:#dcfce7; color:#166534; border-radius:6px; margin-bottom:20px; font-weight:600;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-box-archive"></i> Assigned Shoe Pickups & Deliveries</h3>
        <table>
            <thead><tr><th>Order #</th><th>Customer</th><th>Address</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($ord = $assigned_orders->fetch_assoc()): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                        <td>#<?= $ord['id'] ?></td>
                        <td><?= htmlspecialchars($ord['customer_name']) ?><br><small><?= htmlspecialchars($ord['customer_phone']) ?></small></td>
                        <td><?= htmlspecialchars($ord['pickup_address']) ?></td>
                        <td><?= htmlspecialchars($ord['status']) ?></td>
                        <td>
                            <select name="status" style="margin-bottom:5px;">
                                <option value="Pending Pickup" <?= $ord['status'] == 'Pending Pickup' ? 'selected' : '' ?>>Pending Pickup</option>
                                <option value="In Wash" <?= $ord['status'] == 'In Wash' ? 'selected' : '' ?>>In Wash</option>
                                <option value="Ready for Delivery" <?= $ord['status'] == 'Ready for Delivery' ? 'selected' : '' ?>>Ready for Delivery</option>
                                <option value="Delivered" <?= $ord['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                            </select>
                            <button type="submit" name="update_order_status" style="font-size:0.8rem; width:100%;">Update</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-spray-can-sparkles"></i> Assigned Washing Material Collections</h3>
        <table>
            <thead><tr><th>Task #</th><th>Items</th><th>Store</th><th>Address</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($mat = $material_tasks->fetch_assoc()): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="task_id" value="<?= $mat['id'] ?>">
                        <td>#MT-<?= $mat['id'] ?></td>
                        <td><?= htmlspecialchars($mat['item_description']) ?></td>
                        <td><?= htmlspecialchars($mat['supplier_store']) ?></td>
                        <td><?= htmlspecialchars($mat['pickup_address']) ?></td>
                        <td><?= htmlspecialchars($mat['status']) ?></td>
                        <td>
                            <select name="material_status" style="margin-bottom:5px;">
                                <option value="Assigned" <?= $mat['status'] == 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="In Transit" <?= $mat['status'] == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                                <option value="Collected" <?= $mat['status'] == 'Collected' ? 'selected' : '' ?>>Collected</option>
                            </select>
                            <button type="submit" name="update_material_status" style="font-size:0.8rem; width:100%;">Update</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-comments"></i> Feedback to Receptionist</h3>
        <form method="POST">
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:15px;">
                <input type="text" name="driver_message" placeholder="Message to receptionist..." required>
                <button type="submit" name="send_receptionist_feedback" style="background:#d90429;">Send Message</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>