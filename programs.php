<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PCC | Programs and Profiles</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">

<style>
    /* Grid Styling for Programs */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
        padding: 0 15px;
    }

    /* Program Card Styling */
    .news-card {
        background-color: #fff;
        display: flex;
        align-items: center;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        min-height: 120px;
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .news-card img {
        width: 120px;
        height: 200px;
        object-fit: cover;
        border-radius: 50%;
        margin-right: 15px;
        flex-shrink: 0;
        border: 2px solid #f0f0f0;
    }

    .news-content {
        flex: 1;
        min-width: 0; /* Prevent text overflow */
    }

    .news-content h4 {
        font-size: 16px;
        margin: 0 0 5px 0;
        color: #003366;
        line-height: 1.3;
    }

    .news-content p {
        font-size: 13px;
        color: #666;
        margin: 0;
        line-height: 1.4;
    }

    @media (max-width: 768px) {
        .news-grid {
            grid-template-columns: 1fr;
        }
        
        .news-card {
            padding: 15px;
        }
        
        .news-card img {
            width: 60px;
            height: 60px;
            margin-right: 12px;
        }
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
                <li><a href="programs.php" class="active">CBED Coordinators</a></li> 
                <li><a href="announcements.php">Announcements</a></li>
                <li><a href="login.php" class="btn-login"><i class="fas fa-user"></i> Login</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    </div>
</header>

<!-- Programs Section -->
<section class="news-section" style="padding-top: 60px;">
    <div class="container">
        <h2 class="section-title">CBED Coordinators</h2>
        <div class="news-grid" id="programsGrid">
            <!-- Loaded dynamically -->
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
function loadAllPrograms() {
    $.ajax({
        url: 'fetch_all_programs.php',
        method: 'GET',
        success: function (response) {
            $('#programsGrid').html(response);
        },
        error: function () {
            $('#programsGrid').html('<p>Error loading programs.</p>');
        }
    });
}
$(document).ready(function () {
    loadAllPrograms();
});
</script>

</body>
</html>
