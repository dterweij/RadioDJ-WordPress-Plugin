<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap top-tracks">
	<table class="main_table" id="nptable">
		<thead>
			<tr class="header_live">
				<th class="entry_no position">
					<?php _ex('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="artist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				<th class="title">
					<?php _e('Title', 'radiodj'); ?>
				</th>
				<th class="entry_no count-played">
					<?php _e('Count', 'radiodj'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$counter = 0;
			foreach($toptracks as $song){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
			?>
			<tr class="<?php echo $td_class; ?>">
				<td class="position"><?php echo $counter.'.'; ?></td>
				<td class="artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></td>
				<td class="title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></td>
				<td class="count-played"><?php echo $song->count_played; ?></td>
				</td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>
