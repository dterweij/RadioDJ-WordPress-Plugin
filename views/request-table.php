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
				<legend class="screenreader-text"><?php _e('Use this form to search for artist or title', 'radiodj'); ?></legend>
				<p>
					<label for="searchterm"><?php _e('Search artist or title', 'radiodj'); ?></label>
					<input type="text" value="<?php echo esc_attr( $searchterm ); ?>" name="searchterm" id="searchterm" />
					<input type="submit" value="<?php esc_attr_e('Search', 'radiodj'); ?>" />
				</p>
			</fieldset>
		</form>
	</div>
	<?php
	if( !empty($tracks) ) {
	?>
	<!-- pagination -->
	<?php echo $paginate ?>
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
				<th class="rdj-entry-no rdj-duration">
					<?php _e('Duration', 'radiodj'); ?>
				</th>
				<th class="rdj-entry-no rdj-request-col">
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
					$td_class = ($counter++) % 2 ? 'rdj-odd' : 'rdj-even';
			?>
			<tr class="<?php echo $td_class; ?>" data-track="<?php echo esc_attr(json_encode($track)); ?>">
				<td scope="row" class="rdj-entry-no rdj-position"><?php echo $cnt ?></td>
				<td class="rdj-artist"><?php echo htmlspecialchars( $track->artist, ENT_QUOTES ); ?></td>
				<td class="rdj-title"><?php echo htmlspecialchars( $track->title, ENT_QUOTES ); ?></td>
				<td class="rdj-duration"><?php echo RadioDJ::track_duration( $track->duration ); ?></td>
				<td class="rdj-entry-no rdj-request-col">
				<?php
				if(!$track->requested && $track->played_minutes > $track_rep && $track->artist_played_minutes > $artist_rep && !$track->in_queue) {
					$arr_params = array( 'pg' => $page, 'requestid' => $track->ID );
					$req_url = add_query_arg($arr_params);
				?>
					<a href="<?php echo esc_url($req_url); ?>" title="<?php echo esc_attr_x('Request this track', 'action button', 'radiodj'); ?>"/>
						<img src="<?php echo RDJ_PLUGIN_URL.'images/add.png'; ?>" alt="<?php echo esc_attr_x('Request', 'action button', 'radiodj'); ?>" />
					</a>
				<?php
				} else {
				?>
					<img src="<?php echo RDJ_PLUGIN_URL.'images/delete.png'; ?>" alt="<?php echo esc_attr_x('Requested', 'indicates status','radiodj'); ?>" title="<?php echo esc_attr_x('This track cannot be requested', 'action button', 'radiodj'); ?>" />
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
	} elseif ( ! empty( $has_searched ) ) {
	?>
		<div class="rdj-notice"><?php _e('No track was found by give search query. Please try different search phrase.', 'radiodj'); ?></div>
	<?php
	} else {
	?>
		<div class="rdj-notice rdj-search-prompt"><?php _e('Please enter an artist or title to search.', 'radiodj'); ?></div>
	<?php
	}
	?>
</div>
