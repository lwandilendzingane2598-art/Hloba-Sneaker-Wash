<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit;
}

$manager_id = $_SESSION['user_id'];
$message = "";

// 1. Handle Stock Restocking / Addition (Increases stock & logs expenditure)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restock_item'])) {
    $item_id = intval($_POST['item_id']);
    $add_qty = intval($_POST['add_quantity']);
    $cost = floatval($_POST['total_cost']);
    $store = $conn->real_escape_string($_POST['supplier_store']);

    if ($add_qty > 0) {
        $conn->query("UPDATE inventory SET stock_level = stock_level + $add_qty, supplier_store = '$store' WHERE id = $item_id");
        
        $item_res = $conn->query("SELECT item_name FROM inventory WHERE id = $item_id")->fetch_assoc();
        $exp_title = "Restocked " . $add_qty . "x " . $item_res['item_name'] . " from " . $store;
        $conn->query("INSERT INTO expenditures (expense_title, amount, category, recorded_by) VALUES ('$exp_title', $cost, 'Stock Purchase', $manager_id)");

        $message = "Stock restocked and expenditure logged!";
    }
}

// 2. Handle Communication / Feedback to Receptionist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_receptionist_msg'])) {
    $feedback = $conn->real_escape_string($_POST['manager_message']);
    if (!empty($feedback)) {
        $conn->query("INSERT INTO role_communications (sender_id, recipient_role, message) VALUES ($manager_id, 'receptionist', '$feedback')");
        $message = "Message sent to receptionist successfully!";
    }
}

// Financial Calculations
$rev = $conn->query("SELECT SUM(total_amount) as total_rev, COUNT(id) as total_orders FROM orders")->fetch_assoc();
$total_revenue = $rev['total_rev'] ?? 0;
$total_orders = $rev['total_orders'] ?? 0;

$exp = $conn->query("SELECT SUM(amount) as total_exp FROM expenditures")->fetch_assoc();
$total_expenditures = $exp['total_exp'] ?? 0;
$net_profit = $total_revenue - $total_expenditures;

$inventory = $conn->query("SELECT * FROM inventory");
$low_stock = [];
$chk = $conn->query("SELECT * FROM inventory WHERE stock_level <= 10");
while($ls = $chk->fetch_assoc()) { $low_stock[] = $ls['item_name'] . " (" . $ls['stock_level'] . ")"; }

$all_orders = $conn->query("SELECT o.*, u.full_name as driver_name FROM orders o LEFT JOIN users u ON o.driver_id = u.id ORDER BY o.id DESC");
$expenditures_list = $conn->query("SELECT e.*, u.full_name as manager_name FROM expenditures e JOIN users u ON e.recorded_by = u.id ORDER BY e.id DESC");

