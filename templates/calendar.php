<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$year  = intval( $atts['year'] );
$month = intval( $atts['month'] );

$bookings = ABF_DB::get_bookings_for_month( $year, $month );
$by_date  = array();
foreach ( $bookings as $b ) {
    $by_date[ $b->booking_date ][] = $b;
}

$program_name = get_option( 'abf_pc_program_name', 'ABF PrayerConnect' );
?>
<div class="abf-prayerconnect-wrap" id="abf-calendar-wrap" data-year="<?php echo esc_attr( $year ); ?>" data-month="<?php echo esc_attr( $month ); ?>">
    <div class="abf-header">
        <h2 class="abf-title"><?php echo esc_html( $program_name ); ?></h2>
        <p class="abf-subtitle"><?php _e( 'Public Prayer Chain Calendar — See which churches are praying on which days', 'abf-prayerconnect' ); ?></p>
    </div>

    <div class="abf-calendar-nav">
        <button type="button" class="abf-btn abf-btn-secondary" id="abf-prev-month">&laquo; <?php _e( 'Previous', 'abf-prayerconnect' ); ?></button>
        <h3 class="abf-month-label" id="abf-month-label"><?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?></h3>
        <button type="button" class="abf-btn abf-btn-secondary" id="abf-next-month"><?php _e( 'Next', 'abf-prayerconnect' ); ?> &raquo;</button>
    </div>

    <div class="abf-legend">
        <span class="abf-legend-item"><span class="abf-legend-box abf-legend-available"></span> <?php _e( 'Available', 'abf-prayerconnect' ); ?></span>
        <span class="abf-legend-item"><span class="abf-legend-box abf-legend-booked"></span> <?php _e( 'Booked', 'abf-prayerconnect' ); ?></span>
        <span class="abf-legend-item"><span class="abf-legend-box abf-legend-full"></span> <?php _e( 'Fully Booked', 'abf-prayerconnect' ); ?></span>
        <span class="abf-legend-item"><span class="abf-legend-box abf-legend-past"></span> <?php _e( 'Past', 'abf-prayerconnect' ); ?></span>
    </div>

    <div id="abf-calendar-container">
        <?php
        // Render initial grid using the same method
        $ajax = ABF_Ajax::get_instance();
        $reflection = new ReflectionClass( $ajax );
        $method = $reflection->getMethod( 'render_calendar_grid' );
        $method->setAccessible( true );
        $method->invoke( $ajax, $year, $month, $by_date );
        ?>
    </div>

    <!-- Date detail modal -->
    <div id="abf-date-modal" class="abf-modal" style="display:none;">
        <div class="abf-modal-content">
            <span class="abf-modal-close">&times;</span>
            <h3 id="abf-modal-date-title"></h3>
            <div id="abf-modal-slots"></div>
        </div>
    </div>
</div>
