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


	class tx_spbetterflex {

		/**
		 * Get exclude items
		 *
		 * @return Array with all flexform fields
		 */
		public function aGetFlexItems () {
			global $TYPO3_LOADED_EXT, $LANG;
			$aFlexForms	= $this->aGetAllFlexForms();
			$aExtKeys	= $TYPO3_LOADED_EXT;
			$aItems		= array();

			foreach ($aFlexForms as $sKey => $aFlexform) {
				// Get extension name
				$sExtName = '';
				foreach ($aExtKeys as $sName => $aValue) {
					$sTempName = ($sName == 'tt_board')    ? '2' : $sTempName; // Bugfix for tt_board !
					$sTempName = ($sName == 'tt_guest')    ? '3' : $sTempName; // Bugfix for tt_guest
					$sTempName = ($sName == 'tt_board')    ? '4' : $sTempName; // Bugfix for tt_board !
					$sTempName = ($sName == 'tt_products') ? '5' : $sTempName; // Bugfix for tt_products
					$sTempName = ($sName == 'tt_news')     ? '9' : $sName;     // Bugfix for tt_news
					if (strpos($sKey, $sTempName) !== false) {
						$sExtName = $sName;
						unset($aExtKeys[$sName]);
						break;
					}
				}
				if (!strlen($sExtName)) {
					continue;
				}

				// Get speaking extension name
				$sSpeakingName = $this->sGetSpeakingName($sExtName);

				// Add flexform fields
				if (is_array($aFlexform['sheets'])) {
					foreach ($aFlexform['sheets'] as $sKey => $aTab) {
						$aFields = $aTab['ROOT']['el'];

						if (is_array($aFields)) {
							foreach ($aFields as $sName => $aConfig) {
								$sLabel = '[FX] '.$sSpeakingName.': '.$LANG->sl($aConfig['TCEforms']['label']);
								$aItems[] = array($sLabel, 'tt_content:'.$sExtName.'_'.trim($sName, '. '));
							}
						}
					}
				}
			}

			return $aItems;
		}


		/**
		 * Get an array of all flexforms from $TCA
		 *
		 * @return Array with flexforms
		 */
		protected function aGetAllFlexForms () {
			global $TCA;
			$aLoadedForms = $TCA['tt_content']['columns']['pi_flexform']['config']['ds'];

			$aForms = array();

			if (is_array($aLoadedForms)) {
				foreach ($aLoadedForms as $sKey => $sValue) {
					$aForms[$sKey] = $this->aGetFlexContent($sValue);
				}
			}

			return $aForms;
		}


		/**
		 * Get speaking extension name from ext_emconf.php
		 *
		 * @return string with speaking name
		 */
		protected function sGetSpeakingName ($psExtKey) {
			if (!strlen($psExtKey)) {
				return '';
			}

			global $TYPO3_LOADED_EXT;
			$sSpeakingName = $psExtKey;

			if (is_array($TYPO3_LOADED_EXT)) {
				$sFileName = $TYPO3_LOADED_EXT[$psExtKey]['siteRelPath'].'ext_emconf.php';
				$sFileName = t3lib_div::getFileAbsFileName($sFileName);

				if ($sFileName && @is_file($sFileName)) {
					$_EXTKEY = $psExtKey;
					$EM_CONF = array();
					include($sFileName);
					if (is_array($EM_CONF[$psExtKey]) && strlen($EM_CONF[$psExtKey]['title'])) {
						$sSpeakingName = htmlspecialchars($EM_CONF[$psExtKey]['title']);
					}
				}
			}

			return $sSpeakingName;
		}


		/**
		 * Remove excluded fields from flexform in backend form
		 *
		 */
		public function getFlexFormDS_postProcessDS (&$paStructure, $paConfig, $paRow, $psTable, $psFieldName) {
			if (!is_array($paStructure['sheets']) || !is_array($paRow)) {
				return;
			}

			$sExtName		= $this->sGetExtName($paRow);
			$aFieldNames	= $this->aGetExcludedFields($sExtName, $paRow['pid']);

			if (is_array($aFieldNames)) {
				foreach ($aFieldNames as $sFieldName) {
					foreach ($paStructure['sheets'] as $sKey => $mTab) {
						// Check for file reference
						if (is_string($mTab) && strtolower(substr($mTab, -4)) == '.xml') {
							$paStructure['sheets'][$sKey] = $this->aGetFlexContent($mTab);
						}

						// Remove field
						if (array_key_exists($sFieldName, $paStructure['sheets'][$sKey]['ROOT']['el'])) {
							unset($paStructure['sheets'][$sKey]['ROOT']['el'][$sFieldName]);
						}

						// Remove whole tab if empty
						if (!count($paStructure['sheets'][$sKey]['ROOT']['el'])) {
							unset($paStructure['sheets'][$sKey]);
						}
					}
				}
			}
		}


		/**
		 * Get extension from db
		 *
		 * @return String with extension name
		 */
		protected function sGetExtName ($paRow) {
			// Get configuration
			global $TCA, $TYPO3_LOADED_EXT;
			$aFlexList		= $TCA['tt_content']['columns']['pi_flexform']['config']['ds'];
			$sIdentifier	= '';
			$sExtName		= '';

			// Try to get the name from list_type for default extensions
			if ($paRow['CType'] == 'list' && strlen($paRow['list_type'])) {
				$sExtName = preg_replace('/_pi./', '', (string) $paRow['list_type']);
				$sExtName = ($sExtName == '2') ? 'tt_board' : $sExtName;    // Bugfix for tt_board
				$sExtName = ($sExtName == '3') ? 'tt_guest' : $sExtName;    // Bugfix for tt_guest
				$sExtName = ($sExtName == '4') ? 'tt_board' : $sExtName;    // Bugfix for tt_board
				$sExtName = ($sExtName == '5') ? 'tt_products' : $sExtName; // Bugfix for tt_products
				$sExtName = ($sExtName == '9') ? 'tt_news' : $sExtName;     // Bugfix for tt_news
			}

			// If no name was found until now try to find the name in other db fields
			if (!strlen($sExtName) && is_array($TYPO3_LOADED_EXT)) {
				foreach ($TYPO3_LOADED_EXT as $sName => $aValue) {
					// Ignore system extensions
					if ($aValue['type'] == 'S' || $sName == 'version') {
						continue;
					}

					// Check field content
					$sDBContent = $paRow['list_type'].'|'.$paRow['CType'].'|'.$paRow['pi_flexform'];
					if ((strpos($sDBContent, $sName) !== false)) {
						$sExtName = $sName;
						break;
					}
				}
			}

			return $sExtName;
		}


		/**
		 * Get a list of excluded flexform fields from db and ts_config
		 *
		 * @return Array with fieldnames
		 */
		public function aGetExcludedFields ($psExtName='', $piPID=0) {
			if (!strlen($psExtName)) {
				return array();
			}

			global $BE_USER;
			$aFields = array();

			// Get fields from TSConfig
			$aTSConfig = t3lib_BEfunc::getPagesTSconfig((int) $piPID);
			$aTSConfig = $aTSConfig['TCEFORM.']['tt_content.'];
			foreach ($aTSConfig as $sKey => $aValue) {
				if (strpos($sKey, $psExtName) !== false && $aValue['disabled']) {
					$aFields[] = trim(str_replace($psExtName.'_', '', $sKey), '. ');
				}
			}

			// Get fields from db
			$aExcludeFields = explode(',', $BE_USER->groupData['non_exclude_fields']);
			if (is_array($aExcludeFields)) {
				foreach ($aExcludeFields as $sExcludeField) {
					list($sTable, $sName) = explode(':', $sExcludeField);
					if (strpos($sName, $psExtName) !== false) {
						$aFields[] = str_replace($psExtName.'_', '', $sName);
					}
				}
			}

			return $aFields;
		}


		/**
		 * Load a flexform structure from file or string into an array
		 *
		 * @return Array with flex content
		 */
		protected function aGetFlexContent ($psStructure, $pbRecursive=true) {
			$aFlexData = array();

			if (is_string($psStructure) && strlen($psStructure)) {
				if (substr($psStructure, 0, 5) == 'FILE:' || strtolower(substr($psStructure, -4)) == '.xml') {
					// Get flexform from file
					$sFileName = t3lib_div::getFileAbsFileName(str_replace('FILE:','',$psStructure));
					if ($sFileName && @is_file($sFileName)) {
						$aFlexData = t3lib_div::xml2array(t3lib_div::getUrl($sFileName));
					}
				} else {
					// Else get it from value
					$aFlexData = t3lib_div::xml2array($psStructure);
				}

				// Check for file references in tabs (e.g. EXT:comments)
				if ($pbRecursive && is_array($aFlexData) && count($aFlexData)) {
					array_walk_recursive($aFlexData, array($this, 'aGetExternalFlex'));
				}
			}

			return $aFlexData;
		}


		/**
		 * Walk through the flex array to find external flexform definitions
		 *
		 * @return Array with complete flex structure
		 */
		protected function aGetExternalFlex (&$mItem, $sKey) {
			if (is_string($mItem) && strtolower(substr($mItem, -4)) == '.xml') {
				$mItem = $this->aGetFlexContent($mItem, false);
			}
		}

	}


	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']);
	}

?>