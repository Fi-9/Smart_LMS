import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const root = document.documentElement;
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const themeLabel = document.querySelector('[data-theme-label]');
    const sunIcon = document.querySelector('[data-theme-icon-sun]');
    const moonIcon = document.querySelector('[data-theme-icon-moon]');

    const syncThemeUi = () => {
        const isDark = root.classList.contains('dark');
        root.dataset.theme = isDark ? 'dark' : 'light';

        if (themeLabel) {
            themeLabel.textContent = isDark ? 'Dark' : 'Light';
        }

        if (sunIcon && moonIcon) {
            sunIcon.classList.toggle('hidden', isDark);
            moonIcon.classList.toggle('hidden', !isDark);
        }
    };

    syncThemeUi();

    themeToggle?.addEventListener('click', () => {
        const nextDark = !root.classList.contains('dark');
        root.classList.toggle('dark', nextDark);
        localStorage.setItem('smart-library-theme', nextDark ? 'dark' : 'light');
        syncThemeUi();
    });

    const forms = document.querySelectorAll('[data-loading-form]');

    forms.forEach((form) => {
        form.addEventListener('submit', () => {
            const progress = form.querySelector('progress');
            if (!progress) {
                return;
            }

            progress.classList.remove('hidden');
            progress.value = 10;

            const timer = setInterval(() => {
                if (progress.value < 90) {
                    progress.value += 10;
                } else {
                    clearInterval(timer);
                }
            }, 120);
        });
    });
});
