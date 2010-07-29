<?php

########################################################################
# Extension Manager/Repository config file for ext "sp_betterflex".
#
# Auto generated 29-07-2010 23:52
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Better Flexforms',
	'description' => 'Modify and exclude flexform fields like normal table fields via backend group configuration or page TSConfig.',
	'category' => 'be',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'bottom',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Kai Vogel',
	'author_email' => 'kai.vogel ( at ) speedprogs.de',
	'author_company' => 'www.speedprogs.de',
	'version' => '1.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.0.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:8:{s:9:"ChangeLog";s:4:"821f";s:25:"class.tx_spbetterflex.php";s:4:"63ea";s:12:"ext_icon.gif";s:4:"5070";s:17:"ext_localconf.php";s:4:"d569";s:14:"ext_tables.php";s:4:"af29";s:14:"doc/manual.sxw";s:4:"cb23";s:33:"hooks/class.ux_t3lib_tceforms.php";s:4:"06b3";s:37:"hooks/class.ux_t3lib_transferdata.php";s:4:"66ed";}',
	'suggests' => array(
	),
);

?>