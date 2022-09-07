// Filter
jQuery(function($){

	var filter = $('#ajax-filter-form');

	filter.submit(function(){
		$.ajax({
			url : ajax_object.url,
            data : filter.serialize(),
            dataType : 'json',
			type : 'POST',
            beforeSend : function ( xhr ) {
                $('#ajax-filter-form').find('button').text('Filtering...');
            },
			success:function(data){

                ajax_object.current_page = 1;

                ajax_object.posts = data.posts;

                ajax_object.max_page = data.max_page;

                $('#ajax-filter-form').find('button').text('Apply filter');

                $('#ajax_filter_search_results').html(data.content);

                if ( data.max_page < 2 ) {
                    $('#my_estate_loadmore').hide();
                } else {
                    $('#my_estate_loadmore').show();
                }

			}
		});
		return false;
	});

});


//Load More Posts
jQuery(function($){

    $(document).on('click', '.load_more_posts', function(){
        console.log('script started');
       // var button = $(this),
        $.ajax({
            url : ajax_object.url,
            data : {
                action: load_more_posts,
                query: ajax_object.posts,
                page : ajax_object.current_page,
               // max_page : ajax_object.max_page,
            },
            type : 'POST',
            beforeSend : function ( xhr ) {
                $('#my_estate_loadmore').text('Loading...');
            },
            success : function( posts ){
                if( posts ) {

                    $('#my_estate_loadmore').text( 'More posts' );
                    $('#ajax_filter_search_results').append( posts );
                    ajax_object.current_page++;

                    if ( ajax_object.current_page == ajax_object.max_page )
                        $('#my_estate_loadmore').hide();
                } else {
                    $('#my_estate_loadmore').hide();
                }
            }
        });
        return false;
    });
});