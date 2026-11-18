<?php
include 'config.php';
if (!isset($_GET['id'])) { die("Invalid Receipt"); }
$id = intval($_GET['id']);
$order = $conn->query("SELECT * FROM orders WHERE id=$id")->fetch_assoc();
if (!$order) { die("Order not found"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $order['id'] ?> - Hloba Ntanga</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #eee; padding: 20px; }
        .receipt { background: white; width: 320px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .text-center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 10px 0; }
        .flex { display: flex; justify-content: space-between; }
        .btn-print { display: block; width: 320px; margin: 10px auto; padding: 10px; background: #0a2540; color: white; text-align: center; border: none; cursor: pointer; font-weight: bold; }
        @media print { .btn-print { display: none; } body { background: white; } }
    </style>
</head>
<body>

<button class="btn-print" onclick="window.print()">Print Receipt</button>

<div class="receipt">
    <div class="text-center">
        <h2>HLOBA NTANGA</h2>
        <p>SNEAKER WASH & CARE</p>
        <p>Checkers, Mbabane</p>
        <p>Tel: +268 793 69 657</p>
    </div>
    <div class="line"></div>
    <p><strong>Receipt #:</strong> HLO-<?= sprintf("%04d", $order['id']) ?></p>
    <p><strong>Date:</strong> <?= $order['created_at'] ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
    <div class="line"></div>
    <div class="flex"><span><?= htmlspecialchars($order['service_type']) ?></span><span><?= $order['quantity'] ?>x E<?= number_format($order['price_per_unit'], 2) ?></span></div>
    <div class="line"></div>
    <div class="flex" style="font-weight:bold;"><span>TOTAL:</span><span>E<?= number_format($order['total_amount'], 2) ?></span></div>
    <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_mode']) ?></p>
    <div class="line"></div>
    <div class="text-center">
        <p>We've Got You Covered,</p>
        <p><strong>MANJE CLEAN!!</strong></p>
    </div>
</div>

</body>
</html>