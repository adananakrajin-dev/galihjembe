<?php
include 'database.php';
session_start();

if (isset($_SESSION["sudah_login"])) {
    header("Location: dashboard.php"); 
    exit;
}

$error = "";

if(isset($_POST['submit_register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Cek apakah password dan konfirmasi password cocok
    if($password !== $confirm_password) {
        $error = "Password dan Konfirmasi Password tidak cocok!";
    } else {
        // Cek apakah username sudah dipakai
        $stmt_check = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if($stmt_check->num_rows > 0) {
            $error = "Username sudah terdaftar! Gunakan nama lain.";
        } else {
            // Masukkan data user baru
            $stmt_insert = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $username, $password);
            
            if($stmt_insert->execute()) {
                // Jika berhasil, langsung redirect ke login tanpa alert javascript
                header("Location: login.php");
                exit;
            } else {
                $error = "Gagal mendaftar, coba lagi nanti.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SESSIONS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ── RESET & BASE TEMPLATE ── */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body { 
            background-color: #09090b; 
            background-image: 
                radial-gradient(circle at 10% 30%, rgba(99, 102, 241, 0.15), transparent 40%),
                radial-gradient(circle at 90% 70%, rgba(236, 72, 153, 0.15), transparent 40%);
            background-attachment: fixed;
            color: #fff; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden; 
        }

        /* ── NAVBAR AUTH ── */
        .auth-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 6%;
            background: transparent;
        }

        .auth-nav .logo {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #ffffff;
            text-decoration: none;
        }

        .auth-nav .nav-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-left: 24px;
            transition: color 0.3s ease;
        }

        .auth-nav .nav-links a:hover {
            color: #fff;
        }

        /* ── WRAPPER & CARD LOGIN/REGISTER ── */
        .login-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: fadeUp 0.8s ease both;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 20px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            padding: 4px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .login-header h3 {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
        }

        /* ── ERROR MESSAGE ── */
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── FORM ELEMENTS ── */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box i {
            position: absolute;
            left: 16px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .input-box input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .input-box input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .input-box input:focus {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.1);
        }

        .input-box input:focus + i {
            color: #6366f1;
        }

        /* ── BUTTON ── */
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(236, 72, 153, 0.3);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* ── FOOTER CARD ── */
        .login-footer {
            text-align: center;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 8px;
        }

        .login-footer a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #ec4899;
            text-decoration: underline;
        }

        /* ── GLOBAL SITE FOOTER ── */
        .footer-simple {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.25);
            font-size: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
        }

        /* ── ANIMATION ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 24px;
            }
            .auth-nav {
                padding: 20px 5%;
            }
        }
    </style>
</head>
<body class="login-body">

<header class="auth-nav">
    <a href="index.html" class="logo">SESSIONS</a>
    <div class="nav-links">
    </div>
</header>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <img src="log1.jpeg" alt="Logo SESSIONS" class="brand-logo">
            <h3>Buat Akun Baru</h3>
            <p>Mulai kelola bisnismu bersama kami</p>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="login-form">
            <div class="input-group">
                <label>Username</label>
                <div class="input-box">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="Pilih username" name="username" required/>
                </div>
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <div class="input-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" placeholder="Masukkan Password" name="password" required/>
                </div>
            </div>

            <div class="input-group">
                <label>Konfirmasi Password</label>
                <div class="input-box">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" placeholder="Ketik ulang Password" name="confirm_password" required/>
                </div>
            </div>
            
            <button type="submit" name="submit_register" class="btn-primary">Daftar Sekarang</button>
            
            <div class="login-footer">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </div>
        </form>
    </div>
</div>

<footer class="footer-simple">
    <p>&copy; 2026 SESSIONS. All Rights Reserved by ahdankerenabiezz.</p>
</footer>

</body>
</html>