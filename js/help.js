document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation
    const helpLinks = document.querySelectorAll('.help-sidebar a');
    const helpSections = document.querySelectorAll('.help-section');
    
    helpLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            helpLinks.forEach(l => l.classList.remove('active'));
            helpSections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href');
            document.querySelector(targetId).classList.add('active');
            
            // Scroll to top of content
            document.querySelector('.help-content').scrollTop = 0;
        });
    });
    
    // Smooth scroll for anchor links within page
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if(this.getAttribute('href') !== '#') {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});