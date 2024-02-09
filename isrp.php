<?php
/*
Plugin Name: Infinite Scroll Random Post
Description: This plugin will add Infinite Scroll with Random Posts at the end of each Post Single Page.
Version: 1.2
Author: GeroNikolov, dylanfetch
Author URI: https://geronikolov.com
License: GPLv2
*/

class ISRP_LL {
    private
    $_ASSETS_VERSION;

    function __construct() {
        // Init Setup Variables
        $this->_ASSETS_VERSION = "1.2";

        // Register needed assets
        add_action( "wp_enqueue_scripts", array( $this, "isrp_ll_register_assets" ) );

        // Register Get Post AJAX Method
        add_action( "wp_ajax_isrp_ll_get_post", array( $this, "isrp_ll_get_post" ) );
        add_action( "wp_ajax_nopriv_isrp_ll_get_post", array( $this, "isrp_ll_get_post" ) );

    }

    function isrp_ll_register_assets() {
        // Load the needed assets only on Posts
        if ( is_singular( "post" ) ) {
            wp_enqueue_script( "isrp_ll_public_js", plugins_url( "/assets/public.js" , __FILE__ ), array( "jquery" ), $this->_ASSETS_VERSION, true );
            wp_enqueue_style( "isrp_ll_public_css", plugins_url( "/assets/public.css", __FILE__ ), array(), $this->_ASSETS_VERSION, "screen" );

            wp_add_inline_script( "isrp_ll_public_js", 'var isrpLLAjaxURL = "' . admin_url( "admin-ajax.php" ) . '";', 'before' );
        }
    }

    function isrp_ll_get_post() {
        $listed_posts = isset( $_POST[ "listed_posts" ] ) && !empty( $_POST[ "listed_posts" ] ) && is_array( $_POST[ "listed_posts" ] ) ? $this->isrp_ll_sanitize_post_ids( $_POST[ "listed_posts" ] ) : array();
        $response = false;

        $args = array(
            "posts_per_page" => 1,
            "post_type" => "post",
            "post_status" => "publish",
            "orderby" => "rand",
            "order" => "DESC",
            "exclude" => $listed_posts
        );
        $posts_ = get_posts( $args );

        if ( !empty( $posts_ ) ) {
            $response = new stdClass;
            $response->post_id = $posts_[ 0 ]->ID;
            $response->permalink = get_permalink( $response->post_id );
        }

        echo json_encode( $response );
        die( "" );
    }

    function isrp_ll_sanitize_post_ids( $ids ) {
        $result = array();

        foreach ( $ids as $id ) {
            $id = intval( $id );
            if ( $id > 0 ) { array_push( $result, $id ); }
        }

        return $result;
    }

}

$ISRP_LL = new ISRP_LL();
