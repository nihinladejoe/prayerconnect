<?php
/**
 * Plugin Name: ABF PrayerConnect
 * Plugin URI: https://example.com/abf-prayerconnect
 * Description: A prayer chain program for Baptist churches in Africa. Churches register, pick available dates from a central calendar, choose time slots, and submit feedback (prayer points, pictures, offerings).
 * Version: 1.0.0
 * Author: ABF Admin
 * License: GPL v2 or later
 * Text Domain: abf-prayerconnect
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Plugin constants
define( 'ABF_PC_VERSION', '1.0.0' );
define( 'ABF_PC_PATH', plugin_dir_path( __FILE__ ) );
define( 'ABF_PC_URL', plugin_dir_url( __FILE__ ) );
define( 'ABF_PC_BASENAME', plugin_basename( __FILE__ ) );

// Include required files
require_once ABF_PC_PATH . 'includes/class-abf-db.php';
require_once ABF_PC_PATH . 'includes/class-abf-admin.php';
require_once ABF_PC_PATH . 'includes/class-abf-shortcodes.php';
require_once ABF_PC_PATH . 'includes/class-abf-ajax.php';

/**
 * Main plugin class
 */
class ABF_PrayerConnect {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    /**
     * Create database tables on activation
     */
    public function activate() {
        ABF_DB::create_tables();
        ABF_DB::set_default_options();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        load_plugin_textdomain( 'abf-prayerconnect', false, dirname( ABF_PC_BASENAME ) . '/languages' );

        // Initialize components
        ABF_Admin::get_instance();
        ABF_Shortcodes::get_instance();
        ABF_Ajax::get_instance();
    }

    public function enqueue_frontend() {
        wp_enqueue_style( 'abf-pc-style', ABF_PC_URL . 'assets/css/abf-style.css', array(), ABF_PC_VERSION );
        wp_enqueue_script( 'abf-pc-script', ABF_PC_URL . 'assets/js/abf-script.js', array( 'jquery' ), ABF_PC_VERSION, true );
        wp_localize_script( 'abf-pc-script', 'abfPC', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'abf_pc_nonce' ),
            'messages' => array(
                'loading'      => __( 'Loading...', 'abf-prayerconnect' ),
                'success'      => __( 'Success!', 'abf-prayerconnect' ),
                'error'        => __( 'An error occurred. Please try again.', 'abf-prayerconnect' ),
                'confirm_book' => __( 'Are you sure you want to book this slot?', 'abf-prayerconnect' ),
            ),
        ) );

        // WordPress media uploader (for image uploads)
        if ( is_user_logged_in() ) {
            wp_enqueue_media();
        }
    }

    public function enqueue_admin( $hook ) {
        if ( strpos( $hook, 'abf-prayerconnect' ) === false ) {
            return;
        }
        wp_enqueue_style( 'abf-pc-admin', ABF_PC_URL . 'assets/css/abf-style.css', array(), ABF_PC_VERSION );
        wp_enqueue_script( 'abf-pc-admin', ABF_PC_URL . 'assets/js/abf-script.js', array( 'jquery' ), ABF_PC_VERSION, true );
        wp_localize_script( 'abf-pc-admin', 'abfPC', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'abf_pc_nonce' ),
        ) );
    }
}

// Boot the plugin
ABF_PrayerConnect::get_instance();
