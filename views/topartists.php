<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-top-artists">
	<table class="rdj-main-table">
		<thead>
			<tr class="rdj-header">
				<th class="rdj-entry-no rdj-position rdj-col-narrow">
					<?php _ex('#', 'table header', 'radiodj'); ?>
				</th>
				<th class="rdj-artist">
					<?php _e('Artist', 'radiodj'); ?>
				</th>
				</tr>
		</thead>
		<tbody>
			<?php
			$counter = 0;
			foreach($topartists as $artist){
				$td_class = ($counter++) % 2 ? 'rdj-odd' : 'rdj-even';
			?>
			<tr class="<?php echo $td_class; ?>">
				<td class="rdj-position"><?php echo $counter.'.'; ?></td>
				<td class="rdj-artist"><?php echo htmlspecialchars( $artist->artist, ENT_QUOTES ); ?></td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>
