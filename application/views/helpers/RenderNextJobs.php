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
class Zend_View_Helper_RenderNextJobs {

    public function renderNextJobs($this_view)
    {
        $translate = Zend_Registry::get('translate');
?>

<?php //echo "<pre>"; print_r($this_view->resultNextJobs); echo "</pre>"; ?>

<h1 align="center"><?php echo $this_view->escape($this_view->titleNextJobs); ?></h1>

<?php if ($this_view->resultNextJobs): ?>
<?php
	if ($this_view->resultNextJobs[0] == 'NOFOUND') {
		echo '<font color="red"><div align="center"><p>',
		      $translate->_("ERROR: Cannot execute bconsole. File not found."), '</p></div></font>';
	}
 	elseif ($this_view->resultNextJobs[0] == 'ERROR') {
 		echo '<font color="red"><div align="center"><p>',
 		     $translate->_("ERROR: There was a problem executing bconsole. See below."), '</p></div>';
 		foreach ($this_view->resultNextJobs as $line) {
			echo $line, '<br>';
		}
		echo '</font>';
 	} else	{
?>

<div align="center">
<table id="box-table">
<thead>
<tr>
	<th scope="col"> <?php print $translate->_("Level"); ?> </th>
	<th scope="col"> <?php print $translate->_("Type"); ?> </th>
	<th scope="col"> <?php print $translate->_("Priority"); ?> </th>
	<th scope="col"> <?php print $translate->_("Scheduled"); ?> </th>
	<th scope="col"> <?php print $translate->_("Job Name"); ?> </th>   
    <th scope="col"> <?php print $translate->_("Volume"); ?> </th>
    <th scope="col"> <?php print $translate->_("% Free space<br /> on Volume"); ?> </th>
</tr>
</thead>
<tbody>
<?php foreach($this_view->resultNextJobs as $line) : ?>
<tr>
	<td><?php echo $this_view->escape($line['level']);?></td>
	<td><?php echo $this_view->escape($line['type']);?></td>
	<td align="center"><?php echo $this_view->escape($line['pri']);?></td>
	<td><?php echo $this_view->escape($line['date']);?></td>

	<?php if ($line['parseok']) : ?>
		<td><?php echo $this_view->escape($line['name']);?></td>
		<td>
			<?php if ($this_view->escape($line['vol']) === '*unknown*') : ?>
				<?php echo $this_view->escape($line['vol']); ?>
			<?php else : ?>
				<a href="<?php echo $this_view->baseUrl, "/volume/find-name/volname/",
	   				$this_view->escape($line['vol']);?>"  title="<?php print $translate->_("Detail Volume");?>">
				<?php echo $this_view->escape($line['vol']);?></a>
			<?php endif; ?>
		</td>

		<?php
		$class = '';
		switch ($line['volfree'])  {
	    	case $this_view->unknown_volume_capacity:
	        	$line['volfree'] = $translate->_('Unknown');
		        break;
		    case $this_view->err_volume:
	    	    $class = 'class="err"';
    			$line['volfree'] = $translate->_('Volume with Error(s)');
	    		break;
		    case $this_view->new_volume:
    			$line['volfree'] = $translate->_('New Volume');
    			break;
    		default:
    	    	if ( $line['volfree'] < 3 )
                	$class = 'class="warn"';
		        elseif ( $line['volfree'] < 7 )
        	    	$class = 'class="warn"';
	            else
    	            $class = '';
        	    break;
		}
		echo '<td ',$class,' align="right">',$line['volfree'] ,'</td>';
		?>
	<?php else : ?>
    	<td colspan="3"><?php echo $this_view->escape($line['name']), ' ', 
    						$this_view->escape($line['vol']), ' &nbsp;&nbsp;', $translate->_('Unknown');?></td>
	<?php endif; ?>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php
}
?>

<?php else : ?>
    <div align="center"><p><b><?php print $translate->_("No Scheduled Jobs found."); ?></b></p></div>
<?php endif; ?>

<?php
    }
}