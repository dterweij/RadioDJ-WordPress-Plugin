<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap rdj-now-playing">
	<table class="rdj-main-table" id="rdj-table">
		<tr>
			<th class="rdj-header-live">
				<b><?php _e('Now Playing', 'radiodj'); ?></b>
			</th>
		</tr>
<?php if(!empty($current)) { ?>
		<tr class="rdj-current-track">
			<td class="rdj-playing-track">
				<div class="rdj-artist"><?php echo htmlspecialchars($current->artist, ENT_QUOTES) ?></div>
                        </td>
		</tr>
		<tr class="rdj-current-track">
			<td class="rdj-playing-track">
				<div class="rdj-title"><?php echo htmlspecialchars($current->title, ENT_QUOTES) ?></div>
			</td>
		</tr>

<?php } ?>

<?php if( !empty($upcoming) ) { ?>
		<tr class="rdj-coming-soon">
			<th class="rdj-header-live">
				<b><?php _e('Coming Soon', 'radiodj'); ?></b>
			</th>
		</tr>
<?php	if(  is_string($upcoming[0]) ) { ?>
		<tr>
			<td class="rdj-comming-soon">
				<?php echo implode( ", ", $upcoming ); ?>
			</td>
		</tr>
<?php	} else {
			foreach($upcoming as $song) { ?>
		<tr class="rdj-comming-soon">
			<td>
				<span class="rdj-artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></span>
				<span class="rdj-separator">-</span>
				<span class="rdj-title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></span>
			</td>
		</tr>
<?php		}
		}
} ?>

<?php if( !empty($nowplaying) ) { ?>
		<tr>
			<th class="rdj-header-live">
				<b><?php _e('Recently Played Songs', 'radiodj'); ?></b>
			</th>
		</tr>
<?php
			$counter = 0;
			foreach($nowplaying as $song){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
?>
		<tr class="rdj-recent-tracks">
			<td class="<?php echo $td_class; ?>">
				<span class="rdj-timestamp"><?php echo date( 'H:i:s', strtotime( $song->date_played ) ); ?></span>
				<span class="rdj-artist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></span><span class="rdj-separator"> - </span>
				<span class="rdj-title"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></span>
				</td>
		</tr>
<?php
			}
		}
?>
	</table>
</div>