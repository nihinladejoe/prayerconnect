(function($) {
    'use strict';

    // ============ CALENDAR ============
    var ABFCalendar = {
        year: 0,
        month: 0,

        init: function() {
            var $wrap = $('#abf-calendar-wrap');
            if (!$wrap.length) return;

            this.year = parseInt($wrap.data('year'));
            this.month = parseInt($wrap.data('month'));

            var self = this;
            $('#abf-prev-month').on('click', function() { self.navigate(-1); });
            $('#abf-next-month').on('click', function() { self.navigate(1); });

            // Click on date cell
            $(document).on('click', '.abf-cal-cell:not(.abf-cal-empty):not(.abf-cal-past)', function() {
                var date = $(this).data('date');
                if (date) self.openDateModal(date);
            });

            // Close modal
            $(document).on('click', '.abf-modal-close, .abf-modal', function(e) {
                if (e.target === this) $('#abf-date-modal').hide();
            });
        },

        navigate: function(direction) {
            this.month += direction;
            if (this.month < 1) { this.month = 12; this.year--; }
            if (this.month > 12) { this.month = 1; this.year++; }
            this.loadMonth();
        },

        loadMonth: function() {
            var self = this;
            $('#abf-calendar-container').css('opacity', 0.5);
            $.post(abfPC.ajax_url, {
                action: 'abf_load_month',
                nonce: abfPC.nonce,
                year: this.year,
                month: this.month
            }, function(response) {
                $('#abf-calendar-container').css('opacity', 1);
                if (response.success) {
                    $('#abf-calendar-container').html(response.data.html);
                    $('#abf-month-label').text(response.data.label);
                    self.year = response.data.year;
                    self.month = response.data.month;
                }
            });
        },

        openDateModal: function(date) {
            var $modal = $('#abf-date-modal');
            $('#abf-modal-date-title').text('📅 ' + date);
            $('#abf-modal-slots').html('<p>Loading...</p>');
            $modal.show();

            $.post(abfPC.ajax_url, {
                action: 'abf_load_date_slots',
                nonce: abfPC.nonce,
                date: date
            }, function(response) {
                if (response.success) {
                    var html = '';
                    var slots = response.data.slots;
                    if (slots.length === 0) {
                        html = '<p>No time slots configured.</p>';
                    } else {
                        html = '<div class="abf-time-slots-grid">';
                        for (var i = 0; i < slots.length; i++) {
                            var s = slots[i];
                            var cls = s.booked ? 'abf-time-slot abf-slot-booked' : 'abf-time-slot abf-slot-available';
                            var status = s.booked ? '🔒 ' + s.church + ' (' + s.country + ')' : '✅ Available';
                            html += '<div class="' + cls + '">';
                            html += '<div class="slot-time">' + s.label + '</div>';
                            html += '<div class="slot-status">' + status + '</div>';
                            html += '</div>';
                        }
                        html += '</div>';
                    }
                    $('#abf-modal-slots').html(html);
                }
            });
        }
    };

    // ============ REGISTRATION ============
    var ABFRegister = {
        init: function() {
            var $form = $('#abf-register-form');
            if (!$form.length) return;

            $form.on('submit', function(e) {
                e.preventDefault();
                var $msg = $('#abf-register-message');
                var $btn = $form.find('button[type=submit]');
                $btn.prop('disabled', true).text(abfPC.messages.loading);
                $msg.removeClass('success error').hide();

                var data = $form.serialize() + '&action=abf_register_church&nonce=' + abfPC.nonce;
                $.post(abfPC.ajax_url, data, function(response) {
                    $btn.prop('disabled', false).text('Register Church');
                    if (response.success) {
                        $msg.addClass('success').text(response.data.message).show();
                        $('#abf-new-church-id').text(response.data.church_id);
                        $('#abf-register-result').show();
                        $form[0].reset();
                    } else {
                        $msg.addClass('error').text(response.data.message).show();
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('Register Church');
                    $msg.addClass('error').text(abfPC.messages.error).show();
                });
            });
        }
    };

    // ============ BOOKING ============
    var ABFBook = {
        churchId: 0,
        churchName: '',
        selectedDate: '',
        selectedSlot: '',

        init: function() {
            var $form = $('#abf-verify-form');
            if (!$form.length) return;

            var self = this;

            // Step 1: Verify Church ID
            $form.on('submit', function(e) {
                e.preventDefault();
                self.churchId = parseInt($('#abf-church-id-input').val());
                if (!self.churchId) return;
                self.verifyChurch();
            });

            // Step 2: Date selection
            $('#abf-booking-date').on('change', function() {
                self.selectedDate = $(this).val();
                self.loadTimeSlots(self.selectedDate);
            });
        },

        verifyChurch: function() {
            var self = this;
            // Use the register AJAX to check if church exists (or add a dedicated one)
            // Simpler: just proceed — booking AJAX will validate
            $('#abf-book-step1').hide();
            $('#abf-book-step2').show();
            $('#abf-church-info-box').html(
                '<strong>Church ID:</strong> ' + self.churchId + 
                ' — <em>Select a date below to see available time slots.</em>'
            );
        },

        loadTimeSlots: function(date) {
            var self = this;
            $('#abf-time-slots-container').show();
            $('#abf-time-slots').html('<p>Loading...</p>');

            $.post(abfPC.ajax_url, {
                action: 'abf_load_date_slots',
                nonce: abfPC.nonce,
                date: date
            }, function(response) {
                if (!response.success) {
                    $('#abf-time-slots').html('<p class="error">' + response.data.message + '</p>');
                    return;
                }
                var html = '';
                var slots = response.data.slots;
                for (var i = 0; i < slots.length; i++) {
                    var s = slots[i];
                    if (s.booked) {
                        html += '<div class="abf-time-slot abf-slot-booked">';
                        html += '<div class="slot-time">' + s.label + '</div>';
                        html += '<div class="slot-status">🔒 ' + s.church + '</div>';
                        html += '</div>';
                    } else {
                        html += '<div class="abf-time-slot abf-slot-available" data-slot="' + s.key + '">';
                        html += '<div class="slot-time">' + s.label + '</div>';
                        html += '<div class="slot-status">✅ Available — Click to book</div>';
                        html += '</div>';
                    }
                }
                $('#abf-time-slots').html(html);

                // Click to book
                $('#abf-time-slots').off('click', '.abf-slot-available').on('click', '.abf-slot-available', function() {
                    self.selectedSlot = $(this).data('slot');
                    self.confirmBooking();
                });
            });
        },

        confirmBooking: function() {
            var self = this;
            if (!confirm(abfPC.messages.confirm_book)) return;

            $.post(abfPC.ajax_url, {
                action: 'abf_make_booking',
                nonce: abfPC.nonce,
                church_id: self.churchId,
                date: self.selectedDate,
                time_slot: self.selectedSlot
            }, function(response) {
                if (response.success) {
                    $('#abf-book-step2').hide();
                    $('#abf-book-step3').show();
                    $('#abf-booking-summary').html(
                        '<p><strong>Booking ID:</strong> ' + response.data.booking_id + '</p>' +
                        '<p><strong>Church:</strong> ' + response.data.church_name + '</p>' +
                        '<p><strong>Date:</strong> ' + response.data.date + '</p>' +
                        '<p><strong>Time:</strong> ' + response.data.time_slot + '</p>' +
                        '<p class="abf-warning">⚠️ Please save your <strong>Booking ID: ' + response.data.booking_id + '</strong></p>'
                    );
                } else {
                    alert(response.data.message);
                }
            });
        }
    };

    // ============ FEEDBACK ============
    var ABFFeedback = {
        images: [],

        init: function() {
            var $form = $('#abf-feedback-form');
            if (!$form.length) return;

            var self = this;

            // Image uploader (WordPress media library)
            $('#abf-add-images-btn').on('click', function(e) {
                e.preventDefault();
                if (typeof wp === 'undefined' || !wp.media) {
                    alert('Media uploader not available. Please log in as a registered user or contact admin.');
                    return;
                }
                var frame = wp.media({
                    title: 'Select Program Pictures',
                    multiple: true,
                    library: { type: 'image' }
                });
                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    for (var i = 0; i < attachments.length; i++) {
                        self.addImage(attachments[i].url);
                    }
                });
                frame.open();
            });

            // Remove image
            $(document).on('click', '.remove-img', function() {
                var url = $(this).data('url');
                self.images = self.images.filter(function(u) { return u !== url; });
                $(this).closest('.abf-image-preview-item').remove();
                self.updateImageField();
            });

            // Submit
            $form.on('submit', function(e) {
                e.preventDefault();
                self.submit($form);
            });
        },

        addImage: function(url) {
            if (this.images.indexOf(url) !== -1) return;
            this.images.push(url);
            var html = '<div class="abf-image-preview-item">' +
                       '<img src="' + url + '" alt="">' +
                       '<button type="button" class="remove-img" data-url="' + url + '">×</button>' +
                       '</div>';
            $('#abf-image-preview').append(html);
            this.updateImageField();
        },

        updateImageField: function() {
            // We'll send as serialized array
            $('#abf-image-urls').remove();
            var $form = $('#abf-feedback-form');
            for (var i = 0; i < this.images.length; i++) {
                $form.append('<input type="hidden" name="image_urls[]" value="' + this.images[i] + '">');
            }
        },

        submit: function($form) {
            var $msg = $('#abf-feedback-message');
            var $btn = $form.find('button[type=submit]');
            $btn.prop('disabled', true).text(abfPC.messages.loading);
            $msg.removeClass('success error').hide();

            var formData = new FormData($form[0]);
            formData.append('action', 'abf_submit_feedback');
            formData.append('nonce', abfPC.nonce);

            $.ajax({
                url: abfPC.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.prop('disabled', false).text('Submit Feedback');
                    if (response.success) {
                        $msg.addClass('success').text(response.data.message).show();
                        $form[0].reset();
                        $('#abf-image-preview').empty();
                        ABFFeedback.images = [];
                        $('html, body').animate({ scrollTop: $msg.offset().top - 50 }, 300);
                    } else {
                        $msg.addClass('error').text(response.data.message).show();
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Submit Feedback');
                    $msg.addClass('error').text(abfPC.messages.error).show();
                }
            });
        }
    };

    // ============ INIT ============
    $(document).ready(function() {
        ABFCalendar.init();
        ABFRegister.init();
        ABFBook.init();
        ABFFeedback.init();
    });

})(jQuery);
