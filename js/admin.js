document.addEventListener("DOMContentLoaded", function () {
    /*** Navigation Functionality ***/
    const navLinks = document.querySelectorAll(".nav-link");
    const contentSections = document.querySelectorAll(".content-section");
    
    navLinks.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            
            navLinks.forEach(navLink => navLink.classList.remove("active"));
            contentSections.forEach(section => section.classList.remove("active"));
            
            this.classList.add("active");
            document.getElementById(this.dataset.section).classList.add("active");
        });
    });

    /*** Update Sidebar Profile ***/
    function updateSidebarProfile(data) {
        const profileImg = document.getElementById("sidebar-profile-img");
        if (data.profile_image) {
            profileImg.src = "uploads/profile_images/" + data.profile_image;
        } else {
            profileImg.src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(data.full_name) + "&background=0056b3&color=fff&size=128";
        }

        document.getElementById("sidebar-profile-name").textContent = data.full_name;
        document.getElementById("sidebar-profile-email").textContent = data.email;
    }

    // Profile Update Form
    const profileForm = document.getElementById("profileForm");
    if (profileForm) {
        profileForm.addEventListener("submit", function (e) {
            e.preventDefault();
            
            const btn = document.getElementById("submitBtn");
            const notification = document.getElementById("notification");
            const formData = new FormData(this);
            
            btn.textContent = "Processing...";
            btn.disabled = true;
            notification.style.display = "none";
            
            fetch("update_profile.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateSidebarProfile({
                        full_name: formData.get("full_name"),
                        email: formData.get("email"),
                        profile_image: data.profile_image
                    });
                    notification.className = "notification success";
                    notification.innerHTML = "<i class='fas fa-check-circle'></i> " + data.message;
                } else {
                    notification.className = "notification error";
                    notification.innerHTML = "<i class='fas fa-exclamation-circle'></i> " + data.message;
                }
                notification.style.display = "block";
            })
            .catch(error => {
                console.error("Error:", error);
                notification.className = "notification error";
                notification.innerHTML = "<i class='fas fa-exclamation-circle'></i> An error occurred. Please try again.";
                notification.style.display = "block";
            })
            .finally(() => {
                btn.textContent = "Update Profile";
                btn.disabled = false;
            });
        });
    }

    
    
    /*** Chart.js Initialization ***/
    const chartColors = { primary: "#0056b3", success: "#38a169", danger: "#e53e3e", gray: "#e2e8f0" };
    const createChart = (ctx, labels, data, backgroundColor) => {
        return new Chart(ctx, {
            type: "doughnut",
            data: { labels, datasets: [{ data, backgroundColor, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: "75%" }
        });
    };
    
    if (document.getElementById("usersChart")) {
        createChart(document.getElementById("usersChart"), ["Registered", "Remaining"], [1254, 1500-1254], [chartColors.primary, chartColors.gray]);
    }
    if (document.getElementById("carabaosChart")) {
        createChart(document.getElementById("carabaosChart"), ["Carabaos", "Remaining"], [3421, 3800-3421], [chartColors.success, chartColors.gray]);
    }
    if (document.getElementById("servicesChart")) {
        createChart(document.getElementById("servicesChart"), ["Completed", "Remaining"], [892, 1000-892], [chartColors.primary, chartColors.gray]);
    }
    if (document.getElementById("requestsChart")) {
        createChart(document.getElementById("requestsChart"), ["Pending", "Target"], [59, 30], [chartColors.danger, chartColors.gray]);
    }
    
    /*** Profile Image Preview ***/
    const profileImageInput = document.getElementById("profile_image");
    if (profileImageInput) {
        profileImageInput.addEventListener("change", function () {
            if (this.files.length > 0) {
                document.getElementById("profilePreview").src = URL.createObjectURL(this.files[0]);
            }
        });
    }
    
});




