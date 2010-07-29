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


	/**
	 * Flexform manipulation class for the 'sp_betterflex' extension.
	 *
	 * @author     Kai Vogel <kai.vogel ( at ) speedprogs.de>
	 * @package    TYPO3
	 * @subpackage tx_spbetterflex
	 */
	class tx_spbetterflex {

		/**
		 * @var array Replacements for old extensions
		 * @access protected
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
		 * @return array
		 * @access public
		 */
		public function aGetFlexItems() {
			$aReturn = array();

			// Get registered flexforms
			$aFlexForms = $this->aGetFlexForms();
			if (!is_array($aFlexForms)) {
				return array();
			}

			// Add flexform fields
			foreach ($aFlexForms as $sExtKey => $aFlexform) {
				$aItems  = $this->aGetAccessListItems($aFlexform, $sExtKey);
				$aReturn = array_merge($aReturn, $aItems);
			}

			return $aReturn;
		}


		/**
		 * Get an array of loaded flexforms from $TCA
		 *
		 * @return array
		 * @access protected
		 */
		protected function aGetFlexForms() {
			$aExtKeys = $GLOBALS['TYPO3_LOADED_EXT'];
			$aForms   = array();
			$aResult  = array();

			// Get loaded flexforms
			$aLoadedForms = $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'];
			unset($aLoadedForms['default']);

			// Get extensions with registered flexform
			if (is_array($aLoadedForms)) {
				foreach ($aLoadedForms as $sFlexKey => $sFlexData) {
					$sFinalKey = '';

					if ((int) $sFlexKey) {
						$sFlexKey = str_replace(array_keys($this->aReplace), array_values($this->aReplace), $sFlexKey);
					}

					if (empty($sFlexKey)) {
						continue;
					}

					// Check if extension key exists
					foreach ($aExtKeys as $sExtKey => $aExtData) {
						if (strpos($sFlexKey, $sExtKey) !== FALSE) {
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
		 * @param string $psStructure XML structure of the flexform
		 * @return array
		 * @access
		 */
		protected function aGetFlexContent($psStructure) {
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

				// Check for file references in sheets
				$aFlexData = t3lib_div::resolveAllSheetsInDS($aFlexData);
			}

			return $aFlexData;
		}


		/**
		 * Get access list items from flexform
		 *
		 * @param array  $paStructure Flexform structure
		 * @param string $psExtKey    Extension key
		 * @param string $psExtName   Extension name
		 * @return array
		 * @access protected
		 */
		protected function aGetAccessListItems(array $paStructure, $psExtKey) {
			if (empty($paStructure['sheets']) || !is_array($paStructure['sheets']) || empty($psExtKey)) {
				return;
			}

			$sLabel   = "[FX] %s: %s";
			$sIdent   = "tt_content:%s_%s";
			$sExtName = $this->sGetExtName($psExtKey);
			$aReturn  = array();

			// Traverse into sheets
			foreach ($paStructure['sheets'] as $aSheet) {
				if (empty($aSheet['ROOT']['el']) || !is_array($aSheet['ROOT']['el'])) {
					continue;
				}

				// Get all fields in sheet
				foreach ($aSheet['ROOT']['el'] as $sFieldName => $aConfig) {
					$sFieldLabel = (!empty($aConfig['TCEforms']['label'])) ? $GLOBALS['LANG']->sl($aConfig['TCEforms']['label']) : '';
					$sFieldLabel = (!empty($sFieldLabel)) ? $sFieldLabel : $sFieldName;

					$aReturn[] = array(
						sprintf($sLabel, $sExtName, rtrim($sFieldLabel, ':')),
						sprintf($sIdent, $psExtKey, rtrim($sFieldName, '. ')),
					);
				}
			}

			return $aReturn;
		}


		/**
		 * Get speaking extension name from ext_emconf.php
		 *
		 * @param string $psExtKey Extension key
		 * @return string
		 * @access protected
		 */
		protected function sGetExtName($psExtKey) {
			if (empty($psExtKey)) {
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
		 * Modify flexform fields in backend form
		 *
		 * @param array  $paStructure Flexform structure
		 * @param array  $paConfig    Field configuration
		 * @param array  $paRow       Current table row
		 * @param string $psTable     Current table
		 * @param string $psFieldName Current field name
		 * @access public
		 */
		public function getFlexFormDS_postProcessDS(array &$paStructure, array $paConfig, array $paRow, $psTable, $psFieldName) {
			$sExtKey      = $this->sGetExtKeyFromRow($paRow);
			$aModified    = $this->aGetModifiedFields($sExtKey, $paRow['pid']);
			$aModified    = (is_array($aModified)) ? $aModified : array();
			$bSingleSheet = (!isset($paStructure['sheets']) || !is_array($paStructure['sheets']));
			$aMetaConf    = (!empty($paStructure['meta'])) ? $paStructure['meta'] : array();
			$paStructure  = t3lib_div::resolveAllSheetsInDS($paStructure);

			// Modify flexform sheets
			foreach ($paStructure['sheets'] as $sName => $aSheet) {
				if (empty($aSheet['ROOT']['el']) || !is_array($aSheet['ROOT']['el'])) {
					continue;
				}

				// Modify all configured fields in sheet
				$paStructure['sheets'][$sName]['ROOT']['el'] = $this->aModifyFields($aSheet['ROOT']['el'], $aModified);

				// Remove empty tabs
				if (empty($paStructure['sheets'][$sName]['ROOT']['el'])) {
					unset($paStructure['sheets'][$sName]);
				}
			}

			// Reverse single flexform structure
			if ($bSingleSheet && isset($paStructure['sheets']['sDEF'])) {
				$paStructure = $paStructure['sheets']['sDEF'];
			}

			// Reverse meta configuration
			if (!empty($aMetaConf)) {
				$paStructure['meta'] = $aMetaConf;
			}
		}


		/**
		 * Get extension key from db fields
		 *
		 * @param array $paRow Table row to check for extension key
		 * @return string
		 * @access protected
		 */
		protected function sGetExtKeyFromRow(array $paRow) {
			$aExtKeys  = $GLOBALS['TYPO3_LOADED_EXT'];
			$aDBFields = array('list_type', 'CType', 'pi_flexform');

			// Search in db fields for extension key
			foreach ($aDBFields as $sField) {
				if (empty($paRow[$sField])) {
					continue;
				}

				$sFinalKey = '';
				$sRowKey   = $paRow[$sField];

				if ((int) $sRowKey) {
					$sRowKey = str_replace(array_keys($this->aReplace), array_values($this->aReplace), $sRowKey);
				}

				// Check if extension key exists
				foreach ($aExtKeys as $sExtKey => $aExtData) {
					if (strpos($sRowKey, $sExtKey) !== FALSE) {
						return $sExtKey;
					}
				}
			}

			return '';
		}


		/**
		 * Get a list of modified flexform fields from db and ts_config
		 *
		 * @param string  $psExtKey Extension key
		 * @param integer $piPID    PID of current dataset
		 * @return array
		 * @access public
		 */
		public function aGetModifiedFields($psExtKey, $piPID = 0) {
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
		 * @param array $paSheets   Flexform sheet to manipulate
		 * @param array $paModified Modified fields
		 * @return array
		 * @access protected
		 */
		protected function aModifyFields(array $paFields, array &$paModified) {
			if (empty($paFields) || empty($paModified)) {
				return $paFields;
			}

			// Modify each configured field
			foreach ($paModified as $sFieldName => $aConfig) {
				if (!isset($paFields[$sFieldName])) {
					continue;
				}

				$aField       = $paFields[$sFieldName];
				$aRemoveItems = (!empty($aConfig['removeItems'])) ? t3lib_div::trimExplode(',', $aConfig['removeItems'], TRUE) : array();
				$aRenameItems = (!empty($aConfig['altLabels'])) ? $aConfig['altLabels'] : array();
				$aAddItems    = (!empty($aConfig['addItems']))  ? $aConfig['addItems']  : array();

				unset($paModified[$sFieldName]);
				unset($aConfig['removeItems']);
				unset($aConfig['altLabels']);
				unset($aConfig['addItems']);

				// Remove excludes fields
				if (!empty($aConfig['disabled'])) {
					unset($paFields[$sFieldName]);
					continue;
				}

				// Modify fields
				if (!empty($aField['TCEforms']) && is_array($aField['TCEforms']) && is_array($aConfig)) {
					$paFields[$sFieldName]['TCEforms'] = t3lib_div::array_merge_recursive_overrule($aField['TCEforms'], $aConfig);
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
									unset($paFields[$sFieldName]['TCEforms']['config']['items'][$sItemKey]);
									unset($aRemoveItems[$sRemoveKey]);
								}
							}
						}

						// Rename options in select
						if (!empty($aRenameItems)) {
							foreach ($aRenameItems as $sRenameKey => $sRenameValue) {
								if (strtolower($sRenameKey) == $sItemIdent) {
									$paFields[$sFieldName]['TCEforms']['config']['items'][$sItemKey][0] = $sRenameValue;
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
						$paFields[$sFieldName]['TCEforms']['config']['items'][] = array(
							$sAddLabel,
							$sAddKey,
						);
					}
				}
			}

			return $paFields;
		}

	}


	// XCLASS
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sp_betterflex/class.tx_spbetterflex.php']);
	}

?>