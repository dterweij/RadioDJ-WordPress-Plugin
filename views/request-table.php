<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-requests">
	<div class="rdj-searchbox">
		<form id="rdj_searchform" method="get" action="<?php the_permalink() ?>">
			<?php
			// Hackity-hack for sites without pretty permalinks
			if( isset( $_GET['page_id'] ) ){
				echo '<input type="hidden" name="page_id" value="' . esc_attr($_GET['page_id']) . '" />';
			}
			?>
			<fieldset>
				<legend class="rdj-request-form-title"><?php _e('Use this form to search for artist or title', 'radiodj'); ?></legend>
				<p>
					<label class="rdj-request-form-text" for="searchterm"><?php _e('Search artist or title', 'radiodj'); ?></label>
					<input type="text" value="<?php echo $searchterm; ?>" name="searchterm" id="searchterm" />
					<input type="submit" value="<?php _e('Search', 'radiodj'); ?>" />
				</p>
			</fieldset>
		</form>
	</div>
	<?php
	if( !empty($tracks) ) {
	?>
	<!-- pagination -->
	<?php echo $paginate ?>
	<table id="rdj-table">
		<thead>
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
				<th class="rdj-header-duration">
					<?php _e('Duration', 'radiodj'); ?>
				</th>
				<th class="rdj-header-request-col">
					<?php _e('Request', 'radiodj'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$counter = 0;
				$track_rep = get_option('track_rep');
				$artist_rep = get_option('artist_rep');

				$cnt = 1+($limit*$page)-$limit; //Results counter

				foreach($tracks as $track) {
					$td_class = ($counter++) % 2 ? 'odd' : 'even';
			?>
			<tr class="<?php echo $td_class; ?>" data-track="<?php echo esc_attr(json_encode($track)); ?>">
				<th scope="row" class="rdj-header-count-played"><?php echo $cnt ?></th>
				<td class="rdj-artist"><?php echo htmlspecialchars( $track->artist, ENT_QUOTES ); ?></td>
				<td class="rdj-title"><?php echo htmlspecialchars( $track->title, ENT_QUOTES ); ?></td>
				<td class="rdj-duration"><?php echo RadioDJ::track_duration( $track->duration ); ?></td>
				<td class="rdj-request-col">
				<?php
					if(!$track->requested && $track->played_minutes > $track_rep && $track->artist_played_minutes > $artist_rep && !$track->in_queue) {
					$arr_params = array( 'pg' => $page, 'requestid' => $track->ID );
					$req_url = add_query_arg($arr_params);
					
				?>
					<a href="<?php echo esc_url($req_url); ?>" title="<?php echo _e('Request', 'radiodj'); ?>"/>
						<img src="<?php echo RDJ_PLUGIN_URL.'images/add.png'; ?>" alt="<?php echo _e('Request', 'radiodj'); ?>" />
					</a>
				<?php
				} else {
				?>
					<img src="<?php echo RDJ_PLUGIN_URL.'images/delete.png'; ?>" alt="<?php echo _e('This track cannot be requested', 'radiodj'); ?>" title="<?php _e('This track cannot be requested', 'radiodj'); ?>" />
				<?php
				}
				?>
				</td>
			</tr>
			<?php
					$cnt++;
				}
			?>
		</tbody>
	</table>
	<?php echo $paginate ?>
	<?php
	} else {
	?>
		<div class="noticediv"><?php _e('No track was found using your search query. Please try different search phrase.'); ?></div>
	<?php
	}
	?>
</div>