// Fetch 2-Way Communication between Manager & Receptionist
$m_reception_comms = $conn->query("SELECT rc.*, u.full_name as sender_name, u.role as sender_role 
                                   FROM role_communications rc 
                                   JOIN users u ON rc.sender_id = u.id 
                                   WHERE (rc.sender_id = $manager_id AND rc.recipient_role = 'receptionist') 
                                      OR (rc.recipient_role = 'manager') 
                                   ORDER BY rc.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard | Hloba Ntanga</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fb; margin: 0; color: #1e293b; }
        header { background: #0a2540; color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1250px; margin: 30px auto; padding: 0 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 4px solid #1890ff; }
        .stat-card.profit { border-left-color: #10b981; }
        .stat-card.expense { border-left-color: #d90429; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: #0a2540; margin-top: 5px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.88rem; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #0a2540; color: white; }
        select, input { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { background: #0a2540; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #d90429; }
        .chat-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-top: 15px; max-height: 300px; overflow-y: auto; }
        .msg-bubble { margin-bottom: 10px; padding: 10px; border-radius: 6px; }
        .msg-sender { background: #dcfce7; border-left: 4px solid #10b981; }
        .msg-receiver { background: #e0f2fe; border-left: 4px solid #1890ff; }
        .btn-print { background: #1890ff; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>

<header>
    <h2><i class="fa-solid fa-chart-line"></i> Manager Executive Portal</h2>
    <div>Manager: <?= htmlspecialchars($_SESSION['name']) ?> | <a href="logout.php" style="color:#d90429; font-weight:bold; text-decoration:none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</header>

<div class="container">
    <?php if(!empty($low_stock)): ?>
        <div style="background:#fee2e2; border-left:5px solid #ef4444; color:#991b1b; padding:15px; border-radius:6px; margin-bottom:25px; font-weight:600;">
            <i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alert (<=10): <?= implode(', ', $low_stock) ?> need restocking!
        </div>
    <?php endif; ?>

    <?php if($message): ?><div style="padding:12px; background:#dcfce7; color:#166534; border-radius:6px; margin-bottom:20px; font-weight:600;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <!-- Manager & Receptionist Communication & Feedback Hub -->
    <div class="card" style="border-left: 4px solid #0a2540;">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-comments"></i> Communication & Feedback with Reception Desk</h3>
        <p style="color:#64748b; font-size:0.9rem;">Send instructions to the receptionist and view all feedback and messages exchanged between both roles.</p>
        
        <form method="POST" style="margin-top:15px;">
            <div style="display:flex; gap:15px;">
                <input type="text" name="manager_message" placeholder="Type instruction, stock order update, or note for receptionist..." required>
                <button type="submit" name="send_receptionist_msg" style="background:#d90429; white-space:nowrap;"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
            </div>
        </form>

        <div class="chat-box">
            <h4 style="margin:0 0 10px 0; color:#0a2540;">2-Way Communication Log</h4>
            <?php if($m_reception_comms->num_rows > 0): ?>
                <?php while($c = $m_reception_comms->fetch_assoc()): ?>
                    <?php $is_me = ($c['sender_id'] == $manager_id); ?>
                    <div class="msg-bubble <?= $is_me ? 'msg-sender' : 'msg-receiver' ?>">
                        <div style="font-size:0.75rem; color:#64748b; margin-bottom:3px;">
                            <strong><?= $is_me ? 'You (Manager)' : htmlspecialchars($c['sender_name']) . ' (' . ucfirst($c['sender_role']) . ')' ?></strong> | <?= $c['created_at'] ?>
                        </div>
                        <div style="font-size:0.9rem;"><?= htmlspecialchars($c['message']) ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#64748b; margin:0;">No messages or feedback exchanged yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Analytics -->
    <div class="stats-grid">
        <div class="stat-card"><h4>Total Revenue</h4><div class="value">E<?= number_format($total_revenue, 2) ?></div></div>
        <div class="stat-card expense"><h4>Total Expenditures</h4><div class="value">E<?= number_format($total_expenditures, 2) ?></div></div>
        <div class="stat-card profit"><h4>Net Profit</h4><div class="value">E<?= number_format($net_profit, 2) ?></div></div>
        <div class="stat-card"><h4>Total Orders</h4><div class="value"><?= $total_orders ?></div></div>
    </div>

    <!-- Inventory Restocking -->
    <div class="card">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-boxes-stacked"></i> Company Inventory & Stores (Build It, Wash Hub, Ruchi)</h3>
        <table>
            <thead><tr><th>Item</th><th>Stock Level</th><th>Supplier Store</th><th>Restock & Log Expense</th></tr></thead>
            <tbody>
                <?php while($inv = $inventory->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($inv['item_name']) ?></strong></td>
                    <td><span style="color:<?= $inv['stock_level'] <= 10 ? '#dc2626' : '#166534' ?>; font-weight:bold;"><?= $inv['stock_level'] ?> Units</span></td>
                    <td><?= htmlspecialchars($inv['supplier_store']) ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:10px;">
                            <input type="hidden" name="item_id" value="<?= $inv['id'] ?>">
                            <input type="number" name="add_quantity" placeholder="Qty" min="1" style="width:80px;" required>
                            <input type="number" step="0.01" name="total_cost" placeholder="Cost (E)" min="0" style="width:90px;" required>
                            <select name="supplier_store" style="width:110px;">
                                <option value="Wash Hub">Wash Hub</option>
                                <option value="Ruchi">Ruchi</option>
                                <option value="Build It">Build It</option>
                            </select>
                            <button type="submit" name="restock_item" style="padding:6px 12px; font-size:0.8rem;">Restock</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Total Receipts & Order Archives -->
    <div class="card" style="border-left: 4px solid #10b981;">
        <h3 style="color:#0a2540;"><i class="fa-solid fa-receipt"></i> Total Receipts & Order Archives</h3>
        <p style="color:#64748b; font-size:0.9rem;">View all transaction records and print or reprint official thermal receipts for any company order.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Receipt / Order #</th>
                    <th>Customer Name</th>
                    <th>Service Type</th>
                    <th>Total Amount</th>
                    <th>Payment Mode</th>
                    <th>Date Generated</th>
                    <th>Print Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Re-query orders for receipt view
                $receipt_orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
                while($rcpt = $receipt_orders->fetch_assoc()): 
                ?>
                <tr>
                    <td><strong>#HLO-<?= sprintf("%04d", $rcpt['id']) ?></strong></td>
                    <td><?= htmlspecialchars($rcpt['customer_name']) ?><br><small><?= htmlspecialchars($rcpt['customer_phone']) ?></small></td>
                    <td><?= htmlspecialchars($rcpt['service_type']) ?> (<?= $rcpt['quantity'] ?>x)</td>
                    <td><strong>E<?= number_format($rcpt['total_amount'], 2) ?></strong></td>
                    <td><?= htmlspecialchars($rcpt['payment_mode']) ?></td>
                    <td><?= $rcpt['created_at'] ?></td>
                    <td>
                        <a href="print_receipt.php?id=<?= $rcpt['id'] ?>" target="_blank" class="btn-print">
                            <i class="fa-solid fa-print"></i> View & Print
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>