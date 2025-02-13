jQuery(function($){
	if ($('iframe').length)
	{
		$('iframe').each(function( index ) {
			var playerurl = $(this).attr('src');
			if (playerurl.indexOf('vimeo.com') !== -1)
			{
				var contactid = vimeo_ajax_object.contactid;
				if($(this).attr('id')) {
					var iframe    = $(this).attr('id');
				} else {
					var iframe 	  = $(this);
				}
				var player    = new Vimeo.Player(iframe);

				//var status = $('.status');
				
				var twentyfivedone  = false;
				var fiftydone       = false;
				var seventyfivedone = false;
				var hundreddone     = false;
				
				var vimeovideoid  = getVimeoId(playerurl);		
				
			    // When the player is ready, add listeners for pause, finish, and playProgress
				//player.on('play', onPlay);
				player.on('ended', onFinish);
				player.on('timeupdate', onPlayProgress);
				
				function onFinish(data) {
					if(!hundreddone)
					{
						sendAjaxRequest(100);
						hundreddone = true;
					}
										
				}

				function onPlayProgress(data) {
					var playedpercent = parseFloat(data.percent*100);
					
					//console.log('Now Playing: '+ playedpercent );
					
					if((playedpercent >= 25) && (playedpercent < 50) && (!twentyfivedone))
					{
						sendAjaxRequest(25);
						twentyfivedone = true;
					}
					if((playedpercent >= 50) && (playedpercent < 75) && (!fiftydone))
					{
						sendAjaxRequest(50);
						fiftydone = true;
					}
					if((playedpercent >= 75) && (playedpercent < 100) && (!seventyfivedone))
					{
						sendAjaxRequest(75);
						seventyfivedone = true;
					}
					
					// 06.11 THE GREAT FIX
					if(seventyfivedone) {
						var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
						if(vidtags) {
							if(vidtags.indexOf("4955")>-1) {
								$('.videoWrapper.video2').removeClass('hide-video').addClass('show-video');
							}
							if(vidtags.indexOf("4955")>-1 && vidtags.indexOf("4957")>-1) {
								$('.videoWrapper.video3').removeClass('hide-video').addClass('show-video');
							}
							if(vidtags.indexOf("4955")>-1 && vidtags.indexOf("4957")>-1 && vidtags.indexOf("4959")>-1) {
								$('.videoWrapper.video4').removeClass('hide-video').addClass('show-video');
							}
							if(vidtags.indexOf("4955")>-1 && vidtags.indexOf("4957")>-1 && vidtags.indexOf("4959")>-1 && vidtags.indexOf("4961")>-1) {
								$('.videoWrapper.video5').removeClass('hide-video').addClass('show-video');
							}
						}
					}
					// 	var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
					// 	$('.videoWrapper.tab-0da59596a00da9b2bc8').removeClass('hide-video').addClass('show-video');
					// 	$('#tab-0da59596a00da9b2bc8 .videoWrapper').removeClass('red-border').addClass('green-border played');
					// 	$('#tab-0da59596a00da9b2bc8 .videoWrapper').removeClass('hide-video').addClass('show-video');
					// 	if(vidtags) {
					// 		/* check the text and videoWrapper colors and alter as needed */
					// 		if(vidtags.indexOf("269")>-1 || vidtags.indexOf("281")>-1) {
					// 			if($('#tab-0da59596a00da9b2bc8 .videoWrapper').hasClass('red-border')) {
					// 				$('#tab-0da59596a00da9b2bc8 .videoWrapper').removeClass('red-border').addClass('green-border played');
					// 			}
					// 			if($('#tab-0da59596a00da9b2bc8').find('.vid-text').hasClass('vid-red')) {
					// 				$('#tab-0da59596a00da9b2bc8').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
					// 			}
					// 			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid1').hasClass('vid-red')) {
					// 				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid1').removeClass('vid-red').addClass('vid-green');
					// 			}
					// 		}
					// 		if(vidtags.indexOf("271")>-1 || vidtags.indexOf("283")>-1) {
					// 			if($('#tab-2dd1dbf1c863d27d544 .videoWrapper').hasClass('red-border')) {
					// 				$('#tab-2dd1dbf1c863d27d544 .videoWrapper').removeClass('red-border').addClass('green-border played');
					// 			}
					// 			if($('#tab-2dd1dbf1c863d27d544').find('.vid-text').hasClass('vid-red')) {
					// 				$('#tab-2dd1dbf1c863d27d544').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
					// 			}
					// 			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid2').hasClass('vid-red')) {
					// 				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid2').removeClass('vid-red').addClass('vid-green');
					// 			}
					// 		}
					// 		if(vidtags.indexOf("273")>-1 || vidtags.indexOf("285")>-1) {
					// 			if($('#tab-1869c64a802e1638073 .videoWrapper').hasClass('red-border')) {
					// 				$('#tab-1869c64a802e1638073 .videoWrapper').removeClass('red-border').addClass('green-border played');
					// 			}
					// 			if($('#tab-1869c64a802e1638073').find('.vid-text').hasClass('vid-red')) {
					// 				$('#tab-1869c64a802e1638073').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
					// 			}
					// 			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid3').hasClass('vid-red')) {
					// 				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid3').removeClass('vid-red').addClass('vid-green');
					// 			}
					// 		}
					// 		if(vidtags.indexOf("275")>-1 || vidtags.indexOf("287")>-1) {
					// 			if($('#tab-d3274a27b0c47818398 .videoWrapper').hasClass('red-border')) {
					// 				$('#tab-d3274a27b0c47818398 .videoWrapper').removeClass('red-border').addClass('green-border played');
					// 			}
					// 			if($('#tab-d3274a27b0c47818398').find('.vid-text').hasClass('vid-red')) {
					// 				$('#tab-d3274a27b0c47818398').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
					// 			}
					// 			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid4').hasClass('vid-red')) {
					// 				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid4').removeClass('vid-red').addClass('vid-green');
					// 			}
					// 		}
					// 	}
					// }
				}
				
				function sendAjaxRequest(percent) {
					var data = {
						'action': 'vimeo_action',
						'videoid': vimeovideoid,
						'contactid': contactid,
						'percent': parseInt(percent)
					};
					   
					jQuery.post(vimeo_ajax_object.ajax_url, data, function(response) {
						// Ensure we have a valid response object
						if (!response) {
							console.log('Empty response received');
							return;
						}

						// Parse response if it's a string
						if (typeof response === 'string') {
							try {
								response = JSON.parse(response);
							} catch (e) {
								console.log('Failed to parse response:', e);
								return;
							}
						}

						// Only proceed if we have the expected properties
						if (response && typeof response.tagged !== 'undefined') {
							console.log('Got this from the server: ' + response.tagged);
							
							if (response.tagged === true) {  // Explicit check for true
								var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
								if (vidtags && response.tagid) {
									if (vidtags.indexOf(response.tagid) === -1) {
										document.cookie = "contactTags=" + vidtags + ',' + response.tagid;
									}
								} else if (response.tagid) {
									document.cookie = "contactTags=" + response.tagid;
								}

								if ($(iframe).parents('.videoWrapper').hasClass('red-border')) {
									$(iframe).parents('.videoWrapper').removeClass('red-border').addClass('green-border played');
								}
								if ($(iframe).parents('.tab-pane').find('.vid-text').hasClass('vid-red')) {
									$(iframe).parents('.tab-pane').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
								}
							}
						} else {
							console.log('Invalid response structure:', response);
						}
					}, 'json').fail(function(jqXHR, textStatus, errorThrown) {
						console.log('AJAX request failed:', textStatus, errorThrown);
					});
				}
			}
		});
	}
	
	function getVimeoId( url ) {
	  var match = /vimeo.*\/(\d+)/i.exec( url );

	  if ( match ) {
		return match[1];
	  }
	}
	
	
	$('#fusion-tab-scheduleacall').click(function() {
	var contactID = vimeo_ajax_object.contactid;
	var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
	if(vidtags) {
		if(vidtags.indexOf("269")>-1 || vidtags.indexOf("281")>-1) {
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid1').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid1').removeClass('vid-red').addClass('vid-green');
			}
		}
		if(vidtags.indexOf("271")>-1 || vidtags.indexOf("283")>-1) {
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid2').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid2').removeClass('vid-red').addClass('vid-green');
			}
		}
		if(vidtags.indexOf("273")>-1 || vidtags.indexOf("285")>-1) {
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid3').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid3').removeClass('vid-red').addClass('vid-green');
			}
		}
		if(vidtags.indexOf("275")>-1 || vidtags.indexOf("287")>-1) {
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid4').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid4').removeClass('vid-red').addClass('vid-green');
			}
		}
	}
	
	if(! $('#tab-f84fc5e2ba1f13f1d60 .vidchecks.divshow').find('.vid-text').hasClass('vid-red')) {
		$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').removeClass('divshow').addClass('divhide');
		$('#tab-f84fc5e2ba1f13f1d60 .vidcomplete').removeClass('divhide').addClass('divshow');
		$('#tab-f84fc5e2ba1f13f1d60 .vidcomplete .btnSched').attr('href','http://www.scheduleyou.in/urymXC6rId?id=' + contactID);
	}else if($('#tab-f84fc5e2ba1f13f1d60 .vidcomplete').hasClass('divshow')) {
		$('#tab-f84fc5e2ba1f13f1d60 .vidcomplete .btnSched').attr('href','http://www.scheduleyou.in/urymXC6rId?id=' + contactID);
	}

	});

	/* on page load, check cookies and modify .videoWrapper and .tab-pane .vid-text if necessary */
	/* http://www.scheduleyou.in/urymXC6rId */
});

