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

document.querySelectorAll('[data-sidebar] a').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            closeSidebar();
        }
    });
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

const hasChartValues = (values) => Array.isArray(values) && values.some((value) => Number(value) > 0);
const chartFont = { family: 'Poppins', size: 11 };
const chartGrid = { color: 'rgba(148, 163, 184, 0.15)', drawBorder: false };
const chartColors = {
    primary: '#2563EB',
    info: '#06B6D4',
    success: '#22C55E',
    warning: '#F59E0B',
    danger: '#EF4444',
    purple: '#8B5CF6',
    muted: '#64748B',
};
const chartPalette = [
    chartColors.primary,
    chartColors.info,
    chartColors.success,
    chartColors.warning,
    chartColors.danger,
    chartColors.purple,
    chartColors.muted,
];

function setChartHeight(canvas, height) {
    const wrapper = canvas?.closest('.dashboard-chart-wrapper');
    if (wrapper) {
        wrapper.style.height = `${height}px`;
    }
}

function compactDoughnutData(labels, values, colors) {
    if (hasChartValues(values)) {
        return { labels, datasets: [{ data: values, backgroundColor: colors }] };
    }

    return {
        labels: ['No data'],
        datasets: [{ data: [1], backgroundColor: ['#E2E8F0'], hoverBackgroundColor: ['#CBD5E1'] }],
    };
}

function compactDoughnutOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 600 },
        cutout: '68%',
        layout: { padding: 4 },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 10,
                    boxHeight: 10,
                    padding: 12,
                    font: chartFont,
                    usePointStyle: true,
                },
            },
            tooltip: {
                bodyFont: {
                    family: 'Poppins',
                    size: 12,
                },
                titleFont: {
                    family: 'Poppins',
                    size: 12,
                    weight: '600',
                },
                padding: 10,
            },
        },
    };
}

function compactLineOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 600 },
        plugins: {
            legend: { display: false },
            tooltip: {
                bodyFont: { family: 'Poppins', size: 12 },
                titleFont: { family: 'Poppins', size: 12, weight: '600' },
                padding: 10,
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 0, minRotation: 0, font: chartFont },
            },
            y: {
                beginAtZero: true,
                grid: chartGrid,
                ticks: { precision: 0, font: chartFont },
            },
        },
    };
}

function compactBarOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 600 },
        plugins: {
            legend: { display: false },
            tooltip: {
                bodyFont: { family: 'Poppins', size: 12 },
                titleFont: { family: 'Poppins', size: 12, weight: '600' },
                padding: 10,
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { autoSkip: true, maxTicksLimit: 8, maxRotation: 0, minRotation: 0, font: chartFont },
            },
            y: {
                beginAtZero: true,
                grid: chartGrid,
                ticks: { precision: 0, font: chartFont },
            },
        },
    };
}

function horizontalBarOptions() {
    return {
        ...compactBarOptions(),
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                grid: chartGrid,
                ticks: { precision: 0, font: chartFont },
            },
            y: {
                grid: { display: false },
                ticks: { autoSkip: false, font: chartFont },
            },
        },
    };
}

if (window.Chart && window.elevanixDashboardCharts) {
    const data = window.elevanixDashboardCharts;
    const colors = chartPalette;
    const pieOptions = compactDoughnutOptions();
    const lineOptions = compactLineOptions();
    const barOptions = compactBarOptions();

    const makeChart = (selector, config) => {
        const canvas = document.querySelector(selector);
        if (canvas) {
            new Chart(canvas, config);
        }
    };

    makeChart('[data-chart="companyGrowth"]', {
        type: 'line',
        data: { labels: data.labels, datasets: [{ label: 'Companies', data: data.companyGrowth, borderColor: chartColors.primary, backgroundColor: 'rgba(37, 99, 235, 0.10)', tension: 0.35, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true }] },
        options: lineOptions,
    });

    makeChart('[data-chart="revenueGrowth"]', {
        type: 'bar',
        data: { labels: data.labels, datasets: [{ label: 'Revenue', data: data.revenueGrowth, backgroundColor: chartColors.purple, borderRadius: 6, borderSkipped: false, maxBarThickness: 32, categoryPercentage: 0.7, barPercentage: 0.75 }] },
        options: barOptions,
    });

    [
        ['companyStatus', data.companyStatusLabels, data.companyStatusValues],
        ['planUsage', data.planUsageLabels, data.planUsageValues],
    ].forEach(([name, labels, values]) => {
        makeChart(`[data-chart="${name}"]`, {
            type: 'doughnut',
            data: compactDoughnutData(labels, values, colors),
            options: pieOptions,
        });
    });

    makeChart('[data-chart="platformProjectStatus"]', {
        type: 'doughnut',
        data: compactDoughnutData(data.projectStatusLabels, data.projectStatusValues, colors),
        options: pieOptions,
    });

    makeChart('[data-chart="platformTaskStatus"]', {
        type: 'doughnut',
        data: compactDoughnutData(data.taskStatusLabels, data.taskStatusValues, colors),
        options: pieOptions,
    });

    makeChart('[data-chart="platformUserGrowth"]', {
        type: 'line',
        data: { labels: data.labels, datasets: [{ label: 'Users', data: data.userGrowth, borderColor: chartColors.primary, backgroundColor: 'rgba(37, 99, 235, 0.10)', tension: 0.35, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true }] },
        options: lineOptions,
    });
}

