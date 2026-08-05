<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-now-playing">
	<table class="rdj-main-table">
		<tr>
			<th class="rdj-header">
				<b><?php _e('Now:', 'radiodj'); ?></b>
			</th>
		</tr>
<?php if(!empty($current)) { ?>
		<tr class="rdj-current-track">
			<td class="rdj-playing-track">
				<span class="rdj-artist"><?php echo htmlspecialchars($current->artist, ENT_QUOTES) ?></span><span class="rdj-separator"> - </span>
				<span class="rdj-title"><?php echo htmlspecialchars($current->title, ENT_QUOTES) ?></span>

			</td>
		</tr>
<?php } ?>

<?php if( !empty($upcoming) ) { ?>
		<tr class="rdj-coming-soon">
			<th class="rdj-header">
				<b><?php _e('Coming Soon:', 'radiodj'); ?></b>
			</th>
		</tr>
<?php	if(  is_string($upcoming[0]) ) { ?>
		<tr>
			<td class="rdj-coming-soon">
				<?php echo implode( ", ", $upcoming ); ?>
			</td>
		</tr>
<?php	} else {
			foreach($upcoming as $song) { 
				$date_added = strtotime($song->date_added);
				$date_played_raw = $song->date_played; // e.g. "2002-01-01 00:00:00"

				if (substr($date_played_raw, 0, 10) === '2002-01-01') {
    				$date_played = __('never', 'radiodj');
				} else {
    				$timestamp = strtotime($date_played_raw);
					$date_played = RadioDJ::format_date( $timestamp ); // e.g. "10 April 2024"
				}
		?>
<tr class="rdj-coming-soon">
    <td>
        <div class="rdj-track-info">
            <div class="rdj-track-left">
                <span class="rdj-artist"><?php echo htmlspecialchars($song->artist, ENT_QUOTES); ?></span>
                <span class="rdj-separator">–</span>
                <span class="rdj-title"><?php echo htmlspecialchars($song->title, ENT_QUOTES); ?></span>

            </div>
            <div class="rdj-track-right">
                <div class="rdj-meta-line"><?php _e('Last played on:', 'radiodj'); ?> <strong><?php echo $date_played; ?></strong></div>
                <div class="rdj-meta-line"><?php _e('Added to the database on:', 'radiodj'); ?> <strong><?php echo RadioDJ::format_date( $date_added ); ?></strong></div>
            </div>
        </div>
    </td>
</tr>
<?php		}
		}
} ?>

<?php if( !empty($nowplaying) ) { ?>
		<tr>
			<th class="rdj-header">
				<b><?php _e('Recently Played:', 'radiodj'); ?></b>
			</th>
		</tr>
<?php
			$counter = 0;
			foreach($nowplaying as $song){
				$td_class = ($counter++) % 2 ? 'rdj-odd' : 'rdj-even';
?>
<tr class="rdj-recent-tracks">
    <td class="<?php echo $td_class; ?>">
        <span class="rdj-timestamp"><?php echo date( 'H:i:s', strtotime( $song->date_played ) ); ?></span>
        <span class="rdj-track-info">
            <span class="rdj-artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></span>
            <span class="rdj-separator"> - </span>
            <span class="rdj-title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></span>
        </span>
        <span class="rdj-duration">[<?php echo RadioDJ::track_duration( $song->duration ); ?>]</span>
    </td>
</tr>

<?php
			}
		}
?>
	</table>
</div>
