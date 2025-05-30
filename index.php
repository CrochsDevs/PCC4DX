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
    <link rel="stylesheet" href="css/map.css">

    <style>
        /* General styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            margin: 0 auto;
        }

        /* About Section */
        .about-section {
            display: flex;
            justify-content: space-between;
            padding: 40px 0;
        }

        .about-content {
            flex: 1;
            padding-right: 30px; /* Adds space between text and images */
            margin: 50px;
        }

        .section-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .about-content p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
            margin-bottom: 20px;
        }

        .btn-outline {
            display: inline-block;
            background-color: transparent;
            color: #007bff;
            border: 2px solid #007bff;
            width: 400px;
            text-decoration: none;
            border-radius: 5px;
            
        }

        .btn-outline:hover {
            background-color: #007bff;
            color: white;
        }

        /* Images Grid */
        .about-images {
            flex: 1;
            margin-left: 30px; /* Adds space between text and images */
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 columns for images */
            gap: 20px;
        }

        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .image-container:hover img {
            transform: scale(1.1);
        }

        .hover-text {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 1rem;
            display: none;
        }

        .image-container:hover .hover-text {
            display: block;
        }

        /* Responsive Layout - Adjust grid layout for small screens */
        @media (max-width: 1200px) {
            .image-grid {
                grid-template-columns: repeat(3, 1fr); /* 3 columns for medium screens */
            }
        }

        @media (max-width: 992px) {
            .image-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 columns for smaller screens */
            }
        }

        @media (max-width: 768px) {
            .about-section {
                flex-direction: column;
                align-items: center; /* Center the content */
            }

            .about-content, .about-images {
                flex: none;
                width: 100%;
            
                
            }

            .section-title {
                font-size: 2rem;
            }
        }


            </style>
        
        </head>
        <body>
    <!-- Government Header Bar  -->
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
                    <li><a href="#about-section">About</a></li>           
                    <li><a href="programs.php">CBED Coordinators</a></li>

                    <li><a href="announcements.php">Announcements</a></li> 

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
                <i class="fas fa-solid-jug"></i>
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
            <div class="quick-link-item">
                <i class="fas fa-store"></i>
                <h3>Dairy Box</h3>
                <p>Learn about dairy box</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id= "about-section" class="about-section">
    <!-- Left: Text Content -->
    <div class="about-content">
        <h2 class="section-title">About Quick Facts</h2>
        <p><p>The 4DX is under the Department of Agriculture, mandated to conserve, propagate, and promote the carabao as a source of milk, meat, draft power, and hide to benefit rural farming families. It also plays a vital role in enhancing livestock-based livelihoods, supporting dairy enterprises, and advancing scientific research to improve carabao genetics and productivity. Through sustainable breeding programs, technological innovations, and community-based initiatives, 4DX aims to strengthen the agricultural sector while preserving the cultural and economic significance of the carabao in rural communities. In line with its mission, 4DX actively collaborates with local government units, academic institutions, and farming cooperatives to ensure that carabao-related interventions reach even the most remote and underserved rural areas. Its efforts extend to providing continuous training for farmers, promoting the adoption of climate-smart agricultural practices, and improving market access for dairy and meat products derived from carabaos. </p>
        </p>

        <!-- ✅ Move button here -->
        <div class="section-footer about-readmore">
            <a href="#" class="btn-outline">Read more</a>
        </div>

    </div>

    <!-- Right: Image Grid -->
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
    </section>


     <!-- Announcement Section -->
     <section class="news-section">
        <div class="container">
            <h2 class="section-title">Announcements & Updates</h2>

            <!-- Dynamic announcement cards will load here -->
            <div class="news-grid" id="latestAnnouncements"></div>

            <div class="section-footer">
                <a href="announcements.php" class="btn-outline">View All News</a>
            </div>
        </div>
    </section>


  <!-- Footer -->
<footer id ="footer" class="main-footer">
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
</body>
<!-- Load Google Maps API -->
<script>
 
        // DOM elements for info panel
        const infoPanel = document.getElementById('info-panel');
        const centerName = document.getElementById('center-name');
        const centerLocation = document.getElementById('center-location');
        const centerContact = document.getElementById('center-contact');

        // Function to update info panel
        function updateInfoPanel(center) {
            // Clear previous contact information
            centerContact.innerHTML = '';
            
            // Set center name and location
            centerName.textContent = center.name;
            centerLocation.textContent = center.location;
            
            // Add contact information
            let contactHtml = '';
            if (center.phone) {
                contactHtml += `<div class="contact-item">Phone: ${center.phone}</div>`;
            }
            if (center.mobile) {
                contactHtml += `<div class="contact-item">Mobile: ${center.mobile}</div>`;
            }
            contactHtml += `<div class="contact-item">Email: <a href="mailto:${center.email}" style="color: inherit;">${center.email}</a></div>`;
            
            centerContact.innerHTML = contactHtml;
            infoPanel.classList.add('visible');
        }

        // Function to hide info panel
        function hideInfoPanel() {
            infoPanel.classList.remove('visible');
        }

        // Add markers with hover and click functionality
        regionalCenters.forEach(center => {
            const pinOptions = {
                background: center.color === 'blue' ? '#FFD700' : '#4169E1',
                borderColor: center.color === 'blue' ? '#FFD700' : '#4169E1',
                glyphColor: 'white',
                scale: center.color === 'blue' ? 2.0 : 1.5
            };

            const pin = new PinElement(pinOptions);
            const marker = new AdvancedMarkerElement({
                map,
                position: { lat: center.lat, lng: center.lng },
                content: pin.element,
                title: center.shortName
            });

            // Add hover event listeners to the marker content
            marker.content.addEventListener('mouseenter', () => {
                updateInfoPanel(center);
            });

            marker.content.addEventListener('mouseleave', () => {
                hideInfoPanel();
            });

            // Add click listener to show detailed information
            marker.addListener('click', ({ domEvent }) => {
                // Close any open info windows first
                infoPanel.classList.remove('visible');
                
                // Update the info panel with all contact details
                updateInfoPanel(center);
                
                // Prevent the default click behavior on the marker
                domEvent.stopPropagation();
            });
        });
    }

    // Initialize the map when the window loads
    window.addEventListener('load', initMap);
</script>
<script src="js/landing.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
        function loadLatestAnnouncements() {
            $.ajax({
                url: 'fetch_latest_announcements.php',
                method: 'GET',
                success: function(response) {
                    $('#latestAnnouncements').html(response);
                },
                error: function() {
                    $('#latestAnnouncements').html('<p>Error loading announcements.</p>');
                }
            });
        }

        $(document).ready(function () {
            loadLatestAnnouncements();
        });
</script>
<div class="social-sidebar">
    <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
    <a href="#" class="social-icon youtube"><i class="fab fa-youtube"></i></a>
    <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
</div>
</html>