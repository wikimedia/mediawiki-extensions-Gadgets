/**
 * JavaScript to initialize the UI of the gadget manager.
 *
 * @author Timo Tijhof
 * @copyright © 2011 Timo Tijhof
 * @license GNU General Public Licence 2.0 or later
 */
( function( $ ) {

	var
		/**
		 * @var {Object} Local alias to mw.gadgets
		 */
		ga = mw.gadgets,
		/**
		 * @var {Object} HTML fragements
		 */
		tpl = {
			fancyForm: '<form class="mw-gadgetmanager-form">\
					<fieldset>\
						<legend><html:msg key="gadgetmanager-propsgroup-module" /></legend>\
						<table>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-scripts"><html:msg key="gadgetmanager-prop-scripts" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-scripts" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-styles"><html:msg key="gadgetmanager-prop-styles" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-styles" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-dependencies"><html:msg key="gadgetmanager-prop-dependencies" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-dependencies" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-messages"><html:msg key="gadgetmanager-prop-messages" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-messages" /></td>\
							</tr>\
						</table>\
					</fieldset>\
					<fieldset>\
						<legend><html:msg key="gadgetmanager-propsgroup-settings" /></legend>\
						<table>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-category"><html:msg key="gadgetmanager-prop-category" /></label></td>\
								<td><select id="mw-gadgetmanager-input-category"></select></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-rights"><html:msg key="gadgetmanager-prop-rights" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-rights" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-default"><html:msg key="gadgetmanager-prop-default" /></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-default" /></td>\
							</tr>\
							<tr>\
								<td><label for="mw-gadgetmanager-input-hidden"><html:msg key="gadgetmanager-prop-hidden"></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-hidden" /></td>\
							</tr>\
							' + ( ga.conf.enableSharing ? '<tr>\
								<td><label for="mw-gadgetmanager-input-shared"><html:msg key="gadgetmanager-prop-shared" /></label></td>\
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
		 suggestCacheRights = ga.conf.allRights,
		/**
		 * @var {Number} Maximum number of autocomplete suggestions in the gadget editor input fields.
		 */
		 suggestLimit = 7;

	/* Local functions */

	/**
	 * Utility function to pad a zero
	 * to single digit number. Used by ISODateString().
	 * @param n {Number}
	 * @return {String}
	 */
	function pad( n ) {
		return n < 10 ? '0' + n : n;
	}
	/**
	 * Format a date in an ISO 8601 format using UTC.
	 * https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Date#Example:_ISO_8601_formatted_dates
	 *
	 * @param d {Date}
	 * @return {String}
	 */
	function ISODateString( d ) {
		return d.getUTCFullYear() + '-'
		+ pad( d.getUTCMonth() + 1 ) + '-'
		+ pad( d.getUTCDate() ) + 'T'
		+ pad( d.getUTCHours() ) + ':'
		+ pad( d.getUTCMinutes() ) + ':'
		+ pad( d.getUTCSeconds() ) + 'Z';
	}

	/* Public functions */

	ga.ui = {
		/**
		 * Initializes the the page. For now just binding click handlers
		 * to the anchor tags in the table.
		 */
		initUI: function() {
			// Bind trigger to the links
			$( '.mw-gadgetmanager-gadgets .mw-gadgetmanager-gadgets-title a' )
				.click( function( e ) {
					e.preventDefault();
					ga.ui.startGadgetEditor( $( this ).data( 'gadget-id' ) );
				});
		},

		/**
		 * Initialize the gadget editor dialog.
		 *
		 * @asynchronous
		 * @param gadgetId {String}
		 */
		startGadgetEditor: function( gadgetId ) {
			// Ad hoc multi-loader. We need both requests, which are asynchronous,
			// to be complete. Which ever finishes first will set the local variable
			// to it's return value for the other callback to use.
			// @todo Notification: In case of an 'error'.
			var gadget, cats;

			ga.api.getGadgetCategories( function( ret ) {
				if ( gadget ) {
					// getGadgetData already done
					return ga.ui.showFancyForm( gadget, ret );
				}
				// getGadgetData not done yet, leave cats for it's callback to use
				cats = ret;
			});

			ga.api.getGadgetData( gadgetId, function( ret ) {
				if ( cats ) {
					// getGadgetCategories already done
					return ga.ui.showFancyForm( ret, cats );
				}
				// getGadgetCategories not done yet, leave gadget for it's callback to use
				gadget = ret;
			});
		},

		/**
		 * Generate form, create a dialog and open it into view.
		 *
		 * @param gadget {Object} Gadget object of the gadget to be modified.
		 * @param categories {Array} Gadget categories.
		 * @return {jQuery} The (dialogged) form.
		 */
		showFancyForm: function( gadget, categories ) {
			var	$form = ga.ui.getFancyForm( gadget.metadata, categories ),
				buttons = {};

			// Form submit
			buttons[mw.msg( 'gadgetmanager-editor-save' )] = function() {
				ga.api.doModifyGadget( gadget, {
					starttimestamp: ISODateString( new Date() ),
					success: function( response ) {
						$form.dialog( 'close' );
						window.location.reload();
					},
					error: function( error ) {
						mw.log( 'mw.gadgets.api.doModifyGadget: error', error );
						// @todo Notification: $formNotif.add( .. );
					}
				});
			};

			return $form
				.dialog({
					autoOpen: true,
					width: 800,
					modal: true,
					draggable: false,
					resizable: false,
					title: mw.message( 'gadgetmanager-editor-title', gadget.title ).escaped(),
					buttons: buttons,
					open: function() {
						// Dialog is ready for action.
						// Push out any notifications if some were queued up already between
						// getting the gadget data and the display of the form.

						// @todo Notification: $formNotif.add( .. );
					}
				});
		},

		/**
		 * Generate a <form> for the given module.
		 * Also binds events for submission and autocompletion.
		 *
		 * @param metadata {Object} Object to read and write to, used when saving
		 * the gadget metadata back through the API.
		 * @param categories {Array} Gadget categories.
		 * @return {jQuery} The form.
		 */
		 getFancyForm: function( metadata, categories ) {
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
								var suggestions = json.query.gadgetpages.splice( 0, suggestLimit );
								suggestions = $.map( suggestions, function( val, i ) {
									return val.pagename;
								});

								// Update cache
								suggestCacheScripts[data.term] = suggestions;

								response( suggestions );
							} else {
								response( [] );
							}
						}
					);
				},
				prefix: 'mw-gadgetmanager-',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' )
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
				prefix: 'mw-gadgetmanager-',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' )
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
				prefix: 'mw-gadgetmanager-',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' )
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
				prefix: 'mw-gadgetmanager-',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' )
			});

			// Gadget settings: category
			$form.find( '#mw-gadgetmanager-input-category' ).append( function() {
				var	current = metadata.settings.category,
					opts = '',
					i = 0,
					cat;
				for ( ; i < categories.length; i++ ) {
					cat = categories[i];
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
				prefix: 'mw-gadgetmanager-',
				removeTooltip: mw.msg( 'gadgetmanager-editor-removeprop-tooltip' )
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
	$( document ).ready( ga.ui.initUI );

})( jQuery );
