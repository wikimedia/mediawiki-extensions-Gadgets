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
			 * @param success {Function} To be called with the gadget object as first argument.
			 * @param error {Fucntion} If something went wrong (inexisting gadget, api
			 * error, request error), this is called with error code as first argument.
			 * @return {jqXHR|Null}: Null if served from cache, otherwise the jqXHR.
			 */
			getGadgetData: function( id, success, error ) {
				// Check cache
				if ( id in gadgetCache && gadgetCache[id] !== null ) {
					success( objClone( gadgetCache[id] ) );
					return null;
				}
				// Get from API if not cached
				return $.ajax({
					url: mw.util.wikiScript( 'api' ),
					data: {
						format: 'json',
						action: 'query',
						list: 'gadgets',
						gaprop: 'id|title|metadata|definitiontimestamp',
						gaids: id,
						galanguage: mw.config.get( 'wgUserLanguage' )
					},
					type: 'GET',
					dataType: 'json',
					success: function( data ) {
						if ( data && data.query && data.query.gadgets && data.query.gadgets[0] ) {
							data = data.query.gadgets[0];
							// Update cache
							gadgetCache[id] = data;
							success( objClone( data ) );
						} else {
							// Invalidate cache
							gadgetCache[id] = null;
							if ( data && data.error ) {
								error( data.error.code );
							} else {
								error( 'unknown' );
							}
						}
					},
					error: function() {
						// Invalidate cache
						gadgetCache[id] = null;
						error( 'unknown' );
					}
				});
			},

			/**
			 * @param callback {Function} To be called with an array as first argument.
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
							callback( [] );
						}
					},
					error: function() {
						// Invalidate cache
						gadgetCategoryCache = null;
						callback( [] );
					}
				});
			},

			/**
			 * Creates or edits an existing gadget definition.
			 *
			 * @param gadget {Object}
			 * - id {String} Id of the gadget to modify
			 * - metadata {Object} Gadget meta data
			 * @param o {Object} Additional options:
			 * - starttimestamp {String} ISO_8601 timestamp of when user started editing
			 * - success {Function} Called with one argument (API response object of the
			 * 'edit' action)
			 * - error {Function} Called with one argument (status from API if availabe,
			 * otherwise, if the request failed, 'unknown' is given)
			 * @return {jqXHR}
			 */
			doModifyGadget: function( gadget, o ) {
				var t = new mw.Title(
					gadget.id + '.js',
					mw.config.get( 'wgNamespaceIds' ).gadget_definition
				);
				return $.ajax({
					url: mw.util.wikiScript( 'api' ),
					type: 'POST',
					data: {
						format: 'json',
						action: 'edit',
						title: t.getPrefixedDb(),
						text: $.toJSON( gadget.metadata ),
						summary: mw.msg( 'gadgetmanager-comment-modify', gadget.id ),
						token: mw.user.tokens.get( 'editToken' ),
						basetimestamp: gadget.definitiontimestamp,
						starttimestamp: o.starttimestamp
					},
					dataType: 'json',
					success: function( data ) {
						// Invalidate cache
						gadgetCache[gadget.id] = null;

						if ( data && data.edit && data.edit ) {
							if ( data.edit.result === 'Success' ) {
								o.success( data.edit );
							} else {
								o.error( data.edit.result );
							}
						} else if ( data && data.error ) {
							o.error( data.error.code );
						} else {
							o.error( 'unknown' );
						}
					},
					error: function(){
						// Invalidate cache
						gadgetCache[gadget.id] = null;
						o.error( 'unknown' );
					}
				});
			},

			/**
			 * Deletes a gadget definition.
			 *
			 * @param id {String} Id of the gadget to delete.
			 * @param callback {Function} Called with one argument (ok', 'error' or 'conflict').
			 * @return {jqXHR}
			 */
			doDeleteGadget: function( id, success, error ) {
				// @todo ApiDelete
				// Invalidate cache
				gadgetCache[id] = null;
				error( '@todo' );
				return null;
			}
		}
	};

})( jQuery );
