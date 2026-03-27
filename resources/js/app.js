import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
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
