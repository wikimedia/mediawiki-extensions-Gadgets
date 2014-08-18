<?php
// Prevent unnecessary path errors when run from update.php
if ( !class_exists( 'Maintenance' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
	if ( $IP === false ) {
		$IP = dirname( __FILE__ ) . '/../..';
	}
	require( "$IP/maintenance/Maintenance.php" );
}

class MigrateGadgets extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Migrates old-style Gadgets defined in MediaWiki:Gadgets-definition to the new format";
	}
	
	protected function getUpdateKey() {
		return '2.0 migration to gadgets table';
	}

	protected function updateSkippedMessage() {
		return 'Old-style Gadgets already migrated.';
	}

	protected function doDBUpdates() {
		global $wgUser;

		// Username to use for page creations and moves performed by this script
		// (should be in wgReservedUsernames).
		$wgUser->setName( 'Maintenance script' );

		$this->output( "Migrating old-style Gadgets from [[MediaWiki:Gadgets-definition]] ...\n" );
		
		$g = wfMessage( 'gadgets-definition' )->inContentLanguage();
		if ( !$g->exists() ) {
			$this->output( "No Gadget definition page found.\n" );
			return false;
		}
		$wikitext = $g->plain();
		$gadgets = $this->parseGadgets( $wikitext );
		$this->output( count( $gadgets ) . " gadget definitions found.\n" );
		
		$notResourceLoaded = array();
		$notMoved = $notCreated = array();
		$categories = array();
		foreach ( $gadgets as $id => $gadget ) {
			if ( !$gadget['resourceLoaded'] ) {
				$notResourceLoaded[] = $id;
			}
			unset( $gadget['resourceLoaded'] );
			if ( $gadget['settings']['category'] !== '' ) {
				$categories[$gadget['settings']['category']] = true;
			}
			
			$this->output( "Converting $id ...\n" );
			$moves = array(
				"MediaWiki:Gadget-$id" => "MediaWiki:Gadget-$id-desc",
				"MediaWiki talk:Gadget-$id" => "MediaWiki talk:Gadget-$id-desc"
			);
			foreach ( array_merge( $gadget['module']['scripts'], $gadget['module']['styles'] ) as $page ) {
				$moves["MediaWiki:Gadget-$page"] = "Gadget:$page";
				$moves["MediaWiki talk:Gadget-$page"] = "Gadget talk:$page";
			}
			$notMoved = array_merge( $notMoved, $this->processMoves( $moves,
				wfMessage( 'gadgets-migrate-movereason-gadget', $id )->inContentLanguage()->plain()
			) );
			
			$result = $this->createGadgetPage( $id, $gadget );
			if ( $result === true ) {
				$this->output( "Created [[Gadget definition:$id]]\n" );
			} else {
				$this->output( "ERROR when creating [[Gadget definition:$id]]: $result\n" );
				$notCreated[] = $id;
			}
		}
		
		$this->output( "Moving category title messages ...\n" );
		$categoryMoves = array();
		foreach ( $categories as $category => $unused ) {
			$categoryMoves["MediaWiki:Gadget-section-$category"] = "MediaWiki:Gadgetcategory-$category";
		}
		$notMoved = array_merge( $notMoved, $this->processMoves( $categoryMoves,
			wfMessage( 'gadgets-migrate-movereason-category' )->inContentLanguage()->plain()
		) );
		
		$noErrors = count( $notMoved ) == 0 && count( $notCreated ) == 0;
		if ( count( $notMoved ) ) {
			$this->output( "There were ERRORS moving " . count( $notMoved ) . " pages.\n" );
			$this->output( "The following pages were NOT successfully moved:\n" );
			foreach ( $notMoved as $from => $to ) {
				$this->output( "[[$from]] -> [[$to]]\n" );
			}
		}
		if ( count( $notCreated ) ) {
			$this->output( "There were ERRORS creating " . count( $notCreated ) . " pages.\n" );
			$this->output( "The following pages were NOT successfully created:\n" );
			foreach ( $notCreated as $page ) {
				$this->output( "[[$page]]\n" );
			}
		}
		if ( $noErrors ) {
			$this->output( "Gadgets migration finished without errors.\n" );
		}
		
		if ( count( $notResourceLoaded ) ) {
			$this->output( "WARNING: The following gadgets will now be loaded through ResourceLoader, but were not marked as supporting ResourceLoader. They may now be broken.\n" );
			foreach ( $notResourceLoaded as $id ) {
				$this->output( "* $id\n" );
			}
		}
		
		$this->output( "All done migrating gadgets.\n" );
		return $noErrors;
	}
	
	/**
	 * Parse an old-style MediaWiki:Gadgets-definition page. This basically contains
	 * the important parts of loadStructuredList() from the old Gadgets code.
	 * @param $wikitext string Wikitext
	 * @return array( id => blob structure ) where the blob structure is the unserialized version of a gadget JSON blob
	 *         with an additional 'resourceLoaded' key.
	 */
	protected function parseGadgets( $wikitext ) {
		// Remove comments
		$wikitext = preg_replace( '/<!--.*?-->/s', '', $wikitext );
		// Split by line
		$lines = preg_split( '/(\r\n|\r|\n)+/', $wikitext );

		$gadgets = array();
		$category = '';

		foreach ( $lines as $line ) {
			$m = array();
			if ( preg_match( '/^==+ *([^*:\s|]+?)\s*==+\s*$/', $line, $m ) ) {
				$category = $m[1];
			}
			else {
				$gadget = $this->parseGadgetDefinition( $line );
				if ( $gadget ) {
					$id = key( $gadget );
					$gadget[$id]['settings']['category'] = $category;
					$gadgets += $gadget;
				}
			}
		}
		return $gadgets;
	}
	
	/**
	 * Parse an old-style gadget definition. This is pretty much newFromDefinition() from the old Gadgets code,
	 * with small modifications to make it output an array structure.
	 * @param $definition string Old-style gadget definition from MediaWiki:Gadgets-definition
	 * @return array( id => blob structure ) where the blob structure is the unserialized version of a gadget JSON blob
	 *         with an additional 'resourceLoaded' key.
	 */
	protected function parseGadgetDefinition( $definition ) {
		$gadget = array(
			'settings' => array(
				'rights' => array(),
				'default' => false,
				'hidden' => false,
				'shared' => false,
				'category' => ''
			),
			'module' => array(
				'scripts' => array(),
				'styles' => array(),
				'dependencies' => array(),
				'messages' => array()
			),
			'resourceLoaded' => false
		);
		
		if ( !preg_match( '/^\*+ *([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)(\s*\[.*?\])?\s*((\|[^|]*)+)\s*$/', $definition, $m ) ) {
			return false;
		}
		
		$name = trim( str_replace( ' ', '_', $m[1] ) );
		$options = trim( $m[2], ' []' );

		foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
			$arr = preg_split( '/\s*=\s*/', $option, 2 );
			$option = $arr[0];
			if ( isset( $arr[1] ) ) {
				$params = explode( ',', $arr[1] );
				$params = array_map( 'trim', $params );
			} else {
				$params = array();
			}

			switch ( $option ) {
				case 'ResourceLoader':
					$gadget['resourceLoaded'] = true;
					break;
				case 'dependencies':
					$gadget['module']['dependencies'] = $params;
					break;
				case 'rights':
					$gadget['settings']['rights'] = $params;
					break;
				case 'skins':
					$gadget['settings']['skins'] = array_values( array_intersect( array_keys( Skin::getSkinNames() ), $params ) );
					break;
				case 'default':
					$gadget['settings']['default'] = true;
					break;
			}
		}

		foreach ( preg_split( '/\s*\|\s*/', $m[3], -1, PREG_SPLIT_NO_EMPTY ) as $page ) {
			if ( preg_match( '/\.js/', $page ) ) {
				$gadget['module']['scripts'][] = $page;
			} elseif ( preg_match( '/\.css/', $page ) ) {
				$gadget['module']['styles'][] = $page;
			}
		}

		return array( $name => $gadget );
	}
	
	protected function processMoves( $moves, $reason ) {
		$notMoved = array();
		
		// Preprocessing step: add subpages
		$movesWithSubpages = array();
		foreach ( $moves as $from => $to ) {
			$title = Title::newFromText( $from );
			if ( !$title ) {
				continue;
			}
			$fromNormalized = $title->getPrefixedText();
			$movesWithSubpages[$fromNormalized] = $to;
			$subpages = $title->getSubpages();
			foreach ( $subpages as $subpage ) {
				$fromSub = $subpage->getPrefixedText();
				$toSub = preg_replace( '/^' . preg_quote( $fromNormalized, '/' ) . '/',
					StringUtils::escapeRegexReplacement( $to ), $fromSub
				);
				$movesWithSubpages[$fromSub] = $toSub;
			}
		}
		
		foreach ( $movesWithSubpages as $from => $to ) {
			$result = $this->moveGadgetPage( $from, $to, $reason );
			if ( $result === true ) {
				$this->output( "Moved [[$from]] to [[$to]]\n" );
			} else if ( $result === false ) {
				$this->output( "...skipping [[$from]], doesn't exist\n" );
			} else {
				$this->output( $result );
				$notMoved[$from] = $to;
			}
		}
		return $notMoved;
	}
	
	protected function moveGadgetPage( $from, $to, $reason ) {
		$fromTitle = Title::newFromText( $from );
		$toTitle = Title::newFromText( $to );
		if ( !$fromTitle ) {
			return "Invalid title `$from'";
		}
		if ( !$fromTitle->exists() ) {
			return false;
		}
		if ( !$toTitle ) {
			return "Invalid title: `$to'";
		}
		
		$errors = $fromTitle->moveTo( $toTitle, /* $auth = */ false,
			$reason,
			/* $createRedirect = */ false
		);

		if ( $errors === true ) {
			return true;
		} else {
			$errorMsgs = array();
			foreach ( $errors as $error ) {
				$key = array_shift( $error );
				$msg = wfMessage( $key, $error );
				$errorMsgs[] = $msg->text();
			}
			return "ERROR when moving [[{$fromTitle->getPrefixedText()}]] to [[{$toTitle->getPrefixedText()}]]: " .
				implode( "\n", $errorMsgs ) . "\n";
		}
	}
	
	protected function createGadgetPage( $id, $gadget ) {
		$title = Title::makeTitleSafe( NS_GADGET_DEFINITION, $id );
		if ( !$title ) {
			return "Invalid title `Gadget definition:$id'";
		}
		$page = WikiPage::factory( $title );
		$status = $page->doEdit(
			FormatJson::encode( $gadget ),
			wfMessage( 'gadgets-migrate-editsummary-gadget', $id )->plain()
		);
		if ( $status->isOK() ) {
			return true;
		} else {
			return $status->getWikiText();
		}
	}
}

$maintClass = "MigrateGadgets";
require_once( RUN_MAINTENANCE_IF_MAIN );
