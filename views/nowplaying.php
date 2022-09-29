<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap now-playing">
	<table class="main_table" id="nptable">
		<tr>
			<th class="header_live">
				<b><?php _e('Now Playing', 'radiodj'); ?></b>
			</th>
		</tr>
<?php if(!empty($current)) { ?>
		<tr class="current-track">
			<td class="Aplaying_track">
				<div class="Aartist"><?php echo htmlspecialchars($current->artist, ENT_QUOTES) ?></div>
                        </td>
		</tr>
		<tr class="current-track">
			<td class="Aplaying_track">
				<div class="Atitle"><?php echo htmlspecialchars($current->title, ENT_QUOTES) ?></div>
			</td>
		</tr>

<?php } ?>

<?php if( !empty($upcoming) ) { ?>
		<tr class="Acoming-soon">
			<th class="header_live">
				<b><?php _e('Coming Soon', 'radiodj'); ?></b>
			</th>
		</tr>
<?php	if(  is_string($upcoming[0]) ) { ?>
		<tr>
			<td class="Acomming-soon">
				<?php echo implode( ", ", $upcoming ); ?>
			</td>
		</tr>
<?php	} else {
			foreach($upcoming as $song) { ?>
		<tr class="Acomming-soon">
			<td>
				<span class="Aartist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></span>
				<span class="Aseparator">-</span>
				<span class="Atitle"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></span>
			</td>
		</tr>
<?php		}
		}
} ?>

<?php if( !empty($nowplaying) ) { ?>
		<tr>
			<th class="header_live">
				<b><?php _e('Recently Played Songs', 'radiodj'); ?></b>
			</th>
		</tr>
<?php
			$counter = 0;
			foreach($nowplaying as $song){
				$td_class = ($counter++) % 2 ? 'odd' : 'even';
?>
		<tr class="Arecent-tracks">
			<td class="<?php echo $td_class; ?>">
				<span class="Atimestamp"><?php echo date( 'H:i:s', strtotime( $song->date_played ) ); ?></span>
				<span class="Aartist"><?php echo htmlspecialchars( $song->artist, ENT_QUOTES ); ?></span><span class="Aseparator"> - </span>
				<span class="Atitle"><?php echo htmlspecialchars( $song->title, ENT_QUOTES ); ?></span>
				</td>
		</tr>
<?php
			}
		}
?>
	</table>
</div>