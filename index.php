<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Carabao Center | Official Website</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
                <li><a href="#" class="active">Home</a></li>
                <li><a href="#">About</a></li>
            
                <li class="dropdown">
                    <a href="#">Programs<i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="https://www.pcc.gov.ph/genetic-improvement/"><i class="fas fa-dna"></i> Genetic Improvement</a></li>
                        <li><a href="https://www.pcc.gov.ph/enterprise-development/"><i class="fas fa-briefcase"></i> Carabao-based Enterprise Development</a></li>
                        <li><a href="https://www.pcc.gov.ph/research-for-development/"><i class="fas fa-flask"></i> Research for Development</a></li>
                    </ul>
                </li>

                <li><a href="#">News</a></li> 
                <li><a href="#">Contact</a></li>
                <li><a href="login.php" class="btn-login"><i class="fas fa-user"></i>Login Account</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    </div>
</header>
 
    <!-- Hero Section with Video Background -->
    <section class="hero">
        <video class="video-background" autoplay loop muted playsinline>
            <source src="images/introvideo.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h2>Enhancing Carabao Value Chain for Sustainable Development</h2>
                <p>Empowering Filipino farmers through carabao-based enterprises and genetic improvement programs</p>
                <div class="hero-btns">
                    <a href="#" class="btn-primary">Get Started</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Popup Container -->
    <div id="videoContainer" style="display: none;">
        <video width="800" controls autoplay loop muted>
            <source src="images/introvideo.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <button class="close-video">Close</button>
    </div>

    
    <!-- Quick Links -->
    <section class="quick-links">
        <div class="container">
            <div class="quick-link-item">
                <i class="fas fa-wine-bottle"></i>
                <h3>Milk feeding</h3>
                <p>Learn about milk feeding</p>
            </div>
            <div class="quick-link-item">
                <i class="fas fa-syringe"></i>
                <h3>AI services</h3>
                <p>Latest in AI services</p>
            </div>
            <div class="quick-link-item">
                <i class="fas fa-chart-bar"></i>
                <h3>Calf drop</h3>
                <p>Latest in Calf drop</p>
            </div>
            <div class="quick-link-item">
                <i class="fas fa-box"></i>
                <h3>Milk production</h3>
                <p>Learn about milk production</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
    <div class="container">
        <div class="about-content">
            <h2 class="section-title">About 4DX</h2>
            <p>The 4DX is under the Department of Agriculture, mandated to conserve, propagate, and promote the carabao as a source of milk, meat, draft power, and hide to benefit rural farming families. It also plays a vital role in enhancing livestock-based livelihoods, supporting dairy enterprises, and advancing scientific research to improve carabao genetics and productivity. Through sustainable breeding programs, technological innovations, and community-based initiatives, 4DX aims to strengthen the agricultural sector while preserving the cultural and economic significance of the carabao in rural communities.</p>
            <a href="#" class="btn-outline">Read More About Us</a>
        </div>
        <div class="about-images">
        <div class="image-grid">
    <div class="image-container">
        <img src="images/milkfeeding.jpg" alt="milkpi">
        <div class="hover-text">Milk Feeding</div>
    </div>
    <div class="image-container">
        <img src="images/calf.jpeg" alt="calf">
        <div class="hover-text">Calf</div>
    </div>
    <div class="image-container">
        <img src="images/AI.jpg" alt="AI">
        <div class="hover-text">Artificial Insemination</div>
    </div>
    <div class="image-container">
        <img src="images/milkprodaksyon.jpg" alt="milkpro">
        <div class="hover-text">Milk Production</div>
    </div>
    </div>

        </div>
    </div>
</section>

    <!-- News Section -->
    <section class="news-section">
        <div class="container">
            <h2 class="section-title">Announcements & Updates</h2>
            <div class="news-grid">
                <article class="news-card featured">
                    <img src="images/1.jpg" alt="News Feature">
                    <div class="news-content">
                        <span class="news-date">June 15, 2023</span>
                        <h3>PCC Launches New Dairy Facility in Nueva Ecija</h3>
                        <p>The new facility will serve as training center and milk processing plant for farmer cooperatives...</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
                <article class="news-card">
                    <img src="images/2.png" alt="News">
                    <div class="news-content">
                        <span class="news-date">June 10, 2023</span>
                        <h3>PCC Conducts AI Training for Farmers</h3>
                        <p>Artificial insemination techniques taught to 50 farmers from Central Luzon...</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
                <article class="news-card">
                    <img src="images/3.png" alt="News">
                    <div class="news-content">
                        <span class="news-date">March 28, 2025</span>
                        <h3>DA marks 32nd anniversary in Nueva Ecija</h3>
                        <p>Muñoz, Nueva Ecija — The Department of Agriculture (DA) marked its 32nd Anniversary at...</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
            </div>
            <div class="section-footer">
                <a href="#" class="btn-outline">View All News</a>
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

    <script src="js/landing.js"></script>
</body>
<div class="social-sidebar">
    <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
    <a href="#" class="social-icon youtube"><i class="fab fa-youtube"></i></a>
    <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
</div>
</html>