jQuery(document).ready(function($) {
	var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
	if(vidtags) {
		/* check the text and videoWrapper colors and alter as needed */
		if(vidtags.indexOf("269")>-1 || vidtags.indexOf("281")>-1) {
			if($('#tab-0da59596a00da9b2bc8 .videoWrapper').hasClass('red-border')) {
				$('#tab-0da59596a00da9b2bc8 .videoWrapper').removeClass('red-border').addClass('green-border played');
			}
			if($('#tab-0da59596a00da9b2bc8').find('.vid-text').hasClass('vid-red')) {
				$('#tab-0da59596a00da9b2bc8').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
			}
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid1').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid1').removeClass('vid-red').addClass('vid-green');
			}
		}
		if(vidtags.indexOf("271")>-1 || vidtags.indexOf("283")>-1) {
			if($('#tab-2dd1dbf1c863d27d544 .videoWrapper').hasClass('red-border')) {
				$('#tab-2dd1dbf1c863d27d544 .videoWrapper').removeClass('red-border').addClass('green-border played');
			}
			if($('#tab-2dd1dbf1c863d27d544').find('.vid-text').hasClass('vid-red')) {
				$('#tab-2dd1dbf1c863d27d544').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
			}
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid2').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid2').removeClass('vid-red').addClass('vid-green');
			}
		}
		if(vidtags.indexOf("273")>-1 || vidtags.indexOf("285")>-1) {
			if($('#tab-1869c64a802e1638073 .videoWrapper').hasClass('red-border')) {
				$('#tab-1869c64a802e1638073 .videoWrapper').removeClass('red-border').addClass('green-border played');
			}
			if($('#tab-1869c64a802e1638073').find('.vid-text').hasClass('vid-red')) {
				$('#tab-1869c64a802e1638073').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
			}
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid3').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid3').removeClass('vid-red').addClass('vid-green');
			}
		}
		if(vidtags.indexOf("275")>-1 || vidtags.indexOf("287")>-1) {
			if($('#tab-d3274a27b0c47818398 .videoWrapper').hasClass('red-border')) {
				$('#tab-d3274a27b0c47818398 .videoWrapper').removeClass('red-border').addClass('green-border played');
			}
			if($('#tab-d3274a27b0c47818398').find('.vid-text').hasClass('vid-red')) {
				$('#tab-d3274a27b0c47818398').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
			}
			if($('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid4').hasClass('vid-red')) {
				$('#tab-f84fc5e2ba1f13f1d60 .vidchecks').find('.vid-text.vid4').removeClass('vid-red').addClass('vid-green');
			}
		}
	}
});

