<?php
include 'config.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $res = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];
            
            header("Location: dashboard_" . $user['role'] . ".php");
            exit;
        } else { $message = "Incorrect password!"; }
    } else { $message = "No account found with this email!"; }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $conn->real_escape_string($_POST['role']);

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "Email is already registered!";
    } else {
        $conn->query("INSERT INTO users (full_name, email, phone, password, role) VALUES ('$name', '$email', '$phone', '$password', '$role')");
        $message = "Registration successful! You can now log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Access | Hloba Ntanga</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0a2540; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
        .auth-card { background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        h2 { color: #0a2540; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #d90429; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { background: #b00020; }
        .msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .toggle { text-align: center; margin-top: 15px; cursor: pointer; color: #1890ff; font-weight: 600; }
        .back-home { text-align: center; margin-top: 10px; display: block; color: #64748b; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="auth-card">
    <h2>Hloba Ntanga Portal</h2>
    <?php if($message): ?> <div class="msg"><?= htmlspecialchars($message) ?></div> <?php endif; ?>

    <form id="loginForm" method="POST">
        <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit" name="login">Login to Account</button>
        <div class="toggle" onclick="toggleAuth()">Need an account? Register here</div>
        <a href="index.php" class="back-home">&larr; Back to Main Homepage</a>
    </form>

    <form id="registerForm" method="POST" style="display:none;">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
        <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Phone Number</label><input type="text" name="phone" required></div>
        <div class="form-group">
            <label>Account Role</label>
            <select name="role">
                <option value="customer">Customer</option>
                <option value="receptionist">Receptionist</option>
                <option value="driver">Driver</option>
                <option value="washer">Washer</option>
                <option value="manager">Manager</option>
            </select>
        </div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit" name="register">Create Account</button>
        <div class="toggle" onclick="toggleAuth()">Already registered? Login</div>
        <a href="index.php" class="back-home">&larr; Back to Main Homepage</a>
    </form>
</div>

<script>
    function toggleAuth() {
        var l = document.getElementById('loginForm');
        var r = document.getElementById('registerForm');
        l.style.display = l.style.display === 'none' ? 'block' : 'none';
        r.style.display = r.style.display === 'none' ? 'block' : 'none';
    }
</script>
</body>
</html>