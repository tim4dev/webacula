<?php
/**
 * Decode Job Type
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Wanderlei Hüttel <wanderlei.huttel@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_DecodeJobLevel {

    public function decodeJobLevel($short)
    {
                switch ($short) {
                        case 'F':       return 'Full';
                        case 'I':       return 'Incremental';
                        case 'D':       return 'Differential';
                        case 'S':       return 'Since';
                        case 'C':       return 'VerifyFromCatalog';
                        case 'V':       return 'InitCatalog';
                        case 'O':       return 'VolumeToCatalog';
                        case 'd':       return 'DiskToCatalog';
                        case 'A':       return 'Data';
                        case 'B':       return 'Base';
                        default:        return 'Unknown';
                }
    }
}
