document.addEventListener('DOMContentLoaded', function() {
    // Chart colors
    const colors = {
        primary: '#0056b3',
        primaryLight: '#3a7fc5',
        secondary: '#ffc107',
        secondaryLight: '#ffd54f',
        success: '#38a169',
        danger: '#e53e3e',
        gray: '#e2e8f0'
    };

    // Common chart configuration
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

    // Farmers Chart
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

    // Carabaos Chart
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

    // Services Chart
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

    // Requests Chart
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
      // Navigation functionality
      document.addEventListener('DOMContentLoaded', function() {
        // Hide all sections except dashboard initially
        document.querySelectorAll('#services-section, #reports-section, #settings-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Handle sidebar navigation clicks
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.sidebar a').forEach(a => {
                    a.classList.remove('active');
                });
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('#dashboard-section, #services-section, #reports-section, #settings-section').forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show the corresponding section
                const target = this.getAttribute('href');
                if (target === '#services-section') {
                    document.getElementById('services-section').style.display = 'block';
                } else if (target === '#reports-section') {
                    document.getElementById('reports-section').style.display = 'block';
                } else if (target === '#settings-section') {
                    document.getElementById('settings-section').style.display = 'block';
                } else {
                    document.getElementById('dashboard-section').style.display = 'block';
                }
            });
        });
        
        // [Previous Chart.js initialization code remains the same]
    });
});