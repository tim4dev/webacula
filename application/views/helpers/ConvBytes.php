<?php
/**
 * Helper for convert bytes in KB, MB, GB
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_ConvBytes {

    public function convBytes($bytes)
    {
		switch (TRUE) {
			case ($bytes == 0)       :		return 0;
			case ($bytes < pow(2,10)):                             return $bytes.' bytes';
			case ($bytes >= pow(2,10) && $bytes < pow(2,20)):      return round($bytes / pow(2,10), 0).' KB';
			case ($bytes >= pow(2,20) && $bytes < pow(2,30)):      return round($bytes / pow(2,20), 1).' MB';
			case ($bytes >= pow(2,30) && $bytes < pow(2,40)):      return round($bytes / pow(2,30), 1).' GB';
			case ($bytes > pow(2,40)):                             return round($bytes / pow(2,40), 2).' TB';
		}        
    }
}
