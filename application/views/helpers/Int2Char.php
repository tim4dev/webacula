<?php
/**
 * Helper for convert integer value to character '+' or '-'
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_Int2Char {

    public function int2Char($val)
    {
		if ( $val > 0 )	return '+';
		else  
			return '-';
    }
}