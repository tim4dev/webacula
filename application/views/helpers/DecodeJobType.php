<?php
/**
 * Decode Job Type
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_DecodeJobType {

    public function decodeJobType($short)
    {
		switch ($short) {
			case 'B':	return 'Backup';
			case 'V':	return 'Verify';
			case 'R':	return 'Restore';
			case 'C':	return 'Console program';
			case 'D':	return 'Admin';
			case 'A':	return 'Archive';
			default:	return 'Unknown';
		}
    }
}
