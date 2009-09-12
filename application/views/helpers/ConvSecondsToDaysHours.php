<?php
/**
 * Helper for convert seconds to days:hours
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_convSecondsToDaysHours {

	public function convSecondsToDaysHours($seconds)
	{
		$days = floor($seconds / 86400);
		if ($days > 0)
			$seconds -= $days * 86400;

		$hours = floor($seconds / 3600);
		if ($days > 0 || $hours > 0)
			$seconds -= $hours * 3600;

		return ( $days . ":" . $hours );
	}
}
