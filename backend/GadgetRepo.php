<?php
// TODO: Fix comment lengths, inconsistent across this project
/**
 * Abstract base class for gadget repositories, can be local or remote
 */
abstract class GadgetRepo {
	/**
	 * Constructor.
	 * @param $info array with configuration data, depends on repo type
	 */
	public function __construct( array $info = array() ) {
	}
	
	/**** Abstract methods ****/
	
	/**
	 * Get the name of the ResourceLoader source of the modules
	 * returned by this repository.
	 * @return string Source name
	 */
	abstract public function getSource();
	
	/**
	 * Get the ids of the gadgets provided by this repository
	 * @return array of ids (strings)
	 */
	abstract public function getGadgetIds();
	
	/**
	 * Get a Gadget object for a given gadget id
	 * @param $id string Gadget id
	 * @return Gadget object or null if no such gadget
	 */
	abstract public function getGadget( $id );
	
	/**
	 * Check whether this repository allows write actions. If this method returns false,
	 * methods that modify the state of the repository or the gadgets in it (i.e. modifyGadget()
	 * and deleteGadget()) will always fail.
	 * @return bool
	 */
	abstract public function isWriteable();
	
	/**
	 * Get the Database object for the database this repository is based on, or null if this
	 * repository is not based on a database (but e.g. on a remote API)
	 * @return DatabaseBase object (slave connection) or null
	 */
	abstract public function getDB();
	
	/**
	 * Modify a gadget, replacing its metadata with the
	 * metadata in the provided Gadget object. The id is taken
	 * from the Gadget object as well. If no Gadget exists by that id,
	 * it will be created.
	 * @param $gadget Gadget object
	 * @param string $timestamp Timestamp to record for this action, or current timestamp if null
	 * @return Status
	 */
	abstract public function modifyGadget( Gadget $gadget, $timestamp = null );
	
	/**
	 * Irrevocably delete a gadget from the repository. Will fail
	 * if there is no gadget by the given id.
	 * @param $id string Unique id of the gadget to delete
	 * @return Status
	 */
	abstract public function deleteGadget( $id );
	
	/**** Public methods ****/
	
	public function getGadgetsByCategory() {
		$retval = array();
		$gadgetIDs = $this->getGadgetIds();
		foreach ( $gadgetIDs as $id ) {
			$gadget = $this->getGadget( $id );
			$retval[$gadget->getCategory()][] = $gadget;
		}
		return $retval;
	}
	
	/*** Public static methods ***/
	
	/**
	 * Get all gadget repositories. Returns the LocalGadgetRepo singleton and any
	 * repositories configured in $wgGadgetRepositories, in that order.
	 * @return GadgetRepo[]
	 */
	public static function getAllRepos() {
		global $wgGadgetRepositories;
		$repos = array( LocalGadgetRepo::singleton() );
		foreach ( $wgGadgetRepositories as $params ) {
			$repoClass = $params['class'];
			unset( $params['class'] ); // Safe because foreach operates on a copy of the array
			$repos[] = new $repoClass( $params );
		}
		return $repos;
	}
	
	/**
	 * Get all gadgets from all repositories
	 * @return Gadget[]
	 */
	public static function getAllGadgets() {
		$retval = array();
		$repos = self::getAllRepos();
		foreach ( $repos as $repo ) {
			$gadgets = $repo->getGadgetIds();
			foreach ( $gadgets as $id ) {
				// If there is a naming collision, let the first one win
				if ( !isset( $retval[$id] ) ) {
					$retval[$id] = $repo->getGadget( $id );
				}
			}
		}
		return $retval;
	}
}
