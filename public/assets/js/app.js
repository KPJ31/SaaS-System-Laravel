const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');

function setSidebarState(isOpen) {
    sidebar?.classList.toggle('is-open', isOpen);
    overlay?.classList.toggle('is-open', isOpen);
    sidebarToggles.forEach((button) => button.setAttribute('aria-expanded', isOpen ? 'true' : 'false'));
    document.body.classList.toggle('sidebar-open', isOpen);
}

function closeSidebar() {
    setSidebarState(false);
}

sidebarToggles.forEach((button) => {
    button.addEventListener('click', () => {
        setSidebarState(!sidebar?.classList.contains('is-open'));
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
            text: form.dataset.confirmText || 'Please confirm before continuing.',
            showCancelButton: true,
            confirmButtonColor: '#3651D4',
            cancelButtonColor: '#64748B',
            confirmButtonText: form.dataset.confirmButton || 'Yes, continue',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

const landingNav = document.querySelector('[data-landing-nav]');
const landingMenu = document.querySelector('[data-landing-menu]');
const landingMenuToggle = document.querySelector('[data-landing-menu-toggle]');
const navLinks = document.querySelectorAll('[data-nav-link]');
const scrollTopButton = document.querySelector('[data-scroll-top]');

function updateLandingNav() {
    if (!landingNav) {
        return;
    }

    landingNav.classList.toggle('is-scrolled', window.scrollY > 12);
    scrollTopButton?.classList.toggle('is-visible', window.scrollY > 500);
}

function closeLandingMenu() {
    landingNav?.classList.remove('is-open');
    landingMenuToggle?.setAttribute('aria-expanded', 'false');
}

landingMenuToggle?.addEventListener('click', () => {
    const isOpen = !landingNav.classList.contains('is-open');
    landingNav.classList.toggle('is-open', isOpen);
    landingMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});

navLinks.forEach((link) => {
    link.addEventListener('click', () => closeLandingMenu());
});

window.addEventListener('scroll', updateLandingNav, { passive: true });
updateLandingNav();

scrollTopButton?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeLandingMenu();
    }
});

const sections = Array.from(navLinks)
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

if ('IntersectionObserver' in window && sections.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            navLinks.forEach((link) => {
                const isActive = link.getAttribute('href') === `#${entry.target.id}`;
                link.classList.toggle('is-active', isActive);
                if (isActive) {
                    link.setAttribute('aria-current', 'page');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        });
    }, { rootMargin: '-35% 0px -55% 0px' });

    sections.forEach((section) => observer.observe(section));
}

document.querySelectorAll('[data-caps-lock]').forEach((input) => {
    const warning = input.closest('.mb-3, .col-md-6, form')?.querySelector('[data-caps-warning]');

    input.addEventListener('keyup', (event) => {
        warning?.classList.toggle('is-visible', event.getModifierState?.('CapsLock') === true);
    });

    input.addEventListener('blur', () => warning?.classList.remove('is-visible'));
});

document.querySelectorAll('[data-password-strength]').forEach((input) => {
    const output = input.closest('.mb-3, .col-md-6, form')?.querySelector('[data-password-strength-output]');
    const bar = output?.querySelector('span');
    const text = output?.querySelector('small');

    input.addEventListener('input', () => {
        const value = input.value;
        let score = 0;
        if (value.length >= 8) score += 1;
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;

        const widths = ['15%', '35%', '60%', '82%', '100%'];
        const colors = ['#EF4444', '#F59E0B', '#2563EB', '#10B981', '#10B981'];
        const labels = ['Very weak', 'Weak', 'Fair', 'Strong', 'Strong'];

        bar?.style.setProperty('--strength-width', widths[score]);
        bar?.style.setProperty('--strength-color', colors[score]);
        if (text) {
            text.textContent = value ? `${labels[score]} password` : 'Use at least 8 characters with a mix of letters, numbers and symbols.';
        }
    });
});

document.querySelectorAll('[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        if (!form.checkValidity()) {
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        if (!button || button.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        button.dataset.submitting = 'true';
        button.innerHTML = `<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ${button.dataset.loadingText || 'Please wait...'}`;
        button.disabled = true;
    });
});

document.querySelectorAll('[data-register-form]').forEach((form) => {
    const steps = Array.from(form.querySelectorAll('[data-step]'));
    const tabs = Array.from(document.querySelectorAll('[data-step-tab]'));
    const prev = form.querySelector('[data-step-prev]');
    const next = form.querySelector('[data-step-next]');
    const actions = form.querySelector('.register-actions');
    let currentStep = Math.max(1, Math.min(Number(form.dataset.initialStep || 1), steps.length));

    function showStep(step) {
        currentStep = step;
        steps.forEach((section) => section.classList.toggle('is-active', Number(section.dataset.step) === step));
        tabs.forEach((tab) => tab.classList.toggle('is-active', Number(tab.dataset.stepTab) === step));
        prev.hidden = step === 1;
        actions?.classList.toggle('is-final', step === steps.length);
    }

    function visibleFieldsAreValid() {
        const active = form.querySelector(`.form-step[data-step="${currentStep}"]`);
        const fields = Array.from(active.querySelectorAll('input, textarea, select'));
        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();
                field.focus();
                return false;
            }
        }

        return true;
    }

    prev?.addEventListener('click', () => showStep(Math.max(1, currentStep - 1)));
    next?.addEventListener('click', () => {
        if (visibleFieldsAreValid()) {
            showStep(Math.min(steps.length, currentStep + 1));
        }
    });
    tabs.forEach((tab) => tab.addEventListener('click', () => showStep(Number(tab.dataset.stepTab))));

    showStep(currentStep);

    const firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus?.();
    }
});

