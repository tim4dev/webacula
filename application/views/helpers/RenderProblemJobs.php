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
 * Helper for render problem jobs
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class Zend_View_Helper_RenderProblemJobs {

    public function renderProblemJobs($this_view)
    {
        $translate = Zend_Registry::get('translate');
?>
<h1 align="center"><?php echo $this_view->escape($this_view->titleProblemJobs); ?></h1>

<?php if ($this_view->resultProblemJobs): ?>


<div align="center">
<table id="box-table">
<thead>
<tr>
	 <th scope="col"> <?php print $translate->_("Job Id"); ?> </th>
     <th scope="col"> <?php print $translate->_("Job Name"); ?> </th>
     <th scope="col"> <?php print $translate->_("Status"); ?> </th>
     <th scope="col"> <?php print $translate->_("Level"); ?> </th>
     <th scope="col"> <?php print $translate->_("Files"); ?> </th>
     <th scope="col"> <?php print $translate->_("Bytes"); ?> </th>
     <th scope="col"> <?php print $translate->_("Errors"); ?> </th>
     <th scope="col"> <?php print $translate->_("Client"); ?> </th>
     <th scope="col"> <?php print $translate->_("Pool"); ?> </th>
     <th scope="col"> <?php print $translate->_("Start Time"); ?> </th>
     <th scope="col"> <?php print $translate->_("End Time"); ?> </th>
     <th scope="col"> <?php print $translate->_("Duration"); ?> <br /> <?php print $translate->_("hh-mm-ss"); ?> </th>
</tr>
</thead>
<tbody>
<?php foreach($this_view->resultProblemJobs as $line) : ?>
<tr>
	<td>
		<?php
			if ( isset($line['poolid']) && isset($line['poolname']) )	{
				echo '<a href="';
				echo $this_view->baseUrl, '/job/detail/jobid/', $this_view->escape($line['jobid']);
				echo '" title="', $translate->_("Detail Job"), '">';
			}
			echo $line['jobid'];
			if ( isset($line['poolid']) && isset($line['poolname']) )	{
				echo '</a>';
			}
		?>
	</td>

	<!-- Job.Name -->
	<td><?php echo $this_view->escape($line['jobname']);?></td>

	<!-- Status -->
	<?php
		$class_td = '';
		if ( $line['joberrors'] != 0 )	{
		    if ( $line['jobstatus'] == 'T' )	{
		        $class_td = 'class="warn"';
		    } else {
		        // 100% что была какая-то ошибка
		        $class_td = 'class="err"';
		    }
			echo '<td ', $class_td, ' align="center"> ',
			     $translate->_( $this_view->escape($line['jobstatuslong']) ), ' </td>';
		}
		elseif	( $line['jobstatus'] == 'T' )	{
			echo '<td align="center">OK</td>';
		} else {
		    // ошибок вроде нет, но что-то не в порядке
		    if ( ($line['jobstatus'] == 'f') OR ($line['jobstatus'] == 'E') )	{
		        // fatal error
		        $class_td = 'class="err"';
		    } else {
		    	$class_td = 'class="warn"';
		    }
			echo '<td ', $class_td, ' align="center">',
			     $translate->_( $this_view->escape($line['jobstatuslong']) ), '</td>';
		}
	?>

	<td align="center"><?php echo $this_view->escape($line['level']);?></td>
	
	<?php
		$class = '';
		if ( $line['jobfiles'] == 0 ) $class = 'class="warn"';
		echo '<td ', $class, ' align="right">', number_format($line['jobfiles']), '</td>';
		if ( $line['jobbytes'] == 0 ) $class_td = 'class="warn"';
		echo '<td ', $class, ' align="right">', $this_view->convBytes($line['jobbytes']), '</td>';
	?>

	<!-- Errors -->
	<?php
		if ( $line['joberrors'] == 0 )
			echo '<td align="right">-</td>';
		else {
		    echo '<td class="err" align="right">';
		    echo '<a href="';
			echo $this_view->baseUrl, '/log/view-log-id/jobid/', $this_view->escape($line['jobid']), '" title="',
			     $translate->_("View Messages"), '" class="linkerr">';
			echo $this_view->escape($line['joberrors']), '</a></td>';
		}
	?>	
	
	<td><?php echo $line['clientname'];?></td>

	<td>
		<?php
			if ( isset($line['poolid']) && isset($line['poolname']) )	{
				echo '<a href="';
				echo $this_view->baseUrl, "/volume/find-pool-id/id/", $this_view->escape($line['poolid']) .
				"/name/", $this_view->escape($line['poolname']);
				echo '" title="', $translate->_("Detail Volume"), '">';
			}	else 	{
				echo "&nbsp;";
			}

			if ( isset($line['poolid']) && isset($line['poolname']) )	{
				echo $this_view->escape($line['poolname']);
				echo '</a>';
			}	else {
				echo '&nbsp;';
			}
		?>
	</td>

	<td><?php echo $this_view->escape($line['starttime']);?></td>
	<td><?php echo $this_view->escape($line['endtime']);?></td>
	<td align="right"><?php echo $this_view->escape($line['durationtime']);?></td>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php else: ?>
	<div align="center"><p><b><?php print $translate->_("No Jobs found."); ?></b></p></div>
<?php endif; ?>
<?php
    }
}