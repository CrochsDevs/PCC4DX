document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');

    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener('click', function() {
            mainNav.classList.toggle('active');
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));

            if (target) {
                e.preventDefault();
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                if (mainNav?.classList.contains('active')) {
                    mainNav.classList.remove('active');
                }
            }
        });
    });

    // Sticky header on scroll
    const header = document.querySelector('.main-header');
    if (header) {
        window.addEventListener('scroll', function() {
            header.classList.toggle('sticky', window.scrollY > 100);
        });
    }

    // Animation on scroll
    const animateOnScroll = function() {
        document.querySelectorAll('.quick-link-item, .program-card, .news-card').forEach(element => {
            if (element.getBoundingClientRect().top < window.innerHeight - 100) {
                element.classList.add('animate');
            }
        });
    };

    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Run once on page load

    // Video Modal Controls (Fixed Issue)
    const watchVideoBtn = document.getElementById("watchVideoBtn");
    const videoContainer = document.getElementById("videoContainer");
    const closeVideoBtn = document.querySelector(".close-video");
    const video = videoContainer.querySelector("video");

    if (watchVideoBtn && videoContainer && closeVideoBtn && video) {
        // Auto-play video when page loads
        videoContainer.style.display = "block";
        video.play();
        
        // Hide the container initially (it will show when play button is clicked)
        videoContainer.style.display = "none";

        watchVideoBtn.addEventListener("click", function(event) {
            event.preventDefault();
            videoContainer.style.display = "block";
            video.play();
        });

        closeVideoBtn.addEventListener("click", function() {
            videoContainer.style.display = "none";
            video.pause();
            video.currentTime = 0;
        });
    }
});

// scripts.js
document.addEventListener('DOMContentLoaded', function() {
    // Loading screen
    window.addEventListener('load', function() {
        const loader = document.querySelector('.page-loader');
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    });

    // Initialize AOS animations
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // Counter animation
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;
    
    counters.forEach(counter => {
        const animate = () => {
            const target = +counter.getAttribute('data-count');
            const count = +counter.innerText;
            const increment = target / speed;
            
            if(count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(animate, 1);
            } else {
                counter.innerText = target;
            }
        }
        
        const observer = new IntersectionObserver((entries) => {
            if(entries[0].isIntersecting) {
                animate();
            }
        });
        
        observer.observe(counter);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});