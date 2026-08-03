<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap top-artists">
	<table class="main_table" id="nptable">
		<thead>
			<tr class="header_live">
				<th style="width: 10px" class="entry_no position">
					<?php _ex('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="artist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				</tr>
		</thead>
		<tbody>
			<?php
			$counter = 0;
			foreach($topartists as $artist){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
			?>
			<tr class="<?php echo $td_class; ?>">
				<td class="position"><?php echo $counter.'.'; ?></td>
				<td class="artist"><?php echo htmlspecialchars( $artist->artist, ENT_QUOTES ); ?></td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>