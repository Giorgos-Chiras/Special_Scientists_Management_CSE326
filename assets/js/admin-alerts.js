document.addEventListener('DOMContentLoaded', function () {
    const flashDataElement = document.getElementById('flash-data');

    if (flashDataElement) {
        const type = flashDataElement.dataset.type || '';
        const title = flashDataElement.dataset.title || '';
        const text = flashDataElement.dataset.text || '';

        if (type && title) {
            Swal.fire({
                icon: type,
                title: title,
                html: text,
                confirmButtonColor: '#2563eb'
            });
        }
    }

    document.addEventListener('click', function (event) {
        const logoutLink = event.target.closest('.js-confirm-logout');

        if (logoutLink) {
            event.preventDefault();

            const href = logoutLink.getAttribute('href');

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

            return;
        }

        const deleteButton = event.target.closest('.js-delete-button');

        if (deleteButton) {
            event.preventDefault();

            const form = deleteButton.closest('form');

            if (!form) {
                return;
            }

            const title = deleteButton.getAttribute('data-title') || 'Are you sure?';
            const text = deleteButton.getAttribute('data-text') || 'This action cannot be undone.';
            const confirmText = deleteButton.getAttribute('data-confirm-text') || 'Yes';

            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });

            return;
        }
    });

    document.querySelectorAll('.js-validate-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const inputs = form.querySelectorAll('input, select, textarea');
            let errors = [];

            inputs.forEach(function (input) {
                if (input.hasAttribute('required')) {
                    if (!input.value.trim()) {
                        const label = form.querySelector(`label[for="${input.id}"]`);
                        const fieldName = label ? label.innerText : input.name;
                        errors.push(fieldName + ' is required');
                    }
                }

                if (input.type === 'email' && input.value) {
                    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
                    if (!emailValid) {
                        errors.push('Please enter a valid email');
                    }
                }
            });

            if (errors.length > 0) {
                e.preventDefault();

                Swal.fire({
                    icon: 'error',
                    title: 'Form Error',
                    html: '<ul style="text-align:left; padding-left: 20px; margin: 0;">' +
                        errors.map(function (err) {
                            return '<li>' + err + '</li>';
                        }).join('') +
                        '</ul>',
                    confirmButtonColor: '#2563eb'
                });
            }
        });
    });
});