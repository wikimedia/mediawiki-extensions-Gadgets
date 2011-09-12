<?php
/**
 * Gadget repository that gets its gadgets from a foreign database.
 * 
 * Options (all of these are MANDATORY):
 * 'source': Name of the source these gadgets are loaded from, as defined in ResourceLoader
 * 'dbType': Database type, see DatabaseBase::factory()
 * 'dbServer': Database host
 * 'dbUser': Database user name
 * 'dbPassword': Database password
 * 'dbName': Database name
 * 'dbFlags': Bitmap of the DBO_* flags. Recommended value is  ( $wgDebugDumpSql ? DBO_DEBUG : 0 ) | DBO_DEFAULT
 * 'tablePrefix': Table prefix
 * 'hasSharedCache': Whether the foreign wiki's cache is accessible through $wgMemc
 */
class ForeignDBGadgetRepo extends LocalGadgetRepo {
	protected $db = null;
	
	protected $source, $dbServer, $dbUser, $dbPassword, $dbName, $dbFlags, $tablePrefix, $hasSharedCache;
	
	/**
	 * Constructor.
	 * @param $options array See class documentation comment for option details
	 */
	public function __construct( array $options ) {
		parent::__construct( $options );
		
		$optionKeys = array( 'source', 'dbType', 'dbServer', 'dbUser', 'dbPassword', 'dbName',
			'dbFlags', 'tablePrefix', 'hasSharedCache' );
		foreach ( $optionKeys as $optionKey ) {
			$this->{$optionKey} = $options[$optionKey];
		}
		
		$this->namesKey = $this->getMemcKey( 'gadgets', 'foreigndbreponames' );
	}
	
	public function isWriteable() {
		return false;
	}
	
	public function getSource() {
		return $this->source;
	}
	
	public function getDB() {
		return $this->getMasterDB();
	}
	
	/*** Overridden protected functions from LocalGadgetRepo ***/
	protected function getMasterDB() {
		if ( $this->db === null ) {
			$this->db = DatabaseBase::factory( $this->dbType,
				array(
					'host' => $this->dbServer,
					'user' => $this->dbUser,
					'password' => $this->dbPassword,
					'dbname' => $this->dbName,
					'flags' => $this->dbFlags,
					'tablePrefix' => $this->tablePrefix
				)
			);
		}
		return $this->db;
	}
	
	protected function getMemcKey( /* ... */ ) {
		if ( $this->hasSharedCache ) {
			$args = func_get_args();
			array_unshift( $args, $this->dbName, $this->tablePrefix );
			return call_user_func_array( 'wfForeignMemcKey', $args );
		} else {
			return false;
		}
	}
	
	protected function getLoadIDsQuery() {
		$query = parent::getLoadIDsQuery();
		$query['conds']['gd_shared'] = 1;
		return $query;
	}
	
	protected function getLoadDataForQuery( $id ) {
		$query = parent::getLoadDataForQuery( $id );
		$query['conds']['gd_shared'] = 1;
		return $query;
	}
}
