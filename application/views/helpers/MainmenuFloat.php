<?php
/**
 *
 * Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see http://www.gnu.org/licenses/
 *
 *
 * Helper for render last jobs
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
require_once 'Zend/View/Helper/Abstract.php';

class Zend_View_Helper_MainmenuFloat extends Zend_View_Helper_Abstract
{
    public function mainmenuFloat()
    {
		$config = Zend_Registry::get('config');
    	if ( empty($config->mainmenu_floating) ) {
    		return '<!-- main menu not floating -->';
    	} else {
    		if ( $config->mainmenu_floating == 1 ) {
				return '
<script language="javascript">
var name = "ul.sf-menu";
var menuYloc = null;

$j(document).ready(function(){
    menuYloc = parseInt($j(name).css("top").substring(0,$j(name).css("top").indexOf("px")));
    $j(window).scroll(function () {
        offset = menuYloc+$j(document).scrollTop()+"px";
        $j(name).animate({top:offset},{duration:700,queue:false});
    });
});
</script>
'; }
            }
    }



}