if (window.Chart && window.elevanixCompanyCharts) {
    const data = window.elevanixCompanyCharts;
    const colors = chartPalette;
    const pieOptions = compactDoughnutOptions();
    const employeeHoursOptions = horizontalBarOptions();

    const chart = (selector, labels, values) => {
        const canvas = document.querySelector(selector);
        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: 'doughnut',
            data: compactDoughnutData(labels, values, colors),
            options: pieOptions,
        });
    };

    chart('[data-chart="companyProjectStatus"]', data.projectStatusLabels, data.projectStatusValues);
    chart('[data-chart="companyTaskStatus"]', data.taskStatusLabels, data.taskStatusValues);

    const employeeHours = document.querySelector('[data-chart="companyEmployeeHours"]');
    if (employeeHours) {
        const employeeCount = data.employeeHoursLabels?.length || 0;
        setChartHeight(employeeHours, Math.max(260, Math.min(employeeCount * 42, 420)));
        new Chart(employeeHours, {
            type: 'bar',
            data: { labels: data.employeeHoursLabels, datasets: [{ label: 'Hours', data: data.employeeHoursValues, backgroundColor: chartColors.primary, borderRadius: 6, borderSkipped: false, maxBarThickness: 28, categoryPercentage: 0.7, barPercentage: 0.75 }] },
            options: employeeHoursOptions,
        });
    }

    chart('[data-chart="companyPaymentStatus"]', data.paymentStatusLabels, data.paymentStatusValues);
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
    const colors = chartPalette;
    const pieOptions = compactDoughnutOptions();
    const barOptions = compactBarOptions();

    const weekly = document.querySelector('[data-chart="employeeWeeklyHours"]');
    if (weekly) {
        new Chart(weekly, {
            type: 'bar',
            data: { labels: data.weeklyLabels, datasets: [{ label: 'Hours', data: data.weeklyHours, backgroundColor: chartColors.primary, borderRadius: 6, borderSkipped: false, maxBarThickness: 28, categoryPercentage: 0.7, barPercentage: 0.75 }] },
            options: barOptions,
        });
    }

    const status = document.querySelector('[data-chart="employeeTaskStatus"]');
    if (status) {
        new Chart(status, {
            type: 'doughnut',
            data: compactDoughnutData(data.taskStatusLabels, data.taskStatusValues, colors),
            options: pieOptions,
        });
    }
}

if (window.Chart && window.elevanixEmployeePerformance) {
    const canvas = document.querySelector('[data-chart="employeePerformance"]');
    if (canvas) {
        new Chart(canvas, {
            type: 'line',
            data: { labels: window.elevanixEmployeePerformance.labels, datasets: [{ label: 'Completed tasks', data: window.elevanixEmployeePerformance.values, borderColor: chartColors.primary, backgroundColor: 'rgba(37, 99, 235, 0.10)', tension: 0.35, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true }] },
            options: compactLineOptions(),
        });
    }
}

if (window.Chart && window.elevanixRevenueCharts) {
    const data = window.elevanixRevenueCharts;

    const chart = (selector, config) => {
        const canvas = document.querySelector(selector);
        if (canvas) {
            new Chart(canvas, config);
        }
    };

    chart('[data-chart="revenueMonthlyTrend"]', {
        type: 'line',
        data: { labels: data.monthlyLabels, datasets: [{ label: 'Revenue', data: data.monthlyValues, borderColor: chartColors.primary, backgroundColor: 'rgba(37, 99, 235, 0.10)', tension: 0.35, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true }] },
        options: compactLineOptions(),
    });

    chart('[data-chart="revenueCompany"]', {
        type: 'bar',
        data: { labels: data.companyLabels, datasets: [{ label: 'Revenue', data: data.companyValues, backgroundColor: chartColors.primary, borderRadius: 6, borderSkipped: false, maxBarThickness: 30, categoryPercentage: 0.7, barPercentage: 0.75 }] },
        options: compactBarOptions(),
    });

    chart('[data-chart="revenuePlan"]', {
        type: 'bar',
        data: { labels: data.planLabels, datasets: [{ label: 'Revenue', data: data.planValues, backgroundColor: chartColors.info, borderRadius: 6, borderSkipped: false, maxBarThickness: 30, categoryPercentage: 0.7, barPercentage: 0.75 }] },
        options: compactBarOptions(),
    });

    chart('[data-chart="revenueStatus"]', {
        type: 'doughnut',
        data: compactDoughnutData(data.statusLabels, data.statusValues, chartPalette),
        options: compactDoughnutOptions(),
    });
}
