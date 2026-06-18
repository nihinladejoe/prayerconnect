<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ABF_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Churches table
        $sql1 = "CREATE TABLE {$wpdb->prefix}abf_churches (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            country VARCHAR(150) NOT NULL,
            convention VARCHAR(200) NOT NULL,
            church_name VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            contact_person VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email)
        ) $charset;";

        // Bookings table
        $sql2 = "CREATE TABLE {$wpdb->prefix}abf_bookings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            church_id BIGINT(20) UNSIGNED NOT NULL,
            booking_date DATE NOT NULL,
            time_slot VARCHAR(20) NOT NULL,
            status VARCHAR(20) DEFAULT 'confirmed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_date_slot (booking_date, time_slot),
            KEY church_id (church_id)
        ) $charset;";

        // Feedback table
        $sql3 = "CREATE TABLE {$wpdb->prefix}abf_feedback (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            church_id BIGINT(20) UNSIGNED NOT NULL,
            prayer_points TEXT NOT NULL,
            total_offering DECIMAL(12,2) DEFAULT 0.00,
            currency VARCHAR(10) DEFAULT 'USD',
            proof_file VARCHAR(500) DEFAULT '',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY church_id (church_id)
        ) $charset;";

        // Images table (multiple images per feedback)
        $sql4 = "CREATE TABLE {$wpdb->prefix}abf_images (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feedback_id BIGINT(20) UNSIGNED NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY feedback_id (feedback_id)
        ) $charset;";

        dbDelta( $sql1 );
        dbDelta( $sql2 );
        dbDelta( $sql3 );
        dbDelta( $sql4 );

        update_option( 'abf_pc_version', ABF_PC_VERSION );
    }

    public static function set_default_options() {
        if ( ! get_option( 'abf_pc_time_start' ) ) {
            update_option( 'abf_pc_time_start', 8 ); // 8 AM
        }
        if ( ! get_option( 'abf_pc_time_end' ) ) {
            update_option( 'abf_pc_time_end', 20 ); // 8 PM
        }
        if ( ! get_option( 'abf_pc_program_name' ) ) {
            update_option( 'abf_pc_program_name', 'ABF PrayerConnect Program' );
        }
    }

    /* ---------- CHURCHES ---------- */
    public static function insert_church( $data ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'abf_churches', array(
            'country'          => sanitize_text_field( $data['country'] ),
            'convention'       => sanitize_text_field( $data['convention'] ),
            'church_name'      => sanitize_text_field( $data['church_name'] ),
            'address'          => sanitize_textarea_field( $data['address'] ),
            'contact_person'   => sanitize_text_field( $data['contact_person'] ),
            'email'            => sanitize_email( $data['email'] ),
            'phone'            => sanitize_text_field( $data['phone'] ),
            'status'           => 'active',
        ) );
        return $wpdb->insert_id;
    }

    public static function get_church( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abf_churches WHERE id = %d",
            $id
        ) );
    }

    public static function get_church_by_email( $email ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abf_churches WHERE email = %s LIMIT 1",
            $email
        ) );
    }

    public static function get_all_churches( $status = null ) {
        global $wpdb;
        if ( $status ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}abf_churches WHERE status = %s ORDER BY created_at DESC",
                $status
            ) );
        }
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}abf_churches ORDER BY created_at DESC" );
    }

    /* ---------- BOOKINGS ---------- */
    public static function insert_booking( $church_id, $date, $time_slot ) {
        global $wpdb;
        $result = $wpdb->insert( $wpdb->prefix . 'abf_bookings', array(
            'church_id'    => intval( $church_id ),
            'booking_date' => sanitize_text_field( $date ),
            'time_slot'    => sanitize_text_field( $time_slot ),
            'status'       => 'confirmed',
        ) );
        return $result ? $wpdb->insert_id : false;
    }

    public static function is_slot_booked( $date, $time_slot, $exclude_id = 0 ) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}abf_bookings 
                WHERE booking_date = %s AND time_slot = %s AND status = 'confirmed'";
        $params = array( $date, $time_slot );
        if ( $exclude_id ) {
            $sql .= " AND id != %d";
            $params[] = $exclude_id;
        }
        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ) > 0;
    }

    public static function is_date_fully_booked( $date ) {
        global $wpdb;
        $start = intval( get_option( 'abf_pc_time_start', 8 ) );
        $end   = intval( get_option( 'abf_pc_time_end', 20 ) );
        $total_slots = $end - $start;

        $booked = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}abf_bookings 
             WHERE booking_date = %s AND status = 'confirmed'",
            $date
        ) );

        return $booked >= $total_slots;
    }

    public static function get_bookings_for_month( $year, $month ) {
        global $wpdb;
        $start = sprintf( '%04d-%02d-01', $year, $month );
        $end   = date( 'Y-m-t', strtotime( $start ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, c.church_name, c.country, c.convention 
             FROM {$wpdb->prefix}abf_bookings b
             LEFT JOIN {$wpdb->prefix}abf_churches c ON b.church_id = c.id
             WHERE b.booking_date BETWEEN %s AND %s AND b.status = 'confirmed'
             ORDER BY b.booking_date, b.time_slot",
            $start, $end
        ) );
    }

    public static function get_bookings_for_date( $date ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, c.church_name, c.country, c.convention 
             FROM {$wpdb->prefix}abf_bookings b
             LEFT JOIN {$wpdb->prefix}abf_churches c ON b.church_id = c.id
             WHERE b.booking_date = %s AND b.status = 'confirmed'
             ORDER BY b.time_slot",
            $date
        ) );
    }

    public static function get_church_bookings( $church_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abf_bookings 
             WHERE church_id = %d AND status = 'confirmed'
             ORDER BY booking_date DESC",
            $church_id
        ) );
    }

    public static function delete_booking( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'abf_bookings', array( 'id' => $id ), array( '%d' ) );
    }

    /* ---------- FEEDBACK ---------- */
    public static function insert_feedback( $data ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'abf_feedback', array(
            'booking_id'      => intval( $data['booking_id'] ),
            'church_id'       => intval( $data['church_id'] ),
            'prayer_points'   => sanitize_textarea_field( $data['prayer_points'] ),
            'total_offering'  => floatval( $data['total_offering'] ),
            'currency'        => sanitize_text_field( $data['currency'] ?? 'USD' ),
            'proof_file'      => esc_url_raw( $data['proof_file'] ?? '' ),
            'notes'           => sanitize_textarea_field( $data['notes'] ?? '' ),
        ) );
        return $wpdb->insert_id;
    }

    public static function insert_image( $feedback_id, $image_url ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'abf_images', array(
            'feedback_id' => intval( $feedback_id ),
            'image_url'   => esc_url_raw( $image_url ),
        ) );
        return $wpdb->insert_id;
    }

    public static function get_feedback_for_booking( $booking_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abf_feedback WHERE booking_id = %d",
            $booking_id
        ) );
    }

    public static function get_images_for_feedback( $feedback_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abf_images WHERE feedback_id = %d",
            $feedback_id
        ) );
    }

    public static function get_all_feedback() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT f.*, c.church_name, c.country, c.convention, b.booking_date, b.time_slot
             FROM {$wpdb->prefix}abf_feedback f
             LEFT JOIN {$wpdb->prefix}abf_churches c ON f.church_id = c.id
             LEFT JOIN {$wpdb->prefix}abf_bookings b ON f.booking_id = b.id
             ORDER BY f.created_at DESC"
        );
    }
}
