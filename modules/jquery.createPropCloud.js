/**
 * jQuery PropCloud plugin
 * @author Timo Tijhof, 2011
 */
( function() {

	function newPropHtml( label, o ) {
		return $( '<span class="' + o.prefix + 'prop"></span>' )
			.append(
				$( '<span class="' + o.prefix + 'prop-label"></span>' ).text( label )
			)
			.append(
				$( '<span class="' + o.prefix + 'prop-delete"></span> ')
					.attr( 'title', o.removeTooltip )
					.click( function() {
						$(this).parent().remove();
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
	 *         <span editor="jquery-prop">
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
	 *     - prefix {String} Class name prefix
	 *     - props {Array} Array of properties to start with
	 *     - autocompleteSource {Function|Array} Source of autocomplete suggestions (required)
	 *       See also http://jqueryui.com/demos/autocomplete/#options (source)
	 *     - onAdd {Function} Callback when an item is added
	 *     - onRemove {Function} Callback when an item is deleted
	 *     - removeTooltip {String} Tooltip for the remove-icon
	 *
	 * @return {jQuery} prop cloud (input field inside)
	 */
	$.fn.createPropCloud = function( o ) {
		// Some defaults
		o = $.extend({
			prefix: 'editor',
			props: [],
			autocompleteSource: [],
			onAdd: function( prop ) {},
			onRemove: function( prop ) {},
			removeTooltip: 'Remove this item'
		}, o );
		o.prefix = o.prefix + '-';

		var	$el = this.eq(0),
			$input = $el.addClass( o.prefix + 'propinput' ),
			$cloud = $input.wrap( '<div class="' + o.prefix + 'propcloud"></div>' ).parent(),
			$container = $( '<div class="' + o.prefix + 'propcontainer"></div>' );

		// Append while container is still off the DOM
		// This is faster and prevents visible build-up
		for ( var i = 0, props = o.props, len = props.length; i < len; i++ ) {
			$container.append( newPropHtml( '' + props[i], o ) );
		}

		$input.autocomplete( {
			// The source is entirely up to you
			source: o.autocompleteSource,

			// A value is choosen
			// (e.g. by pressing return/tab, clicking on suggestion, etc.)
			select: function( e, data ){
				var val = data.item.value;

				// Prevent duplicate values
				if ( o.props.indexOf( val ) === -1 ) {
					$container.append( newPropHtml( val, o ) );
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

})();