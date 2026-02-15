/**
 * Theme Toggle System
 * Handles light/dark mode switching with localStorage persistence
 */

(function() {
    'use strict';
    
    // Theme constants
    const THEME_KEY = 'preferred-theme';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    
    /**
     * Get saved theme preference or system preference
     */
    function getPreferredTheme() {
        const saved = localStorage.getItem(THEME_KEY);
        if (saved) {
            return saved;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return THEME_DARK;
        }
        
        return THEME_LIGHT;
    }
    
    /**
     * Apply theme to document
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
        
        // Update all toggle buttons
        updateToggleButtons(theme);
    }
    
    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || THEME_LIGHT;
        const newTheme = current === THEME_LIGHT ? THEME_DARK : THEME_LIGHT;
        applyTheme(newTheme);
    }
    
    /**
     * Update all theme toggle button states
     */
    function updateToggleButtons(theme) {
        const buttons = document.querySelectorAll('.theme-toggle');
        buttons.forEach(button => {
            const icon = button.querySelector('i');
            if (icon) {
                if (theme === THEME_DARK) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    button.setAttribute('aria-label', 'Switch to light mode');
                    button.setAttribute('title', 'Switch to light mode');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    button.setAttribute('aria-label', 'Switch to dark mode');
                    button.setAttribute('title', 'Switch to dark mode');
                }
            }
        });
    }
    
    /**
     * Initialize theme system
     */
    function initTheme() {
        // Apply saved/preferred theme immediately to prevent flash
        const theme = getPreferredTheme();
        document.documentElement.setAttribute('data-theme', theme);
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setupToggleButtons();
               updateToggleButtons(theme);
            });
        } else {
            setupToggleButtons();
            updateToggleButtons(theme);
        }
        
        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                }
            });
        }
    }
    
    /**
     * Setup click handlers for toggle buttons
     */
    function setupToggleButtons() {
        const buttons = document.querySelectorAll('.theme-toggle');
        buttons.forEach(button => {
            button.addEventListener('click', toggleTheme);
        });
    }
    
    // Expose global function for manual theme setting
    window.setTheme = applyTheme;
    window.toggleTheme = toggleTheme;
    window.getCurrentTheme = () => document.documentElement.getAttribute('data-theme') || THEME_LIGHT;
    
    // Initialize immediately
    initTheme();
})();
