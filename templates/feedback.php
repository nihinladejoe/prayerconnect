<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="abf-prayerconnect-wrap">
    <h2 class="abf-title"><?php _e( 'Submit Program Feedback', 'abf-prayerconnect' ); ?></h2>
    <p class="abf-subtitle"><?php _e( 'Share prayer points, pictures, and offering details from your prayer program.', 'abf-prayerconnect' ); ?></p>

    <div id="abf-feedback-message" class="abf-message"></div>

    <form id="abf-feedback-form" class="abf-form" enctype="multipart/form-data">
        <div class="abf-form-row">
            <label><?php _e( 'Church ID', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="number" name="church_id" id="abf-fb-church-id" required min="1">
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Booking ID', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="number" name="booking_id" id="abf-fb-booking-id" required min="1">
            <p class="description"><?php _e( 'The Booking ID you received when you booked your date.', 'abf-prayerconnect' ); ?></p>
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Prayer Points', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <textarea name="prayer_points" required rows="6" placeholder="List the prayer points from your program..."></textarea>
        </div>

        <div class="abf-form-row abf-form-row-2col">
            <div>
                <label><?php _e( 'Total Offering', 'abf-prayerconnect' ); ?></label>
                <input type="number" name="total_offering" step="0.01" min="0" value="0">
            </div>
            <div>
                <label><?php _e( 'Currency', 'abf-prayerconnect' ); ?></label>
                <select name="currency">
                    <option value="USD">USD</option>
                    <option value="NGN">NGN (Naira)</option>
                    <option value="KES">KES (Shilling)</option>
                    <option value="GHS">GHS (Cedi)</option>
                    <option value="ZAR">ZAR (Rand)</option>
                    <option value="UGX">UGX</option>
                    <option value="TZS">TZS</option>
                    <option value="ETB">ETB (Birr)</option>
                    <option value="XOF">XOF (CFA)</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Proof of Payment / Offering Receipt', 'abf-prayerconnect' ); ?></label>
            <input type="file" name="proof_file" id="abf-proof-file" accept=".pdf,.jpg,.jpeg,.png">
            <p class="description"><?php _e( 'Accepted: PDF, JPG, PNG. Max size depends on server.', 'abf-prayerconnect' ); ?></p>
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Program Pictures', 'abf-prayerconnect' ); ?></label>
            <div id="abf-image-uploader">
                <button type="button" class="abf-btn abf-btn-secondary" id="abf-add-images-btn">
                    <?php _e( '+ Add Pictures', 'abf-prayerconnect' ); ?>
                </button>
                <div id="abf-image-preview" class="abf-image-preview"></div>
                <input type="hidden" name="image_urls" id="abf-image-urls" value="">
            </div>
            <p class="description"><?php _e( 'Upload pictures taken during the prayer program.', 'abf-prayerconnect' ); ?></p>
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Additional Notes (optional)', 'abf-prayerconnect' ); ?></label>
            <textarea name="notes" rows="3"></textarea>
        </div>

        <div class="abf-form-row">
            <button type="submit" class="abf-btn abf-btn-primary"><?php _e( 'Submit Feedback', 'abf-prayerconnect' ); ?></button>
        </div>
    </form>
</div>
