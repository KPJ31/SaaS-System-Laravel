const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');

function closeSidebar() {
    sidebar?.classList.remove('is-open');
    overlay?.classList.remove('is-open');
}

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        sidebar?.classList.add('is-open');
        overlay?.classList.add('is-open');
    });
});

document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
    button.addEventListener('click', closeSidebar);
});

overlay?.addEventListener('click', closeSidebar);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSidebar();
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.parentElement.querySelector('input');
        const icon = button.querySelector('i');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        icon?.classList.toggle('fa-eye');
        icon?.classList.toggle('fa-eye-slash');
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.Swal) {
            return;
        }

        event.preventDefault();

        Swal.fire({
            icon: 'question',
            title: form.dataset.confirm,
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
