<?php
require_once 'classes/AuthManager.php';
$auth = new AuthManager();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $auth->register($_POST['username'], $_POST['password']);
    if ($res['success']) {
        $success = $res['message'];
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Registrierung - ESA Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #000;
            color: #fff;
            font-family: sans-serif;
        }

        .login-box {
            background: #111;
            padding: 40px;
            border: 1px solid #333;
            border-radius: 8px;
            width: 300px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background: #222;
            border: 1px solid #444;
            color: #fff;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2 style="text-align: center;">Neuer Commander</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
            <p style="text-align: center;"><a href="login.php" style="color: #fff;">Zum Login</a></p>
        <?php else: ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Benutzername" required>
                <input type="password" name="password" placeholder="Passwort" required>
                <button type="submit">Registrieren</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                <a href="login.php" style="color: #888;">Zurück zum Login</a>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>