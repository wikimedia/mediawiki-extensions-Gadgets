<?php
/**
 * Gadget repository that gets its gadgets from a foreign wiki using api.php
 * 
 * Options (all of these are MANDATORY):
 * 'source': Name of the source these gadgets are loaded from, as defined in ResourceLoader
 * 'cacheTimeout': Expiry for locally cached data, in seconds (optional; default is 600)
 */
class ForeignAPIGadgetRepo extends CachedGadgetRepo {
	/**
	 * This version string is used in the user agent for requests and will help
	 * server maintainers in identify ForeignAPIGadgetRepo usage.
	 * Update the version every time you make breaking or significant changes.
	 */
	const VERSION = '1.0';
	
	protected $source, $apiURL, $cacheTimeout = 600;
	
	/**
	 * Constructor.
	 * @param $options array See class documentation comment for option details
	 */
	public function __construct( array $options ) {
		global $wgResourceLoaderSources;
		parent::__construct( $options );
		
		$this->source = $options['source'];
		$this->apiURL = $wgResourceLoaderSources[$this->source]['apiScript'];
		if ( isset( $options['cacheTimeout'] ) ) {
			$this->cacheTimeout = $options['cacheTimeout'];
		}
	}
	
	/*** Protected methods inherited from CachedGadgetRepo ***/
	
	protected function loadAllData() {
		$apiResult = $this->doAPIRequest( null );
		if ( is_array( $apiResult ) ) {
			return $this->reformatAPIResult( $apiResult );
		} else {
			// TODO what do we do with the error message? loadAllData() doesn't have a facility for this
			return array();
		}
	}
	
	protected function loadDataFor( $id ) {
		$apiResult = $this->doAPIRequest( $id );
		if ( is_array( $apiResult ) ) {
			$formatted = $this->reformatAPIResult( $apiResult );
			return $formatted[0];
		} else {
			// TODO what do we do with the error message? loadAllData() doesn't have a facility for this
			return array();
		}
	}
	
	protected function getCacheKey( $id ) {
		// No access to the foreign wiki's memc, so cache locally
		if ( $id === null ) {
			return wfMemcKey( 'gadgets', 'foreignapirepoids', $this->source );
		} else {
			return wfMemcKey( 'gadgets', 'foreignapirepodata', $this->source, $id );
		}
	}
	
	protected function getCacheExpiry( $id ) {
		return $this->cacheTimeout;
	}
	
	/*** Protected methods ***/
	
	/**
	 * Obtain a gadget list using HTTP to call api.php
	 * @param $id string ID of the gadget to obtain data for, or null to obtain data for all gadgets
	 * @return array|Status The decoded JSON response from the API (array), or a Status object on failure
	 */
	protected function doAPIRequest( $id ) {
		$query = array(
			'format' => 'json',
			'action' => 'query',
			'list' => 'gadgets',
			'gaprop' => 'id|metadata|definitiontimestamp',
		);
		if ( $id !== null ) {
			$query['gaids'] = $id;
		}
		$url = wfAppendQuery( $this->apiURL, $query );
		
		$options = array( 'timeout' => 'default', 'method' => 'GET' );
		$req = MWHttpRequest::factory( $url, $options );
		$req->setUserAgent( Http::userAgent() . " ForeignAPIGadgetRepo/" . self::VERSION );
		$status = $req->execute();
		
		if ( $status->isOK() ) {
			return FormatJson::decode( $req->getContent(), true );
		} else {
			return $status;
		}
	}
	
	/**
	 * Reformat a response from the API into the format that loadAllData()
	 * is supposed to return.
	 * @param $apiResponse array
	 * @return array of gadgets keyed by ID.
	 */
	protected function reformatAPIResult( $apiResult ) {
		if ( !isset( $apiResult['query']['gadgets'] ) ) {
			return array();
		}
		$gadgets = $apiResult['query']['gadgets'];
		if ( !is_array( $gadgets ) ) {
			return array();
		}
		
		$retval = array();
		foreach ( $gadgets as $gadget ) {
			$retval[$gadget['id']] = array(
				'json' => FormatJson::encode( $gadget['metadata'] ),
				'timestamp' => $gadget['definitiontimestamp']
			);
		}
		return $retval;
	}
	
	/*** Public methods inherited from GadgetRepo ***/
	
	public function getSource() {
		return $this->source;
	}
	
	public function isWriteable() {
		return false;
	}
	
	public function getDB() {
		return null;
	}
	
	public function modifyGadget( Gadget $gadget, $timestamp = null ) {
		return Status::newFatal( 'gadget-manager-readonly-repository' );
	}
	
	public function deleteGadget( $id ) {
		return Status::newFatal( 'gadget-manager-readonly-repository' );
	}
}
