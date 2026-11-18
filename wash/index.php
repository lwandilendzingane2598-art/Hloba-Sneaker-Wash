<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hloba Ntanga Sneaker Wash | Premium Care</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a2540;
            --accent: #d90429;
            --sky: #1890ff;
            --light: #f4f7fb;
            --dark: #1e293b;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: var(--light); color: var(--dark); line-height: 1.6; }
        
        header { background: var(--primary); color: white; padding: 0.8rem 5%; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .logo-container { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; }
        
        /* Increased logo size from 50px to 65px */
        .logo-img { width: 65px; height: 65px; border-radius: 50%; object-fit: cover; border: 2px solid var(--sky); }
        .logo-text { font-size: 1.3rem; font-weight: bold; letter-spacing: 1px; }
        .logo-text span { color: var(--accent); }
        
        nav { display: flex; align-items: center; }
        nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: 500; transition: 0.3s; }
        nav a:hover { color: var(--sky); }
        .btn-portal { background: var(--accent); padding: 8px 18px; border-radius: 20px; color: white !important; font-weight: bold; }
        .btn-portal:hover { background: #b00020; }

        /* Custom Hero Background using img/back.jpg */
        .hero { background: linear-gradient(rgba(10,37,64,0.75), rgba(10,37,64,0.75)), url('img/back.jpg') center/cover no-repeat; color: white; padding: 120px 5% 100px; text-align: center; }
        .hero h1 { font-size: 2.8rem; margin-bottom: 15px; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .hero p { font-size: 1.2rem; max-width: 650px; margin: 0 auto 25px; color: #e2e8f0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
        .hero-btn { background: var(--sky); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(24,144,255,0.4); display: inline-block; transition: 0.3s; }
        .hero-btn:hover { background: #0066cc; transform: translateY(-2px); }

        .section-title { text-align: center; font-size: 2rem; color: var(--primary); margin: 50px 0 10px; }
        .section-subtitle { text-align: center; color: #64748b; margin-bottom: 40px; }

        .price-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; padding: 0 5%; max-width: 1200px; margin: 0 auto; }
        .price-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border-top: 4px solid var(--accent); transition: 0.3s; padding: 25px; text-align: center; }
        .price-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .price-card h3 { color: var(--primary); font-size: 1.3rem; margin-bottom: 10px; }
        .price-card .price { font-size: 2rem; font-weight: bold; color: var(--accent); margin: 15px 0; }
        .price-card p { color: #64748b; font-size: 0.95rem; }

        .notice-banner { background: #fff3cd; border-left: 5px solid #ffc107; color: #856404; padding: 15px; max-width: 1200px; margin: 40px auto 0; border-radius: 6px; text-align: center; font-weight: 600; }

        .posters-section { padding: 50px 5%; background: white; margin-top: 40px; }
        .posters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
        .poster-card { border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: #f8fafc; text-align: center; padding: 15px; }
        .poster-card img { width: 100%; height: 350px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }

        .about-contact { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding: 60px 5%; max-width: 1200px; margin: 0 auto; }
        @media (max-width: 768px) { .about-contact { grid-template-columns: 1fr; } }
        
        .info-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .info-box h2 { color: var(--primary); margin-bottom: 15px; }
        .contact-detail { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; font-size: 1.05rem; }
        .contact-detail i { color: var(--accent); font-size: 1.2rem; }

        .map-container { width: 100%; height: 280px; border-radius: 8px; overflow: hidden; margin-top: 15px; }
        iframe { width: 100%; height: 100%; border: 0; }

        footer { background: var(--primary); color: white; text-align: center; padding: 25px; margin-top: 50px; }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo-container">
        <img src="img/logo.jpg" alt="Hloba Ntanga Logo" class="logo-img">
        <div class="logo-text">HLOBA <span>NTANGA</span></div>
    </a>
    <nav>
        <a href="#showcase">Gallery</a>
        <a href="#services">Services</a>
        <a href="#about">About & Contact</a>
        <a href="login.php" class="btn-portal"><i class="fa-solid fa-right-to-bracket"></i> Account Login</a>
    </nav>
</header>

<section class="hero">
    <h1>Professional Care For Your Sneakers</h1>
    <p>Quality Clean • Fresh Look • We've Got You Covered, Manje Clean!</p>
    <a href="login.php" class="hero-btn"><i class="fa-solid fa-paper-plane"></i> Book Shoe Wash Now</a>
</section>

<div class="notice-banner">
    <i class="fa-solid fa-triangle-exclamation"></i> Notice: Due to high order volumes, washing & deep care takes up to 2 - 3 Days. Walk-ins are welcome!
</div>

<!-- Gallery / Showcase Section -->
<section class="posters-section" id="showcase">
    <h2 class="section-title">Our Work & Gallery</h2>
    <p class="section-subtitle">Take a look at our professional transformations, price lists, and cleaning processes</p>
    <div class="posters-grid">
        <div class="poster-card">
            <img src="img/before.jpg" alt="Before and After Sneaker Restoration">
        </div>
        <div class="poster-card">
            <img src="img/bogart.jpg" alt="Bogart Clean and Polish Showcase">
        </div>
        <div class="poster-card">
            <img src="img/list.jpg" alt="Official Price List & Service Poster">
        </div>
        <div class="poster-card">
            <img src="img/vialli.jpg" alt="Vialli Sneaker Care & Deep Clean">
        </div>
    </div>
</section>

<!-- Official Price List -->
<h2 class="section-title" id="services">Our Official Price List</h2>
<p class="section-subtitle">Affordable and professional sneaker care services</p>

<div class="price-grid">
    <div class="price-card">
        <h3>Basic Sneaker Wash</h3>
        <p>Deep clean, laces & fresh finish</p>
        <div class="price">E80</div>
    </div>
    <div class="price-card">
        <h3>Basic Shoe Cleaning (Polish)</h3>
        <p>Polish & shine for a fresh look</p>
        <div class="price">E80</div>
    </div>
    <div class="price-card">
        <h3>Arbiter Service</h3>
        <p>Professional respray & refinishing</p>
        <div class="price">E120</div>
    </div>
    <div class="price-card">
        <h3>Carvella (Respray)</h3>
        <p>Premium respray & color refresh</p>
        <div class="price">E180</div>
    </div>
    <div class="price-card">
        <h3>Timberland Wash & Care</h3>
        <p>Deep clean & care for Timberland boots</p>
        <div class="price">E150</div>
    </div>
    <div class="price-card">
        <h3>Clean & Polish</h3>
        <p>Full body wash, polish, sole deep clean</p>
        <div class="price">E200</div>
    </div>
    <div class="price-card">
        <h3>Repair & Restoration</h3>
        <p>Tear mending, sole re-gluing, material refresh</p>
        <div class="price">E400+</div>
    </div>
</div>

<div class="about-contact" id="about">
    <div class="info-box">
        <h2>About Us</h2>
        <p><strong>Hloba Ntanga Sneaker Wash</strong> is Eswatini's premier sneaker maintenance and restoration lab. We specialize in deep cleaning, premium un-yellowing, leather polishing, and full restoration to keep your shoes looking brand new.</p>
        <p style="margin-top:15px;">Est. 2026 | Dedicated to top-tier craftsmanship and customer satisfaction.</p>
    </div>

    <div class="info-box">
        <h2>Contact & Location</h2>
        <div class="contact-detail">
            <i class="fa-solid fa-phone"></i>
            <span><strong>Call / WhatsApp:</strong> 793 69 657</span>
        </div>
        <div class="contact-detail">
            <i class="fa-brands fa-instagram"></i>
            <span><strong>Instagram:</strong> @HlobaNtanga_SneakerWash</span>
        </div>
        <div class="contact-detail">
            <i class="fa-solid fa-location-dot"></i>
            <span><strong>Location:</strong> Checkers, Mbabane, Eswatini</span>
        </div>
        
        <div class="map-container">
            <iframe src="https://maps.google.com/maps?q=Mbabane%20Checkers&t=&z=15&ie=UTF8&iwloc=&output=embed" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Hloba Ntanga Sneaker Wash. All Rights Reserved. | We've Got You Covered, Manje Clean!!</p>
</footer>

</body>
</html>