/**
 * Interface to the API for the gadgets extension.
 *
 * @author Timo Tijhof
 * @author Roan Kattouw
 * @license GNU General Public Licence 2.0 or later
 */

( function ( $, mw ) {
	var
		/**
		 * @var {Object} Keyed by repo, object of gadget objects by id
		 * @example { repoName: { gadgetID: { title: .., metadata: ..}, otherId: { .. } } }
		 */
		gadgetCache = {},
		/**
		 * @var {Object} Keyed by repo, array of category objects
		 * @example { repoName: [ {name: .., title: .., members: .. }, { .. }, { .. } ] }
		 */
		gadgetCategoryCache = {};

	/* Local functions */

	/**
	 * For most returns from api.* functions, a clone is made when data from
	 * cache is used. This is to avoid situations where later modifications
	 * (e.g. by the AJAX editor) to the object affect the cache (because
	 * the object would otherwise be passed by reference).
	 */
	function objClone( obj ) {
		/*
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

	/**
	 * Reformat an array of gadget objects, into an object keyed by the id.
	 * Note: Maintains object reference
	 * @param arr {Array}
	 * @return {Object}
	 */
	function gadgetArrToObj( arr ) {
		for( var obj = {}, i = 0, g = arr[i], len = arr.length; i < len; g = arr[++i] ) {
			obj[g.id] = g;
		}
		return obj;
	}

	/**
	 * Write data to gadgetCache, taking into account that id may be null
	 * and working around JS's annoying refusal to just let us do
	 * var foo = {}; foo[bar][baz] = quux;
	 *
	 * This sets gadgetCache[repoName][id] = data; if id is not null,
	 * or gadgetCache[repoName] = data; if id is null.
	 *
	 * @param repoName {String} Repository name
	 * @param id {String|null} Gadget ID or null
	 * @param data {Object} Data to put in the cache
	 */
	function cacheGadgetData( repoName, id, data ) {
		if ( id === null ) {
			gadgetCache[repoName] = data;
		} else {
			if ( !( repoName in gadgetCache ) ) {
				gadgetCache[repoName] = {};
			}
			gadgetCache[repoName][id] = data;
		}
	}

	/**
	 * Call an asynchronous function for each repository, and merge
	 * their return values into an object keyed by repository name.
	 * @param getter function( success, error, repoName ), called for each repo to get the data
	 * @param success function( data ), called when all data has successfully been retrieved
	 * @param error function( error ), called if one of the getter calls called its error callback
	 */
	function mergeRepositoryData( getter, success, error ) {
		var combined = {}, successes = 0, numRepos = 0, repo, failed = false;
		// Find out how many repos there are
		// Needs to be in a separate loop because we have to have the final number ready
		// before we fire the first potentially (since it could be cached) async request
		for ( repo in mw.gadgets.conf.repos ) {
			numRepos++;
		}

		// Use $.each instead of a for loop so we can access repoName in the success callback
		// without annoying issues
		$.each( mw.gadgets.conf.repos, function ( repoName ) {
			getter(
				function ( data ) {
					combined[repoName] = data;
					if ( ++successes === numRepos ) {
						success( combined );
					}
				}, function ( errorCode ) {
					if ( !failed ) {
						failed = true;
						error( errorCode );
					}
				}, repoName
			);
		} );
	}

	/* Public functions */

	mw.gadgets.api = {
			/**
			 * Get the gadget blobs for all gadgets from all repositories.
			 *
			 * @param success {Function} To be called with an object of arrays of gadget objects,
			 * keyed by repository name, as first argument.
			 * @param error {Function} To be called with a string (error code) as first argument.
			 */
			getForeignGadgetsData: function ( success, error ) {
				mergeRepositoryData(
					function ( s, e, repoName ) { mw.gadgets.api.getGadgetData( null, s, e, repoName ); },
					success, error
				);
			},

			/**
			 * Get the gadget categories from all repositories.
			 *
			 * @param success {Function} To be called with an array
			 * @param success {Function} To be called with an object of arrays of category objects,
			 * keyed by repository name, as first argument.
			 * @param error {Function} To be called with a string (error code) as the first argument.
			 */
			getForeignGadgetCategories: function ( success, error ) {
				mergeRepositoryData( mw.gadgets.api.getGadgetCategories, success, error );
			},

			/**
			 * Get gadget blob from the API (or from cache if available).
			 *
			 * @param id {String|null} Gadget id, or null to get all from the repo.
			 * @param success {Function} To be called with the gadget object or array
			 * of gadget objects as first argument.
			 * @param error {Function} If something went wrong (inexistent gadget, api
			 * error, request error), this is called with error code as first argument.
			 * @param repoName {String} Name of the repository, key in mw.gadgets.conf.repos.
			 * Defaults to 'local'
			 */
			getGadgetData: function ( id, success, error, repoName ) {
				repoName = repoName || 'local';
				// Check cache
				if ( repoName in gadgetCache && gadgetCache[repoName] !== null ) {
					if ( id === null ) {
						success( objClone( gadgetCache[repoName] ) );
						return;
					} else if ( id in gadgetCache[repoName] && gadgetCache[repoName][id] !== null ) {
						success( objClone( gadgetCache[repoName][id] ) );
						return;
					}
				}
				// Get from API if not cached
				var queryData = {
					list: 'gadgets',
					gaprop: 'id|title|desc|metadata|definitiontimestamp',
					galanguage: mw.config.get( 'wgUserLanguage' )
				};
				if ( id !== null ) {
					queryData.gaids = id;
				}
				new mw.Api( { ajax: { url: mw.gadgets.conf.repos[repoName].apiScript, dataType: 'jsonp' } } )
					.get( queryData )
					.done( function ( data ) {
						data = data.query.gadgets;
						if ( id !== null ) {
							data = data[0] || null;
						} else {
							data = gadgetArrToObj( data );
						}
						// Update cache
						cacheGadgetData( repoName, id, data );
						success( objClone( data ) );
					} )
					.fail( function ( code ) {
						// Invalidate cache
						cacheGadgetData( repoName, id, null );
						error( code );
					} );
			},

			/**
			 * Get the gadget categories for a certain repository from the API.
			 *
			 * @param success {Function} To be called with an array as first argument.
			 * @param error {Function} To be called with a string (error code) as first argument.
			 * @param repoName {String} Name of the repository, key in mw.gadgets.conf.repos.
			 * Defaults to 'local'
			 * @return {jqXHR|Null}: Null if served from cache, otherwise the jqXHR.
			 */
			getGadgetCategories: function ( success, error, repoName ) {
				repoName = repoName || 'local';
				// Check cache
				if ( repoName in gadgetCategoryCache && gadgetCategoryCache[repoName] !== null ) {
					success( arrClone( gadgetCategoryCache[repoName] ) );
					return null;
				}
				// Get from API if not cached
				return new mw.Api( { ajax: { url: mw.gadgets.conf.repos[repoName].apiScript, dataType: 'jsonp' } } ).get( {
					list: 'gadgetcategories',
					gcprop: 'name|title|members',
					gclanguage: mw.config.get( 'wgUserLanguage' )
				} ).done( function ( data ) {
					data = data.query.gadgetcategories;
					// Update cache
					gadgetCategoryCache[repoName] = data;
					success( arrClone( data ) );
				} ).fail( function ( code ) {
					// Invalidate cache
					gadgetCategoryCache[repoName] = null;
					error( code );
				} );
			},

			/**
			 * Edits an existing gadget definition.
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
			 * - extraquery {Object} Query parameters to add or overwrite the default.
			 * @return {jqXHR}
			 */
			doModifyGadget: function ( gadget, o ) {
				var t = new mw.Title(
						gadget.id,
						/*jshint camelcase: false*/ mw.config.get( 'wgNamespaceIds' ).gadget_definition
					),
					query = {
						action: 'edit',
						title: t.getPrefixedDb(),
						text: JSON.stringify( gadget.metadata ),
						summary: mw.msg( 'gadgetmanager-comment-modify', gadget.id ),
						token: mw.user.tokens.get( 'editToken' )
					};
				// Optional, only include if passed
				if ( gadget.definitiontimestamp ) {
					query.basetimestamp = gadget.definitiontimestamp;
				}
				if ( o.starttimestamp ) {
					query.starttimestamp = o.starttimestamp;
				}
				// Allow more customization for mw.gadgets.api.doCreateGadget
				if ( o.extraquery ) {
					$.extend( query, o.extraquery );
				}
				return new mw.Api().post( query ).done( function ( data ) {
					// Invalidate cache
					cacheGadgetData( 'local', gadget.id, null );
					if ( data.edit.result === 'Success' ) {
						o.success( data.edit );
					} else {
						o.error( data.edit.result );
					}
				} ).fail( function ( code ) {
					// Invalidate cache
					cacheGadgetData( 'local', gadget.id, null );
					o.error( code );
				} );
			},

			/**
			 * Create a gadget by creating a gadget definition page.
			 * Like mw.gadgets.api.doModifyGadget(), but to create a new gadget.
			 * Will fail if gadget exists already.
			 *
			 * @param gadget {Object}
			 * - id {String}
			 * - metadata {Object}
			 * @param o {Object} Additional options
			 * @return {jqXHR}
			 */
			doCreateGadget: function ( gadget, o ) {
				return mw.gadgets.api.doModifyGadget( gadget, $.extend( o, {
					extraquery: {
						createonly: 1,
						summary: mw.msg( 'gadgetmanager-comment-create', gadget.id )
					}
				} ) );
			},

			/**
			 * Deletes a gadget definition.
			 *
			 * @param id {String} Id of the gadget to delete.
			 * @param callback {Function} Called with one argument (ok', 'error' or 'conflict').
			 * @return {jqXHR}
			 */
			doDeleteGadget: function ( id, success, error ) {
				// @todo ApiDelete
				// Invalidate cache
				cacheGadgetData( 'local', id, null );
				error( '@todo' );
				return null;
			},

			/**
			 * Cache clearing
			 * @return {Boolean}
			 */
			clearGadgetCache: function () {
				gadgetCache = {};
				return true;
			},
			clearCatgoryCache: function () {
				gadgetCategoryCache = {};
				return true;
			}
	};
} )( jQuery, mediaWiki );
