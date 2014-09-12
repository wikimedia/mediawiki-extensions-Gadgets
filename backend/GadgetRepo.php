<?php
// TODO: Fix comment lengths, inconsistent across this project
/**
 * Interface for all Gadget Repositories
 */
interface GadgetRepo {
	/**
	 * Get the name of the ResourceLoader source of the modules
	 * returned by this repository.
	 * @return string Source name
	 */
	public function getSource();

	/**
	 * Get the ids of the gadgets provided by this repository
	 * @return array of ids (strings)
	 */
	public function getGadgetIds();

	/**
	 * Get a Gadget object for a given gadget id
	 * @param $id string Gadget id
	 * @return Gadget object or null if no such gadget
	 */
	public function getGadget( $id );

	/**
	 * Check whether this repository allows write actions. If this method returns false,
	 * methods that modify the state of the repository or the gadgets in it (i.e. modifyGadget()
	 * and deleteGadget()) will always fail.
	 * @return bool
	 */
	public function isWriteable();

	/**
	 * Get the Database object for the database this repository is based on, or null if this
	 * repository is not based on a database (but e.g. on a remote API)
	 * @return DatabaseBase object (slave connection) or null
	 */
	public function getDB();

	/**
	 * Modify a gadget, replacing its metadata with the
	 * metadata in the provided Gadget object. The id is taken
	 * from the Gadget object as well. If no Gadget exists by that id,
	 * it will be created.
	 * @param $gadget Gadget object
	 * @param string $timestamp Timestamp to record for this action, or current timestamp if null
	 * @return Status
	 */
	public function modifyGadget( Gadget $gadget, $timestamp = null );

	/**
	 * Irrevocably delete a gadget from the repository. Will fail
	 * if there is no gadget by the given id.
	 * @param $id string Unique id of the gadget to delete
	 * @return Status
	 */
	public function deleteGadget( $id );

	/**
	 * @fixme document
	 * @return array
	 */
	public function getGadgetsByCategory();
}
