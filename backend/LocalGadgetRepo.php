<?php
/**
 * Gadget repository that gets its gadgets from the local database.
 */
class LocalGadgetRepo extends GadgetRepo {
	protected $data = null;
	
	/*** Public methods inherited from GadgetRepo ***/
	
	/**
	 * Constructor.
	 * @param info array of options
	 */
	public function __construct( array $options ) {
		parent::__construct( $options );
		
		// TODO: define options
	}
	
	public function getGadgetNames() {
		$this->loadData();
		return array_keys( $this->data );
	}
	
	public function getGadget( $name ) {
		$this->loadData();
		if ( !isset( $this->data[$name] ) ) {
			return null;
		}
		return new Gadget( $name, $this, $this->data[$name]['json'], $this->data[$name]['timestamp']  );
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
		if ( !$this->isWriteable() ) {
			return Status::newFatal( 'gadget-manager-readonly-repository' );
		}
		
		$dbw = $this->getMasterDB();
		$this->loadData();
		$name = $gadget->getName();
		$json = $gadget->getJSON();
		$ts = $dbw->timestamp( $gadget->getTimestamp() );
		$newTs = $dbw->timestamp( $timestamp );
		$row = array(
			'gd_name' => $name,
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
					'gd_name' => $name,
					'gd_timestamp <= ' . $dbw->addQuotes( $ts ) // for conflict detection
				), __METHOD__
			);
		}
		$dbw->commit();
		
		// Detect conflicts
		if ( $dbw->affectedRows() === 0 ) {
			// Some conflict occurred
			return Status::newFatal( 'gadgets-manager-modify-conflict', $name, $ts );
		}
		
		// Update our in-object cache
		// This looks stupid: we have an object that we could be caching. But I prefer
		// to keep $this->data in a consistent format and have getGadget() always return
		// a clone. If it returned a reference to a cached object, the caller could change
		// that object and cause weird things to happen.
		$this->data[$name] = array( 'json' => $json, 'timestamp' => $newTs );
		
		return Status::newGood();
	}
	
	public function deleteGadget( $name ) {
		if ( !$this->isWriteable() ) {
			return Status::newFatal( 'gadget-manager-readonly-repository' );
		}
		
		$this->loadData();
		if ( !isset( $this->data[$name] ) ) {
			return Status::newFatal( 'gadgets-manager-nosuchgadget', $name );
		}
		
		unset( $this->data[$name] );
		$dbw = $this->getMasterDB();
		$dbw->delete( 'gadgets', array( 'gd_name' => $name ), __METHOD__ );
		if ( $dbw->affectedRows() === 0 ) {
			return Status::newFatal( 'gadgets-manager-nosuchgadget', $name );
		}
		return Status::newGood();
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
	 * Get the slave DB connection. Subclasses can override this to use a different DB
	 * @return Database
	 */
	public function getDB() {
		return wfGetDB( DB_SLAVE );
	}
	
	/*** Protected methods ***/
	
	/**
	 * Populate $this->data from the DB, if that hasn't happened yet. All methods using
	 * $this->data must call this before accessing $this->data .
	 */
	protected function loadData() {
		// FIXME: Make the cache shared somehow, it's getting repopulated for every instance now
		// FIXME: Reconsider the query-everything behavior; maybe use memc?
		if ( is_array( $this->data ) ) {
			// Already loaded
			return;
		}
		$this->data = array();
		
		$query = $this->getLoadDataQuery();
		$dbr = $this->getDB();
		$res = $dbr->select( $query['tables'], $query['fields'], $query['conds'], __METHOD__,
			$query['options'], $query['join_conds'] );
		
		foreach ( $res as $row ) {
			$this->data[$row->gd_name] = array( 'json' => $row->gd_blob, 'timestamp' => $row->gd_timestamp );
		}
	}
	
	/**
	 * Get the DB query to use in loadData(). Subclasses can override this to tweak the query.
	 * @return Array
	 */
	protected function getLoadDataQuery() {
		return array(
			'tables' => 'gadgets',
			'fields' => array( 'gd_name', 'gd_blob', 'gd_timestamp' ),
			'conds' => '', // no WHERE clause
			'options' => array(),
			'join_conds' => array(),
		);
	}
}
