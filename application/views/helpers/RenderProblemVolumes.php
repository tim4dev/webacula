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
 * Helper for render problem Volumes
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class Zend_View_Helper_RenderProblemVolumes {

    public function renderProblemVolumes($this_view)
    {
        $translate = Zend_Registry::get('translate');
?>

<?php if ($this_view->resultProblemVolumes): ?>

<h1 align="center"><?php echo $this_view->escape($this_view->titleProblemVolumes); ?></h1>

<div align="center">
<table id="box-table">
<thead>
<tr>
	<th scope="col"> <?php print $translate->_("Volume Name"); ?> </th>
    <th scope="col"> <?php print $translate->_("Volume Status"); ?> </th>
	<th scope="col"> <?php print $translate->_("Volume Bytes"); ?> </th>
	<th scope="col"> <?php print $translate->_("Max Volume<br />Bytes"); ?> </th>
	<th scope="col"> <?php print $translate->_("Volume Jobs"); ?> </th>
	<th scope="col"> <?php print $translate->_("Volume<br />Retention<br />(days)"); ?> </th>
	<th scope="col"> <?php print $translate->_("Media<br />Type"); ?> </th>
	<th scope="col"> <?php print $translate->_("First<br />Written"); ?> </th>
	<th scope="col"> <?php print $translate->_("Last<br />Written"); ?> </th>
	<th scope="col"> <?php print $translate->_("Autochanger<br />Slot<br />number"); ?> </th>
	<th scope="col"> <?php print $translate->_("Can Recycle<br />Volume"); ?> </th>
</tr>
</thead>
<tbody>
<?php foreach($this_view->resultProblemVolumes as $line) : ?>
<tr>
	<td><?php echo $this_view->escape($line['volumename']);?></td>

	<?php
		// http://www.bacula.org/developers/Catalog_Services.html
		// Status of media: Full, Archive, Append, Recycle, Read-Only, Disabled, Error, Busy
		if ( $this_view->escape($line['volstatus']) == 'Disabled' )
			echo '<td class="warn" align="center">', $this_view->escape($line['volstatus']), '</td>';
		elseif ( $this_view->escape($line['volstatus']) == 'Error' )
			echo '<td class="err" align="center">', $this_view->escape($line['volstatus']), '</td>';
		else
			echo '<td align="center">', $this_view->escape($line['volstatus']), '</td>';
	?>

	<td align="right"><?php echo $this_view->ConvBytes($this_view->escape($line['volbytes']));?></td>
	<td align="right"><?php echo $this_view->ConvBytes($this_view->escape($line['maxvolbytes']));?></td>

	<?php
		if ( ( isset($line['firstwritten'])) && ($this_view->escape($line['voljobs']) <= 0) )
			echo '<td class="warn" align="right">', number_format($this_view->escape($line['voljobs'])), '</td>';
		else
			echo '<td align="right">', number_format($this_view->escape($line['voljobs'])), '</td>';
	?>

	<td align="center"><?php echo $this_view->ConvSecondsToDays($this_view->escape($line['volretention']));?></td>
	<td><?php echo $this_view->escape($line['mediatype']);?></td>
	<td><?php echo $this_view->escape($line['firstwritten']);?></td>
	<td><?php echo $this_view->escape($line['lastwritten']);?></td>
	<td align="right"><?php echo $this_view->escape($line['slot']);?></td>
	<td align="right"><?php echo $this_view->Int2Char($this_view->escape($line['recycle']));?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php else: ?>
	<div align="center"><h1><?php print $translate->_("All Volumes are OK."); ?></h1></div>
<?php endif; ?>
<?php
    }
}