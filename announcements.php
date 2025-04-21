<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PCC | All Announcements</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">

    <style>
        /* Grid Styling for Announcements */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 50px;
            margin-top: 30px;
        }

        /* News Card Styling */
        .news-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
        }

        .news-card h4 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .news-card p {
            font-size: 14px;
            color: #555;
        }

        .news-card .date {
            font-size: 12px;
            color: #999;
            margin-top: 15px;
            display: block;
        }
    </style>
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

<!-- All Announcements Section -->
<section class="news-section" style="padding-top: 60px;">
    <div class="container">
        <h2 class="section-title">All Announcements</h2>
        <div class="news-grid" id="announcementGrid">
            <!-- Loaded via AJAX -->
        </div>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadAllAnnouncements() {
    $.ajax({
        url: 'fetch_all_announcements.php',
        method: 'GET',
        success: function (response) {
            $('#announcementGrid').html(response);
        },
        error: function () {
            $('#announcementGrid').html('<p>Error loading announcements.</p>');
        }
    });
}
$(document).ready(function () {
    loadAllAnnouncements();
});
</script>

</body>
</html>
