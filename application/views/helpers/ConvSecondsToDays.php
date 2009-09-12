<?php
/**
 * Helper for convert seconds to days
 *  
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 * 
 * Special thanks to http://www.google.com/codesearch
 */

class Zend_View_Helper_convSecondsToDays {

	public function convSecondsToDays($seconds)
	{
		$days = floor($seconds / 86400);
		return ( $days );
	}
}
