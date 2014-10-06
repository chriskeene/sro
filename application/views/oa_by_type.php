<?php $monthlist = array("Aug", "Sep", "Oct", "Nov", "Dec", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"); ?>
<h2>Open Access public full text by type:</h2>
<table class="style1 stripe">
<thead>
	<tr>
		<th>type</th>
		<th>total</th>
		<th> </th>

	</tr>
</thead>
<tbody>
<?php foreach ($oatotals as $oatotal): ?>
	<?php if (empty($oatotal->type)) { $oatotal->type = "total"; } ?>
	<tr>
	<td><?php echo $oatotal->type ?></td>
	<td><?php echo number_format($oatotal->total) ?></td>
	<td> [<a href="listoa/type/<?php echo $oatotal->type ?>">show</a>] </td>
	</tr>
	<?php endforeach ?>
</tbody>
</table>
