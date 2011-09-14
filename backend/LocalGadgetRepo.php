<?php
/**
 * Gadget repository that gets its gadgets from the local database.
 */
class LocalGadgetRepo extends GadgetRepo {
	protected $data = array();
	protected $namesLoaded = false;
	
	/** Memcached key of the gadget names list. Subclasses may override this in their constructor.
	  * This could've been a static member if we had PHP 5.3's late static binding.
	  */
	protected $namesKey;
	
	/*** Public static methods ***/
	
	/**
	 * Get the instance of this class
	 * @return LocalGadgetRepo
	 */
	public static function singleton() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new self;
		}
		return $instance;
	}
	
	/*** Public methods inherited from GadgetRepo ***/
	
	/**
	 * Constructor.
	 * @param info array of options. There are no applicable options for this class
	 */
	public function __construct( array $options = array() ) {
		parent::__construct( $options );
		$this->namesKey = $this->getMemcKey( 'gadgets', 'localreponames' );
	}
	
	public function getGadgetIds() {
		$this->loadIDs();
		return array_keys( $this->data );
	}
	
	public function getGadget( $id ) {
		$data = $this->loadDataFor( $id );
		if ( !$data ) {
			return null;
		}
		return new Gadget( $id, $this, $data['json'], $data['timestamp']  );
	}
	
	public function getSource() {
		return 'local';
	}
	
	public function clearInObjectCache() {
		$this->data = null;
	}
	
	public function isWriteable() {
		return true;
	}
	
	public function modifyGadget( Gadget $gadget, $timestamp = null ) {
		global $wgMemc;
		if ( !$this->isWriteable() ) {
			return Status::newFatal( 'gadget-manager-readonly-repository' );
		}
		
		$dbw = $this->getMasterDB();
		$id = $gadget->getId();
		$json = $gadget->getJSON();
		$ts = $dbw->timestamp( $gadget->getTimestamp() );
		$newTs = $dbw->timestamp( $timestamp );
		$row = array(
			'gd_id' => $id,
			'gd_blob' => $json,
			'gd_shared' => $gadget->isShared(),
			'gd_timestamp' => $newTs
		);
		
		// First INSERT IGNORE the row, in case the gadget doesn't already exist
		$dbw->begin();
		$created = false;
		$dbw->insert( 'gadgets', $row, __METHOD__, array( 'IGNORE' ) );
		$created = $dbw->affectedRows() > 0;
		// Then UPDATE it if it did already exist
		if ( !$created ) {
			$dbw->update( 'gadgets', $row, array(
					'gd_id' => $id,
					'gd_timestamp <= ' . $dbw->addQuotes( $ts ) // for conflict detection
				), __METHOD__
			);
			$created = $dbw->affectedRows();
		}
		$dbw->commit();
		
		// Detect conflicts
		if ( !$created ) {
			// Some conflict occurred
			wfDebug( __METHOD__ . ": conflict detected\n" );
			return Status::newFatal( 'gadgets-manager-modify-conflict', $id, $ts );
		}
		
		// Update our in-object cache
		// This looks stupid: we have an object that we could be caching. But I prefer
		// to keep $this->data in a consistent format and have getGadget() always return
		// a clone. If it returned a reference to a cached object, the caller could change
		// that object and cause weird things to happen.
		$this->data[$id] = array( 'json' => $json, 'timestamp' => $newTs );
		// Write to memc too
		$key = $this->getMemcKey( 'gadgets', 'localrepodata', $id );
		if ( $key !== false ) {
			$wgMemc->set( $key, $this->data[$id] );
		}
		// Clear the gadget names array in memc
		if ( $this->namesKey !== false ) {
			$wgMemc->delete( $this->namesKey );
		}
		
		return Status::newGood();
	}
	
	public function deleteGadget( $id ) {
		global $wgMemc;
		if ( !$this->isWriteable() ) {
			return Status::newFatal( 'gadget-manager-readonly-repository' );
		}
		
		// Remove gadget from database
		$dbw = $this->getMasterDB();
		$dbw->delete( 'gadgets', array( 'gd_id' => $id ), __METHOD__ );
		$affectedRows = $dbw->affectedRows();
		
		// Remove gadget from in-object cache
		unset( $this->data[$id] );
		// Remove from memc too
		$key = $this->getMemcKey( 'gadgets', 'localrepodata', $id );
		if ( $key !== false ) {
			$wgMemc->delete( $key );
		}
		// Clear the gadget names array in memc
		if ( $this->namesKey !== false ) {
			$wgMemc->delete( $this->namesKey );
		}
		
		if ( $affectedRows === 0 ) {
			return Status::newFatal( 'gadgets-manager-nosuchgadget', $id );
		}
		return Status::newGood();
	}
	
	/**
	 * Get the slave DB connection. Subclasses can override this to use a different DB
	 * @return Database
	 */
	public function getDB() {
		return wfGetDB( DB_SLAVE );
	}
	
	/*** Public methods ***/
	
	/**
	 * Get the localized title for a given category in a given language.
	 * 
	 * The "gadgetcategory-$category" message is used, if it exists.
	 * If it doesn't exist, ucfirst( $category ) is returned.
	 * 
	 * @param $category string Category ID
	 * @param $lang string Language code. If null, $wgLang is used
	 * @return string Localized category title
	 */
	public function getCategoryTitle( $category, $lang = null ) {
		$msg = wfMessage( "gadgetcategory-$category" );
		if ( $lang !== null ) {
			$msg = $msg->inLanguage( $lang );
		}
		if ( !$msg->exists() ) {
			global $wgLang;
			$langObj = $lang === null ? $wgLang : Language::factory( $lang );
			return $langObj->ucfirst( $category );
		}
		return $msg->plain();
	}
	
	
	/*** Protected methods ***/
	
	/**
	 * Get the master DB connection. Subclasses can override this to use a different DB
	 * @return Database
	 */
	protected function getMasterDB() {
		return wfGetDB( DB_MASTER );
	}
	
	/**
	 * Get a memcached key. Subclasses can override this to use a foreign memc
	 * @return string|bool Cache key, or false if this repo has no shared memc
	 */
	protected function getMemcKey( /* ... */ ) {
		$args = func_get_args();
		return call_user_func_array( 'wfMemcKey', $args );
	}
	
	/**
	 * Populate the keys in $this->data. Values are only populated when loading from the DB;
	 * when loading from memc, all values are set to null and are lazy-loaded in loadDataFor().
	 * @return array Array of gadget IDs
	 */
	protected function loadIDs() {
		global $wgMemc;
		if ( $this->namesLoaded ) {
			// Already loaded
			return array_keys( $this->data );
		}
		
		// Try memc
		$cached = $this->namesKey !== false ? $wgMemc->get( $this->namesKey ) : false;
		if ( is_array( $cached ) ) {
			// Yay, data is in cache
			// Add to $this->data , but let things already in $this->data take precedence
			$this->data += $cached;
			$this->namesLoaded = true;
			return array_keys( $this->data );
		}
		
		// Get from DB
		$query = $this->getLoadIDsQuery();
		$dbr = $this->getDB();
		$res = $dbr->select( $query['tables'], $query['fields'], $query['conds'], __METHOD__,
			$query['options'], $query['join_conds'] );
		
		$toCache = array();
		foreach ( $res as $row ) {
			$this->data[$row->gd_id] = array( 'json' => $row->gd_blob, 'timestamp' => $row->gd_timestamp );
			$toCache[$row->gd_id] = null;
		}
		// Write to memc
		$wgMemc->set( $this->namesKey, $toCache );
		$this->namesLoaded = true;
		return array_keys( $this->data );
	}
	
	/**
	 * Populate a given Gadget's data in $this->data . Tries memc first, then falls back to a DB query.
	 * @param $id string Gadget ID
	 * @return array( 'json' => JSON string, 'timestamp' => timestamp ) or empty array if the gadget doesn't exist.
	 */
	protected function loadDataFor( $id ) {
		global $wgMemc;
		if ( isset( $this->data[$id] ) && is_array( $this->data[$id] ) ) {
			// Already loaded, nothing to do here.
			return $this->data[$id];
		}
		
		// Try cache
		$key = $this->getMemcKey( 'gadgets', 'localrepodata', $id );
		$cached = $key !== false ? $wgMemc->get( $key ) : false;
		if ( is_array( $cached ) ) {
			// Yay, data is in cache
			$this->data[$id] = $cached;
			return $cached;
		}
		
		// Get from database
		$query = $this->getLoadDataForQuery( $id );
		$dbr = $this->getDB();
		$row = $dbr->selectRow( $query['tables'], $query['fields'], $query['conds'], __METHOD__,
			$query['options'], $query['join_conds']
		);
		if ( !$row ) {
			// Gadget doesn't exist
			// Use empty array to prevent confusion with $wgMemc->get() return values for missing keys
			$data = array();
		} else {
			$data = array( 'json' => $row->gd_blob, 'timestamp' => $row->gd_timestamp );
		}
		// Save to object cache
		$this->data[$id] = $data;
		// Save to memc
		$wgMemc->set( $key, $data );
		
		return $data;
	}
	
	/**
	 * Get the DB query to use in getLoadIDs(). Subclasses can override this to tweak the query.
	 * @return Array
	 */
	protected function getLoadIDsQuery() {
		return array(
			'tables' => 'gadgets',
			'fields' => array( 'gd_id', 'gd_blob', 'gd_timestamp' ),
			'conds' => '', // no WHERE clause
			'options' => array(),
			'join_conds' => array(),
		);
	}
	
	/**
	 * Get the DB query to use in loadDataFor(). Subclasses can override this to tweak the query.
	 * @param $id string Gadget ID
	 * @return Array
	 */
	protected function getLoadDataForQuery( $id ) {
		return array(
			'tables' => 'gadgets',
			'fields' => array( 'gd_blob', 'gd_timestamp' ),
			'conds' => array( 'gd_id' => $id ),
			'options' => array(),
			'join_conds' => array(),
		);
	}
}
