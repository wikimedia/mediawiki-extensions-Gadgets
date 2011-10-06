/**
 * Initialize the mw.gadgets object
 */
(function() {

	mw.gadgets = {
		/**
		 * @todo: Add something derived from $wgGadgetRepositories to gadgetsConf
		 * ... + repos: { local: { apiScript: .. }, awesomeRepo: { .. }, .. }
		 */
		conf: mw.config.get( 'gadgetsConf' )
	};

})();
