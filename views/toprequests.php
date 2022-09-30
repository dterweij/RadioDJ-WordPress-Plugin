<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-top-requests">
	<table class="rdj-main-table" id="rdj-table">
		<thead>
			<tr class="rdj-header-live">
			   <tr class="rdj-header-live">
				<th class="rdj-header-position">
					<?php _e('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="rdj-header-artist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				<th class="rdj-header-title">
					<?php _e('Title', 'radiodj'); ?>
				</th>
				<th class="rdj-header-count-played">
					<?php _e('Count', 'radiodj'); ?>
				</th>
			</tr>
			</tr>
		</thead>
		<tbody>
			<?php
			$counter = 0;
			foreach($tracks as $song){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
			?>
			<tr class="<?php echo $td_class; ?>">
				<td class="rdj-position"><?php echo $counter.'.'; ?></td>
				<td class="rdj-artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></td>
				<td class="rdj-title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></td>
				<td class="rdj-count-played"><?php echo $song->requests; ?></td>
				</td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>