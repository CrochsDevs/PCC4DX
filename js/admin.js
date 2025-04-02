document.addEventListener('DOMContentLoaded', function() {
    // Navigation functionality
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            navLinks.forEach(navLink => navLink.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show the corresponding section
            const targetSection = this.getAttribute('data-section');
            document.getElementById(targetSection).classList.add('active');
        });
    });
    
    // Chart.js initialization
    const colors = {
        primary: '#0056b3',
        primaryLight: '#3a7fc5',
        secondary: '#ffc107',
        secondaryLight: '#ffd54f',
        success: '#38a169',
        danger: '#e53e3e',
        gray: '#e2e8f0'
    };

    const chartConfig = {
        type: 'doughnut',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            family: 'Poppins',
                            size: 11,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 12
                    },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    usePointStyle: true
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    };

    // Initialize all charts
    new Chart(
        document.getElementById('usersChart'),
        {
            ...chartConfig,
            data: {
                labels: ['Registered Farmers', 'Remaining Target'],
                datasets: [{
                    data: [1254, 1500-1254],
                    backgroundColor: [colors.primary, colors.gray],
                    borderWidth: 0
                }]
            }
        }
    );

    new Chart(
        document.getElementById('carabaosChart'),
        {
            ...chartConfig,
            data: {
                labels: ['Registered Carabaos', 'Remaining Target'],
                datasets: [{
                    data: [3421, 3800-3421],
                    backgroundColor: [colors.success, colors.gray],
                    borderWidth: 0
                }]
            }
        }
    );

    new Chart(
        document.getElementById('servicesChart'),
        {
            ...chartConfig,
            data: {
                labels: ['Completed Services', 'Remaining Target'],
                datasets: [{
                    data: [892, 1000-892],
                    backgroundColor: [colors.primaryLight, colors.gray],
                    borderWidth: 0
                }]
            }
        }
    );

    new Chart(
        document.getElementById('requestsChart'),
        {
            ...chartConfig,
            data: {
                labels: ['Pending Requests', 'Target Limit'],
                datasets: [{
                    data: [59, 30],
                    backgroundColor: [colors.danger, colors.gray],
                    borderWidth: 0
                }]
            }
        }
    );
});