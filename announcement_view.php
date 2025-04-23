<?php
require 'db_config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid announcement ID.');
}

$announcement_id = (int) $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT title, content, image, created_at FROM announcement WHERE announcement_id = ?");
    $stmt->execute([$announcement_id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$announcement) {
        die('Announcement not found.');
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($announcement['title']) ?> | PCC Announcement</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- Government Header Bar -->
<div class="govt-bar">
    <div class="container">
        <span></span>
        <div class="govt-links">
            <a href="https://pcc.gov.ph/"><i class="fas fa-globe"></i> PCC.GOV.PH</a>
            <a href="#"><i class="fas fa-flag"></i> NATIONAL SYMBOLS</a>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="main-header">
    <div class="container">
        <div class="logo-container">
            <img src="images/logo.png" alt="PCC Logo" class="logo">
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#">About</a></li>
                <li class="dropdown">
                    <a href="#">Programs<i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="https://www.pcc.gov.ph/genetic-improvement/"><i class="fas fa-dna"></i> Genetic Improvement</a></li>
                        <li><a href="https://www.pcc.gov.ph/enterprise-development/"><i class="fas fa-briefcase"></i> Enterprise Dev</a></li>
                        <li><a href="https://www.pcc.gov.ph/research-for-development/"><i class="fas fa-flask"></i> R&D</a></li>
                    </ul>
                </li>
                <li><a href="announcements.php" class="active">News</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="login.php" class="btn-login"><i class="fas fa-user"></i> Login</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    </div>
</header>

<!-- View Single Announcement -->
<section class="news-section" style="padding-top: 60px;">
    <div class="container">
        <a href="announcements.php" class="btn-outline" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> Back to Announcements</a>

        <article class="news-card" style="max-width: 800px; margin: 0 auto;">
            <img src="uploads/<?= htmlspecialchars($announcement['image']) ?>" alt="Announcement Image" style="width: 100%; height: auto; object-fit: cover; border-radius: 12px;">
            <div class="news-content">
                <span class="news-date"><?= date("F j, Y", strtotime($announcement['created_at'])) ?></span>
                <h2><?= htmlspecialchars($announcement['title']) ?></h2>
                <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
            </div>
        </article>
    </div>
</section>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-col">
                <img src="images/logo2.png" alt="PCC Logo" class="footer-logo">
                <p>Science City of Muñoz, Nueva Ecija<br>Philippines 3120</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#">Organizational Structure</a></li>
                    <li><a href="#">PCC Directory</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Services</h3>
                <ul>
                    <li><a href="#">Artificial Insemination</a></li>
                    <li><a href="#">Semen Processing</a></li>
                    <li><a href="#">Dairy Production</a></li>
                    <li><a href="#">Training Programs</a></li>
                    <li><a href="#">Veterinary Services</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contact Us</h3>
                <ul class="contact-info">
                    <li><i class="fas fa-phone"></i> (044) 456-7890</li>
                    <li><i class="fas fa-envelope"></i> info@pcc.gov.ph</li>
                    <li><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; 2023 Philippine Carabao Center. All Rights Reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Use</a>
                <a href="#">Accessibility</a>
            </div>
        </div>
    </div>
</footer>

<!-- Social Sidebar -->
<div class="social-sidebar">
    <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
    <a href="#" class="social-icon youtube"><i class="fab fa-youtube"></i></a>
    <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
</div>

</body>
</html>
