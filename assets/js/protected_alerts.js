document.addEventListener('DOMContentLoaded', function () {
    const flash = document.getElementById('flash-data');

    if (flash) {
        Swal.fire({
            icon: flash.dataset.type || 'success',
            title: flash.dataset.title || 'Done',
            text: flash.dataset.text || '',
            confirmButtonColor: '#2563eb'
        });
    }

    const logoutButtons = document.querySelectorAll('.js-confirm-logout');

    logoutButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const href = button.getAttribute('href');

            Swal.fire({
                title: 'Log out?',
                text: 'You will be signed out.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });
});