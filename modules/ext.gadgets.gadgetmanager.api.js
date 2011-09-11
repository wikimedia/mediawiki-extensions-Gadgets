/**
 * Implement the editing API for the gadget manager.
 *
 * @author Timo Tijhof
 * @copyright © 2011 Timo Tijhof
 * @license GNU General Public Licence 2.0 or later
 */
( function( $ ) {

	var
		/**
		 * @var {Object} Keyed by gadget id, contains the metadata as an object.
		 */
		gadgetCache = {},
		/**
		 * @var {Object} If cached, object keyed by category id with categormember-count as value.
		 * Set to null if there is no cache, yet, or when the cache is cleared. */
		gadgetCategoryCache = null;

	/* Local functions */

	/**
	 * For most returns from api.* functions, a clone is made when data from
	 * cache is used. This is to avoid situations where later modifications
	 * (e.g. by the AJAX editor) to the object affect the cache (because
	 * the object would otherwise be passed by reference).
	 */
	function objClone( obj ) {
		/**
		 * A normal `$.extend( {}, obj );` is not suffecient,
		 * it has to be recursive, because the values of this
		 * object are also refererenes to objects.
		 * Consider:
		 * <code>
		 *     var a = { words: [ 'foo', 'bar','baz' ] };
		 *     var b = $.extend( {}, a );
		 *     b.words.push( 'quux' );
		 *     a.words[3]; // quux !
		 * </code>
		 */
		 return $.extend( true /* recursive */, {}, obj );
	}
	function arrClone( arr ) {
		return arr.slice();
	}

	/* Public functions */

	mw.gadgetManager = {

		conf: mw.config.get( 'gadgetManagerConf' ),

		api: {

			/**
			 * Get gadget blob from the API (or from cache if available).
			 *
			 * @param id {String} Gadget id.
			 * @param callback {Function} To be called with an object as first argument,
			 * and status as second argument (success or error).
			 * @return {jqXHR|Null}: Null if served from cache, otherwise the jqXHR.
			 */
			getGadgetMetadata: function( id, callback ) {
				// Check cache
				if ( id in gadgetCache && gadgetCache[id] !== null ) {
					callback( objClone( gadgetCache[id] ), 'success' );
					return null;
				}
				// Get from API if not cached
				return $.ajax({
					url: mw.util.wikiScript( 'api' ),
					data: {
						format: 'json',
						action: 'query',
						list: 'gadgets',
						gaprop: 'id|metadata|desc',
						gaids: id,
						galanguage: mw.config.get( 'wgUserLanguage' )
					},
					type: 'GET',
					dataType: 'json',
					success: function( data ) {
						if ( data && data.query && data.query.gadgets && data.query.gadgets[0] ) {
							data = data.query.gadgets[0].metadata;
							// Update cache
							gadgetCache[id] = data;
							callback( objClone( data ), 'success' );
						} else {
							// Invalidate cache
							gadgetCache[id] = null;
							callback( {}, 'error' );
						}
					},
					error: function() {
						// Invalidate cache
						gadgetCache[id] = null;
						callback( {}, 'error' );
					}
				});
			},

			/**
			 * @param callback {Function} To be called with an array as first argument,
			 * and status as second argument (success or error).
			 * @return {jqXHR|Null}: Null if served from cache, otherwise the jqXHR.
			 */
			getGadgetCategories: function( callback ) {
				// Check cache
				if ( gadgetCategoryCache !== null ) {
					callback( arrClone( gadgetCategoryCache ) );
					return null;
				}
				// Get from API if not cached
				return $.ajax({
					url: mw.util.wikiScript( 'api' ),
					data: {
						format: 'json',
						action: 'query',
						list: 'gadgetcategories',
						gcprop: 'name|title|members',
						gclanguage: mw.config.get( 'wgUserLanguage' )
					},
					type: 'GET',
					dataType: 'json',
					success: function( data ) {
						if ( data && data.query && data.query.gadgetcategories
							&& data.query.gadgetcategories[0] )
						{
							data = data.query.gadgetcategories;
							// Update cache
							gadgetCategoryCache = data;
							callback( arrClone( data ), 'success' );
						} else {
							// Invalidate cache
							gadgetCategoryCache = null;
							callback( [], 'error' );
						}
					},
					error: function() {
						// Invalidate cache
						gadgetCategoryCache = null;
						callback( [], 'error' );
					}
				});
			},

			/**
			 * Creates or edits an existing gadget definition.
			 *
			 * @param gadget {Object}
			 * - id {String} Id of the gadget to modify
			 * - blob {Object} Gadget meta data
			 * @param callback {Function} Called with two arguments:
			 * - status ('ok' or 'error')
			 * - msg (localized, something like "Successful", "Conflict occurred" etc.)
			 * @return {jqXHR|Null}: Null if served from cache, otherwise the jqXHR.
			 */
			doModifyGadget: function( gadget, callback ) {
				mw.log( gadget );
				// @todo
				// Get token
				// JSON.stringify
				// Do with ApiEdit
				// Invalidate cache
				gadgetCache[gadget.id] = null;
				callback( 'error', '@todo: Saving not implemented yet. Check console for object that would be saved.' );
				return null;
			},

			/**
			 * Deletes a gadget definition.
			 *
			 * @param id {String} Id of the gadget to delete.
			 * @param callback {Function} Called with one argument (ok', 'error' or 'conflict').
			 * @return {jqXHR|Null}: Null if served from cache, otherwise the jqXHR.
			 */
			doDeleteGadget: function( id, callback ) {
				// @todo
				// Do with ApiDelete
				// Invalidate cache
				gadgetCache[id] = null;
				callback( 'error' );
				return null;
			}
		}
	};

})( jQuery );
