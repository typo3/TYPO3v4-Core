<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Displays 'SU' link with sprite icon to change current backend user to target (non-admin) backendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_ViewHelpers_SUViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param Tx_Beuser_Domain_Model_BackendUser $backendUser Target backendUser to switch active session to
	 * @param boolean $emulate Return to current session or logout after target session termination?
	 * @return string
	 */
	public function render(Tx_Beuser_Domain_Model_BackendUser $backendUser, $emulate = FALSE) {
			// No SU to another administrator users
		if ($backendUser->getIsAdministrator())
			return '';

		$title = $GLOBALS['LANG']->getLL('switchUserTo', TRUE) . ' ' . $backendUser->getUserName() . ' ' . $GLOBALS['LANG']->getLL('switchBackMode', TRUE);

		return '<a href="' . t3lib_div::linkThisScript(array('SwitchUser' => $backendUser->getUid(), 'switchBackUser' => $emulate)) .
				'" target="_top" title="' . htmlspecialchars($title) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-system-backend-user-' . ($emulate ? 'emulate' : 'switch')) .
				'</a>';
	}
}

?>
