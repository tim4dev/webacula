<?php
/**
 * Decode Job Type
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_DecodeJobType {

    public function decodeJobType($short)
    {
		switch ($short) {
			case 'B':	return 'Backup';
			case 'V':	return 'Verify';
			case 'R':	return 'Restore';
			case 'C':	return 'Copy';
			case 'c':	return 'Copy Job';
			case 'D':	return 'Admin';
			case 'A':	return 'Archive';
			case 'g':	return 'Migration';
			default:	return 'Unknown';
		}
    }
}
