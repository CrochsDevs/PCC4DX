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
    padding: 10px 20px;
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
        margin-bottom: 30px;
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
                <li><a href="#">About</a></li>
            
                <li class="dropdown">
                    <a href="#">Programs<i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="https://www.pcc.gov.ph/genetic-improvement/"><i class="fas fa-dna"></i> Genetic Improvement</a></li>
                        <li><a href="https://www.pcc.gov.ph/enterprise-development/"><i class="fas fa-briefcase"></i> Carabao-based Enterprise Development</a></li>
                        <li><a href="https://www.pcc.gov.ph/research-for-development/"><i class="fas fa-flask"></i> Research for Development</a></li>
                    </ul>
                </li>

                <li><a href="announcements.php">News</a></li> 
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
</body>
<!-- Load Google Maps API -->
<script>
    (g => {
        var h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__";
        var m = document, b = window;
        b = b[c] || (b[c] = {});
        var d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams, u = () => h || (
            h = new Promise(async(f, n) => {
                await (a = m.createElement("script"));
                e.set("libraries", [...r] + "");
                for(k in g)e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                e.set("callback", c + ".maps." + q);
                a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                d[q] = f;
                a.onerror = () => h = n(Error(p + " could not load."));
                a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                m.head.append(a);
            })
        );
        d[l] ? console.warn(p + " only loads once. Ignoring:", g) :
               d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n));
    })({
        key: "AIzaSyC-76TlP4VSPEjMYYUOTNvXFhoDRpZqa54",
        v: "beta"
    });

    async function initMap() {
        // Import required libraries
        const { Map } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");

        // Create map instance with disabled controls
        const map = new Map(document.getElementById('map'), {
            center: { lat: 12.8797, lng: 121.7740 },
            zoom: 6,
            mapId: 'philippines_dapcc_map',
            disableDefaultUI: true,
            zoomControl: false,
            zoomControlOptions: {
                position: google.maps.ControlPosition.LEFT_CENTER
            }
        });

        // Regional centers data
        const regionalCenters = [
            {
                name: "DA-PCC at Mariano Marcos State University",
                shortName: "DA-PCC at MMSU",
                lat: 18.0479061,
                lng: 120.5525777,
                location: "Batac City, Ilocos Norte",
                mobile: "+63 919.224.9062",
                email: "pcc-mmsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Cagayan State University",
                shortName: "DA-PCC at CSU",
                lat: 17.5534463,
                lng: 121.782553,
                location: "Tuguegarao City, Cagayan",
                phone: "+63 (078) 377.9315",
                mobile: "+63 977.806.4930",
                email: "pcc-csu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Don Mariano Marcos Memorial State University",
                shortName: "DA-PCC at DMMMSU",
                lat: 16.2378308,
                lng: 120.4157506,
                location: "Rosario, La Union",
                mobile: "+63 920.982.9666",
                email: "pcc-dmmmsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC National Headquarters and Gene Pool",
                shortName: "DA-PCC NHGP",
                lat: 15.744035,
                lng: 120.942936,
                location: "Science City of Muñoz, Nueva Ecija",
                phone: "+63 (044) 456.0731",
                email: "oed@pcc.gov.ph",
                color: "blue"
            },
            {
                name: "DA-PCC at Central Luzon State University",
                shortName: "DA-PCC at CLSU",
                lat: 15.739369,
                lng: 120.9312026,
                location: "Science City of Muñoz, Nueva Ecija",
                phone: "+63 (044) 940 3061",
                mobile: "+63 968.853.5754",
                email: "pcc-clsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at University Of The Philipines - Los Baños",
                shortName: "DA-PCC at UPLB",
                lat: 14.1587114,
                lng: 121.2440314,
                location: "Los Baños, Laguna",
                phone: "(049) 536.2729",
                email: "pcc-uplb@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Western Visayas State University",
                shortName: "DA-PCC at WVSU",
                lat: 11.1152003,
                lng: 122.5360161,
                location: "Calinog, Iloilo",
                phone: "+63 (033) 323.4781",
                mobile: "+63 999.991.6115",
                email: "pcc-wvsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at La Carlota Stock Farm",
                shortName: "DA-PCC at LCSF",
                lat: 10.4039666,
                lng: 122.9991764,
                location: "La Granja, La Carlota City, Negros Occidental",
                mobile: "+63 919.006.8392",
                email: "pcc-lcsf@pcc.gov.ph"
            },
            {
                name: "DA-PCC at VIsayas State University",
                shortName: "DA-PCC at VSU",
                lat: 10.7413785,
                lng: 124.7940473,
                location: "Baybay City, Leyte",
                mobile: "+63 908.895.2461",
                email: "pcc-vsu@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Ubay Stock Farm",
                shortName: "DA-PCC at USF",
                lat: 9.9928517,
                lng: 124.4482512,
                location: "Ubay, Bohol",
                mobile: "+63 992 161 5798",
                email: "pcc-usf@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Mindanao Livestock Production Center",
                shortName: "DA-PCC at MLPC",
                lat: 7.9253361,
                lng: 122.5321893,
                location: "Kalawit, Zamboanga del Norte",
                mobile: "+63 912.784.4668",
                email: "pcc-mlpc@pcc.gov.ph"
            },
            {
                name: "DA-PCC at University of Southern Mindanao",
                shortName: "DA-PCC at USM",
                lat: 7.1108477,
                lng: 124.839300,
                location: "Kabacan, North Cotabato",
                mobile: "+63 999.229.5948",
                email: "pcc-usm@pcc.gov.ph"
            },
            {
                name: "DA-PCC at Central Mindanao University",
                shortName: "DA-PCC at CMU",
                lat: 7.8802248,
                lng: 125.0619829,
                location: "Maramag, Bukidnon",
                mobile: "+63 939.916.9719",
                email: "pcc-cmu@pcc.gov.ph"
            }
        ];

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