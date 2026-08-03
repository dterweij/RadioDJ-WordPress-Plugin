<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap now-playing">
	<table class="main_table" id="nptable">
		<tr>
			<th class="header_live">
				<b><?php _e('Nu:', 'radiodj'); ?></b>
			</th>
		</tr>
<?php if(!empty($current)) { ?>
		<tr class="current-track">
			<td class="playing_track">
				<span class="artist"><?php echo htmlspecialchars($current->artist, ENT_QUOTES) ?></span><span class="separator"> - </span>
				<span class="title"><?php echo htmlspecialchars($current->title, ENT_QUOTES) ?></span>

			</td>
		</tr>
<?php } ?>

<?php if( !empty($upcoming) ) { ?>
		<tr class="coming-soon">
			<th class="header_live">
				<b><?php _e('Straks:', 'radiodj'); ?></b>
			</th>
		</tr>
<?php	if(  is_string($upcoming[0]) ) { ?>
		<tr>
			<td class="coming-soon">
				<?php echo implode( ", ", $upcoming ); ?>
			</td>
		</tr>
<?php	} else {
			foreach($upcoming as $song) { 
				$date_added = strtotime($song->date_added);
				$date_played_raw = $song->date_played; // bijvoorbeeld: "2002-01-01 00:00:00"

				if (substr($date_played_raw, 0, 10) === '2002-01-01') {
    				$date_played = 'nooit';
				} else {
    				$timestamp = strtotime($date_played_raw);
					$date_played = RadioDJ::format_date( $timestamp ); // bijv. "10 april 2024"
				}
		?>
<tr class="coming-soon">
    <td>
        <div class="track-info">
            <div class="track-left">
                <span class="artist"><?php echo htmlspecialchars($song->artist, ENT_QUOTES); ?></span>
                <span class="separator">–</span>
                <span class="title"><?php echo htmlspecialchars($song->title, ENT_QUOTES); ?></span>

            </div>
            <div class="track-right">
                <div class="meta-line">Laatst gespeeld op: <strong><?php echo $date_played; ?></strong></div>
                <div class="meta-line">Toegevoegd in de database op: <strong><?php echo RadioDJ::format_date( $date_added ); ?></strong></div>
            </div>
        </div>
    </td>
</tr>
<?php		}
		}
} ?>

<?php if( !empty($nowplaying) ) { ?>
		<tr>
			<th class="header_live">
				<b><?php _e('Recent afgespeeld:', 'radiodj'); ?></b>
			</th>
		</tr>
<?php
			$counter = 0;
			foreach($nowplaying as $song){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
?>
<tr class="recent-tracks">
    <td class="<?php echo $td_class; ?>">
        <span class="timestamp"><?php echo date( 'H:i:s', strtotime( $song->date_played ) ); ?></span>
        <span class="track-info">
            <span class="artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></span>
            <span class="separator"> - </span>
            <span class="title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></span>
        </span>
        <span class="duration">[<?php echo RadioDJ::track_duration( $song->duration ); ?>]</span>
    </td>
</tr>

<?php
			}
		}
?>
	</table>
</div>