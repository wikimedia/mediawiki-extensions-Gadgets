<?php

/**
 * 
 * API for setting Gadget's preferences
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

class ApiGetGadgetPrefs extends ApiBase {

	public function execute() {
		$user = RequestContext::getMain()->getUser();
		
		$params = $this->extractRequestParams();
		//Check permissions
		if ( !$user->isLoggedIn() ) {
			$this->dieUsage( 'You must be logged-in to get gadget\'s preferences', 'notloggedin' );
		}

		$gadget = $params['gadget'];

		//Checks if the gadget actually exists
		$gadgetsList = Gadget::loadStructuredList();
		$found = false;
		foreach ( $gadgetsList as $section => $gadgets ) {
			if ( isset( $gadgets[$gadget] ) ) {
				$found = true;
				break;
			}
		}
		
		if ( !$found ) {
			$this->dieUsage( 'Gadget not found', 'notfound' );
		}
		
		$prefsDescriptionJson = Gadget::getGadgetPrefsDescription( $gadget );
		$prefsDescription = FormatJson::decode( $prefsDescriptionJson, true );
		
		if ( $prefsDescription === null ) {
			$this->dieUsage( "Gadget $gadget does not have any preference.", 'noprefs' );
		}

		$userPrefs = Gadget::getUserPrefs( $user, $gadget );
		
		//Add user preferences to preference description
		foreach ( $userPrefs as $pref => $value ) {
			$prefsDescription['fields'][$pref]['value'] = $value;
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $prefsDescription );
	}

	public function getAllowedParams() {
		return array(
			'gadget'   	=> array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		);
	}

	public function getParamDescription() {
		return array(
			'gadget'  	=> 'The name of the gadget'
		);
	}

	public function getDescription() {
		return 'Allows user code to get preferences for gadgets, along with preference descriptions';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
