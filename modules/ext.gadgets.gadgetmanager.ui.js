/**
 * JavaScript to initialize the UI of the gadget manager.
 *
 * @author Timo Tijhof
 * @copyright © 2011 Timo Tijhof
 * @license GNU General Public Licence 2.0 or later
 */
( function() {

	var
		/**
		 * @var {Object} Local alias to gadgetmananger
		 */
		gm = mw.gadgetManager,
		/**
		 * @var {Object} HTML fragements
		 */
		tpl = {
			fancyForm: '<form class="mw-gadgetmanager-form">\
					<fieldset>\
						<legend>Module properties</legend>\
						<table>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-scripts"><html:msg key="gadgetmanager-prop-scripts"></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-scripts" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-styles"><html:msg key="gadgetmanager-prop-styles"></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-styles" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-dependencies"><html:msg key="gadgetmanager-prop-dependencies"></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-dependencies" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-messages"><html:msg key="gadgetmanager-prop-messages"></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-messages" /></td>\
							</tr>\
						</table>\
					</fieldset>\
					<fieldset>\
						<legend>Gadget settings</legend>\
						<table>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-category"><html:msg key="gadgetmanager-prop-category"></label></td>\
								<td><select id="mw-gadgetmanager-input-category"></select></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-rights"><html:msg key="gadgetmanager-prop-rights"></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-rights" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-default"><html:msg key="gadgetmanager-prop-default"></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-default" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-hidden"><html:msg key="gadgetmanager-prop-hidden"></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-hidden" /></td>\
							</tr>\
							' + ( gm.conf.enableSharing ? '<tr>\
								<td><label for="mw-gadgetmanager-input-shared"><html:msg key="gadgetmanager-prop-shared"></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-shared" /></td>\
							</tr>\
						' : '' ) + '</table>\
					</fieldset>\
				</form>'
		},
		/**
		 * @var {Object} Static cache for suggestions by script prefix.
		 */
		 suggestCacheScripts = {},
		/**
		 * @var {Object} Static cache for suggestions by style prefix.
		 */
		 suggestCacheStyles = {},
		/**
		 * @var {Object} Static cache for suggestions by messages prefix.
		 */
		 suggestCacheMsgs = {},
		/**
		 * @var {Object} Complete static cache for module names. Lazy loaded from null.
		 */
		 suggestCacheDependencies = null,
		/**
		 * @var {Object} Complete static cache for all rights.
		 */
		 suggestCacheRights = gm.conf.allRights,
		/**
		 * @var {Number} Maximum number of autocomplete suggestions in the gadget editor input fields.
		 */
		 suggestLimit = 7,
		 /**
		  * @var {Array} List of category objects with their name, localized title and member count.
		  */
		 gadgetCategoriesCache = [];

	/* Local functions */

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

	/* Public functions */

	gm.ui = {
		/**
		 * Initializes the the page. For now just binding click handlers
		 * to the anchor tags in the table.
		 */
		initUI: function() {
			// Bind trigger to the links
			$( '.mw-gadgetmanager-gadgets .mw-gadgetmanager-gadgets-title a' )
				.click( function( e ) {
					e.preventDefault();
					var $el = $( this );
					var gadget = {
						id: $el.data( 'gadgetname' ),
						displayTitle: $el.text(),
						metadata: null
					};
					gm.ui.startGadgetEditor( gadget );
				});
		},

		/**
		 * Initialize the gadget editor dialog.
		 *
		 * @asynchronous
		 * @param id {String}
		 * @param displayTitle {String}
		 */
		startGadgetEditor: function( gadget ) {
			// We need two things. Gadget meta-data and category information.
			var done = 0, ready = 2;

			gm.api.getGadgetMetadata( gadget.id, function( metadata, status ) {
				// @todo Notification: If status is 'error'
				gadget.metadata = metadata;
				done++;
				if ( done >= ready ) {
					gm.ui.showFancyForm( gadget );
				}
			});

			gm.api.getGadgetCategories( function( cats ) {
				gadgetCategoriesCache = cats;
				done++;
				if ( done >= ready ) {
					gm.ui.showFancyForm( gadget );
				}
			});
		},

		/**
		 * Generate form, create a dialog and open it into view.
		 *
		 * @param gadget {Object}
		 * @return {jQuery} The (dialogged) form.
		 */
		showFancyForm: function( gadget ) {
			var $form = gm.ui.getFancyForm( gadget.metadata );
			var buttons = {};
			buttons[mw.msg( 'gadgetmanager-editor-save' )] = function() {
				gm.api.doModifyGadget( gadget, function( status, msg ) {
					alert( "Save result: \n- status: " + status + "\n- msg: " + msg );
					/* @todo Notification
					addNotification( {
						msg: msg,
						type: status !== 'error' ? 'success' : status,
						timedActionDelay: 5,
						timedAction: function(){
							// refresh page
						}
					});
					*/
				});
			};
			return $form
				.dialog({
					autoOpen: true,
					width: 800,
					modal: true,
					draggable: false,
					resizable: false,
					title: mw.message( 'gadgetmanager-editor-title', gadget.displayTitle ).escaped(),
					buttons: buttons,
					open: function() {
						// Dialog is ready for action.
						// Push out any notifications if some were queued up already between
						// getting the gadget data and the display of the form.
						/* @todo Notification
						if ( gm.ui.notifications.length ) {
							for ( in ) {
								slice(i,1)_remove;
								gm.ui.addNotification( $form, n[i] );
							}
						}
						*/
					}
				});
		},

		/**
		 * Generate a <form> for the given module.
		 * Also binds events for submission and autocompletion.
		 *
		 * @param metadata {Object} Object to read and write to, used when saving
		 * the gadget metadata back through the API.
		 * @return {jQuery} The form.
		 */
		 getFancyForm: function( metadata ) {
			var	nsGadgetId = mw.config.get( 'wgNamespaceIds' ).gadget,
				$form = $( tpl.fancyForm ).localize();

			// Module properties: scripts
			$form.find( '#mw-gadgetmanager-input-scripts' ).createPropCloud({
				props: metadata.module.scripts,
				autocompleteSource: function( data, response ) {
					// Use cache if available
					if ( data.term in suggestCacheScripts ) {
						response( suggestCacheScripts[data.term] );
						return;
					}
					$.getJSON( mw.util.wikiScript( 'api' ), {
							format: 'json',
							action: 'query',
							list: 'gadgetpages',
							gpnamespace: nsGadgetId,
							gpextension: 'js',
							gpprefix: data.term
						}, function( json ) {
							if ( json && json.query && json.query.gadgetpages ) {
								var suggestions = $.map( json.query.gadgetpages, function( val, i ) {
									return val.pagename;
								});
								suggestions = suggestions.splice( 0, suggestLimit );

								// Update cache
								suggestCacheScripts[data.term] = suggestions;

								response( suggestions );
							} else {
								response( [] );
							}
						}
					);
				},
				prefix: 'mw-gadgetmanager',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' ),
				onAdd: function( prop ) { metadata.module.scripts.push( prop ); },
				onRemove: function( prop ) { arrayRemove( metadata.module.scripts, prop ); }
			});

			// Module properties: styles
			$form.find( '#mw-gadgetmanager-input-styles' ).createPropCloud({
				props: metadata.module.styles,
				autocompleteSource: function( data, response ) {
					// Use cache if available
					if ( data.term in suggestCacheStyles ) {
						response( suggestCacheStyles[data.term] );
						return;
					}
					$.getJSON( mw.util.wikiScript( 'api' ), {
							format: 'json',
							action: 'query',
							list: 'gadgetpages',
							gpnamespace: nsGadgetId,
							gpextension: 'css',
							gpprefix: data.term
						}, function( json ) {
							if ( json && json.query && json.query.gadgetpages ) {
								var suggestions = $.map( json.query.gadgetpages, function( val, i ) {
									return val.pagename;
								});
								suggestions = suggestions.splice( 0, suggestLimit );

								// Update cache
								suggestCacheStyles[data.term] = suggestions;

								response( suggestions );
							} else {
								response( [] );
							}
						}
					);
				},
				prefix: 'mw-gadgetmanager',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' ),
				onAdd: function( prop ) { metadata.module.styles.push( prop ); },
				onRemove: function( prop ) { arrayRemove( metadata.module.styles, prop ); }
			});

			// Module properties: dependencies
			$form.find( '#mw-gadgetmanager-input-dependencies' ).createPropCloud({
				props: metadata.module.dependencies,
				autocompleteSource: function( data, response ) {
					if ( suggestCacheDependencies === null ) {
						suggestCacheDependencies = mw.loader.getModuleNames();
					}
					var output = $.ui.autocomplete.filter( suggestCacheDependencies, data.term );
					response( output.slice( 0, suggestLimit ) );
				},
				prefix: 'mw-gadgetmanager',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' ),
				onAdd: function( prop ) { metadata.module.dependencies.push( prop ); },
				onRemove: function( prop ) { arrayRemove( metadata.module.dependencies, prop ); }
			});

			// Module properties: messages
			$form.find( '#mw-gadgetmanager-input-messages' ).createPropCloud({
				props: metadata.module.messages,
				autocompleteSource: function( data, response ) {
					// Use cache if available
					if ( data.term in suggestCacheMsgs ) {
						response( suggestCacheMsgs[data.term] );
						return;
					}
					$.getJSON( mw.util.wikiScript( 'api' ), {
							format: 'json',
							action: 'query',
							meta: 'allmessages',
							amprefix: data.term,
							amnocontent: true,
							amlang: mw.config.get( 'wgContentLanguage' )
						}, function( json ) {
							if ( json && json.query && json.query.allmessages ) {
								var suggestions = $.map( json.query.allmessages, function( val, i ) {
									return val.name;
								});
								suggestions = suggestions.splice( 0, suggestLimit );

								// Update cache
								suggestCacheMsgs[data.term] = suggestions;

								response( suggestions );
							} else {
								response( [] );
							}
						}
					);
				},
				prefix: 'mw-gadgetmanager',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' ),
				onAdd: function( prop ) { metadata.module.messages.push( prop ); },
				onRemove: function( prop ) { arrayRemove( metadata.module.messages, prop ); }
			});

			// Gadget settings: category
			$form.find( '#mw-gadgetmanager-input-category' ).append( function() {
				var	current = metadata.settings.category,
					opts = '',
					i = 0,
					cat;
				for ( ; i < gadgetCategoriesCache.length; i++ ) {
					cat = gadgetCategoriesCache[i];
					opts += mw.html.element( 'option', {
						value: cat.name,
						selected: cat.name === current
					}, cat.title );
				}
				return opts;
			}).change( function() {
				metadata.settings.category = $(this).val();
			});

			// Gadget settings: rights
			$form.find( '#mw-gadgetmanager-input-rights' ).createPropCloud({
				props: metadata.settings.rights,
				autocompleteSource: function( data, response ) {
					var output = $.ui.autocomplete.filter( suggestCacheRights, data.term );
					response( output.slice( 0, suggestLimit ) );
				},
				prefix: 'mw-gadgetmanager',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' ),
				onAdd: function( prop ) { metadata.settings.rights.push( prop ); },
				onRemove: function( prop ) { arrayRemove( metadata.settings.rights, prop ); }
			});

			// Gadget settings: Default
			$form.find( '#mw-gadgetmanager-input-default' )
				.prop( 'checked', metadata.settings['default'] )
				.change( function() {
					metadata.settings['default'] = this.checked;
				});

			// Gadget settings: Hidden
			$form.find( '#mw-gadgetmanager-input-hidden' )
				.prop( 'checked', metadata.settings.hidden )
				.change( function() { metadata.settings.hidden = this.checked; });

			// Gadget settings: Shared
			$form.find( '#mw-gadgetmanager-input-shared' )
				.prop( 'checked', metadata.settings.shared )
				.change( function() { metadata.settings.shared = this.checked; });


			return $form;
		}
	};

	// Launch on document ready
	$( document ).ready( gm.ui.initUI );

})();
