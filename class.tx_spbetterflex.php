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


	class tx_spbetterflex {

		/**
		 * @var array Replacements for old extensions
		 */
		protected $aReplace = array(
				'2' => 'tt_board',
				'3' => 'tt_guest',
				'4' => 'tt_board',
				'5' => 'tt_products',
				'9' => 'tt_news',
			);


		/**
		 * Get exclude items
		 *
		 * @return Array with all flexform fields
		 */
		public function aGetFlexItems () {
			$aReturn = array();

			// Get registered flexforms
			$aFlexForms = $this->aGetFlexForms();
			if (!is_array($aFlexForms)) {
				return array();
			}

			foreach ($aFlexForms as $sExtKey => $aFlexform) {
				// Get extension name
				$sExtName = $this->sGetExtName($sExtKey);

				// Get flexform sheets
				$aSheets = (isset($aFlexform['sheets'])) ? $aFlexform['sheets'] : array($aFlexform);

				// Add flexform fields
				if (is_array($aSheets)) {
					foreach ($aSheets as $aTab) {
						$aItems  = $this->aGetAccessListItems($aTab, $sExtKey, $sExtName);
						$aReturn = array_merge($aReturn, $aItems);
					}
				}
			}

			return $aReturn;
		}


		/**
		 * Get an array of loaded flexforms from $TCA
		 *
		 * @return Array with flexforms
		 */
		protected function aGetFlexForms () {
			$aExtKeys = $GLOBALS['TYPO3_LOADED_EXT'];
			$aForms   = array();
			$aResult  = array();

			// Get loaded flexforms
			$aLoadedForms = $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'];
			unset($aLoadedForms['default']);

			// Get extensions with registered flexform
			if (is_array($aLoadedForms)) {
				foreach ($aLoadedForms as $sFlexKey => $sFlexData) {
					$sTempKey  = str_replace(array_keys($this->aReplace), array_values($this->aReplace), $sFlexKey);
					$sFinalKey = '';

					// Check if extension key exists
					foreach ($aExtKeys as $sExtKey => $aExtData) {
						if (strpos($sTempKey, $sExtKey) !== FALSE) {
							$sFinalKey = $sExtKey;
							unset($aExtKeys[$sExtKey]);
							break;
						}
					}

					if (!strlen($sFinalKey)) {
						continue;
					}

					// Get flexform content
					$aResult[$sFinalKey] = $this->aGetFlexContent($sFlexData);
				}
			}

			return $aResult;
		}


		/**
		 * Load a flexform structure from file or string into an array
		 *
		 * @param string  $psStructure XML structure of the flexform
		 * @param boolean $pbRecursive Traverse flexform nodes recursively
		 * @return Array with flex content
		 */
		protected function aGetFlexContent ($psStructure, $pbRecursive = TRUE) {
			$aFlexData = array();

			if (is_string($psStructure) && strlen($psStructure)) {
				if (substr($psStructure, 0, 5) == 'FILE:' || strtolower(substr($psStructure, -4)) == '.xml') {
					// Get flexform from file
					$sFileName = t3lib_div::getFileAbsFileName(str_replace('FILE:', '', $psStructure));
					if ($sFileName && @is_file($sFileName)) {
						$aFlexData = t3lib_div::xml2array(t3lib_div::getUrl($sFileName));
					}
				} else {
					// Get flexform from value
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
		 * @param mixed $pmItem Current flexform node
		 * @return Array with complete flex structure
		 */
		protected function aGetExternalFlex (&$pmItem) {
			if (is_string($pmItem) && strtolower(substr($pmItem, -4)) == '.xml') {
				$pmItem = $this->aGetFlexContent($pmItem, FALSE);
			}
		}


		/**
		 * Get speaking extension name from ext_emconf.php
		 *
		 * @param string $psExtKey Extension key
		 * @return string with speaking name
		 */
		protected function sGetExtName ($psExtKey) {
			if (!strlen($psExtKey)) {
				return '';
			}

			$sResult = $psExtKey;

			if (is_array($GLOBALS['TYPO3_LOADED_EXT'])) {
				$sFileName = $GLOBALS['TYPO3_LOADED_EXT'][$psExtKey]['siteRelPath'] . 'ext_emconf.php';
				$sFileName = t3lib_div::getFileAbsFileName($sFileName);

				if ($sFileName && @is_file($sFileName)) {
					$_EXTKEY = $psExtKey;
					$EM_CONF = array();
					include($sFileName);

					if (is_array($EM_CONF[$psExtKey]) && strlen($EM_CONF[$psExtKey]['title'])) {
						$sResult = $EM_CONF[$psExtKey]['title'];
					}
				}
			}

			return $sResult;
		}


		/**
		 * Get access list items from flexform
		 *
		 * @param array  $paStructure Flexform structure
		 * @param string $psExtKey    Extension key
		 * @param string $psExtName   Extension name
		 * @return array
		 */
		protected function aGetAccessListItems(array $paStructure, $psExtKey, $psExtName) {
			if (empty($paStructure['ROOT']['el']) || !is_array($paStructure['ROOT']['el']) || empty($psExtKey) || empty($psExtName)) {
				return array();
			}

			$sLabel  = "[FX] %s: %s";
			$sIdent  = "tt_content:%s_%s";
			$aReturn = array();

			foreach ($paStructure['ROOT']['el'] as $sFieldName => $aConfig) {
				$sFieldLabel = (!empty($aConfig['TCEforms']['label'])) ? $GLOBALS['LANG']->sl($aConfig['TCEforms']['label']) : '';
				$sFieldLabel = (!empty($sFieldLabel)) ? $sFieldLabel : $sFieldName;

				$aReturn[] = array(
					sprintf($sLabel, $psExtName, rtrim($sFieldLabel, ':')),
					sprintf($sIdent, $psExtKey,  rtrim($sFieldName, '. ')),
				);
			}

			return $aReturn;
		}


		/**
		 * Remove excluded flexform fields in backend form
		 *
		 * @param array  $paStructure Flexform structure
		 * @param array  $paConfig    Field configuration
		 * @param array  $paRow       Current table row
		 * @param string $psTable     Current table
		 * @param string $psFieldName Current field name
		 */
		public function getFlexFormDS_postProcessDS (array &$paStructure, array $paConfig, array $paRow, $psTable, $psFieldName) {
			$sExtKey     = $this->sGetExtKeyFromRow($paRow);
			$aFieldNames = $this->aGetExcludedFields($sExtKey, $paRow['pid']);

			// Get flexform sheets
			if (isset($paStructure['sheets'])) {
				$aSheets = &$paStructure['sheets'];
			} else {
				$aSheets = array(&$paStructure);
			}

			// Remove excluded fields and empty tabs
			if (is_array($aFieldNames)) {
				foreach ($aFieldNames as $sFieldName) {
					foreach ($aSheets as $mKey => $mTab) {

						// Check for file reference
						if (is_string($mTab) && strtolower(substr($mTab, -4)) == '.xml') {
							$mTab = $this->aGetFlexContent($mTab);
						}

						// Remove field
						if (!empty($mTab['ROOT']['el']) && is_array($mTab['ROOT']['el'])
						 && array_key_exists($sFieldName, $mTab['ROOT']['el'])) {
							unset($aSheets[$mKey]['ROOT']['el'][$sFieldName]);
						}

						// Remove whole tab if empty
						if (empty($aSheets[$mKey]['ROOT']['el'])) {
							unset($aSheets[$mKey]);
						}
					}
				}
			}
		}


		/**
		 * Get extension key from db fields
		 *
		 * @param array $paRow Table row to check for extension key
		 * @return String with extension key
		 */
		protected function sGetExtKeyFromRow (array $paRow) {
			$aExtKeys  = $GLOBALS['TYPO3_LOADED_EXT'];
			$aDBFields = array('list_type', 'CType', 'pi_flexform');

			// Search in db fields for extension key
			foreach ($aDBFields as $sField) {
				if (empty($paRow[$sField])) {
					continue;
				}

				$sTempKey  = str_replace(array_keys($this->aReplace), array_values($this->aReplace), $paRow[$sField]);
				$sFinalKey = '';

				// Check if extension key exists
				foreach ($aExtKeys as $sExtKey => $aExtData) {
					if (strpos($sTempKey, $sExtKey) !== FALSE) {
						return $sExtKey;
					}
				}
			}

			return '';
		}


		/**
		 * Get a list of excluded flexform fields from db and ts_config
		 *
		 * @return Array with fieldnames
		 */
		public function aGetExcludedFields ($psExtKey, $piPID = 0) {
			if (empty($psExtKey)) {
				return array();
			}

			$aTSConfig = t3lib_BEfunc::getPagesTSconfig((int) $piPID);
			$aTSConfig = $aTSConfig['TCEFORM.']['tt_content.'];
			$aExclude  = explode(',', $GLOBALS['BE_USER']->groupData['non_exclude_fields']);
			$aFields   = array();

			// Get fields from TSConfig
			foreach ($aTSConfig as $sKey => $aConfig) {
				if (strpos($sKey, $psExtKey) !== FALSE && !empty($aConfig['disabled'])) {
					$aFields[] = trim(str_replace($psExtKey . '_', '', $sKey), '. ');
				}
			}

			// Get fields from db
			if (is_array($aExclude)) {
				foreach ($aExclude as $sField) {
					list(, $sKey) = explode(':', $sField);
					if (strpos($sKey, $psExtKey) !== FALSE) {
						$aFields[] = str_replace($psExtKey . '_', '', $sKey);
					}
				}
			}

			return $aFields;
		}

	}


	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']);
	}

?>