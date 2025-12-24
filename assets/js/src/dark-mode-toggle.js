/**
 * Dark Mode Toggle
 * Handles dark mode switching with localStorage persistence
 * and system preference detection
 */

(function() {
    'use strict';

    const STORAGE_KEY = 'crispy-dark-mode';
    const DARK_CLASS = 'dark-mode';

    /**
     * Get the current dark mode preference
     * Priority: localStorage > system preference
     */
    function getDarkModePreference() {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored !== null) {
            return stored === 'true';
        }

        // Fall back to system preference
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /**
     * Apply dark mode to the document
     */
    function applyDarkMode(isDark) {
        document.documentElement.classList.toggle(DARK_CLASS, isDark);
        document.body.classList.toggle(DARK_CLASS, isDark);

        // Update any toggle buttons
        const toggles = document.querySelectorAll('.crispy-dark-mode-toggle');
        toggles.forEach(toggle => {
            toggle.setAttribute('aria-pressed', isDark.toString());
            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        });

        // Dispatch custom event for other scripts to react
        document.dispatchEvent(new CustomEvent('crispy:darkModeChange', {
            detail: { isDark }
        }));
    }

    /**
     * Toggle dark mode and save preference
     */
    function toggleDarkMode() {
        const isDark = document.documentElement.classList.contains(DARK_CLASS);
        const newValue = !isDark;

        localStorage.setItem(STORAGE_KEY, newValue.toString());
        applyDarkMode(newValue);
    }

    /**
     * Initialize dark mode toggle buttons
     */
    function initToggles() {
        const toggles = document.querySelectorAll('.crispy-dark-mode-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleDarkMode();
            });

            // Keyboard accessibility
            toggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleDarkMode();
                }
            });
        });
    }

    /**
     * Listen for system preference changes
     */
    function initSystemPreferenceListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        mediaQuery.addEventListener('change', function(e) {
            // Only respond to system changes if user hasn't set a manual preference
            if (localStorage.getItem(STORAGE_KEY) === null) {
                applyDarkMode(e.matches);
            }
        });
    }

    /**
     * Initialize dark mode
     */
    function init() {
        // Apply initial dark mode state
        const isDark = getDarkModePreference();
        applyDarkMode(isDark);

        // Initialize toggle buttons when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initToggles();
                initSystemPreferenceListener();
            });
        } else {
            initToggles();
            initSystemPreferenceListener();
        }
    }

    // Initialize immediately
    init();

    // Expose API for external use
    window.CrispyDarkMode = {
        toggle: toggleDarkMode,
        isDark: function() {
            return document.documentElement.classList.contains(DARK_CLASS);
        },
        setDark: function(isDark) {
            localStorage.setItem(STORAGE_KEY, isDark.toString());
            applyDarkMode(isDark);
        },
        reset: function() {
            localStorage.removeItem(STORAGE_KEY);
            applyDarkMode(window.matchMedia('(prefers-color-scheme: dark)').matches);
        }
    };
})();
