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
					<div class="mw-gadgetmanager-id-wrapper">\
						<label for="mw-gadgetmanager-input-id"><html:msg key="gadgetmanager-prop-id" /><html:msg key="colon-separator" /></label>\
						<span class="mw-gadgetmanager-id"><input type="text" id="mw-gadgetmanager-input-id" /></span>\
						<div class="mw-gadgetmanager-id-errorbox"></div>\
					</div>\
					<fieldset>\
						<legend><html:msg key="gadgetmanager-propsgroup-module" /></legend>\
						<table>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-scripts"><html:msg key="gadgetmanager-prop-scripts" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-scripts" /></td>\
							</tr>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-styles"><html:msg key="gadgetmanager-prop-styles" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-styles" /></td>\
							</tr>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-dependencies"><html:msg key="gadgetmanager-prop-dependencies" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-dependencies" /></td>\
							</tr>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-messages"><html:msg key="gadgetmanager-prop-messages" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-messages" /></td>\
							</tr>\
						</table>\
					</fieldset>\
					<fieldset>\
						<legend><html:msg key="gadgetmanager-propsgroup-settings" /></legend>\
						<table>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-category"><html:msg key="gadgetmanager-prop-category" /></label></td>\
								<td><select id="mw-gadgetmanager-input-category"></select></td>\
							</tr>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-rights"><html:msg key="gadgetmanager-prop-rights" /></label></td>\
								<td><input type="text" id="mw-gadgetmanager-input-rights" /></td>\
							</tr>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-default"><html:msg key="gadgetmanager-prop-default" /></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-default" /></td>\
							</tr>\
							<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-hidden"><html:msg key="gadgetmanager-prop-hidden"></label></td>\
								<td><input type="checkbox" id="mw-gadgetmanager-input-hidden" /></td>\
							</tr>\
							' + ( ga.conf.enableSharing ? '<tr>\
								<td class="mw-gadgetmanager-label"><label for="mw-gadgetmanager-input-shared"><html:msg key="gadgetmanager-prop-shared" /></label></td>\
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
	 * @return {String|Number}
	 */
	function pad( n ) {
		return n < 10 ? '0' + n : n;
	}
	/**
	 * Format a date in an ISO 8601 format using UTC.
	 * https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Date#Example:_ISO_8601
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
	/**
	 * Validate a gadget id, which must be a valid
	 * title, as well as a valid module name.
	 * @param id {String}
	 * @return {Boolean}
	 */
	function validateGadgetId( id ) {
		return id.length
			&& new mw.Title( id, mw.config.get( 'wgNamespaceIds' ).gadget_definition ).getMainText() === id;
	}
	/**
	 * Toggle the state of the UI buttons in a dialog.
	 * @param $dialog {jQuery.ui.widget from jquery.ui.dialog}
	 * @param state {String} 'enable' or 'disable' (defaults to disable)
	 */
	function toggleDialogButtons( $form, state ) {
		$form.dialog( 'widget' ).find( 'button' ).button( state );
	}

	/* Public functions */

	ga.ui = {
		/**
		 * Initializes the the page. For now just binding click handlers
		 * to the anchor tags in the table.
		 */
		initUI: function() {
			// Add ajax links
			$( '.mw-gadgets-gadgetlinks' ).each( function( i, el ) {
				var $el = $( el );
				if ( ga.conf.userIsAllowed['gadgets-definition-edit'] ) {
					$el.find( '.mw-gadgets-modify' ).click( function( e ) {
						e.preventDefault();
						e.stopPropagation(); // To stop bubbling up to .mw-gadgets-gadget
						ga.ui.startGadgetManager( 'modify', $el.data( 'gadget-id' ) );
					});
				}
				if ( ga.conf.userIsAllowed['gadgets-definition-delete'] ) {
					$el.find( '.mw-gadgets-delete' ).click( function( e ) {
						//e.preventDefault();
						//e.stopPropagation();
						// @todo: Show delete action form
					});
				}
			} );

			// Entire gadget list item is clickable
			$( '.mw-gadgets-gadget' ).click( function( e ) {
				e.preventDefault();
				e.stopPropagation();
				var	t,
					id = $( this ).data( 'gadget-id' );

				if ( ga.conf.userIsAllowed['gadgets-definition-edit'] ) {
					ga.ui.startGadgetManager( id );
					return;
				}
				// Use localized page name if possible to avoid redirect
				if ( mw.config.get( 'wgCanonicalSpecialPageName' ) === 'Gadgets' ) {
					t = new mw.Title( mw.config.get( 'wgTitle' ) + '/' + id, -1 );
				} else {
					t = new mw.Title( 'Gadgets/' + id, -1 );
				}
				window.location.href = t.getUrl();

			} ).find( 'a' ).click( function( e ) {
				// Avoid other links from becoming unclickable,
				// Don't let clicks on those bubble up
				e.stopPropagation();
			});

			if ( ga.conf.userIsAllowed['gadgets-definition-create'] ) {
				var createTab = mw.util.addPortletLink(
					// Not all skins use the new separated tabs yet,
					// Fall back to the general 'p-cactions'.
					$( '#p-views' ).length ? 'p-views' : 'p-cactions',
					'#',
					mw.msg( 'gadgets-gadget-create' ),
					'ca-create', // Use whatever core has for pages ? Or use gadget-create ?
					mw.msg( 'gadgets-gadget-create-tooltip' ),
					'e' // Same as core for ca-edit
				);
				$( createTab ).click( function( e ) {
					e.preventDefault();
					ga.ui.startGadgetManager( 'create' );
				} );
			}

		},

		/**
		 * Initialize the gadget manager dialog.
		 *
		 * @asynchronous
		 * @param mode {String} (See mw.gadgets.ui.getFancyForm)
		 * @param gadgetId {String}
		 */
		startGadgetManager: function( mode, gadgetId ) {

			// Ad hoc multi-loader. We need both requests, which are asynchronous,
			// to be complete. Which ever finishes first will set the local variable
			// to it's return value for the other callback to use.
			// @todo Notification: In case of an 'error'.
			var gadget, cats;


			if ( mode === 'create' ) {
				// New gadget, no need to query the api
				gadget = {
					id: undefined,
					metadata: {
						settings: {
							rights: [],
							'default': false,
							hidden: false,
							shared: false,
							category: ''
						},
						module: {
							scripts: [],
							styles: [],
							dependencies: [],
							messages: []
						}
					},
					definitiontimestamp: undefined,
					title: undefined
				};
			} else {
				ga.api.getGadgetData( gadgetId, function( ret ) {
					if ( cats ) {
						// getGadgetCategories already done
						return ga.ui.showFancyForm( ret, cats, mode );
					}
					// getGadgetCategories not done yet, leave gadget for it's callback to use
					gadget = ret;
				});
			}

			ga.api.getGadgetCategories( function( ret ) {
				if ( gadget ) {
					// getGadgetData already done
					return ga.ui.showFancyForm( gadget, ret, mode );
				}
				// getGadgetData not done yet, leave cats for it's callback to use
				cats = ret;
			});
		},

		/**
		 * Generate form, create a dialog and open it into view.
		 *
		 * @param gadget {Object} Gadget object of the gadget to be modified.
		 * @param categories {Array} Gadget categories.
		 * @param mode {String} (See mw.gadgets.ui.getFancyForm)
		 * @return {jQuery} The (dialogged) form.
		 */
		showFancyForm: function( gadget, categories, mode ) {
			var	$form = ga.ui.getFancyForm( gadget, categories, mode ),
				buttons = {};

			// Form submit
			buttons[mw.msg( 'gadgetmanager-editor-save' )] = function() {
				if ( mode === 'create' ) {
					ga.api.doCreateGadget( gadget, {
						success: function( response ) {
							mw.log( 'mw.gadgets.api.doModifyGadget: success', arguments );
							$form.dialog( 'close' );
							window.location.reload();
						},
						error: function( error ) {
							mw.log( 'mw.gadgets.api.doModifyGadget: error', arguments );
							// @todo Notification: $formNotif.add( .. );
						}
					});
				} else {
					ga.api.doModifyGadget( gadget, {
						starttimestamp: ISODateString( new Date() ),
						success: function( response ) {
							mw.log( 'mw.gadgets.api.doModifyGadget: success', arguments );
							$form.dialog( 'close' );
							window.location.reload();
						},
						error: function( error ) {
							mw.log( 'mw.gadgets.api.doModifyGadget: error', arguments );
							// @todo Notification: $formNotif.add( .. );
						}
					});
				}
			};

			return $form
				.dialog({
					autoOpen: true,
					width: 800,
					modal: true,
					draggable: false,
					resizable: false,
					title: mode === 'create'
						? mw.message( 'gadgetmanager-editor-title-creating' ).escaped()
						: mw.message( 'gadgetmanager-editor-title-editing', gadget.title ).escaped(),
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
		 * @param gadget {Object} Gadget object to read from and write to, used when saving
		 * the gadget metadata to the API.
		 * @param categories {Array} Gadget categories.
		 * @param mode {String} (optional) 'create' or 'modify' (defaults to 'modify')
		 * @return {jQuery} The form.
		 */
		 getFancyForm: function( gadget, categories, mode ) {
			var	nsGadgetId = mw.config.get( 'wgNamespaceIds' ).gadget,
				metadata = gadget.metadata,
				$form = $( tpl.fancyForm ).localize(),
				$idSpan = $form.find( '.mw-gadgetmanager-id' ),
				$idErrMsg = $form.find( '.mw-gadgetmanager-id-errorbox' );

			if ( mode === 'create' ) {

				// Validate
				$form.find( '#mw-gadgetmanager-input-id' ).bind( 'keyup keypress keydown', function( e ) {
					var	val = $(this).val();

					// Reset
					toggleDialogButtons( $form, 'enable' );
					$idSpan.removeClass( 'mw-gadgetmanager-id-error mw-gadgetmanager-id-available' );

					// Abort if empty, don't warn when user is still typing,
					// The onblur event handler takes care of that
					if ( !val.length ) {
						$idErrMsg.hide(); // Just in case
						return;
					}

					// Auto-correct trim if needed (leading/trailing spaces are invalid)
					// No need to raise errors just for that.
					if ( $.trim( val ) !== val ) {
						val = $.trim( val );
						$el.val( val );
					}

					if ( validateGadgetId( val ) ) {
						gadget.id = val;
						$idErrMsg.hide();
					} else {
						toggleDialogButtons( $form, 'disable' );
						$idSpan.addClass( 'mw-gadgetmanager-id-error' );
						$idErrMsg.text( mw.msg( 'gadgetmanager-prop-id-error-illegal' ) ).show();
					}

				// Availability and non-empty check
				}).blur( function( e ) {
					var val = $(this).val();

					// Reset
					$idSpan.removeClass( 'mw-gadgetmanager-id-error mw-gadgetmanager-id-available' );
					toggleDialogButtons( $form, 'enable' );

					if ( !val.length ) {
						toggleDialogButtons( $form, 'disable' );
						$idSpan.addClass( 'mw-gadgetmanager-id-error' );
						$idErrMsg.text( mw.msg( 'gadgetmanager-prop-id-error-blank' ) ).show();
						return;
					}

					// Validity check here as well to avoid
					// saying 'available' to an invalid  id.
					if ( !validateGadgetId( val ) ) {
						toggleDialogButtons( $form, 'disable' );
						$idSpan.addClass( 'mw-gadgetmanager-id-error' );
						$idErrMsg.text( mw.msg( 'gadgetmanager-prop-id-error-illegal' ) ).show();
						return;
					}

					ga.api.clearGadgetCache();

					// asynchronous from here, show loading
					$idSpan.addClass( 'loading' );

					ga.api.getGadgetData( null, function( data ) {
						$idSpan.removeClass( 'loading' );
						if ( val in data ) {
							toggleDialogButtons( $form, 'disable' );
							$idSpan.addClass( 'mw-gadgetmanager-id-error' );
							$idErrMsg.text( mw.msg( 'gadgetmanager-prop-id-error-taken' ) ).show();
						} else {
							$idSpan.addClass( 'mw-gadgetmanager-id-available' );
							$idErrMsg.hide();
						}
					});
				});


			} else {
				$form.find( '.mw-gadgetmanager-id input' ).val( gadget.id ).prop( 'disabled', true );
				$idSpan.addClass( 'disabled' );
			}


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
