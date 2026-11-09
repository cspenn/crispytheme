/**
 * Booking page script (/public-speaking/).
 *
 * 1. The audience's main problem: a radio group in a form. On the booking page, a choice fills the first field of the form
 *    (and the topic field). Without script, a plain GET submit opens the form with the problem in the address.
 * 2. Level finder (the talk The Five Levels of AI Enablement): a click on a level fills the problem and the topic.
 * 3. Entrance: parts of each section rise into place once, as they enter the screen.
 * 4. Reel: the Play the reel control opens a dialog, and a silent loop plays beside the room list while it is in view. The controls are absent until the reel option is set.
 *
 * No dependencies. Every part leaves the content visible when the script does not run.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.bp' );

	if ( ! root ) {
		return;
	}

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function field( selector ) {
		return root.querySelector( selector );
	}

	function fire( input ) {
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	// Fill the first form field (the audience's main problem) and the topic field. Return false when the form is not on the page.
	function setForm( problem, topic ) {
		var select = field( '.bp-problem select' );
		var input = field( '.bp-topic input, .bp-topic textarea' );

		if ( ! select || ! input ) {
			return false;
		}

		if ( problem ) {
			select.value = problem;
			fire( select );
		}

		input.value = topic;
		fire( input );

		return true;
	}

	// 1. The audience's main problem: a radio group in a form. The answer of the chosen problem shows by CSS alone.
	// On the booking page, a choice fills the first form field and the topic field, and the button scrolls to the form.
	// Without script, or on the home page, the form is a plain GET form that opens the booking page with the problem in the address.
	var picker = root.querySelector( '[data-bp-picker]' );

	if ( picker ) {
		var radios = picker.querySelectorAll( '.pk__radio' );
		var chosen = function () {
			return picker.querySelector( '.pk__radio:checked' );
		};
		var fill = function () {
			var radio = chosen();

			return radio ? setForm( radio.value, radio.getAttribute( 'data-topic' ) || '' ) : false;
		};

		// An address with a problem (a link from the home page) shows that problem as the chosen one.
		var wanted = new URLSearchParams( window.location.search ).get( 'problem' );

		if ( wanted ) {
			radios.forEach( function ( radio ) {
				if ( radio.value === wanted ) {
					radio.checked = true;
				}
			} );
		}

		radios.forEach( function ( radio ) {
			radio.addEventListener( 'change', fill );
		} );

		picker.addEventListener( 'submit', function ( event ) {
			if ( fill() ) {
				event.preventDefault();
				document.getElementById( 'check-my-dates' ).scrollIntoView( { behavior: reduce ? 'auto' : 'smooth' } );
			}
		} );

		var other = picker.querySelector( 'a[data-problem]' );

		if ( other ) {
			other.addEventListener( 'click', function ( event ) {
				if ( setForm( other.getAttribute( 'data-problem' ) || '', '' ) ) {
					event.preventDefault();
					document.getElementById( 'check-my-dates' ).scrollIntoView( { behavior: reduce ? 'auto' : 'smooth' } );
				}
			} );
		}
	}

	// 2. Level finder: the talk behind the first problem. A click on a level fills the problem and the topic.
	var finder = root.querySelector( '[data-bp-finder]' );

	if ( finder ) {
		var result = finder.querySelector( '[data-finder-result]' );
		var shown = finder.querySelector( '[data-finder-topic]' );
		var rows = finder.querySelectorAll( '.lv' );

		rows.forEach( function ( row ) {
			row.addEventListener( 'click', function ( event ) {
				var topic = row.getAttribute( 'data-topic' ) || '';

				// Keep the plain link when the form field is not on the page.
				if ( ! setForm( row.getAttribute( 'data-problem' ) || '', topic ) ) {
					return;
				}

				event.preventDefault();
				rows.forEach( function ( other ) {
					other.removeAttribute( 'aria-current' );
				} );
				row.setAttribute( 'aria-current', 'true' );

				if ( shown && result ) {
					shown.textContent = topic;
					result.hidden = false;
				}
			} );
		} );

		var skip = finder.querySelector( '.levels__skip a' );

		if ( skip ) {
			skip.addEventListener( 'click', function ( event ) {
				if ( setForm( skip.getAttribute( 'data-problem' ) || '', skip.getAttribute( 'data-topic' ) || '' ) ) {
					event.preventDefault();
					document.getElementById( 'check-my-dates' ).scrollIntoView( { behavior: reduce ? 'auto' : 'smooth' } );
				}
			} );
		}
	}

	// Folds: details elements fold on a phone. On a wide screen they open, so the text reads as before.
	var folds = root.querySelectorAll( 'details[data-wide-open]' );

	if ( folds.length && window.matchMedia ) {
		var wide = window.matchMedia( '(min-width: 761px)' );
		var syncFolds = function () {
			folds.forEach( function ( fold ) {
				fold.open = wide.matches;
			} );
		};

		syncFolds();
		wide.addEventListener( 'change', syncFolds );
	}

	// 3. Entrance. The class on the root hides the parts until they enter the screen.
	var parts = root.querySelectorAll( '[data-reveal]' );

	if ( parts.length && 'IntersectionObserver' in window && ! reduce ) {
		document.documentElement.classList.add( 'bp-js' );

		var seen = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-in' );
						seen.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
		);

		parts.forEach( function ( part, index ) {
			part.style.setProperty( '--bp-i', String( index % 4 ) );
			seen.observe( part );
		} );
	}

	// 4. Reel dialog, and the silent loop beside the room list. The loop plays only while it is in view, and never
	// under reduced motion, where the poster shows.
	var dialog = root.querySelector( '.bp-reel' );
	var opens = root.querySelectorAll( '[data-reel-open]' );

	if ( dialog && opens.length && typeof dialog.showModal === 'function' ) {
		var video = dialog.querySelector( 'video' );

		opens.forEach( function ( open ) {
			open.addEventListener( 'click', function () {
				dialog.showModal();
				video.play().catch( function () {} );
			} );
		} );

		dialog.querySelector( '[data-reel-close]' ).addEventListener( 'click', function () {
			dialog.close();
		} );

		dialog.addEventListener( 'close', function () {
			video.pause();
		} );
	} else {
		opens.forEach( function ( open ) {
			open.hidden = true;
		} );
	}

	var loop = root.querySelector( '.rooms__loop' );

	if ( loop && ! reduce && 'IntersectionObserver' in window ) {
		new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					loop.play().catch( function () {} );
				} else {
					loop.pause();
				}
			} );
		}, { threshold: 0.25 } ).observe( loop );
	}
}() );
