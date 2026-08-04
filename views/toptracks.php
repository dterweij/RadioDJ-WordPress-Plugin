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
			</tr>
		</thead>
		<tbody>
			<?php
			$counter = 0;
			foreach($toptracks as $song){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
				$date_played_raw = $song->date_played; // e.g. "2002-01-01 00:00:00"

				if (substr($date_played_raw, 0, 10) === '2002-01-01') {
    				$date_played = __('never', 'radiodj');
				} else {
    				$timestamp = strtotime($date_played_raw);
					$date_played = RadioDJ::format_date( $timestamp ); // e.g. "10 April 2024"
				}
			?>
			<tr class="<?php echo $td_class; ?>">
				<td class="position"><?php echo $counter; ?></td>
				<td class="artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></td>
				<td class="title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></td>
			</tr>
			<tr class="<?php echo $td_class; ?>">
				<td class="positon-empty"></td>
				<td class="count-played"><?php _e('Times played:', 'radiodj'); ?> <?php echo $song->count_played; ?></td>
				<td class="count-played"><?php _e('Last played:', 'radiodj'); ?> <?php echo $date_played; ?></td>
				
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>
