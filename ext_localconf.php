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


	if (!defined('TYPO3_MODE')) {
		die('Access denied.');
	}

	// Hook required methodes
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php']     = t3lib_extMgm::extPath($_EXTKEY) . '/hooks/class.ux_t3lib_tceforms.php';
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_transferdata.php'] = t3lib_extMgm::extPath($_EXTKEY) . '/hooks/class.ux_t3lib_transferdata.php';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] = 'EXT:' . $_EXTKEY . '/class.tx_spbetterflex.php:&tx_spbetterflex';

?>
