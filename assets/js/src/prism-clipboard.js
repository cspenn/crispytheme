/**
 * Prism.js Clipboard Integration
 * Adds copy-to-clipboard buttons to code blocks
 */

(function() {
    'use strict';

    const COPY_BUTTON_CLASS = 'crispy-copy-button';
    const COPIED_CLASS = 'crispy-copy-button--copied';
    const COPY_TEXT = 'Copy';
    const COPIED_TEXT = 'Copied!';
    const COPIED_DURATION = 2000;

    /**
     * Create a copy button element
     */
    function createCopyButton() {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = COPY_BUTTON_CLASS;
        button.textContent = COPY_TEXT;
        button.setAttribute('aria-label', 'Copy code to clipboard');
        return button;
    }

    /**
     * Copy text to clipboard
     */
    async function copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            textArea.style.top = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                return true;
            } catch (fallbackErr) {
                document.body.removeChild(textArea);
                console.error('Failed to copy code:', fallbackErr);
                return false;
            }
        }
    }

    /**
     * Handle copy button click
     */
    async function handleCopyClick(button, codeBlock) {
        const code = codeBlock.textContent || '';
        const success = await copyToClipboard(code);

        if (success) {
            button.textContent = COPIED_TEXT;
            button.classList.add(COPIED_CLASS);
            button.setAttribute('aria-label', 'Code copied to clipboard');

            setTimeout(function() {
                button.textContent = COPY_TEXT;
                button.classList.remove(COPIED_CLASS);
                button.setAttribute('aria-label', 'Copy code to clipboard');
            }, COPIED_DURATION);
        }
    }

    /**
     * Initialize copy buttons for all code blocks
     */
    function initCopyButtons() {
        // Find all pre > code blocks (Prism.js pattern)
        const codeBlocks = document.querySelectorAll('pre[class*="language-"] > code, pre > code[class*="language-"]');

        codeBlocks.forEach(function(codeBlock) {
            const pre = codeBlock.parentElement;

            // Skip if already has a copy button
            if (pre.querySelector('.' + COPY_BUTTON_CLASS)) {
                return;
            }

            // Ensure pre has relative positioning for button placement
            if (getComputedStyle(pre).position === 'static') {
                pre.style.position = 'relative';
            }

            const button = createCopyButton();

            button.addEventListener('click', function(e) {
                e.preventDefault();
                handleCopyClick(button, codeBlock);
            });

            pre.appendChild(button);
        });
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCopyButtons);
        } else {
            initCopyButtons();
        }

        // Re-initialize when Prism highlights new code (for dynamic content)
        if (typeof Prism !== 'undefined' && Prism.hooks) {
            Prism.hooks.add('complete', function(env) {
                // Delay slightly to ensure DOM is updated
                setTimeout(initCopyButtons, 10);
            });
        }
    }

    // Initialize
    init();

    // Expose for manual re-initialization
    window.CrispyCodeCopy = {
        init: initCopyButtons
    };
})();
