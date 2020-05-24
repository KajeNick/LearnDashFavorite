<?php

/**
 * Plugin Name: LearnDash Favorites
 * Description: Plugin for add favorites page in LearnDash
 * Version: 1.0.1
 * Author: nSukonny
 * Author URI: https://github.com/NSukonny
 * License: A "Slug" license name e.g. GPL3
 */

class LearnDashFavorites {

	/**
	 * Plugin version
	 *
	 * @since 1.0.1
	 *
	 * @var string
	 */
	public $version = '1.0.2';

	/**
	 * Key for use secure ajax call
	 *
	 * @since 1.0.1
	 *
	 * @var string
	 */
	private $key = 'Zjdhe27dha63hGS84';

	/**
	 * Post per page for pagination
	 *
	 * @since 1.0.1
	 *
	 * @var int
	 */
	private $limit = 5;

	/**
	 * LearnDashFavorites constructor.
	 *
	 * @since 1.0.1
	 */
	public function __construct() {

		add_action( 'wp_enqueue_scripts', [ $this, 'add_favorite_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_favorite_styles' ] );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_add_favorite', [ $this, 'add_favorite_callback' ] );
			add_action( 'wp_ajax_nopriv_add_favorite', [ $this, 'add_favorite_callback' ] );

			add_action( 'wp_ajax_order_favorite', [ $this, 'order_favorite_callback' ] );
			add_action( 'wp_ajax_nopriv_order_favorite', [ $this, 'order_favorite_callback' ] );
		}

