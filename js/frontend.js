(function($){
  $(function(){
	// define how wide panel should be when open and closed
	var closed_width = ns_slidebar.trigger_width;
	var open_width = Math.max( $(window).width()*(ns_slidebar.percent_width/100), ns_slidebar.min_width );
	
	/*******************
	 * INITIAL SETUP 
	 */
	// set slidebar to correct initial 'closed' width based on width of control and save for restoring to later
	$("#ns-slidebar").width(closed_width);
	// try to remove autocomplete from search form so it doesn't block view of instant results
	$('#ns-slidebar-search-form [name=s]').attr('autocomplete','off');
	// set slidebar height right so scrolling will work
	$(window).bind('load resize ns_slidebar_show_search_item',function(){
		$('#ns-slidebar-content').height( $(window).height() - $('#ns-slidebar-search-form').outerHeight() - ($('body').is('.admin-bar')?32:0) );
	});
	
	/******************
	 * CLOSE/OPEN LOGIC 
	 */
	// functions for easy closing/opening
	var open_slidebar = function(){
		$('#ns-slidebar').animate( {width:0}, 200, function(){
			$('#ns-slidebar').addClass('opening').animate( {width:open_width}, 400, function(){
				$("#ns-slidebar").removeClass('opening').addClass('open');
			});
		});
	};
	var close_slidebar = function(){
		$("#ns-slidebar").removeClass('open').addClass('closing').animate( {width:0}, 400, function(){
			$("#ns-slidebar").removeClass('closing').animate( {width:closed_width}, 200 );
		});
		
	};	
	// toggle slidebar when control is clicked (right now isn't visible when slidebar is closed so only the open part applies when clicking the control)
	// also set initial width data so animation can put it back to previous width when slidebar is closed and control is shown again
	$('#ns-slidebar-control').click(function(){
		if( $('#ns-slidebar').is(':not(.open)') ){
			open_slidebar();
		}
		else{
			close_slidebar();
		}
	});
	// if the form (top element when slidebar is open) is clicked (and only the form directly, not a child input), close the sidebar
	// since people will probably expect it to act as the toggle
	$('#ns-slidebar-search-form form').click(function(e){
		if( e.target===this ){
			close_slidebar();
		}
	});
	
	/**************************
	 * INSTANT SEACH RESULTS 
	 */
	// function for showing search result html output for easy reuse
	var show_result = function(i,post){
		var $search_container = $('#ns-slidebar-search-results');
		var $result_template = $('.ns-slidebar-search-result:first').clone();
		$result_template.find('.ns-slidebar-search-title a').html( post.post_title ).attr( 'href', post.permalink );
		$result_template.find('.ns-slidebar-search-post-type').text( post.formatted_post_type ).addClass( 'ns-slidebar-'+post.post_type );
		$result_template.find('.ns-slidebar-search-excerpt').html( post.short_content );
		$search_container.find('.ns-slidebar-search-result:last').after( $result_template ).next().delay(200).addClass( i%2==0?'odd':'even' ).slideDown(function(){
			$(this).removeClass('ns-slidebar-hidden');
		});
	};
	// bring up instant search results
	$('#ns-slidebar-search-form form').bind('submit',function(){
		$.get(
			ns_slidebar.ajaxurl,
			{
				action: 'ns_slidebar_search',
				s: $('#ns-slidebar-search-form [name=s]').val()
			},
			function(results){
				// set to global variable so load more function can access
				ns_slidebar.search_results = results;
				//show message to user either way
				var messages = {
					'success': 'Here are your search results!',
					'failure': 'Hmm, nothing found. Try something else?'
				};
				$('#ns-slidebar-search-results-message').slideUp(function(){
					$(this).text( $.isArray(results) && results.length? messages.success : messages.failure ).slideDown();
				});
				// remove previous results
				$('.ns-slidebar-search-result:visible').slideUp( 200, function(){ $(this).remove(); });
				// show search results if some were found
				if( $.isArray(results) && results.length ){
					$.each( results.slice(0,ns_slidebar.results_per_page), show_result );
					// show more button if there were more results
					if( results.length > ns_slidebar.results_per_page ){
						$('#ns-slidebar-search-results-more').slideDown().data('page',0);
					}
				}
			}
		);
		return false;
	});
	// show more search results when clicking more button
	$('#ns-slidebar-search-results-more').click(function(){
		var results_per_page = ns_slidebar.results_per_page;
		var next_page_num = $(this).data('page') + 1;
		var next_results = ns_slidebar.search_results.slice( next_page_num*results_per_page, (next_page_num+1)*results_per_page );
		$.each( next_results, show_result );
		$(this).data('page',next_page_num);
		// hide more button if that was all
		if( (next_page_num+1)*results_per_page >= ns_slidebar.search_results.length ){
			$(this).slideUp();
		}
	});
		
  });
})(jQuery);