<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ABF_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu() {
        add_menu_page(
            __( 'ABF PrayerConnect', 'abf-prayerconnect' ),
            __( 'PrayerConnect', 'abf-prayerconnect' ),
            'manage_options',
            'abf-prayerconnect',
            array( $this, 'page_dashboard' ),
            'dashicons-pray',
            30
        );

        add_submenu_page( 'abf-prayerconnect', __( 'Dashboard', 'abf-prayerconnect' ), __( 'Dashboard', 'abf-prayerconnect' ), 'manage_options', 'abf-prayerconnect', array( $this, 'page_dashboard' ) );
        add_submenu_page( 'abf-prayerconnect', __( 'Churches', 'abf-prayerconnect' ), __( 'Churches', 'abf-prayerconnect' ), 'manage_options', 'abf-pc-churches', array( $this, 'page_churches' ) );
        add_submenu_page( 'abf-prayerconnect', __( 'Bookings', 'abf-prayerconnect' ), __( 'Bookings', 'abf-prayerconnect' ), 'manage_options', 'abf-pc-bookings', array( $this, 'page_bookings' ) );
        add_submenu_page( 'abf-prayerconnect', __( 'Feedback', 'abf-prayerconnect' ), __( 'Feedback', 'abf-prayerconnect' ), 'manage_options', 'abf-pc-feedback', array( $this, 'page_feedback' ) );
        add_submenu_page( 'abf-prayerconnect', __( 'Settings', 'abf-prayerconnect' ), __( 'Settings', 'abf-prayerconnect' ), 'manage_options', 'abf-pc-settings', array( $this, 'page_settings' ) );
    }

    public function register_settings() {
        register_setting( 'abf_pc_settings', 'abf_pc_time_start', array( 'type' => 'integer' ) );
        register_setting( 'abf_pc_settings', 'abf_pc_time_end', array( 'type' => 'integer' ) );
        register_setting( 'abf_pc_settings', 'abf_pc_program_name', array( 'type' => 'string' ) );
    }

    public function page_dashboard() {
        global $wpdb;
        $total_churches = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}abf_churches WHERE status='active'" );
        $total_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}abf_bookings WHERE status='confirmed'" );
        $total_feedback = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}abf_feedback" );
        $total_offering = (float) $wpdb->get_var( "SELECT SUM(total_offering) FROM {$wpdb->prefix}abf_feedback" );

        ?>
        <div class="wrap">
            <h1><?php _e( 'ABF PrayerConnect Dashboard', 'abf-prayerconnect' ); ?></h1>
            <div class="abf-stats-grid">
                <div class="abf-stat-card">
                    <h3><?php _e( 'Registered Churches', 'abf-prayerconnect' ); ?></h3>
                    <div class="abf-stat-number"><?php echo esc_html( $total_churches ); ?></div>
                </div>
                <div class="abf-stat-card">
                    <h3><?php _e( 'Total Bookings', 'abf-prayerconnect' ); ?></h3>
                    <div class="abf-stat-number"><?php echo esc_html( $total_bookings ); ?></div>
                </div>
                <div class="abf-stat-card">
                    <h3><?php _e( 'Feedback Submissions', 'abf-prayerconnect' ); ?></h3>
                    <div class="abf-stat-number"><?php echo esc_html( $total_feedback ); ?></div>
                </div>
                <div class="abf-stat-card">
                    <h3><?php _e( 'Total Offering', 'abf-prayerconnect' ); ?></h3>
                    <div class="abf-stat-number"><?php echo esc_html( number_format( $total_offering, 2 ) ); ?></div>
                </div>
            </div>

            <div class="abf-shortcodes-box">
                <h2><?php _e( 'Shortcodes to Use on Your Pages', 'abf-prayerconnect' ); ?></h2>
                <table class="widefat">
                    <tr><td><code>[abf_calendar]</code></td><td><?php _e( 'Public calendar showing all bookings', 'abf-prayerconnect' ); ?></td></tr>
                    <tr><td><code>[abf_register]</code></td><td><?php _e( 'Church registration form', 'abf-prayerconnect' ); ?></td></tr>
                    <tr><td><code>[abf_book]</code></td><td><?php _e( 'Booking form (for registered churches)', 'abf-prayerconnect' ); ?></td></tr>
                    <tr><td><code>[abf_feedback]</code></td><td><?php _e( 'Feedback submission form', 'abf-prayerconnect' ); ?></td></tr>
                </table>
            </div>
        </div>
        <?php
    }

    public function page_churches() {
        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'abf_delete_church_' . $_GET['id'] ) ) {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . 'abf_churches', array( 'id' => intval( $_GET['id'] ) ), array( '%d' ) );
            echo '<div class="notice notice-success"><p>' . __( 'Church deleted.', 'abf-prayerconnect' ) . '</p></div>';
        }

        $churches = ABF_DB::get_all_churches();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Registered Churches', 'abf-prayerconnect' ); ?></h1>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Church Name', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Country', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Convention', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Contact', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Email', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Phone', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Actions', 'abf-prayerconnect' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $churches ) ) : ?>
                    <tr><td colspan="8"><?php _e( 'No churches registered yet.', 'abf-prayerconnect' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $churches as $c ) : ?>
                        <tr>
                            <td><?php echo esc_html( $c->id ); ?></td>
                            <td><strong><?php echo esc_html( $c->church_name ); ?></strong></td>
                            <td><?php echo esc_html( $c->country ); ?></td>
                            <td><?php echo esc_html( $c->convention ); ?></td>
                            <td><?php echo esc_html( $c->contact_person ); ?></td>
                            <td><?php echo esc_html( $c->email ); ?></td>
                            <td><?php echo esc_html( $c->phone ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=abf-pc-churches&action=delete&id=' . $c->id ), 'abf_delete_church_' . $c->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this church?', 'abf-prayerconnect' ); ?>');" class="button button-small"><?php _e( 'Delete', 'abf-prayerconnect' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function page_bookings() {
        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'abf_delete_booking_' . $_GET['id'] ) ) {
            ABF_DB::delete_booking( intval( $_GET['id'] ) );
            echo '<div class="notice notice-success"><p>' . __( 'Booking deleted.', 'abf-prayerconnect' ) . '</p></div>';
        }

        global $wpdb;
        $bookings = $wpdb->get_results(
            "SELECT b.*, c.church_name, c.country 
             FROM {$wpdb->prefix}abf_bookings b
             LEFT JOIN {$wpdb->prefix}abf_churches c ON b.church_id = c.id
             ORDER BY b.booking_date DESC, b.time_slot"
        );
        ?>
        <div class="wrap">
            <h1><?php _e( 'All Bookings', 'abf-prayerconnect' ); ?></h1>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Date', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Time', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Church', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Country', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Status', 'abf-prayerconnect' ); ?></th>
                        <th><?php _e( 'Actions', 'abf-prayerconnect' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $bookings ) ) : ?>
                    <tr><td colspan="6"><?php _e( 'No bookings yet.', 'abf-prayerconnect' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $bookings as $b ) : ?>
                        <tr>
                            <td><?php echo esc_html( $b->booking_date ); ?></td>
                            <td><?php echo esc_html( $b->time_slot ); ?></td>
                            <td><strong><?php echo esc_html( $b->church_name ?? '—' ); ?></strong></td>
                            <td><?php echo esc_html( $b->country ?? '—' ); ?></td>
                            <td><span class="abf-badge abf-badge-<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=abf-pc-bookings&action=delete&id=' . $b->id ), 'abf_delete_booking_' . $b->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this booking? This will free the slot.', 'abf-prayerconnect' ); ?>');" class="button button-small"><?php _e( 'Delete', 'abf-prayerconnect' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function page_feedback() {
        $feedbacks = ABF_DB::get_all_feedback();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Feedback Submissions', 'abf-prayerconnect' ); ?></h1>
            <?php foreach ( $feedbacks as $f ) : 
                $images = ABF_DB::get_images_for_feedback( $f->id );
            ?>
                <div class="abf-feedback-card">
                    <h3><?php echo esc_html( $f->church_name ); ?> — <?php echo esc_html( $f->booking_date ); ?> @ <?php echo esc_html( $f->time_slot ); ?></h3>
                    <p><strong><?php _e( 'Country:', 'abf-prayerconnect' ); ?></strong> <?php echo esc_html( $f->country ); ?> | 
                       <strong><?php _e( 'Convention:', 'abf-prayerconnect' ); ?></strong> <?php echo esc_html( $f->convention ); ?></p>
                    <p><strong><?php _e( 'Prayer Points:', 'abf-prayerconnect' ); ?></strong></p>
                    <div class="abf-prayer-points"><?php echo nl2br( esc_html( $f->prayer_points ) ); ?></div>
                    <p><strong><?php _e( 'Total Offering:', 'abf-prayerconnect' ); ?></strong> <?php echo esc_html( $f->currency . ' ' . number_format( $f->total_offering, 2 ) ); ?></p>
                    <?php if ( $f->proof_file ) : ?>
                        <p><strong><?php _e( 'Proof of Payment:', 'abf-prayerconnect' ); ?></strong> 
                           <a href="<?php echo esc_url( $f->proof_file ); ?>" target="_blank"><?php _e( 'View File', 'abf-prayerconnect' ); ?></a></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $images ) ) : ?>
                        <p><strong><?php _e( 'Program Pictures:', 'abf-prayerconnect' ); ?></strong></p>
                        <div class="abf-image-grid">
                            <?php foreach ( $images as $img ) : ?>
                                <a href="<?php echo esc_url( $img->image_url ); ?>" target="_blank">
                                    <img src="<?php echo esc_url( $img->image_url ); ?>" alt="">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ( empty( $feedbacks ) ) : ?>
                <p><?php _e( 'No feedback submissions yet.', 'abf-prayerconnect' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function page_settings() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'PrayerConnect Settings', 'abf-prayerconnect' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'abf_pc_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="abf_pc_program_name"><?php _e( 'Program Name', 'abf-prayerconnect' ); ?></label></th>
                        <td><input type="text" id="abf_pc_program_name" name="abf_pc_program_name" value="<?php echo esc_attr( get_option( 'abf_pc_program_name' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="abf_pc_time_start"><?php _e( 'Time Slots Start Hour (24h)', 'abf-prayerconnect' ); ?></label></th>
                        <td>
                            <input type="number" id="abf_pc_time_start" name="abf_pc_time_start" value="<?php echo esc_attr( get_option( 'abf_pc_time_start', 8 ) ); ?>" min="0" max="23" class="small-text">
                            <p class="description"><?php _e( 'E.g. 8 = 8:00 AM', 'abf-prayerconnect' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="abf_pc_time_end"><?php _e( 'Time Slots End Hour (24h)', 'abf-prayerconnect' ); ?></label></th>
                        <td>
                            <input type="number" id="abf_pc_time_end" name="abf_pc_time_end" value="<?php echo esc_attr( get_option( 'abf_pc_time_end', 20 ) ); ?>" min="1" max="24" class="small-text">
                            <p class="description"><?php _e( 'E.g. 20 = 8:00 PM. Each slot is 1 hour.', 'abf-prayerconnect' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
