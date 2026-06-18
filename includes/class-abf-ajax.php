<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ABF_Ajax {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Public AJAX (no login required)
        add_action( 'wp_ajax_abf_load_month', array( $this, 'load_month' ) );
        add_action( 'wp_ajax_nopriv_abf_load_month', array( $this, 'load_month' ) );

        add_action( 'wp_ajax_abf_load_date_slots', array( $this, 'load_date_slots' ) );
        add_action( 'wp_ajax_nopriv_abf_load_date_slots', array( $this, 'load_date_slots' ) );

        // Registration
        add_action( 'wp_ajax_abf_register_church', array( $this, 'register_church' ) );
        add_action( 'wp_ajax_nopriv_abf_register_church', array( $this, 'register_church' ) );

        // Booking
        add_action( 'wp_ajax_abf_make_booking', array( $this, 'make_booking' ) );
        add_action( 'wp_ajax_nopriv_abf_make_booking', array( $this, 'make_booking' ) );

        // Feedback
        add_action( 'wp_ajax_abf_submit_feedback', array( $this, 'submit_feedback' ) );
        add_action( 'wp_ajax_nopriv_abf_submit_feedback', array( $this, 'submit_feedback' ) );
    }

    private function verify_nonce() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'abf_pc_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'abf-prayerconnect' ) ) );
        }
    }

    /* ============ CALENDAR NAVIGATION ============ */
    public function load_month() {
        $this->verify_nonce();
        $year  = intval( $_POST['year'] );
        $month = intval( $_POST['month'] );

        if ( $month < 1 ) { $month = 12; $year--; }
        if ( $month > 12 ) { $month = 1; $year++; }

        $bookings = ABF_DB::get_bookings_for_month( $year, $month );

        // Group by date
        $by_date = array();
        foreach ( $bookings as $b ) {
            $by_date[ $b->booking_date ][] = $b;
        }

        ob_start();
        $this->render_calendar_grid( $year, $month, $by_date );
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'  => $html,
            'year'  => $year,
            'month' => $month,
            'label' => date_i18n( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ),
        ) );
    }

    public function load_date_slots() {
        $this->verify_nonce();
        $date = sanitize_text_field( $_POST['date'] );

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date.', 'abf-prayerconnect' ) ) );
        }

        $bookings = ABF_DB::get_bookings_for_date( $date );
        $start    = intval( get_option( 'abf_pc_time_start', 8 ) );
        $end      = intval( get_option( 'abf_pc_time_end', 20 ) );

        $slots = array();
        for ( $h = $start; $h < $end; $h++ ) {
            $slot_key = sprintf( '%02d:00-%02d:00', $h, $h + 1 );
            $slot_label = date( 'g:i A', mktime( $h, 0 ) ) . ' - ' . date( 'g:i A', mktime( $h + 1, 0 ) );
            $church = null;
            foreach ( $bookings as $b ) {
                if ( $b->time_slot === $slot_key ) {
                    $church = $b;
                    break;
                }
            }
            $slots[] = array(
                'key'    => $slot_key,
                'label'  => $slot_label,
                'booked' => (bool) $church,
                'church' => $church ? $church->church_name : '',
                'country'=> $church ? $church->country : '',
            );
        }

        wp_send_json_success( array(
            'date'  => $date,
            'slots' => $slots,
        ) );
    }

    /* ============ REGISTRATION ============ */
    public function register_church() {
        $this->verify_nonce();

        $required = array( 'country', 'convention', 'church_name', 'address', 'contact_person', 'email', 'phone' );
        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array( 'message' => sprintf( __( 'Field "%s" is required.', 'abf-prayerconnect' ), $field ) ) );
            }
        }

        if ( ! is_email( $_POST['email'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'abf-prayerconnect' ) ) );
        }

        // Check if already registered
        $existing = ABF_DB::get_church_by_email( sanitize_email( $_POST['email'] ) );
        if ( $existing ) {
            wp_send_json_success( array(
                'message'  => __( 'Your church is already registered. Use your Church ID to book.', 'abf-prayerconnect' ),
                'church_id'=> $existing->id,
            ) );
        }

        $church_id = ABF_DB::insert_church( $_POST );

        if ( ! $church_id ) {
            wp_send_json_error( array( 'message' => __( 'Registration failed. Please try again.', 'abf-prayerconnect' ) ) );
        }

        wp_send_json_success( array(
            'message'   => __( 'Registration successful! Please save your Church ID.', 'abf-prayerconnect' ),
            'church_id' => $church_id,
        ) );
    }

    /* ============ BOOKING ============ */
    public function make_booking() {
        $this->verify_nonce();

        $church_id = intval( $_POST['church_id'] ?? 0 );
        $date      = sanitize_text_field( $_POST['date'] ?? '' );
        $time_slot = sanitize_text_field( $_POST['time_slot'] ?? '' );

        if ( ! $church_id || ! $date || ! $time_slot ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields.', 'abf-prayerconnect' ) ) );
        }

        $church = ABF_DB::get_church( $church_id );
        if ( ! $church ) {
            wp_send_json_error( array( 'message' => __( 'Church not found. Please register first.', 'abf-prayerconnect' ) ) );
        }

        if ( ABF_DB::is_slot_booked( $date, $time_slot ) ) {
            wp_send_json_error( array( 'message' => __( 'This time slot is already booked. Please choose another.', 'abf-prayerconnect' ) ) );
        }

        $booking_id = ABF_DB::insert_booking( $church_id, $date, $time_slot );
        if ( ! $booking_id ) {
            wp_send_json_error( array( 'message' => __( 'Booking failed. Slot may have been taken.', 'abf-prayerconnect' ) ) );
        }

        wp_send_json_success( array(
            'message'    => __( 'Booking confirmed!', 'abf-prayerconnect' ),
            'booking_id' => $booking_id,
            'church_name'=> $church->church_name,
            'date'       => $date,
            'time_slot'  => $time_slot,
        ) );
    }

    /* ============ FEEDBACK ============ */
    public function submit_feedback() {
        $this->verify_nonce();

        $booking_id = intval( $_POST['booking_id'] ?? 0 );
        $church_id  = intval( $_POST['church_id'] ?? 0 );

        if ( ! $booking_id || ! $church_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing booking or church ID.', 'abf-prayerconnect' ) ) );
        }

        if ( empty( $_POST['prayer_points'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Prayer points are required.', 'abf-prayerconnect' ) ) );
        }

        // Check if feedback already exists for this booking
        if ( ABF_DB::get_feedback_for_booking( $booking_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Feedback already submitted for this booking.', 'abf-prayerconnect' ) ) );
        }

        // Handle proof of payment upload
        $proof_url = '';
        if ( ! empty( $_FILES['proof_file'] ) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK ) {
            $proof_url = $this->handle_upload( 'proof_file', array( 'pdf', 'jpg', 'jpeg', 'png' ) );
            if ( is_wp_error( $proof_url ) ) {
                wp_send_json_error( array( 'message' => $proof_url->get_error_message() ) );
            }
        }

        // Insert feedback
        $feedback_id = ABF_DB::insert_feedback( array(
            'booking_id'     => $booking_id,
            'church_id'      => $church_id,
            'prayer_points'  => wp_unslash( $_POST['prayer_points'] ),
            'total_offering' => floatval( $_POST['total_offering'] ?? 0 ),
            'currency'       => sanitize_text_field( $_POST['currency'] ?? 'USD' ),
            'proof_file'     => $proof_url,
            'notes'          => wp_unslash( $_POST['notes'] ?? '' ),
        ) );

        // Handle multiple images
        if ( ! empty( $_POST['image_urls'] ) && is_array( $_POST['image_urls'] ) ) {
            foreach ( $_POST['image_urls'] as $url ) {
                ABF_DB::insert_image( $feedback_id, esc_url_raw( $url ) );
            }
        }

        wp_send_json_success( array(
            'message'     => __( 'Feedback submitted successfully! Thank you.', 'abf-prayerconnect' ),
            'feedback_id' => $feedback_id,
        ) );
    }

    /* ============ FILE UPLOAD HELPER ============ */
    private function handle_upload( $file_key, $allowed_ext ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $file = $_FILES[ $file_key ];
        $ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        if ( ! in_array( $ext, $allowed_ext, true ) ) {
            return new WP_Error( 'invalid_ext', __( 'File type not allowed.', 'abf-prayerconnect' ) );
        }

        $upload = wp_handle_upload( $file, array( 'test_form' => false ) );
        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'upload_error', $upload['error'] );
        }

        return $upload['url'];
    }

    /* ============ CALENDAR GRID RENDER ============ */
    private function render_calendar_grid( $year, $month, $by_date ) {
        $first_day   = mktime( 0, 0, 0, $month, 1, $year );
        $days_in_month = intval( date( 't', $first_day ) );
        $start_weekday = intval( date( 'w', $first_day ) ); // 0=Sun

        $today = current_time( 'Y-m-d' );
        ?>
        <div class="abf-calendar-grid">
            <div class="abf-cal-header">
                <?php foreach ( array( __( 'Sun' ), __( 'Mon' ), __( 'Tue' ), __( 'Wed' ), __( 'Thu' ), __( 'Fri' ), __( 'Sat' ) ) as $d ) : ?>
                    <div class="abf-cal-day-name"><?php echo esc_html( $d ); ?></div>
                <?php endforeach; ?>
            </div>
            <div class="abf-cal-body">
                <?php
                // Empty cells before first day
                for ( $i = 0; $i < $start_weekday; $i++ ) {
                    echo '<div class="abf-cal-cell abf-cal-empty"></div>';
                }

                for ( $day = 1; $day <= $days_in_month; $day++ ) {
                    $date_str = sprintf( '%04d-%02d-%02d', $year, $month, $day );
                    $is_past  = $date_str < $today;
                    $has_bookings = isset( $by_date[ $date_str ] );
                    $fully_booked = $has_bookings && ABF_DB::is_date_fully_booked( $date_str );

                    $classes = array( 'abf-cal-cell' );
                    if ( $is_past ) $classes[] = 'abf-cal-past';
                    if ( $has_bookings ) $classes[] = 'abf-cal-has-bookings';
                    if ( $fully_booked ) $classes[] = 'abf-cal-full';
                    if ( $date_str === $today ) $classes[] = 'abf-cal-today';
                    ?>
                    <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" 
                         data-date="<?php echo esc_attr( $date_str ); ?>"
                         data-bookings="<?php echo esc_attr( $has_bookings ? wp_json_encode( array_map( function( $b ) {
                             return array( 'church' => $b->church_name, 'country' => $b->country, 'time' => $b->time_slot );
                         }, $by_date[ $date_str ] ) ) : '[]' ); ?>">
                        <div class="abf-cal-date-num"><?php echo esc_html( $day ); ?></div>
                        <?php if ( $has_bookings ) : ?>
                            <div class="abf-cal-churches">
                                <?php foreach ( $by_date[ $date_str ] as $b ) : ?>
                                    <div class="abf-cal-church-tag" title="<?php echo esc_attr( $b->church_name . ' - ' . $b->time_slot ); ?>">
                                        <?php echo esc_html( wp_trim_words( $b->church_name, 3 ) ); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }
}
