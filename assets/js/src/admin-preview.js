/**
 * Admin Preview Toggle
 * Handles markdown editor preview in WordPress admin
 */

(function($) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('CrispyTheme: jQuery is required for admin preview');
        return;
    }

    const SELECTORS = {
        editor: '.crispy-markdown-editor',
        tabs: '.crispy-markdown-editor__tabs',
        tab: '.crispy-markdown-editor__tab',
        textarea: '.crispy-markdown-editor__textarea',
        preview: '.crispy-markdown-editor__preview',
        placeholder: '.crispy-markdown-editor__placeholder',
        wordCount: '.crispy-markdown-editor__word-count',
        charCount: '.crispy-markdown-editor__char-count'
    };

    const CLASSES = {
        activeTab: 'crispy-markdown-editor__tab--active',
        loading: 'crispy-markdown-editor--loading'
    };

    /**
     * Markdown Editor Controller
     */
    class MarkdownEditor {
        constructor(element) {
            this.$editor = $(element);
            this.$textarea = this.$editor.find(SELECTORS.textarea);
            this.$preview = this.$editor.find(SELECTORS.preview);
            this.$tabs = this.$editor.find(SELECTORS.tab);
            this.$wordCount = this.$editor.find(SELECTORS.wordCount);
            this.$charCount = this.$editor.find(SELECTORS.charCount);

            this.currentTab = 'edit';
            this.previewCache = null;
            this.previewCacheContent = null;
            this.debounceTimer = null;

            this.init();
        }

        init() {
            this.bindEvents();
            this.updateStats();
        }

        bindEvents() {
            // Tab switching
            this.$tabs.on('click', (e) => {
                e.preventDefault();
                const tab = $(e.currentTarget).data('tab');
                this.switchTab(tab);
            });

            // Update stats on input
            this.$textarea.on('input', () => {
                this.updateStats();
                this.invalidatePreviewCache();
            });

            // Keyboard shortcuts
            this.$textarea.on('keydown', (e) => {
                this.handleKeyboard(e);
            });
        }

        switchTab(tab) {
            if (tab === this.currentTab) {
                return;
            }

            this.currentTab = tab;

            // Update tab states
            this.$tabs.removeClass(CLASSES.activeTab);
            this.$tabs.filter(`[data-tab="${tab}"]`).addClass(CLASSES.activeTab);

            if (tab === 'edit') {
                this.$textarea.show().focus();
                this.$preview.hide();
            } else if (tab === 'preview') {
                this.$textarea.hide();
                this.$preview.show();
                this.loadPreview();
            }
        }

        loadPreview() {
            const content = this.$textarea.val();

            // Return cached preview if content hasn't changed
            if (content === this.previewCacheContent && this.previewCache !== null) {
                this.$preview.html(this.previewCache);
                return;
            }

            // Show loading state
            this.$editor.addClass(CLASSES.loading);

            // Make AJAX request to render markdown
            $.ajax({
                url: window.crispyAdmin?.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'crispy_preview_markdown',
                    content: content,
                    post_id: this.$textarea.data('post-id') || $('#post_ID').val(),
                    nonce: window.crispyAdmin?.nonce || ''
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        this.previewCache = response.data.html;
                        this.previewCacheContent = content;
                        this.$preview.html(response.data.html);

                        // Initialize Prism.js if available
                        if (typeof Prism !== 'undefined') {
                            Prism.highlightAllUnder(this.$preview[0]);
                        }

                        // Initialize copy buttons if available
                        if (typeof window.CrispyCodeCopy !== 'undefined') {
                            window.CrispyCodeCopy.init();
                        }
                    } else {
                        this.showPreviewError(response.data?.message || 'Preview failed');
                    }
                },
                error: (xhr, status, error) => {
                    this.showPreviewError('Network error: ' + error);
                },
                complete: () => {
                    this.$editor.removeClass(CLASSES.loading);
                }
            });
        }

        showPreviewError(message) {
            this.$preview.html(
                '<div class="notice notice-error"><p>' +
                this.escapeHtml(message) +
                '</p></div>'
            );
        }

        updateStats() {
            const content = this.$textarea.val();

            // Word count
            const words = content.trim() ? content.trim().split(/\s+/).length : 0;
            this.$wordCount.text(words + ' word' + (words !== 1 ? 's' : ''));

            // Character count
            const chars = content.length;
            this.$charCount.text(chars + ' character' + (chars !== 1 ? 's' : ''));
        }

        invalidatePreviewCache() {
            this.previewCache = null;
            this.previewCacheContent = null;
        }

        handleKeyboard(e) {
            // Ctrl/Cmd + P = Toggle Preview
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                this.switchTab(this.currentTab === 'edit' ? 'preview' : 'edit');
            }

            // Tab key inserts tab character instead of moving focus
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                this.insertAtCursor('\t');
            }

            // Auto-indent on Enter
            if (e.key === 'Enter') {
                this.handleEnter(e);
            }

            // Bold: Ctrl/Cmd + B
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                this.wrapSelection('**', '**');
            }

            // Italic: Ctrl/Cmd + I
            if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
                e.preventDefault();
                this.wrapSelection('_', '_');
            }

            // Link: Ctrl/Cmd + K
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.insertLink();
            }
        }

        handleEnter(e) {
            const textarea = this.$textarea[0];
            const pos = textarea.selectionStart;
            const content = textarea.value;

            // Get current line
            const lineStart = content.lastIndexOf('\n', pos - 1) + 1;
            const currentLine = content.substring(lineStart, pos);

            // Check for list patterns
            const listMatch = currentLine.match(/^(\s*)([-*+]|\d+\.)\s/);

            if (listMatch) {
                e.preventDefault();
                const indent = listMatch[1];
                const marker = listMatch[2];

                // If current line is empty list item, remove it
                if (currentLine.trim() === marker) {
                    textarea.value = content.substring(0, lineStart) + content.substring(pos);
                    textarea.selectionStart = textarea.selectionEnd = lineStart;
                } else {
                    // Continue list
                    const newMarker = /^\d+\./.test(marker)
                        ? (parseInt(marker) + 1) + '.'
                        : marker;
                    this.insertAtCursor('\n' + indent + newMarker + ' ');
                }
            }
        }

        insertAtCursor(text) {
            const textarea = this.$textarea[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const content = textarea.value;

            textarea.value = content.substring(0, start) + text + content.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + text.length;
            textarea.focus();

            this.updateStats();
            this.invalidatePreviewCache();
        }

        wrapSelection(before, after) {
            const textarea = this.$textarea[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const content = textarea.value;
            const selected = content.substring(start, end);

            textarea.value = content.substring(0, start) + before + selected + after + content.substring(end);

            if (selected) {
                textarea.selectionStart = start + before.length;
                textarea.selectionEnd = end + before.length;
            } else {
                textarea.selectionStart = textarea.selectionEnd = start + before.length;
            }

            textarea.focus();
            this.updateStats();
            this.invalidatePreviewCache();
        }

        insertLink() {
            const textarea = this.$textarea[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const content = textarea.value;
            const selected = content.substring(start, end);

            const url = prompt('Enter URL:', 'https://');
            if (url) {
                const linkText = selected || 'link text';
                const markdown = `[${linkText}](${url})`;

                textarea.value = content.substring(0, start) + markdown + content.substring(end);

                if (!selected) {
                    // Select "link text" for easy replacement
                    textarea.selectionStart = start + 1;
                    textarea.selectionEnd = start + 1 + linkText.length;
                } else {
                    textarea.selectionStart = textarea.selectionEnd = start + markdown.length;
                }

                textarea.focus();
                this.updateStats();
                this.invalidatePreviewCache();
            }
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    /**
     * Initialize all markdown editors on the page
     */
    function initEditors() {
        $(SELECTORS.editor).each(function() {
            if (!$(this).data('crispy-editor')) {
                $(this).data('crispy-editor', new MarkdownEditor(this));
            }
        });
    }

    // Initialize on document ready
    $(document).ready(initEditors);

    // Expose for external use
    window.CrispyMarkdownEditor = {
        init: initEditors,
        Editor: MarkdownEditor
    };

})(jQuery);
