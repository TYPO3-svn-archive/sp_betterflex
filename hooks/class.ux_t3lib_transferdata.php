<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2009 Kai Vogel <kai.vogel ( at ) speedprogs.de>
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


	require_once(t3lib_extMgm::extPath('sp_betterflex').'class.tx_spbetterflex.php');


	class ux_t3lib_transferData extends t3lib_transferData {

		/**
		 * Adding "special" types to the $dataAcc array of selector items
		 *
		 * This method is used to highlight selected fields in the selector
		 * box in group configuration!
		 *
		 * @param	array		Array with numeric keys, containing values for the selector box, prepared for interface.
		 * @param	array		The array of original elements - basically the field value exploded by ","
		 * @param	string		The "special" key from the TCA config of the field. Determines the type of processing in here.
		 * @return	array		Modified $dataAcc array
		 */
		function selectAddSpecial ($paDataAcc, $paElements, $psSpecialKey) {
			global $TCA, $TYPO3_DB, $SOBE;

			$aDataAcc = parent::selectAddSpecial ($paDataAcc, $paElements, $psSpecialKey);
			$aExcludeFields = array();

			if ((string) $psSpecialKey == 'exclude') {
				// Get all flexform fields
				$oFlex = t3lib_div::makeInstance('tx_spbetterflex');
				$aFlexItems = $oFlex->aGetFlexItems();
				unset($oFlex);

				// Get exclude fields from user configuration
				$aExcludeFields = array();
				$iCurrentGroupID = $SOBE->editconf['be_groups'] ? reset(array_keys($SOBE->editconf['be_groups'])) : 0;
				if ($aGroupData = $TYPO3_DB->exec_SELECTgetRows('non_exclude_fields','be_groups','uid='.(int)$iCurrentGroupID,'','','1')) {
					$aExcludeFields = explode(',', $aGroupData[0]['non_exclude_fields']);
				}

				// Get exclude fields from post data if group was changed and saved
				if ($aData = t3lib_div::_POST('data')) {
					if (is_array($aData['be_groups'])) {
						$aCurrent = current($aData['be_groups']);
						$aExcludeFields = $aCurrent['non_exclude_fields'];
					}
				}

				// Highlight all excluded fields
				if (is_array($aExcludeFields) && count($aExcludeFields) && is_array($aFlexItems)) {
					foreach ($aExcludeFields as $sFieldName) {
						foreach ($aFlexItems as $aField) {
							if (strpos($aField[1], $sFieldName) !== false) {
								$aDataAcc[] = rawurlencode($aField[1]).'|'.rawurlencode(rtrim($aField[0], ':'));
							}
						}
					}
				}
			}

			return $aDataAcc;
		}
	}


	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/hooks/class.ux_t3lib_transferdata.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/hooks/class.ux_t3lib_transferdata.php']);
	}

?>
