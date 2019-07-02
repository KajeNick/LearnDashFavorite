<?php

/*
Plugin Name: LearnDash Favorites
Description: Plugin for add favorites page in LearnDash
Version: 1.0.1
Author: nSukonny
Author URI: https://github.com/KajeNick
License: A "Slug" license name e.g. GPL3
*/

class LearnDashFavorites {

	public $version = '1.0.1';
	private $wpdb;
	private $key = 'Zjdhe27dha63hGS84';

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
		add_action( 'wp_enqueue_scripts', [ $this, 'add_favorite_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_favorite_styles' ] );
		add_action( 'wp_ajax_add_favorite', [ $this, 'add_favorite_callback' ] );
		add_action( 'wp_ajax_nopriv_add_favorite', [ $this, 'add_favorite_callback' ] );
	}

	public static function init( $wpdb ) {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new LearnDashFavorites( $wpdb );
		}

		return $instance;
	}

	function add_favorite_script() {
		wp_enqueue_script( 'learndash-favorites-js', plugins_url( 'assets/js/learndash-favorites.js', __FILE__ ), array( 'jquery' ), time(), true );
		wp_localize_script( 'learndash-favorites-js', 'ldFavorites',
			[
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( $this->key ),
				'list'     => $this->getList(),
				'preload'=> plugins_url( 'assets/img/preload.gif', __FILE__ )
			]
		);
	}

	function add_favorite_styles() {
		wp_enqueue_style( 'learndash-favorites-css', plugins_url( 'assets/css/learndash-favorites.css', __FILE__ ), null, time(), 'all' );
	}

	function add_favorite_callback() {
		check_ajax_referer( $this->key, 'security' );

		$list = $this->getList();

		if ( count( $list ) ) {
			foreach ( $list as $key => $item ) {
				if ( $item['videoUrl'] == $_POST['videoUrl'] ) {
					unset( $list[ $key ] );
					$this->saveList( $list );
					wp_die(json_encode(['success' => false]));
				}
			}
		}

		$list[] = [
			'videoUrl' => $_POST['videoUrl']
		];
		$this->saveList( $list );

		wp_die(json_encode(['success' => true]));
	}

	private function getList() {
		$list = get_option( 'ldfavorites' );
		if ( empty( $list ) ) {
			return [];
		}

		return json_decode( $list, true );
	}

	private function saveList( $list ) {
		if ( ! empty( $list ) ) {
			$list = json_encode( $list );
		}
		update_option( 'ldfavorites', $list );
	}

}

function init_lear_dash_favorites() {
	global $wpdb;

	return LearnDashFavorites::init( $wpdb );
}

init_lear_dash_favorites();