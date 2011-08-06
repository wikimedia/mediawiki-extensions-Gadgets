<?php
class SpecialGadgetManager extends SpecialPage {
	public function __construct() {
		parent::__construct( 'GadgetManager', 'gadgets-manager-view' );
	}
	
	public function execute( $par ) {
		global $wgOut, $wgUser; // TODO does SpecialPage have an OutputPage member? RequestContext maybe?
		
		if ( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}
		
		$this->setHeaders();
		$wgOut->setPagetitle( wfMsg( 'gadgetmanager-title' ) );
		$wgOut->addWikiMsg( 'gadgetmanager-pagetext' );
		$wgOut->addModuleStyles( 'ext.gadgets.gadgetmanager' );
		
		// Sort gadgets by section
		$repo = new LocalGadgetRepo( array() );
		$gadgetsBySection = array(); // array( section => array( name => Gadget ) )
		foreach ( $repo->getGadgetNames() as $name ) {
			$gadget = $repo->getGadget( $name );
			$gadgetsBySection[$gadget->getSection()][$name] = $gadget;
		}
		
		$html = '';
		foreach ( $gadgetsBySection as $section => $gadgets ) {
			$sectionName = wfMessage( "Gadgetsection-$section-title" )->plain();
			$html .= Html::element( 'h2', array( 'class' => 'mw-gadgetman-section' ), $sectionName );
			$html .= '<div class="mw-gadgetman-gadgets">';
			
			foreach ( $gadgets as $name => $gadget ) {
				// TODO trigger visibility of mod/delete by rights
				// Leave empty wrapper if user has neither right
				$modifyLink = Linker::link(
					$this->getTitle( $name ),
					wfMsg( 'gadgetmanager-modify-link' ),
					array( 'class' => 'mw-gadgetman-modifylink' ),
					array( 'action' => 'modify' )
				);
				$deleteLink = Linker::link(
					$this->getTitle( $name ),
					wfMsg( 'gadgetmanager-delete-link' ),
					array( 'class' => 'mw-gadgetman-deletelink '),
					array( 'action' => 'delete' )
				);
				$title = wfMessage( $gadget->getTitleMsg() )->plain();
				$desc = wfMessage( $gadget->getDescriptionMsg() )->parse();
				
				$html .= "<div class=\"mw-gadgetman-gadget\"><div class=\"mw-gadgetman-toollinks\">$modifyLink $deleteLink</div>";
				$html .= Html::element( 'h3', array( 'class' => 'mw-gadgetman-title' ), $title );
				$html .= Html::element( 'p', array( 'class' => 'mw-gadgetman-desc' ), $desc );
				
				$html .= '<div class="mw-gadgetman-props"><div class="mw-gadgetman-props-module">';
				$html .= $this->buildPropsArrayList(
					'gadgetmanager-prop-scripts',
					$gadget->getScripts(),
					array_map( 'self::getLinkTitleForGadgetNS', $gadget->getScripts() )
				);
				$html .= $this->buildPropsArrayList(
					'gadgetmanager-prop-styles',
					$gadget->getStyles(),
					array_map( 'self::getLinkTitleForGadgetNS', $gadget->getStyles() )
				);
				
				$module = $gadget->getModule();
				$html .= $this->buildPropsArrayList(
					'gadgetmanager-prop-dependencies',
					$module->getDependencies()
				);
				$html .= $this->buildPropsArrayList(
					'gadgetmanager-prop-messages',
					$module->getMessages(),
					array_map( 'self::getLinkTitleForMediaWikiNS', $module->getMessages() )
				);
				// TODO implement load position
				//$html .= Html::element( 'label', array(), wfMessage( 'gadgetmanager-prop-position' )->plain() );
				//$html .= Html::element( 'span', array( 'class' => 'mw-gadgetman-props-value' ), $gadget->getPosition() );
				//$html .= '<br />';
				$html .= '</div>'; // close mw-gadgetman-props-module
				
				$html .= '<div class="mw-gadgetman-props-gadget">';
				$html .= $this->buildPropsArrayList(
					'gadgetmanager-prop-rights',
					$gadget->getRequiredRights()
				);
				$html .= $this->buildBooleanProp( 'gadgetmanager-prop-default', $gadget->isEnabledByDefault() );
				$html .= $this->buildBooleanProp( 'gadgetmanager-prop-hidden', $gadget->isHidden() );
				$html .= $this->buildBooleanProp( 'gadgetmanager-prop-shared', $gadget->isShared() );
				$html .= '</div></div></div>'; // close mw-gadgetman-props-gadget, mw-gadgetman-props and mw-gadgetman-gadget
			}
			$html .= '</div>'; // close mw-gadgetman-gadgets
		}
		$wgOut->addHTML( $html );
	}
	
	protected function buildPropsArrayList( $labelMsg, $arr, $linkTitles = false ) {
		$html = Html::element( 'label', array(), wfMessage( $labelMsg )->plain() );
		$html .= '<span class="mw-gadgetman-props-value mw-gadgetman-props-listwrapper">';
		foreach ( $arr as $i => $value ) {
			if ( $linkTitles ) {
				$value = Linker::link( $linkTitles[$i], $value );
			} else {
				$value = htmlspecialchars( $value );
			}
			$html .= Html::rawElement( 'span', array( 'class' => 'mw-gadgetman-props-listitem' ), $value );
		}
		$html .= '</span><br />';
		return $html;
	}
	
	protected function buildBooleanProp( $labelMsg, $value ) {
		$html = Html::element( 'label', array(), wfMessage( $labelMsg )->plain() );
		$msg = wfMessage( $value ? 'gadgetmanager-prop-yes' : 'gadgetmanager-prop-no' )->plain();
		$html .= Html::element( 'span', array( 'class' => 'mw-gadgetman-props-value' ), $msg );
		$html .= '<br />';
		return $html;
	}
	
	protected static function getLinkTitleForGadgetNS( $str ) {
		return Title::makeTitle( NS_GADGET, $str );
	}
	
	protected static function getLinkTitleForMediaWikiNS( $str ) {
		return Title::makeTitle( NS_MEDIAWIKI, $str );
	}
	
	/**
	 * Log a gadget manager action
	 * @param $action string Action name (one of 'create', 'modify', 'delete')
	 * @param $title Title object for the gadget, like Special:GadgetManager/foo
	 * @param $reason string Log reason TODO figure out how to implement optional reasons; is empty string good enough?
	 * @param $params array Log parameters TODO document
	 */
	protected function logAction( $action, $title, $reason = '', $params = array() ) {
		$log = new LogPage( 'gadgetman' );
		$log->addEntry( $action, $title, $reason, $params );
	}
}
