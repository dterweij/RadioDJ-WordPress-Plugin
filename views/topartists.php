<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap top-artists">
	<table class="main_table" id="nptable">
		<thead>
			<tr class="header_live">
				<th class="Aentry_no Aposition">
					<?php _ex('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="Aartist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				<th class="Aentry_no Acount-played">
					<?php _e('Count', 'radiodj'); ?>
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
				<td class="Aposition"><?php echo $counter.'.'; ?></td>
				<td class="Aartist"><?php echo htmlspecialchars( $artist->artist, ENT_QUOTES ); ?></td>
				<td class="Acount-played"><?php echo $artist->count_played; ?></td>
				</td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>