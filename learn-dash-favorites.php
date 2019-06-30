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

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;

		add_filter( "the_content", [ $this, "add_favorite_btn" ] );
	}

	public static function init( $wpdb ) {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new LearnDashFavorites( $wpdb );
		}

		return $instance;
	}

	function add_favorite_btn( $content ) {
		$content .= '<button class="simplefavorite-button active" >В избранное <i class="sf-icon-star-full"></i></button>';
		return $content;
	}

}

function init_lear_dash_favorites() {
	global $wpdb;

	return LearnDashFavorites::init( $wpdb );
}

init_lear_dash_favorites();