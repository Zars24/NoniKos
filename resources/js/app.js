import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Theme: light | dark | system. Stored in localStorage and (for signed-in users) users.theme_preference.
 */
window.themeManager = function () {
    const stored = () => localStorage.getItem('nonikos-theme') || 'system';

    const apply = (preference) => {
        const dark = preference === 'dark'
            || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('nonikos-theme', preference);
    };

    return {
        preference: stored(),
        init() {
            this.apply();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.preference === 'system') apply('system');
            });
        },
        apply() {
            apply(this.preference);
        },
        set(preference) {
            this.preference = preference;
            this.apply();
            const form = document.getElementById('theme-preference-form');
            if (form) {
                form.querySelector('input[name="theme_preference"]').value = preference;
                form.requestSubmit();
            }
        },
    };
};

Alpine.start();
