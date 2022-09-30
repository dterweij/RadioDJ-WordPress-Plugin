<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-top-artists">
	<table class="rdj-main-table" id="rdj-table">
		<thead>
			<tr class="rdj-header-live">
				<th class="rdj-header-position">
					<?php _ex('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="rdj-header-artist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				<th class="rdj-header-title">
					&nbsp;
				</th>
				<th class="rdj-header-count-played">
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
				<td class="rdj-position"><?php echo $counter.'.'; ?></td>
				<td class="rdj-artist"><?php echo htmlspecialchars( $artist->artist, ENT_QUOTES ); ?></td>
				<td class="rdj-title">&nbsp;</td>
				<td class="rdj-count-played"><?php echo $artist->count_played; ?></td>
				</td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>