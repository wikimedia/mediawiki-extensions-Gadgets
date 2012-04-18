/**
 * jQuery PropCloud plugin
 * @author Timo Tijhof, 2011
 */
( function ( $ ) {

	/**
	 * Remove all occurences of a value from an array.
	 *
	 * @param arr {Array} Array to be changed
	 * @param val {Mixed} Value to be removed from the array
	 * @return {Array} May or may not be changed, reference kept
	 */
	function arrayRemove( arr, val ) {
		var i;
		// Parentheses are crucial here. Without them, var i will be a
		// boolean instead of a number, resulting in an infinite loop!
		while ( ( i = arr.indexOf( val ) ) !== -1 ) {
			arr.splice( i, 1 );
		}
		return arr;
	}

	/**
	 * Create prop-element for a given label.
	 *
	 * @param label {String}
	 * @param o {Object} $.fn.createPropCloud options object
	 * @return {jQuery} <span class="(prefix)prop"> .. </span>
	 */
	function newPropHtml( label, o ) {
		return $( '<span>' ).addClass( o.prefix + 'prop' )
			.append(
				$( '<span>' ).addClass( o.prefix + 'prop-label' ).text( label )
			)
			.append(
				$( '<span>' )
					.addClass( o.prefix + 'prop-delete' )
					.attr( 'title', o.removeTooltip )
					.click( function () {
						// Update UI
						$(this).parent().remove();
						// Update props
						arrayRemove( o.props, label );
						// Callback
						o.onRemove( label );
					}
				)
			);
	}

	/**
	 * Create prop cloud around an input field.
	 *
	 * @example This is the HTML structure being created:
	 *
	 * <div class="editor-propcloud">
	 *     <div class="editor-propcontainer">
	 *         <span class="editor-prop">
	 *             <span class="editor-prop-label"> .. </span>
	 *             <span class="editor-prop-delete" title="Remove this item"></span>
	 *         </span>
	 *         <span class="editor-prop">
	 *             <span class="editor-prop-label"> .. </span>
	 *             <span class="editor-prop-delete" title="Remove this item"></span>
	 *         </span>
	 *     </div>
	 *     <input class="editor-propinput" />
	 * </div>
	 *
	 * @context {jQuery}
	 * @param o {Object} All optional
	 *  - prefix {String} Class name prefix
	 *  - props {Array} Array of properties to start with
	 *  - autocompleteSource {Function|Array} Source of autocomplete suggestions (required)
	 *    See also http://jqueryui.com/demos/autocomplete/#options (source)
	 *  - onAdd {Function} Callback for when an item is added.
	 *    Called with one argument (the value).
	 *  - onRemove {Function} Callback for when an item is removed.
	 *    Called with one argument (the value).
	 *  - removeTooltip {String} Tooltip for the remove-icon
	 *
	 * @return {jQuery} prop cloud (input field inside)
	 */
	$.fn.createPropCloud = function ( o ) {
		// Some defaults
		o = $.extend({
			prefix: 'editor-',
			props: [],
			autocompleteSource: [],
			onAdd: function ( prop ) {},
			onRemove: function ( prop ) {},
			removeTooltip: 'Remove this item'
		}, o );

		var $el = this.eq(0),
			$input = $el.addClass( o.prefix + 'propinput' ),
			$cloud = $input.wrap( '<div>' ).parent().addClass( o.prefix + 'propcloud' ),
			$container = $( '<div>' ).addClass( o.prefix + 'propcontainer' ),
			i;

		// Append while container is still off the DOM
		// This is faster and prevents visible build-up
		for ( i = 0, props = o.props, len = props.length; i < len; i++ ) {
			$container.append( newPropHtml( '' + props[i], o ) );
		}

		$input.autocomplete( {
			// The source is entirely up to you
			source: o.autocompleteSource,

			// A value is choosen
			// (e.g. by pressing return/tab, clicking on suggestion, etc.)
			select: function ( e, data ){
				var val = data.item.value;

				// Prevent duplicate values
				if ( o.props.indexOf( val ) === -1 ) {
					// Update UI
					$container.append( newPropHtml( val, o ) );
					// Update props
					o.props.push( val );
					// Callback for custom stuff
					o.onAdd( val );
				}

				// Clear input whether duplicate (and ignored),
				// or unique (and added to the PropCloud by now)
				$input.val( '' );

				// Return false,
				// otherwise jQuery UI calls .val( val ) again
				return false;
			}
		});

		$cloud.prepend( $container );

		return $cloud;
	};

})( jQuery );