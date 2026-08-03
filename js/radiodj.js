// RadioDJ JavaScript
var RadioDJ = RadioDJ || {};

(function($){
	var nowPlayingTimeout;
	function loadNowPlaying(){
		var data = {
			action: 'rdj_now_playing'
		};
		$.get(RadioDJ.ajaxurl, data, function(response){
				$('.rdj-wrap.now-playing').hide().replaceWith(response);
				$('.rdj-wrap.now-playing').show('slow');
			}
		);
		nowPlayingTimeout = setTimeout(loadNowPlaying, 10000);
	}
	$(document).ready(function(){
		if($('.rdj-wrap.now-playing').length)
		nowPlayingTimeout = setTimeout(loadNowPlaying, 5000);
	});
})(jQuery);