		add_shortcode( 'ldfavorites_list', [ $this, 'display_favorites_list' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'on_ob_start' ] );
		add_shortcode( 'ldfbutton', [ $this, 'add_ldfavorites_button' ] );

	}

	/**
	 * Initialization
	 *
	 * @since 1.0.1
	 *
	 * @return bool|LearnDashFavorites
	 */
	public static function init() {

		static $instance = false;

		if ( ! $instance ) {
			$instance = new LearnDashFavorites();
		}

		return $instance;
	}

	/**
	 * Bufering for redirect
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	function on_ob_start() {

		ob_start();

	}

	/**
	 * Add js scripts
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	function add_favorite_script() {

		wp_enqueue_script( 'learndash-favorites-js', plugins_url( 'assets/js/learndash-favorites.js', __FILE__ ), array( 'jquery' ), time(), true );
		wp_localize_script( 'learndash-favorites-js', 'ldFavorites',
			[
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( $this->key ),
				'list'     => $this->get_list(),
				'page'     => isset( $_GET['page'] ) ? (int) $_GET['page'] : 1,
				'preload'  => plugins_url( 'assets/img/prl.svg', __FILE__ ),
			]
		);

	}

	/**
	 * Add styles
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	function add_favorite_styles() {

		wp_enqueue_style(
			'learndash-favorites-css',
			plugins_url( 'assets/css/learndash-favorites.css', __FILE__ ),
			null,
			time(),
			'all'
		);

	}

	/**
	 * Callback for ajax calls for add/remove in favorite
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	function add_favorite_callback() {

		check_ajax_referer( $this->key, 'security' );

		$list = $this->get_list();

		if ( count( $list ) ) {
			foreach ( $list as $key => $item ) {
				if ( $item['videoUrl'] == $_POST['videoUrl'] ) {
					unset( $list[ $key ] );
					$this->save_list( $list );
					wp_die( json_encode( [ 'success' => false ] ) );
				}
			}
		}

		$list[] = [
			'order'         => count( $list ) ? max( array_column( $list, 'order' ) ) + 1 : 1,
			'videoUrl'      => sanitize_text_field( $_POST['videoUrl'] ),
			'videoTitle'    => sanitize_text_field( $_POST['videoTitle'] ),
			'videoLink'     => sanitize_text_field( $_POST['videoLink'] ),
			'videoDescript' => sanitize_text_field( $_POST['videoDescript'] )
		];

		$this->save_list( $list );

		wp_die( json_encode( [ 'success' => true ] ) );

	}

	/**
	 * Callback for ajax calls for change order in favorite
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function order_favorite_callback() {

		check_ajax_referer( $this->key, 'security' );

		$order_type     = isset( $_POST['order_type'] ) && 'asc' == $_POST['order_type'] ? 1 : 0;
		$order_position = isset( $_POST['order_position'] ) ? sanitize_text_field( $_POST['order_position'] ) : null;
		$page           = isset( $_POST['page'] ) ? sanitize_text_field( $_POST['page'] ) : 1;

		if ( null !== $order_position ) {
			$this->change_order( $order_position, $order_type );

			$list   = $this->get_list();
			$output = $this->make_list( $list, $page );
			$output .= $this->make_pagination( $page, ceil( count( $list ) / $this->limit ) );


			wp_die( json_encode( [ 'success' => true, 'html' => $output ] ) );
		}

		wp_die( json_encode( [ 'false' => true ] ) );
	}

	/**
	 * Get all favorites from Database
	 *
	 * @since 1.0.1
	 *
	 * @return array
	 */
	private function get_list() {

		$list = get_option( 'ldfavorites_user' . get_current_user_id() );
		if ( empty( $list ) ) {
			return [];
		}

		return array_values( json_decode( $list, true ) );
	}

	/**
	 * Update favorite list in database
	 *
	 * @since 1.0.1
	 *
	 * @param $list
	 *
	 * @return void
	 */
	private function save_list( $list ) {

		if ( ! empty( $list ) ) {
			$list = json_encode( $list );
		}
		update_option( 'ldfavorites_user' . get_current_user_id(), $list );

	}

	/**
	 * Favorites page
	 *
	 * @since 1.0.1
	 *
	 * @param $attr
	 *
	 * @return void
	 */
	function display_favorites_list( $attr ) {

		$page = isset( $_GET['ldfpage'] ) && is_numeric( $_GET['ldfpage'] ) ? (int) $_GET['ldfpage'] : 1;

		if ( isset( $_GET['order'] ) ) {
			$this->change_order( $_GET['order'], $_GET['asc'] );

			wp_redirect( esc_url( remove_query_arg( [ 'order', 'asc' ] ) ) );
			exit;
		}

		if ( isset( $_GET['remove'] ) ) {
			$this->remove_from_favorite( $_GET['remove'] );

			wp_redirect( esc_url( remove_query_arg( [ 'remove', 'asc' ] ) ) );
			exit;
		}

		$html = '<div class="ldfavorites-content">';

		$list = $this->get_list();
		$html .= $this->make_list( $list, $page );
		$html .= $this->make_pagination( $page, ceil( count( $list ) / $this->limit ) );

		$html .= '</div>';

		echo $html;

	}

	/**
	 * Shortcode for display favorite button
	 *
	 * @since 1.0.1
	 *
	 * @param $attr
	 *
	 * @return string
	 */
	function add_ldfavorites_button( $attr ) {

		$video    = isset( $attr['video'] ) ? 'data-video_url="https://player.vimeo.com/video/' . $attr['video'] . '?portrait=0&title=0&color=fff&byline=0"' : null;
		$title    = isset( $attr['title'] ) ? 'data-video_title="' . $attr['title'] . '"' : '';
		$descript = isset( $attr['descript'] ) ? 'data-descript="' . $attr['descript'] . '"' : '';

		if ( $video != null ) {
			$active = false;

			$favorites = $this->get_list();

			foreach ( $favorites as $favorite ) {
				if ( $favorite['videoUrl'] == 'https://player.vimeo.com/video/' . $attr['video'] . '?portrait=0&title=0&color=fff&byline=0' ) {
					$active = true;
				}
			}

			if ( $active ) {
				return '<button class="ldfavorite-button active" ' . $video . ' ' . $title . ' ' . $descript . ' ><i class="_mi _before buddyboss bb-icon-heart-fill" ></i> In den Favoriten</button>';
			} else {
				return '<button class="ldfavorite-button" ' . $video . ' ' . $title . ' ' . $descript . ' ><i class="_mi _before buddyboss bb-icon-heart-fill"></i> Zu den Favoriten</button>';
			}

		}

		return '';
	}

	/**
	 * Pagination for favorites page
	 *
	 * @since 1.0.1
	 *
	 * @param $page
	 * @param $max
	 *
	 * @return string
	 */
	private function make_pagination( $page, $max ) {

		if ( $max <= 1 ) {
			return '';
		}

		$html = '<div class="center"><div class="ldfavorites-pagination">';
		for ( $i = 1; $i <= $max; $i ++ ) {
			if ( $i == $page ) {
				$html .= '<a href="#" class="active" >' . $i . '</a>';
			} else {
				$html .= '<a href="' . esc_url( add_query_arg( [ 'ldfpage' => $i ] ) ) . '" >' . $i . '</a>';
			}
		}
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Changing order in favorite list
	 *
	 * @since 1.0.1
	 *
	 * @param $order
	 * @param $asc
	 *
	 * @return void
	 */
	private function change_order( $order, $asc ) {

		$list = $this->get_list();
		usort( $list, function ( $a, $b ) {
			return $a['order'] - $b['order'];
		} );

		foreach ( $list as $key => $val ) {
			if ( $val['order'] == $order ) {
				$neighborKey = $asc == 0 ? $key - 1 : $key + 1;
				if ( isset( $list[ $neighborKey ] ) ) {
					$list[ $key ]['order']         = $list[ $neighborKey ]['order'];
					$list[ $neighborKey ]['order'] = $order;
				}
			}
		}

		usort( $list, function ( $a, $b ) {
			return $a['order'] - $b['order'];
		} );

		$this->save_list( $list );

	}

	/**
	 * Remove element from list
	 *
	 * @since 1.0.1
	 *
	 * @param $remove
	 */
	private function remove_from_favorite( $remove ) {

		$list = $this->get_list();

		foreach ( $list as $key => $val ) {
			if ( $val['order'] == $remove ) {
				unset( $list[ $key ] );
			}
		}

		$this->save_list( $list );

	}

	/**
	 * Make ist of favorite videos
	 *
	 * @since 1.0.2
	 *
	 * @return string
	 */
	private function make_list( $list, $page = 1 ) {

		$output = '';

		for ( $i = ( $page - 1 ) * $this->limit; $i < $page * $this->limit; $i ++ ) {
			if ( isset( $list[ $i ] ) ) {
				$output .= '<div class="ldfavorites-block">';
				$output .= '<h2><a href="' . $list[ $i ]['videoLink'] . '" target="_parent" >' . $list[ $i ]['videoTitle'] . '</a>';
				$output .= '<div class="ldfavorites-arrows">';
				$output .= '      <a href="' . esc_url( add_query_arg( [
						'ldfpage' => $page,
						'order'   => $list[ $i ]['order'],
						'asc'     => 1
					] ) ) . '" >
					<img src="' . plugins_url( 'assets/img/arrow-bottom.png', __FILE__ ) . '" 
					class="ldfavorites-arrow-bottom" data-order="' . $list[ $i ]['order'] . '" ></a>';
				$output .= '      <a href="' . esc_url( add_query_arg( [
						'ldfpage' => $page,
						'order'   => $list[ $i ]['order'],
						'asc'     => 0
					] ) ) . '" >
					<img src="' . plugins_url( 'assets/img/arrow-top.png', __FILE__ ) . '" 
					class="ldfavorites-arrow-top" data-order="' . $list[ $i ]['order'] . '" ></a>';

				$output .= '<a href="' . esc_url( add_query_arg( [
						'ldfpage' => $page,
						'remove'  => $list[ $i ]['order'],
						'asc'     => 1
					] ) ) . '" class="ldfavorites-remove"><img src="' . plugins_url( 'assets/img/delete.png', __FILE__ ) . '" ></a>';
				$output .= '</div></h2>';

				if ( ! empty( $list[ $i ]['videoDescript'] ) && $list[ $i ]['videoDescript'] != 'undefined' ) {
					$output .= '<p>' . $list[ $i ]['videoDescript'] . '</p>';
				}

				$output .= '<iframe src="' . $list[ $i ]['videoUrl'] . '" frameborder="0" title="' . $list[ $i ]['videoTitle'] . '" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
				$output .= '</div>';
			}
		}

		return $output;
	}
}

function init_lear_dash_favorites() {

	return LearnDashFavorites::init();
}

if ( ! isset( $_GET['tve'] ) ) {
	init_lear_dash_favorites();
}