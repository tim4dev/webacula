<?php
/**
 * Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
 *
 * This file is part of Webacula.
 *
 * Webacula is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Webacula is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Webacula.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Helper for render next scheduler jobs
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_RenderDirRunningJobs {

    public function renderDirRunningJobs($this_view)
    {
        $translate = Zend_Registry::get('translate');
?>


<h1 align="center"><?php echo $this_view->escape($this_view->titleDirRunningJobs); ?></h1>

<?php if ($this_view->resultDirRunningJobs): ?>
<?php
	if ($this_view->resultDirRunningJobs[0] == 'NOFOUND') {
		echo '<font color="red"><div align="center"><p>',
		      $translate->_("ERROR: Cannot execute bconsole. File not found."), '</p></div></font>';
	}
 	elseif ($this_view->resultDirRunningJobs[0] == 'ERROR') {
 		echo '<font color="red"><div align="center"><p>',
 		     $translate->_("ERROR: There was a problem executing bconsole. See below."), '</p></div>';
 		foreach ($this_view->resultDirRunningJobs as $line) {
			echo $line, '<br>';
		}
		echo '</font>';
 	} else	{
?>

<div align="center">
<table id="box-table">
<thead>
<tr>
	 <th scope="col"> <?php print $translate->_("Job Id");   ?> </th>
     <th scope="col"> <?php print $translate->_("Level");    ?> </th>
     <th scope="col"> <?php print $translate->_("Job Name"); ?> </th>
     <th scope="col"> <?php print $translate->_("Status");   ?> </th>
</tr>
</thead>
<tbody>
<?php foreach($this_view->resultDirRunningJobs as $line) : ?>
<tr>
	<td><?php echo $this_view->escape($line['id']);?></td>
	<td><?php echo $this_view->escape($line['level']);?></td>
	<td><?php echo $this_view->escape($line['name']);?></td>
	<td><?php echo $this_view->escape($line['status']);?></td>
</tr>
<?php endforeach; ?>
<tbody>
</table>
</div>

<?php
}
?>

<?php else : ?>
<div class="ui-widget" style="width: 50%; margin-left: auto; margin-right: auto;">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em; margin-top: 20px;"> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>
		<strong><?php print $translate->_("Information from Director : No Running Jobs found."); ?></strong></p>
	</div>
</div>

<?php endif; ?>

<?php
    }
}

