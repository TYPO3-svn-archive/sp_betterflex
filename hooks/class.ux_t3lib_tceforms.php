<?php
	/***************************************************************
	 *  Copyright notice
	 *
	 *  (c) 2010 Kai Vogel <kai.vogel ( at ) speedprogs.de>
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
	 *
	 *  This script is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU General Public License for more details.
	 *
	 *  This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/


	t3lib_div::requireOnce(t3lib_extMgm::extPath('sp_betterflex') . 'class.tx_spbetterflex.php');


	/**
	 * TCEforms hook for the 'sp_betterflex' extension.
	 *
	 * @author     Kai Vogel <kai.vogel ( at ) speedprogs.de>
	 * @package    TYPO3
	 * @subpackage tx_spbetterflex
	 */
	class ux_t3lib_TCEforms extends t3lib_TCEforms {

		/**
		 * Add selector box items of more exotic kinds.
		 *
		 * Thist method is used to show all fields in the selector box
		 * in group configuration!
		 *
		 * @param array  The array of items (label,value,icon)
		 * @param array  The "columns" array for the field (from TCA)
		 * @param array  TSconfig for the table/row
		 * @param string The fieldname
		 * @return array
		 * @access public
		 */
		public function addSelectOptionsToItemArray ($paItems, $paFieldValue, $paTSconfig, $psField) {
			$aItems = parent::addSelectOptionsToItemArray($paItems, $paFieldValue, $paTSconfig, $psField);

			// Get flexform items to exclude
			if (!empty($paFieldValue['config']['special']) && $paFieldValue['config']['special'] == 'exclude') {
				$oFlex      = t3lib_div::makeInstance('tx_spbetterflex');
				$aFlexItems = $oFlex->aGetFlexItems();
				unset($oFlex);

				if (is_array($aFlexItems)) {
					foreach ($aFlexItems as $aFlexItem) {
						if (empty($aFlexItem[0]) || empty($aFlexItem[1])) {
							continue;
						}

						$aItems[] = array(rtrim($aFlexItem[0], ':'), $aFlexItem[1], '', '');
					}
				}
			}

			return $aItems;
		}

	}


	// XCLASS
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/hooks/class.ux_t3lib_tceforms.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/hooks/class.ux_t3lib_tceforms.php']);
	}

?>
