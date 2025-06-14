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
				
				var twentyfivedone  = false;
				var fiftydone       = false;
				var seventyfivedone = false;
				var hundreddone     = false;
				
				var vimeovideoid  = getVimeoId(playerurl);
				
			    // When the player is ready, add listeners for pause, finish, and playProgress
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
					
					if((playedpercent >= 25) && (playedpercent < 50) && (!twentyfivedone)) {
						sendAjaxRequest(25);
						twentyfivedone = true;
					}
					if((playedpercent >= 50) && (playedpercent < 75) && (!fiftydone)) {
						sendAjaxRequest(50);
						fiftydone = true;
					}
					if((playedpercent >= 75) && (playedpercent < 100) && (!seventyfivedone)) {
						// Focus on 75% - this is the critical functionality
						sendAjaxRequest(75);
						seventyfivedone = true;
					}
				}
				
				function sendAjaxRequest(percent) {
					var data = {
						'action': 'vimeo_action',
						'videoid': vimeovideoid,
						'contactid': contactid,
						'percent': parseInt(percent)
					};
					
					// Add specific logging for 75% requests
					if (percent == 75 && vimeo_ajax_object.debug) {
						console.log('🎯 75% MILESTONE: Sending critical tag request', {
							videoId: vimeovideoid,
							contactId: contactid,
							percent: percent
						});
					}
					   
					jQuery.post(vimeo_ajax_object.ajax_url, data, function(response) {
						// Handle successful response
						if(response.tagged == true) {
							var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
							
							// Log success for 75% specifically
							if (percent == 75 && vimeo_ajax_object.debug) {
								console.log('✅ 75% SUCCESS: Tag assigned successfully', {
									tagName: response.tag_name,
									videoId: vimeovideoid,
									debug: response.debug
								});
							}
							
							// Use tag name for cookie tracking instead of numeric ID
							var tagIdentifier = response.tag_name || response.tagid || 'unknown';
							if(vidtags) {
								if(vidtags.indexOf(tagIdentifier) == -1) {
									document.cookie = "contactTags=" + vidtags + ',' + tagIdentifier;
								}
							} else {
								document.cookie = "contactTags=" + tagIdentifier;
							}
							
							if($(iframe).parents('.videoWrapper').hasClass('red-border')) {
								$(iframe).parents('.videoWrapper').removeClass('red-border').addClass('green-border played');
							}
							if($(iframe).parents('.tab-pane').find('.vid-text').hasClass('vid-red')) {
								$(iframe).parents('.tab-pane').find('.vid-text').removeClass('vid-red').addClass('vid-green').html('(You Have Completed this Video)');
							}
						} else {
							// Handle failed tagging
							if (vimeo_ajax_object.debug) {
								console.error('❌ TAGGING FAILED:', {
									percent: percent,
									videoId: vimeovideoid,
									error: response.error || 'Unknown error',
									debug: response.debug
								});
							}
							
							// Special attention to 75% failures
							if (percent == 75) {
								console.error('🚨 CRITICAL: 75% tag assignment failed!', response);
							}
						}
					}, 'json').fail(function(xhr, status, error) {
						// Handle AJAX errors
						if (vimeo_ajax_object.debug) {
							console.error('💥 AJAX ERROR:', {
								percent: percent,
								videoId: vimeovideoid,
								status: status,
								error: error,
								response: xhr.responseText
							});
						}
						
						// Special attention to 75% AJAX failures
						if (percent == 75) {
							console.error('🚨 CRITICAL AJAX ERROR: 75% request failed!', {
								status: status,
								error: error
							});
						}
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

	/* on page load, unlock all videos */
	$('.videoWrapper.video2, .videoWrapper.video3, .videoWrapper.video4, .videoWrapper.video5').removeClass('hide-video').addClass('show-video');
});

jQuery(document).ready(function($) {
	var vidtags = document.cookie.replace(/(?:(?:^|.*;\s*)contactTags\s*\=\s*([^;]*).*$)|^.*$/, "$1");
	
	/* Ensure all videos are visible regardless of tags */
	$('.videoWrapper.video2, .videoWrapper.video3, .videoWrapper.video4, .videoWrapper.video5').removeClass('hide-video').addClass('show-video');
	
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

