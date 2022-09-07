<?php
/**
 * Class My Estate Shortcodes
 */

class MyEstateFilter
{

	public function __construct()
	{
		add_action( 'init', array( $this, 'register_scripts' ) );
        add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	public function register_scripts()
	{
		add_action( 'wp_enqueue_scripts', array( $this,  'ajax_enqueue_scripts'  ), 99 );
		add_action( 'wp_ajax_property_filter', array( $this, 'my_ajax_filter_search_callback' ) );
		add_action( 'wp_ajax_nopriv_property_filter', array( $this, 'my_ajax_filter_search_callback' ) );

        add_action( 'wp_ajax_loadmorebutton', array( $this, 'my_estate_load_more_posts' ) );
        add_action( 'wp_ajax_nopriv_loadmorebutton', array( $this, 'my_estate_load_more_posts' ) );

    }

	public function ajax_enqueue_scripts()
	{
        global $wp_query;
		wp_enqueue_script( 'ajax_filter', plugins_url( '../assets/front/js/script.js', __FILE__ ), array( 'jquery' ), time(), true );
		wp_localize_script(
			'ajax_filter',
			'ajax_object',
			array(
				'url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( '_wpnonce' ),
                'posts' => json_encode( $wp_query->query_vars ),
                'current_page' => $wp_query->query_vars['paged'] ? $wp_query->query_vars['paged'] : 1,
                'max_page' => $wp_query->max_num_pages
			),
		);
	}

    public function register_shortcode()
    {
        // Shortcode: [my_ajax_filter_search]
        add_shortcode( 'my_ajax_filter_search', array( $this, 'my_estate_filter_form' ) );
    }

	public function my_estate_filter_form( $atts, $content )
    {
		ob_start();
		?>

		<div id="my-ajax-filter-search">
			<div class="col-md-12 d-flex">
				<div class="col-md-3 flex-direction-column">
					<form action="#" method="post" class="form-search d-flex flex-column"  id="ajax-filter-form">
					
                        <label for="my_estate_number_of_results">Per page</label>
                        <select name="my_estate_number_of_results" id="my_estate_number_of_results">
                            <option><?php echo get_option( 'posts_per_page' ) ?></option>
                            <option>2</option>
                            <option>3</option>
                            <option value="-1">All</option>
                        </select>

						<div class="location filter-field">
							<div class="select-wrap">
                                <?php
                                //ToDo choose Method, add Escaping
                                if ($terms = get_terms(array('taxonomy' => 'district', 'orderby' => 'name'))) :
                                    echo '<select name="district" id="district" class="form-control d-block"><option value="">Choose District ...</option>';
                                    foreach ($terms as $term) :
                                        echo '<option value="' . $term->term_id . '">' . $term->name . '</option>'; // ID of the category as the value of an option
                                    endforeach;
                                    echo '</select>';
                                endif;
                                ?>

							</div>
						</div>

						<div class="price-field filter-field">
							<input type="text" name="min_price" id="min_price" placeholder="<?php esc_html_e( 'Min Price:', 'my-estate' ); ?>"
								   class="d-block filter-input form-control">
							<input type="text" name="max_price" id="max_price" placeholder="<?php esc_html_e( 'Max Price:', 'my-estate' ); ?>"
								   class="d-block filter-input form-control ml-15">
						</div>

						<div class="area-field filter-field">
							<input type="number" name="min_area" id="min_area" placeholder="<?php esc_html_e( 'Min Area:', 'my-estate' ); ?>"
								   class="d-block filter-input form-control" value="">
							<input type="number" name="max_area" id="max_area" placeholder="<?php esc_html_e( 'Max Area:', 'my-estate' ); ?>"
								   class="d-block filter-input form-control ml-15"  value="">
						</div>

                        <!-- ToDo Floors: Add from to -->
						<div class="filter-field">
							<input type="text" name="floor" placeholder="<?php esc_html_e( 'Floor:', 'my-estate' ); ?>"
								   class="d-block filter-input form-control">
						</div>

						<div class="rooms-field filter-field">
							<div class="select-wrap">
                                <!-- ToDo Add multiply checkbox for rooms -->
								<select name="rooms" id="rooms" class="form-control d-block">
									<option value=""><?php esc_html_e( 'Select Rooms', 'my-estate' ); ?></option>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
                                    <option value="4">5</option>
								</select>
							</div>
						</div>

						<div class="materials-field filter-field">
							<div class="select-wrap">
								<select type="select" name="materials" id="materials" class="form-control d-block">
									<option value=""><?php esc_html_e( 'Materials', 'my-estate' ); ?></option>
									<option value="Brick"><?php esc_html_e( 'Brick', 'my-estate' ); ?></option>
									<option value="Panel"><?php esc_html_e( 'Panel', 'my-estate' ); ?></option>
									<option value="Foam Block"><?php esc_html_e( 'Foam Block', 'my-estate' ); ?></option>
								</select>
							</div>
						</div>

                        <div class="property-qty filter-field">
                            <div class="select-wrap">
                                <select type="select" name="items" id="items" class="form-control d-block">
                                    <option value=""><?php esc_html_e( 'Items qty:', 'my-estate' ); ?></option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                        </div>

                        <div class="button-filter">
                            <button class="btn btn-success text-white btn-block"><?php esc_html_e( 'Search', 'my-estate' ); ?></button>
                        </div>

                        <input type="hidden" name="action" value="property_filter">

                    </form>

                </div>

                <div class="col-md-9">
                    <ul id="ajax_filter_search_results" class="property-wrap mb-3 mb-lg-0"></ul>

                    <?php
                    $params = json_decode( stripslashes( $_POST['query'] ), true );
                    print_r($params);
                    $params['paged'] = $_POST['page'] + 1; // we need next page to be loaded


                    if ( $wp_query->max_num_pages > 1 ) : ?>
                        <div class="load-more-posts-container">
                            <button id="my_estate_loadmore" class="load_more_posts"><?php esc_html_e('Load More', 'my_estate');  ?></button>
                            <input type="hidden" name="action" value="load_more_posts">
                        </div>
                    <?php endif; ?>

                </div>

            </div>
		</div>

		<?php
		return ob_get_clean();
	}

	public function my_ajax_filter_search_callback()
	{
        global $wp_query;

        // ToDo wpnonce verify
/*        if( ! wp_verify_nonce( $_POST['nonce'], '_wpnonce' ) ) {
            wp_send_json_error();
            die();
        }*/

		$min_price = ( isset( $_POST['min_price'] ) ? sanitize_text_field( $_POST['min_price'] ) : '' );
		$max_price = ( isset( $_POST['max_price'] ) ? sanitize_text_field( $_POST['max_price'] ) : '' );
		$min_area  = ( isset( $_POST['min_area'] ) ? sanitize_text_field( $_POST['min_area'] ) : '' );
		$max_area  = ( isset( $_POST['max_area'] ) ? sanitize_text_field( $_POST['max_area'] ) : '' );
		$materials = ( isset( $_POST['materials'] ) ? sanitize_text_field( $_POST['materials'] ) : '' );
		$rooms     = ( isset( $_POST['rooms'] ) ? sanitize_text_field( $_POST['rooms'] ) : '' );
		$floor     = ( isset( $_POST['floor'] ) ? sanitize_text_field( $_POST['floor'] ) : '' );
		$district  = ( isset( $_POST['district'] ) ? sanitize_text_field( $_POST['district'] ) : '' );
        $items     = ( isset( $_POST['items'] ) ? sanitize_text_field( $_POST['items'] ) : '' );
		//$submit    = ( isset( $_GET['submit'] ) ? $_GET['submit'] : '' );


		$args = array(
			'orderby'        	=> 'date',
			'post_type' 		=> 'real_estate',
			'taxonomy'  		=> 'district',
			'terms'     		=> $district,
		);

		if ( $min_price || $max_price ) 
		{
			$args['meta_query'] = array( 'relation' => 'AND' );
		}

		if ( $min_price && $max_price ) 
		{
			$args['meta_query'][] = array(
				'key'     => 'price',
				'value'   => array( $min_price, $max_price ),
				'type'    => 'numeric',
				'compare' => 'between',
			);
		}
        else
		{
			if ( $min_price ) {
				$args['meta_query'][] = array(
					'key'     => 'price',
					'value'   => $min_price,
					'type'    => 'numeric',
					'compare' => '>=',
				);
			}

			if ( $max_price ) {
				$args['meta_query'][] = array(
					'key'     => 'price',
					'value'   => $max_price,
					'type'    => 'numeric',
					'compare' => '<=',
				);
			}

			if ( $min_area || $max_area ) {
				$args['meta_query'] = array( 'relation' => 'AND' );
			}

			if ( $min_area && $max_area ) {
				$args['meta_query'][] = array(
					'key'     => 'living_area',
					'value'   => array( $min_area, $max_area ),
					'type'    => 'numeric',
					'compare' => 'between',
				);
			} 
				else 
			{
				if ( $min_area ) 
				{
					$args['meta_query'][] = array(
						'key'     => 'living_area',
						'value'   => $min_area,
						'type'    => 'numeric',
						'compare' => '>=',
					);
				}

				if ( $max_area ) 
				{
					$args['meta_query'][] = array(
						'key'     => 'living_area',
						'value'   => $max_area,
						'type'    => 'numeric',
						'compare' => '<=',
					);
				}
			}

			if ( $rooms ) 
			{
				$args['meta_query'][] = array(
					'key'     => 'rooms',
					'value'   => $rooms,
					'compare' => '=',
				);
			}

			if ( $floor ) 
			{
				$args['meta_query'][] = array(
					'key'     => 'floor',
					'value'   => $floor,
					'compare' => '=',
				);
			}

			if ( $materials )
            {
				$args['meta_query'][] = array(
					'key'     => 'materials_used',
					'value'   => $materials,
					'compare' => '=',
				);
			}
		}

		 if ( $district ) {
		 	$args['tax_query'] = array(
		 		array(
		 			'post_type' => 'real_estate',
		 			'taxonomy'  => 'district',
		 			'terms'     => $district,
		 		),
		 	);
		 }

        $wp_query = new WP_Query( $args );

		if ( $wp_query->have_posts() ) {
            ob_start();
            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                require PLUGIN_DIR_PATH . 'templates/template-parts/property-item-article.php';
            }
            $posts_html = ob_get_contents();
            ob_end_clean();
        } else {
            $posts_html = '<p>Nothing found for your criteria.</p>';
        }

        echo json_encode( array(
            'posts' => json_encode( $wp_query->query_vars ),
            'max_page' => $wp_query->max_num_pages,
            'found_posts' => $wp_query->found_posts,
            'content' => $posts_html
        ) );

        wp_die();

	}

    public function my_estate_load_more_posts()
    {
        global $wp_query;
        $params = json_decode( stripslashes( $_POST['query'] ), true );
        $params['paged'] = $_POST['page'] + 1;
        $params['post_status'] = 'publish';

        $items = ( isset( $_POST['items'] ) ? sanitize_text_field( $_POST['items'] ) : '' );
        $wp_query = query_posts( $params );

        if( $wp_query->have_posts() ) :

            while( $wp_query->have_posts() ): $wp_query->the_post();

                require PLUGIN_DIR_PATH . 'templates/template-parts/property-item-article.php';

            endwhile;
        endif;
        wp_die();

        ?>

        <?php

    }
}
?>
<?php
$my_estate_filter = new MyEstateFilter();

