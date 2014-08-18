<?php
/**
 * ResourceLoader module for a single gadget, really just a wrapper
 * around the Gadget class
 */
class GadgetResourceLoaderModule extends ResourceLoaderWikiModule {
	/** @var Gadget $gadget */
	private $gadget;

	public function __construct( array $options ) {
		$this->gadget = $options['gadget'];
	}

	protected function getDB() {
		return $this->gadget->getRepo()->getDB();
	}

	/**
	 * Overrides the abstract function from ResourceLoaderWikiModule class.
	 * 
	 * This method is public because it's used by GadgetTest.php
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getPages( ResourceLoaderContext $context ) {
		return $this->gadget->getPages();
	}

	/**
	 * Overrides ResourceLoaderModule::getDependencies()
	 * @return array Names of resources this module depends on
	 */
	public function getDependencies() {
		return $this->gadget->getDependencies();
	}

	/**
	 * Overrides ResourceLoaderModule::getMessages()
	 * @return array Keys of messages this module needs
	 */
	public function getMessages() {
		return $this->gadget->getMessages();
	}

	/**
	 * Overrides ResourceLoaderModule::getSource()
	 * @return string Name of the source of this module as defined in ResourceLoader
	 */
	public function getSource() {
		return $this->gadget->getRepo()->getSource();
	}

	/**
	 * Overrides ResourceLoaderModule::getPosition()
	 * @return String: Module position, either 'top' or 'bottom'
	 */
	public function getPosition() {
		return $this->gadget->getPosition();
	}

	/**
	 * Overrides ResourceLoaderWikiModule::getModifiedTime() to take $definitiontimestamp
	 * into account.
	 * @param ResourceLoaderContext $context
	 * @return int UNIX timestamp
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		$retval = parent::getModifiedTime( $context );
		return max( $retval, wfTimestamp( TS_UNIX, $this->gadget->getTimestamp() ) );
	}
}
