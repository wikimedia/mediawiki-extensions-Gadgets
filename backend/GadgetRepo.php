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
	public function __construct( array $info ) {
		// TODO: Common config stuff for all repos?
	}
	
	/**
	 * Get the name of the ResourceLoader source of the modules
	 * returned by this repository.
	 * @return string Source name
	 */
	abstract public function getSource();
	
	/**
	 * Get the names of the gadgets provided by this repository
	 * @return array of names (strings)
	 */
	abstract public function getGadgetNames();
	
	/**
	 * Get a Gadget object for a given gadget name
	 * @param $name string Gadget name
	 * @return Gadget object or null if no such gadget
	 */
	abstract public function getGadget( $name );
	
	/**
	 * Clear any in-object caches this repository may have. In particular,
	 * the return values of getGadgetNames() and getGadget() may be cached.
	 * Callers may wish to clear this cache and reobtain a Gadget object
	 * when they get a conflict error.
	 */
	abstract public function clearInObjectCache();
	
	/**
	 * Check whether this repository allows write actions. If this method returns false,
	 * methods that modify the state of the repository or the gadgets in it (i.e. addGadget(),
	 * modifyGadget() and deleteGadget()) will always fail.
	 * @return bool
	 */
	abstract public function isWriteable();
	
	/**
	 * Get the Database object for the database this repository is based on, or null if this
	 * repository is not based on a database (but e.g. on a remote API)
	 * @return Database object (slave connection) or null
	 */
	abstract public function getDB();
	
	/**
	 * Add a new gadget to the repository. Will fail if there is already
	 * a gadget with the same name.
	 * @param $gadget Gadget object
	 * @return Status
	 */
	abstract public function addGadget( Gadget $gadget );
	
	/**
	 * Modify an existing gadget, replacing its metadata with the
	 * metadata in the provided Gadget object. The name is taken
	 * from the Gadget object as well. Will fail if there is no
	 * gadget by the same name.
	 * @param $gadget Gadget object
	 * @return Status
	 */
	abstract public function modifyGadget( Gadget $gadget );
	
	/**
	 * Irrevocably delete a gadget from the repository. Will fail
	 * if there is no gadget by the given name.
	 * @param $name string Name of the gadget to delete
	 * @return Status
	 */
	abstract public function deleteGadget( $name );
	
	// TODO: cache purging
}
