<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ABF_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'abf_calendar', array( $this, 'shortcode_calendar' ) );
        add_shortcode( 'abf_register', array( $this, 'shortcode_register' ) );
        add_shortcode( 'abf_book', array( $this, 'shortcode_book' ) );
        add_shortcode( 'abf_feedback', array( $this, 'shortcode_feedback' ) );
    }

    /* ============ CALENDAR ============ */
    public function shortcode_calendar( $atts ) {
        $atts = shortcode_atts( array(
            'year'  => current_time( 'Y' ),
            'month' => current_time( 'm' ),
        ), $atts );

        ob_start();
        include ABF_PC_PATH . 'templates/calendar.php';
        return ob_get_clean();
    }

    /* ============ REGISTRATION ============ */
    public function shortcode_register( $atts ) {
        ob_start();
        include ABF_PC_PATH . 'templates/register.php';
        return ob_get_clean();
    }

    /* ============ BOOKING ============ */
    public function shortcode_book( $atts ) {
        ob_start();
        include ABF_PC_PATH . 'templates/book.php';
        return ob_get_clean();
    }

    /* ============ FEEDBACK ============ */
    public function shortcode_feedback( $atts ) {
        ob_start();
        include ABF_PC_PATH . 'templates/feedback.php';
        return ob_get_clean();
    }
}
