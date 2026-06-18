<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="abf-prayerconnect-wrap">
    <h2 class="abf-title"><?php _e( 'Church Registration', 'abf-prayerconnect' ); ?></h2>
    <p class="abf-subtitle"><?php _e( 'Register your church to participate in the ABF PrayerConnect Program. After registration, save your Church ID to book a date.', 'abf-prayerconnect' ); ?></p>

    <div id="abf-register-message" class="abf-message"></div>

    <form id="abf-register-form" class="abf-form">
        <div class="abf-form-row">
            <label><?php _e( 'Country', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="text" name="country" required placeholder="e.g. Nigeria, Kenya, Ghana">
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Convention / Association', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="text" name="convention" required placeholder="e.g. Nigerian Baptist Convention">
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Church Name', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="text" name="church_name" required placeholder="e.g. First Baptist Church Lagos">
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Church Address', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <textarea name="address" required rows="3" placeholder="Full physical address"></textarea>
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Contact Person', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="text" name="contact_person" required placeholder="Pastor / Representative name">
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Email Address', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="email" name="email" required placeholder="church@example.com">
        </div>

        <div class="abf-form-row">
            <label><?php _e( 'Phone Number', 'abf-prayerconnect' ); ?> <span class="required">*</span></label>
            <input type="text" name="phone" required placeholder="+234...">
        </div>

        <div class="abf-form-row">
            <button type="submit" class="abf-btn abf-btn-primary"><?php _e( 'Register Church', 'abf-prayerconnect' ); ?></button>
        </div>
    </form>

    <div id="abf-register-result" class="abf-result-box" style="display:none;">
        <h3><?php _e( '✅ Registration Successful!', 'abf-prayerconnect' ); ?></h3>
        <p><?php _e( 'Your Church ID is:', 'abf-prayerconnect' ); ?></p>
        <div class="abf-church-id" id="abf-new-church-id"></div>
        <p class="abf-warning"><?php _e( '⚠️ Please save this ID — you will need it to book a date and submit feedback.', 'abf-prayerconnect' ); ?></p>
    </div>
</div>
