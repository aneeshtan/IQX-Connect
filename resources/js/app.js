const IQX_ACCENT_THEME_STORAGE_KEY = 'iqx-accent-theme';
const IQX_DEFAULT_ACCENT_THEME = 'emerald';
const IQX_APPEARANCE_STORAGE_KEY = 'flux.appearance';
const IQX_DEFAULT_APPEARANCE = 'system';

function shouldUseDarkAppearance(mode) {
    return mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

function applyIqxAppearance(mode) {
    if (document.documentElement.dataset.iqxForceLight === '1') {
        document.documentElement.dataset.iqxAppearance = 'light';
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';

        return;
    }

    const resolvedMode = mode || localStorage.getItem(IQX_APPEARANCE_STORAGE_KEY) || IQX_DEFAULT_APPEARANCE;
    const useDark = shouldUseDarkAppearance(resolvedMode);

    document.documentElement.dataset.iqxAppearance = resolvedMode;
    document.documentElement.classList.toggle('dark', useDark);
    document.documentElement.style.colorScheme = useDark ? 'dark' : 'light';
}

function applyIqxAccentTheme(theme) {
    if (document.documentElement.dataset.iqxForceLight === '1') {
        document.documentElement.dataset.accentTheme = IQX_DEFAULT_ACCENT_THEME;

        return;
    }

    const resolvedTheme = theme || localStorage.getItem(IQX_ACCENT_THEME_STORAGE_KEY) || IQX_DEFAULT_ACCENT_THEME;

    document.documentElement.dataset.accentTheme = resolvedTheme;
}

window.IQXTheme = {
    getAppearance() {
        return localStorage.getItem(IQX_APPEARANCE_STORAGE_KEY) || IQX_DEFAULT_APPEARANCE;
    },

    setAppearance(mode) {
        localStorage.setItem(IQX_APPEARANCE_STORAGE_KEY, mode);
        applyIqxAppearance(mode);
    },

    getAccentTheme() {
        return localStorage.getItem(IQX_ACCENT_THEME_STORAGE_KEY) || IQX_DEFAULT_ACCENT_THEME;
    },

    setAccentTheme(theme) {
        localStorage.setItem(IQX_ACCENT_THEME_STORAGE_KEY, theme);
        applyIqxAccentTheme(theme);
    },
};

function bindIqxShellInteractions() {
    document.querySelectorAll('[data-iqx-sidebar-toggle]').forEach((button) => {
        if (button.dataset.iqxBound === '1') {
            return;
        }

        button.dataset.iqxBound = '1';
        button.addEventListener('click', () => {
            document.documentElement.classList.toggle('iqx-sidebar-open');
        });
    });

    document.querySelectorAll('[data-iqx-sidebar-close]').forEach((button) => {
        if (button.dataset.iqxBound === '1') {
            return;
        }

        button.dataset.iqxBound = '1';
        button.addEventListener('click', () => {
            document.documentElement.classList.remove('iqx-sidebar-open');
        });
    });

    document.querySelectorAll('[data-iqx-appearance]').forEach((button) => {
        if (button.dataset.iqxBound === '1') {
            return;
        }

        button.dataset.iqxBound = '1';
        button.addEventListener('click', () => {
            const mode = button.dataset.iqxAppearance;

            if (! mode) {
                return;
            }

            window.IQXTheme.setAppearance(mode);
        });
    });

    document.querySelectorAll('[data-iqx-accent]').forEach((button) => {
        if (button.dataset.iqxBound === '1') {
            return;
        }

        button.dataset.iqxBound = '1';
        button.addEventListener('click', () => {
            const theme = button.dataset.iqxAccent;

            if (! theme) {
                return;
            }

            window.IQXTheme.setAccentTheme(theme);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    applyIqxAppearance();
    applyIqxAccentTheme();
    bindIqxShellInteractions();
});

document.addEventListener('livewire:navigated', () => {
    applyIqxAppearance();
    applyIqxAccentTheme();
    bindIqxShellInteractions();
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Ignore registration issues in local or unsupported environments.
        });
    });
}
