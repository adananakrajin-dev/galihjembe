<?php
session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keluar | SESSIONS</title>
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

        /* ── WRAPPER & CARD LOGOUT ── */
        .logout-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: fadeUp 0.8s ease both;
        }

        .logout-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 50px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        /* ── USER AVATAR ── */
        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            margin: 0 auto 24px;
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.2);
            text-transform: uppercase;
        }

        .welcome-msg {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 8px;
        }

        .user-name {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* ── BUTTONS ── */
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Tombol Tetap di Sini (Primary) */
        .btn-stay {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .btn-stay:hover {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(236, 72, 153, 0.3);
            transform: translateY(-2px);
        }

        /* Tombol Keluar (Danger) */
        .btn-exit {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .btn-exit:hover {
            background: rgba(239, 68, 68, 0.9);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
            transform: translateY(-2px);
        }

        /* ── GLOBAL SITE FOOTER ── */
        .footer-simple {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.25);
            font-size: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            width: 100%;
        }

        /* ── ANIMATION ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .logout-card {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body>

    <div class="logout-wrapper">
        <div class="logout-card">
            <div class="user-avatar">
                <?= substr($_SESSION['username'] ?? 'U', 0, 1) ?>
            </div>
            
            <p class="welcome-msg">Sudah selesai mengeksplor?</p>
            <h2 class="user-name"><?= $_SESSION['username'] ?? 'Traveler' ?></h2>

            <div class="button-group">
                <a href="dashboard.php" class="btn btn-stay">
                    <i class="fas fa-home"></i> Tetap di Sini
                </a>

                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-exit">
                        <i class="fas fa-sign-out-alt"></i> Keluar Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer-simple">
        <p>&copy; 2026 SESSIONS. All Rights Reserved by ahdankerenabiezz.</p>
    </footer>

</body>
</html>