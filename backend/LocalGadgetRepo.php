<?php
/**
 * Gadget repository that gets its gadgets from the local database.
 */
class LocalGadgetRepo extends CachedGadgetRepo {
	
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
	
	/*** Protected methods inherited from CachedGadgetRepo ***/
	
	protected function getCacheKey( $id ) {
		if ( $id === null ) {
			return wfMemcKey( 'gadgets', 'localrepoids' );
		} else {
			return wfMemcKey( 'gadgets', 'localrepodata', $id );
		}
	}
	
	protected function getCacheExpiry( $id ) {
		// We're dealing with metadata for local gadgets, and we have
		// full control over cache invalidation.
		// So cache forever
		return 0;
	}
	
	protected function updateCache( $id, $data ) {
		global $wgMemc;
		parent::updateCache( $id, $data );
		// Also purge the IDs list used by foreign repos
		$wgMemc->delete( wfMemcKey( 'gadgets', 'localrepoidsshared' ) );
	}
	
	protected function loadAllData() {
		$query = $this->getLoadAllDataQuery();
		$dbr = $this->getDB();
		$res = $dbr->select( $query['tables'], $query['fields'], $query['conds'], __METHOD__,
			$query['options'], $query['join_conds'] );
		
		$data = array();
		foreach ( $res as $row ) {
			$data[$row->gd_id] = array( 'json' => $row->gd_blob, 'timestamp' => $row->gd_timestamp );
		}
		return $data;
	}
	
	protected function loadDataFor( $id ) {
		$query = $this->getLoadDataForQuery( $id );
		$dbr = $this->getDB();
		$row = $dbr->selectRow( $query['tables'], $query['fields'], $query['conds'], __METHOD__,
			$query['options'], $query['join_conds']
		);
		if ( !$row ) {
			// Gadget doesn't exist
			return array();
		} else {
			return array( 'json' => $row->gd_blob, 'timestamp' => $row->gd_timestamp );
		}
	}
	
	/*** Public methods inherited from GadgetRepo ***/
	
	public function getSource() {
		return 'local';
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
		
		$this->updateCache( $id, array( 'json' => $json, 'timestamp' => $newTs ) );
		
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
		
		$this->updateCache( $id, null );
		
		if ( $affectedRows === 0 ) {
			return Status::newFatal( 'gadgets-manager-nosuchgadget', $id );
		}
		return Status::newGood();
	}
	
	/**
	 * Get the slave DB connection. Subclasses can override this to use a different DB
	 * @return DatabaseBase
	 */
	public function getDB() {
		return wfGetDB( DB_SLAVE );
	}
	
	public function isLocal() {
		return true;
	}
	
	/*** Public methods ***/
	
	/**
	 * Get the localized title for a given category in a given language.
	 * 
	 * The "gadgetcategory-$category" message is used, if it exists.
	 * If it doesn't exist, ucfirst( $category ) is returned.
	 * 
	 * @param $category string Category ID
	 * @param $lang string|Language|null Language code or Language object. If null, $wgLang is used
	 * @return string Localized category title
	 */
	public function getCategoryTitle( $category, $lang = null ) {
		$msg = wfMessage( "gadgetcategory-$category" );
		if ( $lang !== null ) {
			$msg = $msg->inLanguage( $lang );
		}
		if ( !$msg->exists() ) {
			global $wgLang;
			$langObj = $lang === null ? $wgLang : ( is_string( $lang ) ? Language::factory( $lang ) : $lang );
			return $langObj->ucfirst( $category );
		}
		return $msg->plain();
	}
	
	
	/*** Protected methods ***/
	
	/**
	 * Get the master DB connection. Subclasses can override this to use a different DB
	 * @return DatabaseBase
	 */
	protected function getMasterDB() {
		return wfGetDB( DB_MASTER );
	}
	
	/**
	 * Get the DB query to use in loadAllData(). Subclasses can override this to tweak the query.
	 * @return Array
	 */
	protected function getLoadAllDataQuery() {
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
