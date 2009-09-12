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
 * Helper for render running jobs
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_RenderRunningJobs {

    public function renderRunningJobs($this_view)
    {
        $translate = Zend_Registry::get('translate');
?>
<h1 align="center"><?php echo $this_view->escape($this_view->titleRunningJobs); ?></h1>

<?php if ($this_view->resultRunningJobs): ?>

<div align="center">
<table id="box-table">
<thead>
<tr>
	 <th scope="col"> <?php print $translate->_("Job Id"); 	 ?> </th>
     <th scope="col"> <?php print $translate->_("Job Name"); ?> </th>
     <th scope="col"> <?php print $translate->_("Status"); 	 ?> </th>
     <th scope="col"> <?php print $translate->_("Level");    ?> </th>
     <th scope="col"> <?php print $translate->_("Errors"); ?> </th>
     <th scope="col"> <?php print $translate->_("Client");   ?> </th>
     <th scope="col"> <?php print $translate->_("Pool"); 	 ?> </th>
     <th scope="col"> <?php print $translate->_("Start Time"); ?><br /> <?php print $translate->_("yy-mm-dd"); ?> </th>
     <th scope="col"> <?php print $translate->_("Duration"); ?> <br /> <?php print $translate->_("hh-mm-ss"); ?> </th>
</tr>
</thead>
<tbody>
<?php foreach($this_view->resultRunningJobs as $line) : ?>
<tr>
	<td><?php echo $this_view->escape($line['jobid']);?></td>

	<td><?php echo $this_view->escape($line['jobname']);?></td>

	<?php
		if ( $this_view->escape($line['joberrors']) != 0 )	{
			echo '<td class="err" align="center"> ',
			     $translate->_( $this_view->escape($line['jobstatuslong']) ), ' </td>';
		}
		else
			echo '<td align="center">',
			     $translate->_( $this_view->escape($line['jobstatuslong']) ), '</td>';
	?>

	<td align="center"><?php echo $this_view->escape($line['level']);?></td>
	
	<?php
		if ( $this_view->escape($line['joberrors']) == 0 )
			echo '<td align="right">-</td>';
		else
			echo '<td class="err" align="right">', $this_view->escape($line['joberrors']), '</td>';
	?>
	
	<td ><?php echo $this_view->escape($line['clientname']);?></td>

	<td><a href="<?php echo $this_view->baseUrl, "/volume/find-pool-id/id/", $this_view->escape($line['poolid']),
		"/name/", $this_view->escape($line['poolname']);?>" title="<?php print $translate->_("Detail Pool"); ?>">
		<?php echo $this_view->escape($line['poolname']);?></a>
	</td>

	<td><?php echo $this_view->escape($line['starttime']);?></td>
	<td align="right"><?php echo $this_view->escape($line['durationtime']);?></td>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php else: ?>
<div class="ui-widget" style="width: 50%; margin-left: auto; margin-right: auto;">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em; margin-top: 20px;"> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>
		<strong><?php print $translate->_("Information from DB Catalog : No Running Jobs found.");?></strong></p>
	</div>
</div>

<?php endif; ?>
<?php
    }
}