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