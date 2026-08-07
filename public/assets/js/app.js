const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');

window.ElevanixUI = window.ElevanixUI || {};

window.ElevanixUI.toast = function toast(icon, message, title = null) {
    if (!window.Swal || !message) {
        return;
    }

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title: title || message,
        text: title ? message : undefined,
        showConfirmButton: false,
        timer: 3600,
        timerProgressBar: true,
        customClass: {
            popup: 'elevanix-toast',
        },
    });
};

window.ElevanixUI.confirmAction = function confirmAction(options) {
    if (!window.Swal) {
        return Promise.resolve({ isConfirmed: true });
    }

    return Swal.fire({
        icon: options.icon || 'question',
        title: options.title || 'Please confirm',
        text: options.text || 'Please confirm before continuing.',
        showCancelButton: true,
        confirmButtonColor: options.confirmButtonColor || '#2563EB',
        cancelButtonColor: options.cancelButtonColor || '#64748B',
        confirmButtonText: options.confirmButtonText || 'Yes, continue',
    });
};

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

        window.ElevanixUI.confirmAction({
            title: form.dataset.confirm,
            text: form.dataset.confirmText,
            confirmButtonText: form.dataset.confirmButton,
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
        const colors = ['#EF4444', '#F59E0B', '#2563EB', '#22C55E', '#22C55E'];
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

document.querySelectorAll('[data-image-preview]').forEach((input) => {
    const preview = document.querySelector(input.dataset.imagePreview);
    let objectUrl = null;

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        if (!preview || !file || !file.type.startsWith('image/')) {
            if (preview && !preview.getAttribute('src')) {
                preview.style.display = 'none';
            }
            return;
        }

        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.style.display = '';
    });
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
    accent: '#8B5CF6',
    muted: '#64748B',
};
const chartPalette = [
    chartColors.primary,
    chartColors.info,
    chartColors.success,
    chartColors.warning,
    chartColors.danger,
    chartColors.accent,
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
        data: {
            labels: data.labels,
            datasets: [
                { label: 'Companies', data: data.companyGrowth, borderColor: chartColors.primary, backgroundColor: 'rgba(37, 99, 235, 0.10)', tension: 0.35, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true },
                { label: 'Users', data: data.userGrowth || [], borderColor: chartColors.info, backgroundColor: 'rgba(6, 182, 212, 0.08)', tension: 0.35, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true },
            ],
        },
        options: { ...lineOptions, plugins: { ...lineOptions.plugins, legend: { display: true, position: 'bottom', labels: { font: chartFont, usePointStyle: true, boxWidth: 8 } } } },
    });

    makeChart('[data-chart="revenueGrowth"]', {
        type: 'bar',
        data: { labels: data.labels, datasets: [{ label: 'Revenue', data: data.revenueGrowth, backgroundColor: chartColors.accent, borderRadius: 6, borderSkipped: false, maxBarThickness: 32, categoryPercentage: 0.7, barPercentage: 0.75 }] },
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

document.querySelectorAll('[data-calendar-app]').forEach((calendar) => {
    const endpoint = calendar.dataset.eventsEndpoint;
    const grid = calendar.querySelector('[data-calendar-grid]');
    const title = calendar.querySelector('[data-calendar-title]');
    const status = calendar.querySelector('[data-calendar-status]');
    const dayTitle = calendar.querySelector('[data-calendar-day-title]');
    const daySubtitle = calendar.querySelector('[data-calendar-day-subtitle]');
    const dayList = calendar.querySelector('[data-calendar-day-list]');
    const project = calendar.querySelector('[data-calendar-project]');
    const employee = calendar.querySelector('[data-calendar-employee]');
    const typeInputs = Array.from(calendar.querySelectorAll('[data-calendar-type]'));
    const viewButtons = Array.from(calendar.querySelectorAll('[data-calendar-view]'));
    let current = new Date(`${calendar.dataset.initialDate || new Date().toISOString().slice(0, 10)}T12:00:00`);
    let selectedDate = current.toISOString().slice(0, 10);
    let view = 'month';
    let events = [];

    const labels = {
        month: { weekday: 'short', month: 'short', day: 'numeric' },
        title: { month: 'long', year: 'numeric' },
    };

    function iso(date) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate(), 12).toISOString().slice(0, 10);
    }

    function addDays(date, days) {
        const next = new Date(date);
        next.setDate(next.getDate() + days);
        return next;
    }

    function range() {
        if (view === 'day') {
            return { start: iso(current), end: iso(current) };
        }

        if (view === 'week') {
            const start = addDays(current, -current.getDay());
            return { start: iso(start), end: iso(addDays(start, 6)) };
        }

        const start = new Date(current.getFullYear(), current.getMonth(), 1, 12);
        const gridStart = addDays(start, -start.getDay());
        const end = new Date(current.getFullYear(), current.getMonth() + 1, 0, 12);
        const gridEnd = addDays(end, 6 - end.getDay());
        return { start: iso(gridStart), end: iso(gridEnd) };
    }

    function groupedByDate() {
        return events.reduce((groups, item) => {
            const key = item.date || item.starts_at?.slice(0, 10);
            groups[key] = groups[key] || [];
            groups[key].push(item);
            return groups;
        }, {});
    }

    function eventHtml(item) {
        const time = item.starts_at && !item.starts_at.includes('T00:00:00') ? new Date(item.starts_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
        const href = item.url || '#';
        const tag = item.url ? 'a' : 'button';
        const attrs = item.url ? `href="${href}"` : 'type="button"';
        return `<${tag} class="calendar-event calendar-event-${item.type}${item.is_overdue ? ' is-overdue' : ''}" ${attrs}>
            <span>${time}</span>
            <strong>${item.title}</strong>
            <small>${item.meta || item.status || ''}</small>
        </${tag}>`;
    }

    function renderDayPanel(dateKey, groups) {
        const date = new Date(`${dateKey}T12:00:00`);
        const items = groups[dateKey] || [];
        selectedDate = dateKey;
        dayTitle.textContent = date.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
        daySubtitle.textContent = items.length ? `${items.length} scheduled item${items.length === 1 ? '' : 's'}` : 'No scheduled items for this date.';
        dayList.innerHTML = items.length ? items.map(eventHtml).join('') : '<div class="empty-cell">No scheduled items for this date.</div>';
    }

    function render() {
        const groups = groupedByDate();
        const visible = range();
        title.textContent = view === 'month'
            ? current.toLocaleDateString([], labels.title)
            : `${new Date(`${visible.start}T12:00:00`).toLocaleDateString([], labels.month)} - ${new Date(`${visible.end}T12:00:00`).toLocaleDateString([], labels.month)}`;
        grid.className = `calendar-grid calendar-grid-${view}`;

        if (view === 'month') {
            const start = new Date(`${visible.start}T12:00:00`);
            const days = [];
            for (let index = 0; index < 42; index += 1) {
                const day = addDays(start, index);
                const key = iso(day);
                const items = groups[key] || [];
                days.push(`<div class="calendar-cell ${key === selectedDate ? 'is-selected' : ''} ${day.getMonth() === current.getMonth() ? '' : 'is-muted'}" role="button" tabindex="0" data-calendar-day="${key}">
                    <span>${day.getDate()}</span>
                    <div>${items.slice(0, 3).map(eventHtml).join('')}${items.length > 3 ? `<small class="calendar-more">+${items.length - 3} more</small>` : ''}</div>
                </div>`);
            }
            grid.innerHTML = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => `<div class="calendar-weekday">${day}</div>`).join('') + days.join('');
        } else {
            const start = new Date(`${visible.start}T12:00:00`);
            const dayCount = view === 'day' ? 1 : 7;
            grid.innerHTML = Array.from({ length: dayCount }, (_, index) => {
                const day = addDays(start, index);
                const key = iso(day);
                const items = groups[key] || [];
                return `<div class="calendar-cell is-list ${key === selectedDate ? 'is-selected' : ''}" role="button" tabindex="0" data-calendar-day="${key}">
                    <span>${day.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' })}</span>
                    <div>${items.length ? items.map(eventHtml).join('') : '<small class="calendar-more">No items</small>'}</div>
                </div>`;
            }).join('');
        }

        renderDayPanel(selectedDate, groups);
        grid.querySelectorAll('[data-calendar-day]').forEach((cell) => {
            const select = () => {
                renderDayPanel(cell.dataset.calendarDay, groups);
                grid.querySelectorAll('.calendar-cell').forEach((item) => item.classList.toggle('is-selected', item === cell));
            };
            cell.addEventListener('click', select);
            cell.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    select();
                }
            });
        });
    }

    async function load() {
        const visible = range();
        const params = new URLSearchParams({ start: visible.start, end: visible.end });
        typeInputs.filter((input) => input.checked).forEach((input) => params.append('types[]', input.value));
        if (project?.value) params.append('project_id', project.value);
        if (employee?.value) params.append('employee_id', employee.value);

        status.textContent = 'Loading calendar...';
        status.hidden = false;

        try {
            const response = await fetch(`${endpoint}?${params.toString()}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('Calendar request failed');
            const payload = await response.json();
            events = payload.events || [];
            status.hidden = true;
            render();
        } catch {
            status.textContent = 'Calendar could not be loaded. Please retry.';
        }
    }

    calendar.querySelector('[data-calendar-prev]')?.addEventListener('click', () => {
        if (view === 'month') current.setMonth(current.getMonth() - 1);
        if (view === 'week') current = addDays(current, -7);
        if (view === 'day') current = addDays(current, -1);
        selectedDate = iso(current);
        load();
    });

    calendar.querySelector('[data-calendar-next]')?.addEventListener('click', () => {
        if (view === 'month') current.setMonth(current.getMonth() + 1);
        if (view === 'week') current = addDays(current, 7);
        if (view === 'day') current = addDays(current, 1);
        selectedDate = iso(current);
        load();
    });

    calendar.querySelector('[data-calendar-today]')?.addEventListener('click', () => {
        current = new Date();
        selectedDate = iso(current);
        load();
    });

    viewButtons.forEach((button) => button.addEventListener('click', () => {
        view = button.dataset.calendarView;
        viewButtons.forEach((item) => {
            item.classList.toggle('btn-primary', item === button);
            item.classList.toggle('btn-outline-primary', item !== button);
        });
        load();
    }));

    [...typeInputs, project, employee].filter(Boolean).forEach((input) => input.addEventListener('change', load));
    load();
});

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

if (window.Chart && window.elevanixReportCharts) {
    const data = window.elevanixReportCharts;
    const labels = data.labels || data.taskLabels || data.hourLabels || [];
    const values = data.values || data.taskValues || data.hourValues || [];
    const canvas = document.querySelector('[data-chart="reportAnalytics"]');

    if (canvas && labels.length) {
        new Chart(canvas, {
            type: labels.length <= 6 ? 'doughnut' : 'bar',
            data: labels.length <= 6
                ? compactDoughnutData(labels, values, chartPalette)
                : { labels, datasets: [{ label: 'Total', data: values, backgroundColor: chartColors.primary, borderRadius: 6, borderSkipped: false, maxBarThickness: 30 }] },
            options: labels.length <= 6 ? compactDoughnutOptions() : compactBarOptions(),
        });
    }
}

if (window.Chart && window.elevanixEmployeePersonalReports) {
    const data = window.elevanixEmployeePersonalReports;
    const taskCanvas = document.querySelector('[data-chart="employeePersonalTasks"]');
    const hoursCanvas = document.querySelector('[data-chart="employeePersonalHours"]');

    if (taskCanvas) {
        new Chart(taskCanvas, {
            type: 'doughnut',
            data: compactDoughnutData(data.taskLabels || [], data.taskValues || [], chartPalette),
            options: compactDoughnutOptions(),
        });
    }

    if (hoursCanvas) {
        new Chart(hoursCanvas, {
            type: 'bar',
            data: { labels: data.hourLabels || [], datasets: [{ label: 'Hours', data: data.hourValues || [], backgroundColor: chartColors.primary, borderRadius: 6, borderSkipped: false, maxBarThickness: 30 }] },
            options: compactBarOptions(),
        });
    }
}

const kanbanCards = document.querySelectorAll('[data-kanban-card]');
const kanbanColumns = document.querySelectorAll('[data-kanban-column]');

kanbanCards.forEach((card) => {
    card.addEventListener('dragstart', (event) => {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', JSON.stringify({
            url: card.dataset.moveUrl,
            csrf: card.dataset.csrf,
        }));
        card.classList.add('is-dragging');
    });

    card.addEventListener('dragend', () => {
        card.classList.remove('is-dragging');
    });
});

kanbanColumns.forEach((column) => {
    column.addEventListener('dragover', (event) => {
        event.preventDefault();
        column.classList.add('is-drop-target');
    });

    column.addEventListener('dragleave', () => {
        column.classList.remove('is-drop-target');
    });

    column.addEventListener('drop', async (event) => {
        event.preventDefault();
        column.classList.remove('is-drop-target');

        let payload;
        try {
            payload = JSON.parse(event.dataTransfer.getData('text/plain'));
        } catch {
            return;
        }

        if (!payload.url || !payload.csrf || !column.dataset.kanbanStatus) {
            return;
        }

        const body = new URLSearchParams();
        body.append('_method', 'PATCH');
        body.append('_token', payload.csrf);
        body.append('status', column.dataset.kanbanStatus);

        const response = await fetch(payload.url, {
            method: 'POST',
            headers: { 'Accept': 'text/html', 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
            credentials: 'same-origin',
        });

        if (response.redirected || response.ok) {
            window.location.reload();
        }
    });
});
