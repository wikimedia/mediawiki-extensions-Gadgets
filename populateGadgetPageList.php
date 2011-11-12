<?php
// Prevent unnecessary path errors when run from update.php
if ( !class_exists( 'Maintenance' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
	if ( $IP === false ) {
		$IP = dirname( __FILE__ ) . '/../..';
	}
	require( "$IP/maintenance/Maintenance.php" );
}

class PopulateGadgetPageList extends LoggedUpdateMaintenance {
	const BATCH_SIZE = 100;
	
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Populates the gadgetpagelist table";
	}
	
	protected function getUpdateKey() {
		return 'populate gadgetpagelist';
	}

	protected function updateSkippedMessage() {
		return 'gadgetpagelist table already populated.';
	}

	protected function doDBUpdates() {
		$dbr = wfGetDB( DB_SLAVE );
		$dbw = wfGetDB( DB_MASTER );
		
		$this->output( "Populating gadgetpagelist table ...\n" );
		
		$lastPageID = 0;
		$processed = 0;
		$written = 0;
		while ( true ) {
			// Grab a batch of pages from the page table
			$res = $dbr->select( 'page',
				array( 'page_id', 'page_namespace', 'page_title', 'page_is_redirect' ),
				"page_id > $lastPageID", __METHOD__,
				array( 'LIMIT' => self::BATCH_SIZE, 'ORDER BY' => 'page_id' )
			);
			if ( $dbr->numRows( $res ) == 0 ) {
				// We've reached the end
				break;
			}
			$processed += $dbr->numRows( $res );
			
			// Build gadgetpagelist rows
			$gplRows = array();
			foreach ( $res as $row ) {
				$title = Title::newFromRow( $row );
				if ( GadgetPageList::isGadgetPage( $title ) ) {
					$gplRows[] = GadgetPageList::getRowForTitle( $title );
				}
				$lastPageID = intval( $row->page_id );
			}
			$dbr->freeResult( $res );
			
			// Insert the new rows
			$dbw->insert( 'gadgetpagelist', $gplRows, __METHOD__, array( 'IGNORE' ) );
			$written += count( $gplRows );
			
			$this->output( "... $processed pages processed, $written rows written, page_id $lastPageID\n" );
		}
		
		$this->output( "Done\n" );
		return true;
	}
}

$maintClass = "PopulateGadgetPageList";
require_once( RUN_MAINTENANCE_IF_MAIN );