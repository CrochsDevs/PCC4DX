function confirmLogout(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to logout?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
        window.location.href = 'logout.php';
        }
    })
    }