<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="abf-prayerconnect-wrap">
    <h2 class="abf-title"><?php _e( 'Book a Prayer Date', 'abf-prayerconnect' ); ?></h2>
    <p class="abf-subtitle"><?php _e( 'Enter your Church ID to view available dates and pick a time slot.', 'abf-prayerconnect' ); ?></p>

    <div id="abf-book-message" class="abf-message"></div>

    <!-- Step 1: Verify Church ID -->
    <div id="abf-book-step1" class="abf-step">
        <form id="abf-verify-form" class="abf-form">
            <div class="abf-form-row">
                <label><?php _e( 'Church ID', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
                <input type="number" name="church_id" id="abf-church-id-input" required min="1" placeholder="Enter your Church ID">
            </div>
            <div class="abf-form-row">
                <button type="submit" class="abf-btn abf-btn-primary"><?php _e( 'Continue', 'abf-prayerconnect' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Step 2: Pick date and time -->
    <div id="abf-book-step2" class="abf-step" style="display:none;">
        <div class="abf-church-info" id="abf-church-info-box"></div>

        <div class="abf-form-row">
            <label><?php _e( 'Select Date', 'abf-prayerconnect' ); ?></label>
            <input type="date" id="abf-booking-date" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
        </div>

        <div id="abf-time-slots-container" style="display:none;">
            <label><?php _e( 'Available Time Slots', 'abf-prayerconnect' ); ?></label>
            <div id="abf-time-slots" class="abf-time-slots-grid"></div>
        </div>
    </div>

    <!-- Step 3: Confirmation -->
    <div id="abf-book-step3" class="abf-step" style="display:none;">
        <div class="abf-success-box">
            <h3>✅ <?php _e( 'Booking Confirmed!', 'abf-prayerconnect' ); ?></h3>
            <div id="abf-booking-summary"></div>
            <p><?php _e( 'After your prayer day, please submit your feedback using the feedback form.', 'abf-prayerconnect' ); ?></p>
        </div>
    </div>
</div>
