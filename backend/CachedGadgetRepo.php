<?php
/**
 * Abstract class that enhances GadgetRepo with caching. This is useful for
 * repos that obtain gadget data from a database or a REST API. Currently, all
 * repos use this.
 */
abstract class CachedGadgetRepo extends GadgetRepo {
	/*** Protected members ***/
	
	/** Cache for EXISTING gadgets. Nonexistent gadgets must not be cached here,
	 * use $missCache instead. Values may be null, in which case only the gadget's
	 * existence is cached and the data must still be retrieved from memc or the DB.
	 * 
	 * array( id => null|array( 'json' => JSON string, 'timestamp' => timestamp ) )
	 */
	protected $data = array();
	
	/** Cache for gadget IDs that have been queried and found to be nonexistent.
	 * 
	 * array( id => ignored )
	 */
	protected $missCache = array();
	
	/** If true, $data is assumed to contain all existing gadget IDs.
	 */
	protected $idsLoaded = false;
	
	/*** Abstract methods ***/
	
	/**
	 * Load the full data for all gadgets.
	 * @return array( id => array( 'json' => JSON string, 'timestamp' => timestamp ) )
	 */
	abstract protected function loadAllData();
	
	/**
	 * Load the full data for one gadget.
	 * @param $id string Gadget ID
	 * @return array( 'json' => JSON string, 'timestamp' => timestamp ) or empty array if the gadget doesn't exist.
	 */
	abstract protected function loadDataFor( $id );
	
	/**
	 * Get the memc key for caching the data for a given gadget, or for
	 * caching the gadget IDs list.
	 * @param $id string|null Gadget ID to get the memc key for, or null to get the memc key for the IDs list
	 * @return string Memc key including wiki prefix, i.e. the return value of wfMemcKey() or wfForeignMemcKey()
	 */
	abstract protected function getCacheKey( $id );
	
	/*** Protected methods ***/
	
	/**
	 * Update the cache to account for the fact that a gadget has been
	 * added, modified or deleted.
	 * @param $id string Gadget ID
	 * @param $data array array( 'json' => JSON string, 'timestamp' => timestamp ) if added or modified, or null if deleted
	 */
	protected function updateCache( $id, $data ) {
		global $wgMemc;
		$toCache = $data;
		if ( $data !== null ) {
			// Added or modified
			// Store in in-object cache
			$this->data[$id] = $data;
			// Remove from the missing cache if present there
			unset( $this->missCache[$id] );
		} else {
			// Deleted
			// Remove from in-object cache
			unset( $this->data[$id] );
			// Add it to the missing cache
			$this->missCache[$id] = true;
			// Store nonexistence in memc as an empty array
			$toCache = array();
		}
		
		// Write to memc
		$wgMemc->set( $this->getCacheKey( $id ), $toCache );
		// Clear the gadget names array in memc so it'll be regenerated later
		$wgMemc->delete( $this->getCacheKey( null ) );
	}
	
	/*** Public methods inherited from GadgetRepo ***/
	
	public function getGadgetIds() {
		$this->maybeLoadIDs();
		return array_keys( $this->data );
	}
	
	public function getGadget( $id ) {
		$data = $this->maybeLoadDataFor( $id );
		if ( !$data ) {
			return null;
		}
		return new Gadget( $id, $this, $data['json'], $data['timestamp']  );
	}
	
	/*** Private methods ***/
	
	/**
	 * Populate the keys in $this->data. Values are only populated if loadAllData() is called,
	 * when loading from memc, all values are set to null and are lazy-loaded in loadDataFor().
	 * @return array Array of gadget IDs
	 */
	private function maybeLoadIDs() {
		global $wgMemc;
		if ( $this->idsLoaded ) {
			return array_keys( $this->data );
		}
		
		// Try memc
		$key = $this->getCacheKey( null );
		$cached = $wgMemc->get( $key );
		if ( is_array( $cached ) ) {
			// Yay, data is in cache
			// Add to $this->data , but let things already in $this->data take precedence
			$this->data += $cached;
			$this->idsLoaded = true;
			return array_keys( $this->data );
		}
		
		$this->data = $this->loadAllData();
		$arrayKeys = array_keys( $this->data );
		// For memc, prepare an array with the IDs as keys but with each value set to null
		if ( count( $arrayKeys ) > 0 ) {
			$toCache = array_combine( $arrayKeys, array_fill( 0, count( $arrayKeys ), null ) );
		} else {
			// array_fill() and array_combine() don't like empty arrays
			$toCache = array();
		}
		$wgMemc->set( $key, $toCache );
		
		// Now that we have the data for every gadget, let's refresh those cache entries too
		foreach ( $this->data as $id => $gadgetData ) {
			$wgMemc->set( $this->getCacheKey( $id ), $gadgetData );
		}
		
		$this->idsLoaded = true;
		return $arrayKeys;
	}
	
	/**
	 * Populate a given Gadget's data in $this->data . Tries memc first, then falls back to loadDataFor()
	 * @param $id string Gadget ID
	 * @return array( 'json' => JSON string, 'timestamp' => timestamp ) or empty array if the gadget doesn't exist.
	 */
	private function maybeLoadDataFor( $id ) {
		global $wgMemc;
		if ( isset( $this->data[$id] ) && is_array( $this->data[$id] ) ) {
			// Already loaded, nothing to do here.
			return $this->data[$id];
		}
		if ( isset( $this->missCache[$id] ) ) {
			// Gadget is already known to be missing
			return array();
		}
		// Need to use array_key_exists() here because isset() returns true for nulls. !@#$ you, PHP
		if ( $this->idsLoaded && !array_key_exists( $id, $this->data ) ) {
			// All IDs have been loaded into $this->data but $id isn't in there,
			// therefore it doesn't exist.
			$this->missCache[$id] = true;
			return array();
		}
		
		// Try cache
		$key = $this->getCacheKey( $id );
		$cached = $wgMemc->get( $key );
		if ( is_array( $cached ) ) {
			// Yay, data is in cache
			if ( count( $cached ) ) {
				// Cache entry contains data
				$this->data[$id] = $cached;
			} else {
				// Cache entry signals nonexistence
				$this->missCache[$id] = true;
			}
			return $cached;
		}
		
		$data = $this->loadDataFor( $id );
		if ( !$data ) {
			// Gadget doesn't exist
			// Use empty array to prevent confusion with $wgMemc->get() return values for missing keys
			$data = array();
			// DO NOT store $data in $this->data, because it's supposed to contain existing gadgets only
			$this->missCache[$id] = true;
		} else {
			// Save to object cache
			$this->data[$id] = $data;
		}
		// Save to memc
		$wgMemc->set( $key, $data );
		
		return $data;
	}
}