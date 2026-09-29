<?php
// Mulai sesi biar bisa akses data user yang udah login
session_start();
 
// Cek kalau belum login, langsung tendang ke halaman login
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
 
// Ambil nama user untuk ditampilkan di UI
$username = $_SESSION["username"];
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SESSIONS | Premium Business Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* ── RESET & PREMIUM OBSIDIAN THEME ── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body, html {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-color: #050505;
            background-image: radial-gradient(circle at 50% 0%, #151c2c 0%, #050505 70%);
            background-attachment: fixed;
            color: #ffffff;
        }

        /* ── SPACE THEME ELEMENTS ── */
        .space-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        /* Bulan & Orbit Satelit (DI LUAR CONTAINER BIAR BISA DIKLIK) */
        .moon-wrapper {
            position: absolute;
            top: 15%;
            right: 15%;
            width: 50px; 
            height: 50px;
            cursor: pointer;
            z-index: 999;
            transition: transform 0.3s ease, filter 0.3s ease;
            pointer-events: auto; /* <--- TAMBAHKAN BARIS INI */
        }

        .moon-wrapper:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.4));
        }

        .moon {
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 30%, #d1d5db, #4b5563);
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(255,255,255,0.05), inset -10px -10px 15px rgba(0,0,0,0.6);
        }

        .orbit {
            position: absolute;
            top: -30%; left: -30%;
            width: 160%; height: 160%;
            border: 1px dashed rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: spinOrbit 15s linear infinite;
            pointer-events: none; /* Biar orbitnya ga ganggu area klik bulan */
        }

        .satellite {
            position: absolute;
            top: 0; left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 12px;
            text-shadow: 0 0 5px rgba(255,255,255,0.8);
        }

        @keyframes spinOrbit {
            100% { transform: rotate(360deg); }
        }

        /* Astronot Melayang (Fixed posisinya) */
        .astronaut-container {
            position: fixed; /* Direvisi: Biar anteng ga ikut gerak pas layar di-scroll */
            bottom: 35%;
            left: 8%;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: floatAstronaut 6s ease-in-out infinite;
            z-index: 5;
        }

        .speech-bubble {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 12px;
            position: relative;
            letter-spacing: 0.5px;
            font-weight: 300;
        }

        .speech-bubble::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px 6px 0;
            border-style: solid;
            border-color: rgba(255, 255, 255, 0.1) transparent transparent transparent;
        }

        .astronaut-img {
            width: 150px; 
            height: auto;
            transform: rotate(15deg);
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.3)); 
        }

        @keyframes floatAstronaut {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(3deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        /* Hujan Meteor */
        .meteor {
            position: absolute;
            width: 2px;
            height: 70px;
            background: linear-gradient(to bottom, rgba(255,255,255,1), transparent);
            opacity: 0;
            transform: rotate(-45deg);
        }

        .m1 { top: 10%; left: 70%; animation: meteorShower 5s ease-in infinite 2s; }
        .m2 { top: -10%; left: 40%; animation: meteorShower 7s ease-in infinite 4s; }
        .m3 { top: 30%; left: 90%; animation: meteorShower 9s ease-in infinite 1s; }

        @keyframes meteorShower {
            0% { transform: translate(0, 0) rotate(-45deg); opacity: 1; }
            15% { transform: translate(-400px, 400px) rotate(-45deg); opacity: 0; }
            100% { opacity: 0; }
        }

        /* ── CURSOR SPOTLIGHT ── */
        .cursor-spotlight {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(255,255,255,0.035) 0%, transparent 70%);
            transition: opacity 0.4s ease;
            opacity: 0;
        }

        /* ── PARTICLE CANVAS ── */
        #particle-canvas {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 0;
        }

        /* ── SHIMMER SCAN LINE ── */
        .shimmer-line {
            position: fixed;
            top: -2px; left: 0; width: 100%; height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.08) 40%, rgba(255,255,255,0.18) 50%, rgba(255,255,255,0.08) 60%, transparent 100%);
            pointer-events: none; z-index: 1;
            animation: shimmerScan 7s ease-in-out infinite;
            opacity: 0;
        }

        @keyframes shimmerScan {
            0%   { top: -2px; opacity: 0; }
            5%   { opacity: 1; }
            95%  { opacity: 1; }
            100% { top: 100vh; opacity: 0; }
        }

        /* ── SOLID NATURAL NAVBAR ── */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 4%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            background: transparent;
            animation: fadeDownModern 1s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .nav-brand {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 4px; 
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .nav-brand:hover {
            opacity: 0.7;
        }

        /* ── HERO FULLSCREEN ── */
        .hero {
            position: relative;
            height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 20px;
        }

        .hero-content {
            margin-bottom: 60px;
            animation: scaleInModern 1.2s cubic-bezier(0.16, 1, 0.3, 1) both;
            position: relative;
            z-index: 2;
        }

        .hero-content a {
            text-decoration: none;
            color: inherit;
            display: inline-block;
        }

        .hero-content h1 {
            font-size: 44px;
            font-weight: 500;
            letter-spacing: 2px;
            margin-bottom: 12px;
            text-transform: uppercase;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.05);
            transition: opacity 0.3s ease;
        }

        .hero-content a:hover h1 {
            opacity: 0.7;
        }

        .hero-content p {
            font-size: 15px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.6);
            letter-spacing: 0.5px;
            max-width: 500px;
            margin: 0 auto;
            min-height: 22px; 
        }

        /* ── SUBTITLE TYPEWRITER ── */
        .hero-content p .typewriter-text {
            border-right: 2px solid rgba(255,255,255,0.7);
            white-space: pre-wrap;
            display: inline;
            animation: typewriterBlink 0.75s step-end infinite;
        }

        .hero-content p .typewriter-text.done {
            border-right: none;
            animation: none;
        }

        @keyframes typewriterBlink {
            50% { border-color: transparent; }
        }

        /* ── BOTTOM STATS STRIP ── */
        .hero-stats {
            position: absolute;
            bottom: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 60px;
            width: 100%;
            max-width: 900px;
            padding: 0 20px;
            z-index: 2;
        }

        .stat-box {
            text-align: center;
            flex: 1;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUpModern 1.2s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), filter 0.4s ease;
        }

        .stat-box:hover {
            transform: translateY(-6px) scale(1.04);
            filter: drop-shadow(0 10px 20px rgba(255, 255, 255, 0.1));
        }

        .stat-box:nth-child(1) { animation-delay: 0.3s; }
        .stat-divider:nth-child(2) { animation: fadeIn 1s ease both; animation-delay: 0.45s; opacity: 0; }
        .stat-box:nth-child(3) { animation-delay: 0.5s; }
        .stat-divider:nth-child(4) { animation: fadeIn 1s ease both; animation-delay: 0.65s; opacity: 0; }
        .stat-box:nth-child(5) { animation-delay: 0.7s; }

        .stat-box i {
            font-size: 24px;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.8);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), color 0.4s ease;
            display: block;
        }
        
        .stat-box:hover i {
            transform: scale(1.15);
            color: #ffffff;
        }

        .stat-box p {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1.5px;
        }

        .stat-divider {
            width: 1px;
            height: 35px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* ── MODERN SMOOTH ANIMATIONS ── */
        @keyframes fadeDownModern {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeUpModern {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleInModern {
            from { opacity: 0; transform: scale(0.96) translateY(15px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ── RESPONSIVE HANDLING ── */
        @media (max-width: 768px) {
            nav { padding: 25px 6%; }
            .hero-content h1 { font-size: 32px; }
            .hero-content p { font-size: 12px; padding: 0 20px; max-width: 280px; line-height: 1.6; }
            
            .hero-stats {
                bottom: 60px; gap: 20px; flex-direction: row; flex-wrap: wrap;
            }
            .stat-box p { font-size: 9px; }
            .stat-divider { display: none; }
            .cursor-spotlight { display: none; }
            
            .astronaut-container { 
                left: 5%; 
                bottom: 20%; 
                transform: none; 
            }
            
            .astronaut-img {
                width: 90px; 
            }

            .speech-bubble {
                font-size: 10px; 
                padding: 6px 12px;
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>

    <a href="customisasi.html" class="moon-wrapper" title="Ke Halaman Customisasi">
        <div class="moon"></div>
        <div class="orbit">
            <i class="fa-solid fa-satellite satellite"></i>
        </div>
    </a>

    <div class="space-container">
        <div class="astronaut-container">
            <div class="speech-bubble">Halo, <?= htmlspecialchars($username); ?>! 👋</div>
            <img src="nit.png" alt="Astronaut Melayang" class="astronaut-img">
        </div>

        <div class="meteor m1"></div>
        <div class="meteor m2"></div>
        <div class="meteor m3"></div>
    </div>

    <div class="cursor-spotlight" id="cursorSpotlight"></div>

    <canvas id="particle-canvas"></canvas>

    <div class="shimmer-line"></div>

    <nav>
        <a href="index.html" class="nav-brand">SESSIONS</a>
    </nav>

    <section class="hero">
        
        <div class="hero-content">
            <a href="logout.php" title="Click to Logout">
                <h1 id="heroTitle"><?= htmlspecialchars(strtoupper($username)); ?></h1>
            </a>
            <p><span class="typewriter-text" id="subtitle"></span></p>
        </div>

        <div class="hero-stats">
            <a href="websites.html" class="stat-box" style="text-decoration: none; color: inherit; display: block;">
                <i class="fa-solid fa-store"></i>
                <p>Catalog</p>
            </a>
            
            <div class="stat-divider"></div>
            
            <a href="news.html" class="stat-box" style="text-decoration: none; color: inherit; display: block;">
                <i class="fa-solid fa-newspaper"></i>
                <p>News</p>
            </a>
            
            <div class="stat-divider"></div>
            
            <a href="other.html" class="stat-box" style="text-decoration: none; color: inherit; display: block;">
                <i class="fa-solid fa-box-open"></i>
                <p>Other Product</p>
            </a>
        </div>

    </section>

   <script>
        /* ── 1. CURSOR SPOTLIGHT ── */
        const spotlight = document.getElementById('cursorSpotlight');
        let spotX = window.innerWidth / 2, spotY = window.innerHeight / 2;
        let currentX = spotX, currentY = spotY;
        let spotVisible = false;

        document.addEventListener('mousemove', (e) => {
            spotX = e.clientX; spotY = e.clientY;
            if (!spotVisible) { spotlight.style.opacity = '1'; spotVisible = true; }
        });
        document.addEventListener('mouseleave', () => {
            spotlight.style.opacity = '0'; spotVisible = false;
        });

        function animateSpotlight() {
            currentX += (spotX - currentX) * 0.07;
            currentY += (spotY - currentY) * 0.07;
            spotlight.style.left = currentX + 'px';
            spotlight.style.top = currentY + 'px';
            requestAnimationFrame(animateSpotlight);
        }
        animateSpotlight();

        /* ── 2. AMBIENT PARTICLES ── */
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');

        function resizeCanvas() {
            canvas.width = window.innerWidth; canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const particles = [];
        const PARTICLE_COUNT = 55;

        for (let i = 0; i < PARTICLE_COUNT; i++) {
            particles.push({
                x: Math.random() * window.innerWidth,
                y: Math.random() * window.innerHeight,
                r: Math.random() * 1.2 + 0.3,
                alpha: Math.random() * 0.35 + 0.05,
                vx: (Math.random() - 0.5) * 0.18,
                vy: (Math.random() - 0.5) * 0.18,
                pulse: Math.random() * Math.PI * 2,
                pulseSpeed: Math.random() * 0.012 + 0.006
            });
        }

        function drawParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.x += p.vx; p.y += p.vy; p.pulse += p.pulseSpeed;
                if (p.x < -5) p.x = canvas.width + 5;
                if (p.x > canvas.width + 5) p.x = -5;
                if (p.y < -5) p.y = canvas.height + 5;
                if (p.y > canvas.height + 5) p.y = -5;

                const dynamicAlpha = p.alpha * (0.6 + 0.4 * Math.sin(p.pulse));

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${dynamicAlpha})`;
                ctx.fill();
            });
            requestAnimationFrame(drawParticles);
        }
        drawParticles();

        /* ── 3. MAGNETIC ICON ── */
        document.querySelectorAll('.stat-box').forEach(box => {
            const icon = box.querySelector('i');
            box.addEventListener('mousemove', (e) => {
                const rect = box.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                const dx = (e.clientX - cx) * 0.28;
                const dy = (e.clientY - cy) * 0.28;
                if (icon) icon.style.transform = `translate(${dx}px, ${dy}px) scale(1.15)`;
            });
            box.addEventListener('mouseleave', () => {
                if (icon) icon.style.transform = 'translate(0, 0) scale(1)';
            });
        });

        /* ── 4. TYPEWRITER EFFECT ── */
        const textToType = "Welcome to your premium command center."; 
        const typeWriterElement = document.getElementById('subtitle');
        let i = 0;

        function typeWriter() {
            if (i < textToType.length) {
                typeWriterElement.innerHTML += textToType.charAt(i);
                i++;
                setTimeout(typeWriter, 50); 
            } else {
                setTimeout(() => {
                    typeWriterElement.classList.add('done');
                }, 1500);
            }
        }

        setTimeout(typeWriter, 800);

    </script>
</body>
</html>