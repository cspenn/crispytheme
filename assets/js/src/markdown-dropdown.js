/**
 * Markdown Dropdown Component
 *
 * Handles dropdown behavior, clipboard copy, and keyboard navigation
 * for the "Copy page" and "View as Markdown" functionality.
 *
 * @package CrispyTheme
 * @since 1.0.9
 */

( function () {
	'use strict';

	const CLASSES = {
		dropdown: 'crispy-markdown-dropdown',
		trigger: 'crispy-markdown-dropdown__trigger',
		menu: 'crispy-markdown-dropdown__menu',
		item: 'crispy-markdown-dropdown__item',
		title: 'crispy-markdown-dropdown__title',
		copied: 'crispy-markdown-dropdown__item--copied',
		alignRight: 'crispy-markdown-dropdown--align-right',
	};

	const COPIED_DURATION = 2000;

	/**
	 * Dropdown Controller Class
	 */
	class MarkdownDropdown {
		/**
		 * Create a dropdown controller.
		 *
		 * @param {HTMLElement} element The dropdown container element.
		 */
		constructor( element ) {
			this.root = element;
			this.trigger = element.querySelector( '.' + CLASSES.trigger );
			this.menu = element.querySelector( '.' + CLASSES.menu );
			this.items = element.querySelectorAll( '.' + CLASSES.item );
			this.postId = element.dataset.postId;
			this.postTitle = document.title;
			this.postType = document.body.className.match( /single-([a-z]+)/ )
				? document.body.className.match( /single-([a-z]+)/ )[ 1 ]
				: 'post';
			this.isOpen = false;
			this.focusedIndex = -1;

			this.bindEvents();
			this.checkPosition();
		}

		/**
		 * Bind event listeners.
		 */
		bindEvents() {
			// Trigger click.
			this.trigger.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.toggle();
			} );

			// Item actions.
			this.items.forEach( ( item ) => {
				item.addEventListener( 'click', ( e ) => {
					const action = item.dataset.action;
					if ( action === 'copy' ) {
						e.preventDefault();
						this.handleCopy( item );
					} else if ( action === 'view' ) {
						// Track view action - link will still proceed naturally.
						this.trackAction( 'view' );
					}
				} );
			} );

			// Keyboard navigation.
			this.root.addEventListener( 'keydown', ( e ) =>
				this.handleKeydown( e )
			);

			// Close on outside click.
			document.addEventListener( 'click', ( e ) => {
				if ( ! this.root.contains( e.target ) ) {
					this.close();
				}
			} );

			// Close on Escape.
			document.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Escape' && this.isOpen ) {
					this.close();
					this.trigger.focus();
				}
			} );

			// Recheck position on resize.
			window.addEventListener(
				'resize',
				this.debounce( () => this.checkPosition(), 100 )
			);
		}

		/**
		 * Toggle dropdown open/closed.
		 */
		toggle() {
			this.isOpen ? this.close() : this.open();
		}

		/**
		 * Open the dropdown.
		 */
		open() {
			this.isOpen = true;
			this.trigger.setAttribute( 'aria-expanded', 'true' );
			this.menu.hidden = false;
			this.focusedIndex = -1;
		}

		/**
		 * Close the dropdown.
		 */
		close() {
			this.isOpen = false;
			this.trigger.setAttribute( 'aria-expanded', 'false' );
			this.menu.hidden = true;
			this.focusedIndex = -1;
		}

		/**
		 * Handle keyboard navigation.
		 *
		 * @param {KeyboardEvent} e The keyboard event.
		 */
		handleKeydown( e ) {
			if ( ! this.isOpen ) {
				// Open on Enter/Space/ArrowDown when trigger is focused.
				if ( document.activeElement === this.trigger ) {
					if (
						e.key === 'Enter' ||
						e.key === ' ' ||
						e.key === 'ArrowDown'
					) {
						e.preventDefault();
						this.open();
						this.focusItem( 0 );
					}
				}
				return;
			}

			switch ( e.key ) {
				case 'ArrowDown':
					e.preventDefault();
					this.focusNext();
					break;
				case 'ArrowUp':
					e.preventDefault();
					this.focusPrevious();
					break;
				case 'Home':
					e.preventDefault();
					this.focusItem( 0 );
					break;
				case 'End':
					e.preventDefault();
					this.focusItem( this.items.length - 1 );
					break;
				case 'Tab':
					this.close();
					break;
			}
		}

		/**
		 * Focus the next menu item.
		 */
		focusNext() {
			const next =
				this.focusedIndex < this.items.length - 1
					? this.focusedIndex + 1
					: 0;
			this.focusItem( next );
		}

		/**
		 * Focus the previous menu item.
		 */
		focusPrevious() {
			const prev =
				this.focusedIndex > 0
					? this.focusedIndex - 1
					: this.items.length - 1;
			this.focusItem( prev );
		}

		/**
		 * Focus a specific menu item.
		 *
		 * @param {number} index The item index to focus.
		 */
		focusItem( index ) {
			this.focusedIndex = index;
			this.items[ index ].focus();
		}

		/**
		 * Handle copy button click.
		 *
		 * @param {HTMLElement} item The copy button element.
		 */
		async handleCopy( item ) {
			const markdown = await this.getMarkdownContent();

			if ( ! markdown ) {
				// eslint-disable-next-line no-console
				console.error( 'Failed to get Markdown content' );
				return;
			}

			const success = await this.copyToClipboard( markdown );

			if ( success ) {
				this.showCopiedState( item );
				this.trackAction( 'copy', true );
				this.close();
			} else {
				this.trackAction( 'copy', false );
			}
		}

		/**
		 * Fetch markdown content from the server.
		 *
		 * @return {Promise<string|null>} The markdown content or null on error.
		 */
		async getMarkdownContent() {
			// Check for localized script data.
			if (
				typeof window.crispyMarkdownDropdown === 'undefined' ||
				! window.crispyMarkdownDropdown.ajaxUrl
			) {
				// eslint-disable-next-line no-console
				console.error( 'Markdown dropdown not properly initialized' );
				return null;
			}

			try {
				const formData = new FormData();
				formData.append( 'action', 'crispy_get_markdown' );
				formData.append( 'post_id', this.postId );
				formData.append(
					'nonce',
					window.crispyMarkdownDropdown.nonce
				);

				const response = await fetch(
					window.crispyMarkdownDropdown.ajaxUrl,
					{
						method: 'POST',
						body: formData,
						credentials: 'same-origin',
					}
				);

				if ( ! response.ok ) {
					throw new Error( 'Network response was not ok' );
				}

				const data = await response.json();
				return data.success ? data.data.markdown : null;
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Error fetching Markdown:', error );
				return null;
			}
		}

		/**
		 * Copy text to clipboard.
		 *
		 * @param {string} text The text to copy.
		 * @return {Promise<boolean>} True if copy succeeded.
		 */
		async copyToClipboard( text ) {
			// Try modern clipboard API first.
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				try {
					await navigator.clipboard.writeText( text );
					return true;
				} catch ( err ) {
					// Fall through to fallback.
				}
			}

			// Fallback for older browsers.
			const textArea = document.createElement( 'textarea' );
			textArea.value = text;
			textArea.style.cssText =
				'position:fixed;left:-9999px;top:-9999px;opacity:0';
			textArea.setAttribute( 'readonly', '' );
			textArea.setAttribute( 'aria-hidden', 'true' );
			document.body.appendChild( textArea );

			try {
				textArea.focus();
				textArea.select();

				const successful = document.execCommand( 'copy' );
				document.body.removeChild( textArea );
				return successful;
			} catch ( fallbackErr ) {
				document.body.removeChild( textArea );
				// eslint-disable-next-line no-console
				console.error( 'Failed to copy:', fallbackErr );
				return false;
			}
		}

		/**
		 * Show the copied feedback state.
		 *
		 * @param {HTMLElement} item The button element.
		 */
		showCopiedState( item ) {
			const titleEl = item.querySelector( '.' + CLASSES.title );
			const originalText = titleEl.textContent;

			item.classList.add( CLASSES.copied );
			titleEl.textContent =
				window.crispyMarkdownDropdown?.copiedText || 'Copied!';

			// Announce to screen readers.
			this.announceToScreenReader(
				window.crispyMarkdownDropdown?.copiedText || 'Copied!'
			);

			setTimeout( () => {
				item.classList.remove( CLASSES.copied );
				titleEl.textContent = originalText;
			}, COPIED_DURATION );
		}

		/**
		 * Announce a message to screen readers.
		 *
		 * @param {string} message The message to announce.
		 */
		announceToScreenReader( message ) {
			const announcement = document.createElement( 'div' );
			announcement.setAttribute( 'role', 'status' );
			announcement.setAttribute( 'aria-live', 'polite' );
			announcement.setAttribute( 'aria-atomic', 'true' );
			announcement.className = 'screen-reader-text';
			announcement.style.cssText =
				'position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden';
			announcement.textContent = message;

			document.body.appendChild( announcement );

			setTimeout( () => {
				document.body.removeChild( announcement );
			}, 1000 );
		}

		/**
		 * Check if dropdown should align to the right.
		 */
		checkPosition() {
			const rect = this.root.getBoundingClientRect();
			const menuWidth = 300; // Approximate menu width.

			if ( rect.left + menuWidth > window.innerWidth ) {
				this.root.classList.add( CLASSES.alignRight );
			} else {
				this.root.classList.remove( CLASSES.alignRight );
			}
		}

		/**
		 * Debounce a function.
		 *
		 * @param {Function} func The function to debounce.
		 * @param {number}   wait The debounce delay in ms.
		 * @return {Function} The debounced function.
		 */
		debounce( func, wait ) {
			let timeout;
			return ( ...args ) => {
				clearTimeout( timeout );
				timeout = setTimeout( () => func.apply( this, args ), wait );
			};
		}

		/**
		 * Track a markdown dropdown action for analytics.
		 *
		 * Fires both GTM/GA4 dataLayer event and native WordPress tracking.
		 *
		 * @param {string}  action  The action type ('copy' or 'view').
		 * @param {boolean} success Whether the action was successful (optional, defaults to true).
		 */
		trackAction( action, success = true ) {
			// GTM/GA4 dataLayer event.
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( {
				event: 'crispy_markdown_action',
				crispy_action: action,
				crispy_post_id: parseInt( this.postId, 10 ),
				crispy_post_title: this.postTitle,
				crispy_post_type: this.postType,
				crispy_success: success,
			} );

			// Native WordPress tracking (non-blocking).
			if ( window.crispyMarkdownDropdown?.trackingEnabled ) {
				const formData = new URLSearchParams( {
					action: 'crispy_track_markdown_action',
					nonce: window.crispyMarkdownDropdown.trackingNonce,
					post_id: this.postId,
					action_type: action,
				} );

				fetch( window.crispyMarkdownDropdown.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: formData,
					credentials: 'same-origin',
				} ).catch( () => {
					// Silent fail - analytics shouldn't block UX.
				} );
			}
		}
	}

	/**
	 * Initialize all dropdown instances.
	 */
	function initDropdowns() {
		const dropdowns = document.querySelectorAll( '.' + CLASSES.dropdown );
		dropdowns.forEach( ( dropdown ) => {
			if ( ! dropdown.dataset.initialized ) {
				new MarkdownDropdown( dropdown );
				dropdown.dataset.initialized = 'true';
			}
		} );
	}

	/**
	 * Initialize on DOM ready.
	 */
	function init() {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', initDropdowns );
		} else {
			initDropdowns();
		}
	}

	init();

	// Expose for external use.
	window.CrispyMarkdownDropdown = {
		init: initDropdowns,
		MarkdownDropdown,
	};
} )();
