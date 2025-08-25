<?php
/*
Plugin Name: Infinite Scroll Random Post
Description: This plugin will add Infinite Scroll with Random Posts at the end of each Post Single Page.
Version: 1.0
Author: GeroNikolov
Author URI: https://geronikolov.com
License: GPLv2
*/

class ISRP_LL {
    private
    $_ASSETS_VERSION;

    function __construct() {
        // Init Setup Variables
        $this->_ASSETS_VERSION = "1.0";

        // Register Plugin Activation Hook
        register_activation_hook( __FILE__, array( $this, "isrp_ll_prep_db" ) );
        
        // Register needed assets
        add_action( "wp_enqueue_scripts", array( $this, "isrp_ll_register_assets" ) );

        // Register Get Post AJAX Method
        add_action( "wp_ajax_isrp_ll_get_post", array( $this, "isrp_ll_get_post" ) );
        add_action( "wp_ajax_nopriv_isrp_ll_get_post", array( $this, "isrp_ll_get_post" ) );

        // Register Internal Tracking
        add_action( "wp_footer", array( $this, "isrp_ll_internal_tracking" ) );
    }

    /**
     * Prepare the database table for tracking unique visits.
     *
     * @return void
     */
    function isrp_ll_prep_db() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        global $wpdb;

        // Create Unique Visits Tracker
        $isrp_tracker_unique = $wpdb->prefix ."isrp_tracker_unique";
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$isrp_tracker_unique'" ) != $isrp_tracker_unique ) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql_ = "
                        CREATE TABLE $isrp_tracker_unique (
                id INT NOT NULL AUTO_INCREMENT,
                                ip VARCHAR(255),
                url VARCHAR(255),
                browser_type VARCHAR(255),
                last_visit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                                PRIMARY KEY(id)
                        ) $charset_collate;
                        ";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_ );
            
            // Set Page ID Index
            $indexing_sql = "CREATE INDEX page_id ON $isrp_tracker_unique (page_id);";
            $index_status = $wpdb->query( $indexing_sql );

            // Set IP Index
            $indexing_sql = "CREATE INDEX ip ON $isrp_tracker_unique (ip);";
            $index_status = $wpdb->query( $indexing_sql );
            
            // Set URL Index
            $indexing_sql = "CREATE INDEX url ON $isrp_tracker_unique (url);";
            $index_status = $wpdb->query( $indexing_sql );
            
            // Set Browser Type Index
            $indexing_sql = "CREATE INDEX browser_type ON $isrp_tracker_unique (browser_type);";
			$index_status = $wpdb->query( $indexing_sql );
        }
    }

    /**
     * Register frontend assets.
     *
     * @return void
     */
    function isrp_ll_register_assets() {
        // Load the needed assets only on Posts
        if ( is_singular( "post" ) ) {
            wp_enqueue_script( "isrp_ll_public_js", plugins_url( "/assets/public.js", __FILE__ ), array( "jquery" ), $this->_ASSETS_VERSION, true );
            wp_enqueue_style( "isrp_ll_public_css", plugins_url( "/assets/public.css", __FILE__ ), array(), $this->_ASSETS_VERSION, "screen" );

            wp_localize_script(
                "isrp_ll_public_js",
                "isrpLLData",
                array(
                    "ajax_url" => esc_url_raw( admin_url( "admin-ajax.php" ) ),
                    "nonce"    => wp_create_nonce( "isrp_ll_get_post" ),
                )
            );
        }
    }

    /**
     * AJAX handler for retrieving a random post.
     *
     * @return void
     */
    function isrp_ll_get_post() {
        $nonce = isset( $_POST["nonce"] ) ? sanitize_text_field( wp_unslash( $_POST["nonce"] ) ) : "";
        if ( ! wp_verify_nonce( $nonce, "isrp_ll_get_post" ) ) {
            wp_send_json_error();
        }

        $referer = wp_get_referer();
        if ( false === $referer || wp_parse_url( $referer, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
            wp_send_json_error();
        }

        $listed_posts = array();
        if ( isset( $_POST["listed_posts"] ) && ! empty( $_POST["listed_posts"] ) && is_array( $_POST["listed_posts"] ) ) {
            $listed_posts = $this->isrp_ll_sanitize_post_ids( wp_unslash( $_POST["listed_posts"] ) );
        }
        $response = false;

        $args = array(
            "posts_per_page" => 1,
            "post_type"      => "post",
            "post_status"    => "publish",
            "orderby"        => "rand",
            "order"          => "DESC",
            "exclude"        => $listed_posts,
        );
        $posts_ = get_posts( $args );

        if ( ! empty( $posts_ ) ) {
            $response = array(
                "post_id"  => (int) $posts_[0]->ID,
                "permalink" => esc_url_raw( get_permalink( $posts_[0]->ID ) ),
            );
        }

        wp_send_json( $response );
    }

    /**
     * Sanitize an array of post IDs.
     *
     * @param array $ids List of post IDs.
     * @return array
     */
    function isrp_ll_sanitize_post_ids( $ids ) {
        $result = array();

        foreach ( $ids as $id ) {
            $id = absint( $id );
            if ( $id > 0 ) {
                array_push( $result, $id );
            }
        }

        return $result;
    }

    function isrp_ll_internal_tracking() {
        $this->isrp_ll_set_track();
    }

    // Internal Tracking Method is a separated method in case we want to reuse it on different place than the wp_footer hook.
    /**
     * Internal tracking method.
     *
     * @return void
     */
    function isrp_ll_set_track() {
        global $wpdb;
        $isrp_tracker_unique = $wpdb->prefix . "isrp_tracker_unique";
        $tracking = new stdClass();
        $tracking->url = isset( $_SERVER["REDIRECT_URL"] ) ? esc_url_raw( wp_unslash( $_SERVER["REDIRECT_URL"] ) ) : esc_url_raw( wp_unslash( $_SERVER["REQUEST_URI"] ) );
        $tracking->ip = isset( $_SERVER["REMOTE_ADDR"] ) ? sanitize_text_field( wp_unslash( $_SERVER["REMOTE_ADDR"] ) ) : "";
        $tracking->user_agent = isset( $_SERVER["HTTP_USER_AGENT"] ) ? sanitize_text_field( wp_unslash( $_SERVER["HTTP_USER_AGENT"] ) ) : "";

        // Check if page was visited already
        $sql_ = $wpdb->prepare( 
            "SELECT * FROM $isrp_tracker_unique WHERE ip=%s AND url=%s AND browser_type=%s LIMIT 1", 
            array(
                $tracking->ip,
                $tracking->url,
                $tracking->user_agent
            )
        );
        $results_ = $wpdb->get_results( $sql_, OBJECT );

        if ( empty( $results_ ) ) { // That's a new visit
            $wpdb->insert(
                $isrp_tracker_unique,
                array(
                    "ip" => $tracking->ip,
                    "url" => $tracking->url,
                    "browser_type" => $tracking->user_agent
                ),
                array(
                    "%s",
                    "%s",
                    "%s"
                )
            );
        } else { // It's an already existing visit, we shall update the last_visit_date
            $current_date = date( "Y-m-d H:i:s" );
            $wpdb->update(
                $isrp_tracker_unique,
                array(
                    "last_visit_date" => $current_date
                ),
                array(
                    "id" => $results_[ 0 ]->id
                ),
                array(
                    "%s"
                ),
                array(
                    "%d"
                )
            );
        }
    }
}

$ISRP_LL = new ISRP_LL();