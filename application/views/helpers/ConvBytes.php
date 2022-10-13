<?php
/**
 * Helper for convert bytes in KB, MB, GB
 * 
 * @package    webacula
 * @subpackage Helpers
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_ConvBytes {

    public function convBytes($bytes)
    {
        $i = 0;
        $units = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        while( true ){
            if ( $bytes < 1024 ){
               return number_format( $bytes , 2 )." ".$units[$i];
            } 
            $bytes /= 1024;
            $i++;
        }
  
    }

}