document.querySelectorAll('[data-logo-input]').forEach((input) => {
    const drop = input.closest('[data-logo-drop]');
    const preview = drop?.querySelector('[data-logo-preview]');
    const remove = document.querySelector('[data-logo-remove]');
    let objectUrl = null;

    function clearPreview() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        input.value = '';
        preview.hidden = true;
        preview.src = '';
        drop?.classList.remove('has-preview');
        remove?.classList.add('d-none');
    }

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        clearPreview();
        if (!file || !file.type.startsWith('image/')) {
            return;
        }
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        drop?.classList.add('has-preview');
        remove?.classList.remove('d-none');
    });

    remove?.addEventListener('click', clearPreview);
});

if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });
}

if (window.Chart && window.elevanixDashboardCharts) {
    const data = window.elevanixDashboardCharts;
    const colors = ['#6D28D9', '#8B5CF6', '#A855F7', '#22C55E', '#F59E0B', '#EF4444', '#2563EB'];

    const makeChart = (selector, config) => {
        const canvas = document.querySelector(selector);
        if (canvas) {
            new Chart(canvas, config);
        }
    };

    makeChart('[data-chart="companyGrowth"]', {
        type: 'line',
        data: { labels: data.labels, datasets: [{ label: 'Companies', data: data.companyGrowth, borderColor: '#6D28D9', backgroundColor: 'rgba(109, 40, 217, 0.12)', tension: 0.35, fill: true }] },
        options: { responsive: true, plugins: { legend: { display: false } } },
    });

    makeChart('[data-chart="revenueGrowth"]', {
        type: 'bar',
        data: { labels: data.labels, datasets: [{ label: 'Revenue', data: data.revenueGrowth, backgroundColor: '#8B5CF6' }] },
        options: { responsive: true, plugins: { legend: { display: false } } },
    });

    [
        ['companyStatus', data.companyStatusLabels, data.companyStatusValues],
        ['planUsage', data.planUsageLabels, data.planUsageValues],
    ].forEach(([name, labels, values]) => {
        makeChart(`[data-chart="${name}"]`, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: colors }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
        });
    });
}

if (window.Chart && window.elevanixCompanyCharts) {
    const data = window.elevanixCompanyCharts;
    const colors = ['#6D28D9', '#8B5CF6', '#A855F7', '#22C55E', '#F59E0B', '#EF4444', '#3B82F6'];

    const chart = (selector, labels, values) => {
        const canvas = document.querySelector(selector);
        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: colors }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
        });
    };

    chart('[data-chart="companyProjectStatus"]', data.projectStatusLabels, data.projectStatusValues);
    chart('[data-chart="companyTaskStatus"]', data.taskStatusLabels, data.taskStatusValues);
}

document.querySelectorAll('[data-active-timer]').forEach((timer) => {
    const output = timer.querySelector('[data-timer-output]');
    const startedAt = new Date(timer.dataset.startedAt);

    function renderTimer() {
        const seconds = Math.max(0, Math.floor((Date.now() - startedAt.getTime()) / 1000));
        const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const remaining = String(seconds % 60).padStart(2, '0');
        if (output) {
            output.textContent = `${hours}:${minutes}:${remaining}`;
        }
    }

    renderTimer();
    setInterval(renderTimer, 1000);
});

if (window.Chart && window.elevanixEmployeeCharts) {
    const data = window.elevanixEmployeeCharts;
    const colors = ['#6D28D9', '#8B5CF6', '#A855F7', '#22C55E', '#F59E0B', '#EF4444', '#3B82F6'];

    const weekly = document.querySelector('[data-chart="employeeWeeklyHours"]');
    if (weekly) {
        new Chart(weekly, {
            type: 'bar',
            data: { labels: data.weeklyLabels, datasets: [{ label: 'Hours', data: data.weeklyHours, backgroundColor: '#6D28D9' }] },
            options: { responsive: true, plugins: { legend: { display: false } } },
        });
    }

    const status = document.querySelector('[data-chart="employeeTaskStatus"]');
    if (status) {
        new Chart(status, {
            type: 'doughnut',
            data: { labels: data.taskStatusLabels, datasets: [{ data: data.taskStatusValues, backgroundColor: colors }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
        });
    }
}

if (window.Chart && window.elevanixEmployeePerformance) {
    const canvas = document.querySelector('[data-chart="employeePerformance"]');
    if (canvas) {
        new Chart(canvas, {
            type: 'line',
            data: { labels: window.elevanixEmployeePerformance.labels, datasets: [{ label: 'Completed tasks', data: window.elevanixEmployeePerformance.values, borderColor: '#6D28D9', backgroundColor: 'rgba(109, 40, 217, 0.12)', tension: 0.35, fill: true }] },
            options: { responsive: true, plugins: { legend: { display: false } } },
        });
    }
}
