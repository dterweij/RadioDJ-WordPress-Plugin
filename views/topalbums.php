<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-top-albums">
	<table class="rdj-main-table">
		<thead>
			<tr class="rdj-header">
				<th class="rdj-entry-no rdj-position">
					<?php _ex('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="rdj-artist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				<th class="rdj-title">
					<?php _e('Title', 'radiodj'); ?>
				</th>
				<th class="rdj-entry-no rdj-count-played">
					<?php _e('Count', 'radiodj'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$counter = 0;
			foreach($topalbums as $album){
				$td_class = ($counter++) % 2 ? 'rdj-odd' : 'rdj-even';
			?>
			<tr class="<?php echo $td_class; ?>">
				<td class="rdj-position"><?php echo $counter.'.'; ?></td>
				<td class="rdj-artist"><?php echo htmlspecialchars( $album->artist, ENT_QUOTES ); ?></td>
				<td class="rdj-album-title"><?php echo htmlspecialchars( $album->album, ENT_QUOTES ); ?></td>
				<td class="rdj-count-played"><?php echo $album->count_played; ?></td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>
