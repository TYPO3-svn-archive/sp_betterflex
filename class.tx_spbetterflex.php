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

					if (empty($sTempKey)) {
						continue;
					}

					// Check if extension key exists
					foreach ($aExtKeys as $sExtKey => $aExtData) {
						if (strpos($sTempKey, $sExtKey) !== FALSE) {
							$sFinalKey = $sExtKey;
							unset($aExtKeys[$sExtKey]);
							break;
						}
					}

					if (empty($sFinalKey)) {
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
				if ($pbRecursive && is_array($aFlexData) && !empty($aFlexData)) {
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
		 * Modify flexform fields in backend form
		 *
		 * @param array  $paStructure Flexform structure
		 * @param array  $paConfig    Field configuration
		 * @param array  $paRow       Current table row
		 * @param string $psTable     Current table
		 * @param string $psFieldName Current field name
		 */
		public function getFlexFormDS_postProcessDS (array &$paStructure, array $paConfig, array $paRow, $psTable, $psFieldName) {
			$sExtKey   = $this->sGetExtKeyFromRow($paRow);
			$aModified = $this->aGetModifiedFields($sExtKey, $paRow['pid']);
			$aModified = (is_array($aModified)) ? $aModified : array();

			// Get flexform sheets
			if (isset($paStructure['sheets'])) {
				$paStructure['sheets'] = $this->aModifySheets($paStructure['sheets'], $aModified);
			} else {
				$paStructure = @reset($this->aModifySheets(array($paStructure), $aModified));
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
		 * Get a list of modified flexform fields from db and ts_config
		 *
		 * @return Array with fields
		 */
		public function aGetModifiedFields ($psExtKey, $piPID = 0) {
			if (empty($psExtKey)) {
				return array();
			}

			if ($piPID < 1) {
				preg_match('/id=(.*)/', t3lib_div::_GP('returnUrl'), $aMatches);
				$piPID = (!empty($aMatches[1])) ? $aMatches[1] : 0;
			}

			$aTSConfig = t3lib_BEfunc::getPagesTSconfig((int) $piPID);
			$aTSConfig = (!empty($aTSConfig['TCEFORM.']['tt_content.'])) ? $aTSConfig['TCEFORM.']['tt_content.'] : array();
			$aTSConfig = t3lib_div::removeDotsFromTS($aTSConfig);
			$aExclude  = explode(',', $GLOBALS['BE_USER']->groupData['non_exclude_fields']);
			$aFields   = array();

			// Get fields from TSConfig
			foreach ($aTSConfig as $sKey => $aConfig) {
				if (strpos($sKey, $psExtKey) !== FALSE && !empty($aConfig)) {
					$sKey = trim(str_replace($psExtKey . '_', '', $sKey));
					$aFields[$sKey] = $aConfig;
				}
			}

			// Get fields from db
			if (is_array($aExclude)) {
				foreach ($aExclude as $sField) {
					list(, $sKey) = explode(':', $sField);

					if (empty($sKey)) {
						continue;
					}

					if (strpos($sKey, $psExtKey) !== FALSE) {
						$sKey = trim(str_replace($psExtKey . '_', '', $sKey));
						$aFields[$sKey] = array('disabled' => TRUE);
					}
				}
			}

			return $aFields;
		}


		/**
		 * Modify flexform fields
		 *
		 * @param array $paSheets   Flexform sheets to manipulate
		 * @param array $paModified Modified fields
		 * @return Array with modified sheets
		 */
		protected function aModifySheets(array $paSheets, array $paModified) {
			if (empty($paSheets) || empty($paModified)) {
				return $paSheets;
			}

			foreach ($paSheets as $mKey => $mTab) {
				// Check for file reference
				if (is_string($mTab) && strtolower(substr($mTab, -4)) == '.xml') {
					$mTab = $this->aGetFlexContent($mTab);
				}

				if (empty($mTab['ROOT']['el']) || !is_array($mTab['ROOT']['el'])) {
					continue;
				}

				// Modify each configured field
				foreach ($paModified as $sFieldName => $aConfig) {
					if (!isset($paSheets[$mKey]['ROOT']['el'][$sFieldName])) {
						continue;
					}

					$aField       = $paSheets[$mKey]['ROOT']['el'][$sFieldName];
					$aRemoveItems = (!empty($aConfig['removeItems'])) ? t3lib_div::trimExplode(',', $aConfig['removeItems'], TRUE) : array();
					$aRenameItems = (!empty($aConfig['altLabels'])) ? $aConfig['altLabels'] : array();
					$aAddItems    = (!empty($aConfig['addItems']))  ? $aConfig['addItems']  : array();

					unset($paModified[$sFieldName]);
					unset($aConfig['removeItems']);
					unset($aConfig['altLabels']);
					unset($aConfig['addItems']);

					// Remove excludes fields
					if (!empty($aConfig['disabled'])) {
						unset($paSheets[$mKey]['ROOT']['el'][$sFieldName]);
						continue;
					}

					// Modify fields
					if (!empty($aField['TCEforms']) && is_array($aField['TCEforms']) && is_array($aConfig)) {
						$paSheets[$mKey]['ROOT']['el'][$sFieldName]['TCEforms'] = t3lib_div::array_merge_recursive_overrule($aField['TCEforms'], $aConfig);
					}

					if ((!empty($aRemoveItems) || !empty($aRenameItems)) && isset($aField['TCEforms']['config']['items']) && is_array($aField['TCEforms']['config']['items'])) {
						foreach ($aField['TCEforms']['config']['items'] as $sItemKey => $aItemConfig) {
							if (empty($aItemConfig[1])) {
								continue; // Option has no key, no manipulation possible
							}

							$sItemIdent = strtolower($aItemConfig[1]);

							// Remove options from select
							if (!empty($aRemoveItems)) {
								foreach ($aRemoveItems as $sRemoveKey => $sRemoveValue) {
									if (strtolower($sRemoveValue) == $sItemIdent) {
										unset($paSheets[$mKey]['ROOT']['el'][$sFieldName]['TCEforms']['config']['items'][$sItemKey]);
										unset($aRemoveItems[$sRemoveKey]);
									}
								}
							}

							// Rename options in select
							if (!empty($aRenameItems)) {
								foreach ($aRenameItems as $sRenameKey => $sRenameValue) {
									if (strtolower($sRenameKey) == $sItemIdent) {
										$paSheets[$mKey]['ROOT']['el'][$sFieldName]['TCEforms']['config']['items'][$sItemKey][0] = $sRenameValue;
										unset($aRenameItems[$sRenameKey]);
									}
								}
							}
						}
					}

					// Add options to select
					if (!empty($aAddItems)) {
						foreach ($aAddItems as $sAddKey => $sAddLabel) {
							unset($aAddItems[$sAddKey]);
							$paSheets[$mKey]['ROOT']['el'][$sFieldName]['TCEforms']['config']['items'][] = array(
								$sAddLabel,
								$sAddKey,
							);
						}
					}
				}

				// Remove empty tabs
				if (empty($paSheets[$mKey]['ROOT']['el'])) {
					unset($paSheets[$mKey]);
				}
			}

			return $paSheets;
		}

	}


	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']);
	}

?>