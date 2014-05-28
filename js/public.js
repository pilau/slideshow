
/* Trigger when DOM has loaded */
jQuery( document ).ready( function( $ ) {
	var ps_container = $( '.ps-slideshow' );
	var ps_slideshow;

	// Only one slideshow per page allowed for now
	if ( ps_container.length == 1 ) {

		// Create slideshow
		ps_slideshow = new $.PilauSlideshow({
			el:		ps_container
		});

		// Initialize slideshow
		ps_slideshow.init();

	}

});


( function( $ ) {

	$.PilauSlideshow = function( options ) {

		/** The slideshow container element */
		this.el = options.el;

		/** The element into which the navigation arrows are added */
		this.nav = ( typeof options.nav != 'undefined' ) ? this.el.find( options.nav ) : this.el.find( '.ps-wrapper' );

		/** The ID base for the list elements (usually suffixed by "-n", denoting the initial sequence, starting with 1 */
		this.id_base = ( typeof options.id_base != 'undefined' ) ? options.id_base : 'slide';

		/** Full screen? */
		this.fullscreen = this.el.hasClass( 'ps-fullscreen' );

		/** Mobile version ( 'show_all' | 'shrink' ) */
		this.mobile_version = this.el.data( 'ps-mobile-version' );

		/** Rotate type ( 'scroll' | 'fade' ) */
		this.rotate_type = this.el.data( 'ps-rotate-type' );

		/** Rotate speed */
		this.rotate_speed = this.el.data( 'ps-rotate-speed' );

		/** Fade type ( 'crossfade' | [hex color value to fade through color] ) */
		this.fade_type = this.el.data( 'ps-rotate-fade-type' );

		/** Autorotate? */
		this.autorotate = this.el.hasClass( 'ps-autorotate' );

		/** Pause or stop autorotate on hover? */
		this.autorotate_hover = this.el.data( 'ps-hover-behaviour' );

		/** Autorotate interval */
		this.autorotate_interval = this.el.data( 'ps-autorotate-interval' );

		/** Autorotate timer */
		this.autorotate_timer = null;

		/** Callback function to call after slideshow rotates */
		this.rotate_callback = ( typeof options.rotate_callback != 'undefined' ) ? options.rotate_callback : null;

	};

	$.PilauSlideshow.prototype = {

		/** Initialize */
		init: function() {
			var ss = this; // So that ss can be used inside jQuery functions, where `this` refers to the selected element
			var vw = $( window ).width(); // Viewport width
			ss.list = ss.el.find( 'ul.ps-list' );
			ss.width = ss.list.width();
			ss.length = ss.list.children( 'li' ).length;
			ss.start_slide = ss.get_url_param( 'ps' );

			// Initialize list width for scrolling slideshows
			if ( ss.rotate_type == 'scroll' ) {
				ss.list.width( this.width * this.length + 'px' );
			}

			// The first is current unless query parameter used
			if ( ! ss.start_slide ) {
				ss.list.children( 'li:first-child' ).addClass( 'current' );
			} else {
				ss.list.children( 'li:nth-child(' + ss.start_slide + ')' ).addClass( 'current' );
			}

			// Nav arrows
			ss.nav.append( '<div class="nav-arrows"><a href="#" class="nav previous"><span class="arrow">Previous</span></a><a href="#" class="nav next"><span class="arrow">Next</span></a></div>' );
			ss.el.on( 'mouseenter', '.nav-arrows', function() {
				var el = $( this );

				// Fade nav arrows in
				if ( ! el.hasClass( 'ps-fading-out' ) ) {
					el.addClass( 'ps-fading-in' );
					$( this ).animate({ opacity: 1 }, 200, function() {
						el.removeClass( 'ps-fading-in' );
					});
				}

			}).on( 'mouseleave', '.nav-arrows', function() {
				var el = $( this );

				// Fade nav arrows out
				if ( ! el.hasClass( 'ps-fading-in' ) ) {
					el.addClass( 'ps-fading-out' );
					$( this ).animate({ opacity: 0 }, 200, function() {
						el.removeClass( 'ps-fading-out' );
					});
				}

			}).on( 'click', 'a.nav', function( e ) {
				e.preventDefault();
				var el = $( this );

				// Completely stop rotating
				ss.stop();

				if ( ! el.hasClass( 'disabled' ) ) {
					// Temporarily disable both nav links
					ss.nav.find( 'a.nav' ).addClass( 'disabled' );
					ss.rotate( el.hasClass( 'previous' ) ? 'previous' : 'next' );
				}

			});

			// Hover anywhere on slideshow
			ss.el.on( 'mouseover', function() {
				// Always stop autorotating on mouseover (not completely - may be just pausing)
				clearInterval( ss.autorotate_timer );
			} ).on( 'mouseout', function() {
				// If only a pause was flagged, restart autorotating if appropriate
				if ( ss.el.hasClass( 'ps-autorotate' ) && ss.autorotate_hover == 'pause' ) {
					ss.autorotate_timer = setTimeout( function() { ss.rotate( 'next' ) }, ss.autorotate_interval );
				}
			});

			// Initiate autorotate?
			if ( ss.autorotate ) {
				ss.autorotate_timer = setTimeout( function() { ss.rotate( 'next' ) }, ss.autorotate_interval );
			}

			// For fullscreen above mobile, position halfway down viewport
			if ( ss.fullscreen && vw >= pilau_slideshow.mobile_breakpoint ) {
				ss.el.css( 'margin-top', ( ( $( window ).height() - ss.el.outerHeight() ) / 2 ) + 'px' );
			}

		},

		/** Rotate slideshow */
		rotate: function( dir ) {
			var ss = this;
			var cur = ss.list.children( 'li.current' );

			// Which type of rotation?
			switch ( ss.rotate_type ) {

				case 'fade':
					var n;

					// Find the next slide
					switch ( dir ) {

						case 'previous':
							n = cur.prev();
							if ( ! n.length ) {
								n = ss.list.children( 'li:last-child' );
							}
							break;

						case 'next':
							n = cur.next();
							if ( ! n.length ) {
								n = ss.list.children( 'li:first-child' );
							}
							break;

					}

					// Do the fade
					switch ( ss.fade_type ) {

						case 'crossfade':
							n.show().addClass( 'next' );
							cur.fadeOut( ss.rotate_speed, function() {
								cur.removeClass( 'current' );
								n.removeClass( 'next' ).addClass( 'current' );
								// Callback
								ss.do_rotate_callback();
							});
							break;

						case 'colour':
							cur.fadeOut( ss.rotate_speed, function() {
								cur.removeClass( 'current' );
								n.fadeIn( ss.rotate_speed, function() {
									n.addClass( 'current' );
								});
								// Callback
								ss.do_rotate_callback();
							});
							break;

					}

					break;

				case 'scroll':
					var clo = parseInt( ss.list.css( 'left' ) );
					var nlo;

					// Is there an image ready in the direction we're going?
					switch ( dir ) {

						case 'previous':

							if ( ! cur.prev().length ) {
								// Move from the other end
								ss.list.children( 'li:last-child' ).insertBefore( cur );
								// Adjust positioning
								ss.list.css( 'left', '-' + ss.width + 'px' );
								// Set new left offset for animation
								nlo = 0;
							} else {
								// Set new left offset for animation
								nlo = clo + ss.width;
							}
							// Adjust current class
							cur.removeClass( 'current' ).prev().addClass( 'current' );

							break;

						case 'next':

							if ( ! cur.next().length ) {
								// Move from the other end
								ss.list.children( 'li:first-child' ).insertAfter( cur );
								// Adjust positioning
								ss.list.css( 'left', clo + ss.width + 'px' );
								// Set new left offset for animation
								nlo = 0 - ( ss.width * ( ss.length - 1 ) );
							} else {
								// Set new left offset for animation
								nlo = clo - ss.width;
							}
							// Adjust current class
							cur.removeClass( 'current' ).next().addClass( 'current' );

							break;

					}

					// Now animate
					if ( typeof nlo != 'undefined' ) {
						ss.list.animate({ 'left': nlo + 'px' }, ss.rotate_speed, function() {
							// Callback
							ss.do_rotate_callback();
						});
					}

					break;

			}

			// Re-enable links
			ss.nav.find( 'a.nav' ).removeClass( 'disabled' );

		},

		/**
		 * Go to a particular slide
		 *
		 * Relies on the list items in the markup having ids ending with "-n",
		 * where n numbers the initial sequence of the slides from 1 up.
		 *
		 * @since	0.1
		 * @param	{int}	n
		 */
		goToSlide: function( n ) {
			var ss = this;
			var cur = ss.list.children( 'li.current' );
			var cur_n = ss.get_string_part( cur.attr( 'id' ) );
			var to = ss.list.children( 'li#' + ss.id_base + '-' + n );
			var nlo = 0;
			var i;

			// Is index in range, and not the current one?
			if ( to.length && n != cur_n ) {

				// Get the actual index of the target item
				i = ss.list.children( 'li' ).index( to );

				// Scroll
				nlo = 0;
				if ( i > 0 ) {
					nlo -= ( i * ss.width );
				}
				ss.list.animate({ 'left': nlo + 'px' }, ss.rotate_speed, function() {
					// Callback
					ss.do_rotate_callback();
				});

				// Switch classes
				cur.removeClass( 'current' );
				to.addClass( 'current' );

			}

		},

		/**
		 * Stop slideshow
		 *
		 * @since	0.1
		 */
		stop: function() {
			clearInterval( this.autorotate_timer );
			this.el.removeClass( 'autorotate' );
		},

		/**
		 * Do rotate callback
		 *
		 * @since	0.1
		 */
		do_rotate_callback: function() {
			var ss = this;
			var cb;

			// Continue autorotate?
			if ( ss.autorotate ) {
				ss.autorotate_timer = setTimeout( function() { ss.rotate( 'next' ) }, ss.autorotate_interval );
			}

			// Custom callback if present
			if ( ss.rotate_callback !== null ) {
				cb = window[ ss.rotate_callback ];
				if ( typeof cb === 'function' ) {
					cb();
				}
			}

		},

		/**
		 * Get a part of a string
		 *
		 * @since	0.1
		 * @param	{string}		s		The string
		 * @param	{number|string}	i		The numeric index, or 'first' or 'last' (default 'last')
		 * @param	{string}		sep		The character used a separator in the passed string (default '-')
		 * @return	{string}
		 */
		get_string_part: function( s, i, sep ) {
			var parts;
			if ( typeof i == 'undefined' ) {
				i = 'last';
			}
			if ( typeof sep == 'undefined' ) {
				sep = '-';
			}
			parts = s.split( sep );

			if ( i == 'last' ) {
				i = parts.length - 1;
			} else if ( i == 'first' ) {
				i = 0;
			}

			return parts[ i ];
		},

		/**
		 * Parse query string
		 *
		 * @since	0.1
		 * @link	http://stackoverflow.com/a/901144/1087660
		 * @param	{string}	name
		 * @return	{string}
		 */
		get_url_param: function( name ) {
			name = name.replace( /[\[]/, "\\[" ).replace( /[\]]/, "\\]" );
			var	regex = new RegExp( "[\\?&]" + name + "=([^&#]*)" ),
				results = regex.exec( location.search );
			return results == null ? "" : decodeURIComponent( results[1].replace( /\+/g, " " ) );
		}

	};

}( jQuery ) );
