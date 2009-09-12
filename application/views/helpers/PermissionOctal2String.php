<?php
/**
 * Helper for convert octal value to permission string
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 * 
 * Special thanks to http://www.google.com/codesearch
 */
class Zend_View_Helper_PermissionOctal2String
{
	

	public function permissionOctal2String($mode)
	{
		if (($mode & 0xC000) === 0xC000) {
			$type = 's';
		} elseif (($mode & 0xA000) === 0xA000) {
			$type = 'l';
		} elseif (($mode & 0x8000) === 0x8000) {
			$type = '-';
		} elseif (($mode & 0x6000) === 0x6000) {
			$type = 'b';
		} elseif (($mode & 0x4000) === 0x4000) {
			$type = 'd';
		} elseif (($mode & 0x2000) === 0x2000) {
			$type = 'c';
		} elseif (($mode & 0x1000) === 0x1000) {
			$type = 'p';
		} else {
			$type = '?';
		}

		$owner  = ($mode & 00400) ? 'r' : '-';
		$owner .= ($mode & 00200) ? 'w' : '-';
		if ($mode & 0x800) {
			$owner .= ($mode & 00100) ? 's' : 'S';
		} else {
			$owner .= ($mode & 00100) ? 'x' : '-';
		}

		$group  = ($mode & 00040) ? 'r' : '-';
		$group .= ($mode & 00020) ? 'w' : '-';
		if ($mode & 0x400) {
			$group .= ($mode & 00010) ? 's' : 'S';
		} else {
			$group .= ($mode & 00010) ? 'x' : '-';
		}

		$other  = ($mode & 00004) ? 'r' : '-';
		$other .= ($mode & 00002) ? 'w' : '-';
		if ($mode & 0x200) {
			$other .= ($mode & 00001) ? 't' : 'T';
		} else {
			$other .= ($mode & 00001) ? 'x' : '-';
		}

		return $type . $owner . $group . $other;
}

}