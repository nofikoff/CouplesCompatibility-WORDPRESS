/**
 * Calculator JavaScript for Numerology Compatibility Plugin
 * Supports multiple calculators on one page via shortcode
 * plugins/numerology-compatibility/public/assets/js/calculator.js
 */

(function($) {
    'use strict';

    /**
     * Calculator class - creates instance for each calculator on page
     */
    function Calculator($wrapper) {
        this.$wrapper = $wrapper;
        this.instanceId = $wrapper.data('instance') || 1;
        this.currentStep = 1;
        this.selectedPackage = $wrapper.data('package') || 'auto';
        this.selectedTier = null;
        this.calculationData = {};
        this.calculationId = null;
        this.secretCode = null;
        this.pdfUrl = null;
        this.lastSentEmail = null;
        this.isPaid = false;
        this.mode = $wrapper.data('mode') || 'normal';
        this.isReversed = (this.mode === 'reversed');

        console.log('Calculator #' + this.instanceId + ' initialized, mode:', this.mode, 'isReversed:', this.isReversed);

        this.init();
    }

    /**
     * Initialize calculator
     */
    Calculator.prototype.init = function() {
        this.bindEvents();
        this.setupHTML5Validation();
        this.setupDateLimits();
    };

    /**
     * Setup date field limits
     */
    Calculator.prototype.setupDateLimits = function() {
        var today = new Date().toISOString().split('T')[0];
        this.$wrapper.find('input[type="date"]').attr('max', today);
    };

    /**
     * Setup HTML5 validation for this calculator's fields
     */
    Calculator.prototype.setupHTML5Validation = function() {
        var $wrapper = this.$wrapper;

        // Validation for date inputs
        $wrapper.find('input[required][type="date"]').on('invalid', function() {
            this.setCustomValidity(nc_public.i18n.field_required);
        }).on('input change', function() {
            this.setCustomValidity('');
        });

        // Validation for checkboxes
        $wrapper.find('input[required][type="checkbox"]').on('invalid', function() {
            this.setCustomValidity(nc_public.i18n.checkbox_required);
        }).on('change', function() {
            this.setCustomValidity('');
        });

        // Email validation
        $wrapper.find('input[type="email"]').on('invalid', function() {
            if (this.validity.valueMissing) {
                this.setCustomValidity(nc_public.i18n.email_required);
            } else if (this.validity.typeMismatch) {
                this.setCustomValidity(nc_public.i18n.valid_email_required);
            }
        }).on('input', function() {
            this.setCustomValidity('');
        });
    };

    /**
     * Bind events for this calculator instance
     */
    Calculator.prototype.bindEvents = function() {
        var self = this;
        var $wrapper = this.$wrapper;

        // Form submission
        $wrapper.find('.nc-calculator-form').on('submit', function(e) {
            e.preventDefault();
            self.handleFormSubmit($(this));
        });

        // Package selection
        $wrapper.find('.nc-select-package').on('click', function(e) {
            e.preventDefault();
            var packageType = $(this).data('package');
            var tier = $(this).data('tier');
            self.selectPackage(packageType, tier);
        });

        // Email form submission
        $wrapper.on('submit', '.nc-send-email-form', function(e) {
            e.preventDefault();
            self.handleSendEmail($(this));
        });

        // Email input change
        $wrapper.on('input', '.nc-email-after-calc', function() {
            self.handleEmailChange($(this));
        });

        // Date validation
        $wrapper.find('input[type="date"]').on('change', function() {
            self.validateDate($(this));
        });

        // Reset/restart button
        $wrapper.on('click', '.nc-btn-restart', function(e) {
            e.preventDefault();
            self.resetCalculator();
        });
    };

    /**
     * Handle form submission
     */
    Calculator.prototype.handleFormSubmit = function($form) {
        var self = this;

        // Validate form
        if (!this.validateCalculationForm($form)) {
            return;
        }

        // Collect form data
        this.calculationData = {
            person1_date: $form.find('input[name="person1_date"]').val(),
            person2_date: $form.find('input[name="person2_date"]').val(),
            person1_name: $form.find('input[name="person1_name"]').val() || '',
            person2_name: $form.find('input[name="person2_name"]').val() || '',
            person1_time: $form.find('input[name="person1_time"]').val() || '',
            person2_time: $form.find('input[name="person2_time"]').val() || '',
            person1_place: $form.find('input[name="person1_place"]').val() || '',
            person2_place: $form.find('input[name="person2_place"]').val() || '',
            data_consent: $form.find('input[name="data_consent"]').is(':checked') ? '1' : '0',
            harm_consent: $form.find('input[name="harm_consent"]').is(':checked') ? '1' : '0',
            entertainment_consent: $form.find('input[name="entertainment_consent"]').is(':checked') ? '1' : '0'
        };

        // In reversed mode: package is already selected, process calculation
        if (this.isReversed) {
            console.log('Reversed mode: processing calculation with package:', this.selectedPackage, 'tier:', this.selectedTier);
            if (this.selectedPackage === 'free') {
                this.submitFreeCalculation();
            } else {
                this.submitPaidCalculation(this.selectedTier);
            }
            return;
        }

        // Normal mode: check if package selection is needed
        if (this.selectedPackage === 'auto') {
            this.showStep(2); // Show package selection
        } else {
            this.processCalculation();
        }
    };

    /**
     * Validate calculation form
     */
    Calculator.prototype.validateCalculationForm = function($form) {
        var self = this;
        var isValid = true;

        // Validate dates
        var $date1 = $form.find('input[name="person1_date"]');
        var $date2 = $form.find('input[name="person2_date"]');

        if (!$date1.val()) {
            this.showFieldError($date1, nc_public.i18n.birth_date_required);
            isValid = false;
        }

        if (!$date2.val()) {
            this.showFieldError($date2, nc_public.i18n.birth_date_required);
            isValid = false;
        }

        // Validate consents
        if (!$form.find('input[name="harm_consent"]').is(':checked')) {
            this.showError(nc_public.i18n.harm_consent_required);
            isValid = false;
        }

        if (!$form.find('input[name="entertainment_consent"]').is(':checked')) {
            this.showError(nc_public.i18n.entertainment_consent_required);
            isValid = false;
        }

        return isValid;
    };

    /**
     * Validate email field
     */
    Calculator.prototype.validateEmail = function($field) {
        var email = $field.val();

        if (!email) {
            this.showFieldError($field, nc_public.i18n.email_required);
            return false;
        }

        if (!this.isValidEmail(email)) {
            this.showFieldError($field, nc_public.i18n.valid_email_required);
            return false;
        }

        this.clearFieldError($field);
        return true;
    };

    /**
     * Check if email is valid
     */
    Calculator.prototype.isValidEmail = function(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    /**
     * Validate date field
     */
    Calculator.prototype.validateDate = function($field) {
        var date = new Date($field.val());
        var today = new Date();

        if (date > today) {
            this.showFieldError($field, 'Birth date cannot be in the future');
            return false;
        }

        var minDate = new Date('1900-01-01');
        if (date < minDate) {
            this.showFieldError($field, 'Please enter a valid birth date');
            return false;
        }

        this.clearFieldError($field);
        return true;
    };

    /**
     * Select package
     */
    Calculator.prototype.selectPackage = function(packageType, tier) {
        var self = this;
        this.selectedPackage = packageType;
        this.selectedTier = tier;

        // Update UI within this wrapper only
        this.$wrapper.find('.nc-package').removeClass('nc-selected');
        this.$wrapper.find('.nc-package[data-package="' + packageType + '"]').addClass('nc-selected');

        console.log('selectPackage called:', packageType, tier, 'isReversed:', this.isReversed, 'mode:', this.mode);

        // In reversed mode: show date input form (Step 2) after package selection
        if (this.isReversed === true) {
            console.log('Reversed mode: showing date input form (Step 2)');
            this.showStep(2);
            return;
        }

        // Normal mode: process calculation based on package type
        // But ONLY if we have calculation data (form was submitted)
        if (!this.calculationData || !this.calculationData.person1_date) {
            console.log('No calculation data yet, need to collect form first');
            return;
        }

        if (packageType === 'free') {
            this.submitFreeCalculation();
        } else {
            this.submitPaidCalculation(tier);
        }
    };

    /**
     * Process calculation (for pre-selected package)
     */
    Calculator.prototype.processCalculation = function() {
        if (this.selectedPackage === 'free') {
            this.submitFreeCalculation();
        } else {
            this.submitPaidCalculation(this.selectedTier || this.selectedPackage);
        }
    };

    /**
     * Submit free calculation
     */
    Calculator.prototype.submitFreeCalculation = function() {
        var self = this;

        // Show processing
        this.showStep(3);
        this.updateProcessingMessage(nc_public.i18n.calculating_compatibility, nc_public.i18n.please_wait);

        // Submit free calculation
        $.ajax({
            url: nc_public.ajax_url,
            type: 'POST',
            data: {
                action: 'nc_calculate_free',
                person1_date: this.calculationData.person1_date,
                person2_date: this.calculationData.person2_date,
                person1_name: this.calculationData.person1_name,
                person2_name: this.calculationData.person2_name,
                harm_consent: this.calculationData.harm_consent,
                entertainment_consent: this.calculationData.entertainment_consent,
                nonce: nc_public.nonce
            },
            success: function(response) {
                console.log('Free calculation response:', response);

                if (response.success) {
                    // Save calculation data
                    self.calculationId = response.data.calculation_id || null;
                    self.secretCode = response.data.secret_code || null;
                    self.pdfUrl = response.data.pdf_url || null;
                    self.isPaid = false;

                    console.log('Calculation completed:', {
                        calculation_id: self.calculationId,
                        secret_code: self.secretCode,
                        pdf_url: self.pdfUrl
                    });

                    // Show Step 5 with PDF generation progress
                    self.showSuccess(nc_public.i18n.pdf_generation_progress || 'PDF generation in progress...');

                    // Start PDF status check
                    if (self.pdfUrl && self.pdfUrl.length > 0) {
                        console.log('Starting PDF status check...');
                        self.checkPdfStatus();
                    } else {
                        console.error('PDF URL is missing or empty in response!');
                        self.$wrapper.find('.nc-pdf-generating').html(nc_public.i18n.pdf_not_available);
                    }
                } else {
                    self.showError(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Free calculation error:', error);
                self.showError('Failed to complete calculation. Please try again.');
            }
        });
    };

    /**
     * Submit paid calculation
     */
    Calculator.prototype.submitPaidCalculation = function(tier) {
        var self = this;

        // Show processing
        this.showStep(3);
        this.updateProcessingMessage(nc_public.i18n.creating_payment_session, nc_public.i18n.redirecting_to_payment);

        // Submit paid calculation request
        $.ajax({
            url: nc_public.ajax_url,
            type: 'POST',
            data: {
                action: 'nc_calculate_paid',
                tier: tier,
                person1_date: this.calculationData.person1_date,
                person2_date: this.calculationData.person2_date,
                person1_name: this.calculationData.person1_name,
                person2_name: this.calculationData.person2_name,
                harm_consent: this.calculationData.harm_consent,
                entertainment_consent: this.calculationData.entertainment_consent,
                nonce: nc_public.nonce
            },
            success: function(response) {
                if (response.success && response.data.checkout_url) {
                    // Redirect to payment page
                    console.log('Redirecting to checkout:', response.data.checkout_url);
                    window.location.href = response.data.checkout_url;
                } else {
                    self.showError(response.data.message || 'Failed to create payment session');
                }
            },
            error: function(xhr, status, error) {
                console.error('Paid calculation error:', error);
                self.showError('Failed to create payment session. Please try again.');
            }
        });
    };

    /**
     * Update processing message
     */
    Calculator.prototype.updateProcessingMessage = function(title, message) {
        this.$wrapper.find('.nc-processing-title').text(title);
        this.$wrapper.find('.nc-processing-message').text(message);
    };

    /**
     * Show success step
     */
    Calculator.prototype.showSuccess = function(message) {
        this.showStep(5);
        if (message) {
            this.$wrapper.find('.nc-success-message').text(message);
        }
    };

    /**
     * Show pending step and start payment polling
     */
    Calculator.prototype.showPendingStep = function(paymentId, calculationId) {
        this.showStep(4);
        this.startPaymentPolling(paymentId, calculationId);
    };

    /**
     * Start payment status polling
     */
    Calculator.prototype.startPaymentPolling = function(paymentId, calculationId) {
        var self = this;
        var maxAttempts = 10;
        var attempts = 0;
        var pollingActive = true;

        console.log('Starting payment polling for payment_id:', paymentId, 'calculation_id:', calculationId);

        var checkStatus = function() {
            if (!pollingActive) {
                console.log('Polling stopped');
                return;
            }

            attempts++;
            console.log('Payment status check attempt', attempts + '/' + maxAttempts);

            $.ajax({
                url: nc_public.api_base_url + '/payments/' + paymentId + '/status',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('API Response:', response);

                    if (response.success && response.data) {
                        var status = response.data.status;
                        var isPaid = response.data.is_paid;
                        var pdfReady = response.data.pdf_ready;

                        console.log('Attempt ' + attempts + ': status=' + status + ', isPaid=' + isPaid + ', pdfReady=' + pdfReady);

                        if (isPaid === true) {
                            console.log('✓ Payment completed!');
                            pollingActive = false;

                            // Save data from response
                            self.calculationId = response.data.calculation_id || null;
                            self.secretCode = response.data.secret_code || null;
                            self.pdfUrl = response.data.pdf_url || null;
                            self.isPaid = true;

                            if (pdfReady && self.pdfUrl) {
                                self.showPdfReady();
                            } else {
                                self.showSuccess(nc_public.i18n.pdf_generation_progress || 'PDF generation in progress...');
                                self.checkPdfStatus();
                            }
                        } else if (status === 'failed' || status === 'cancelled') {
                            console.log('✗ Payment failed or cancelled');
                            pollingActive = false;
                            self.showError(nc_public.i18n.payment_failed);
                        } else if (status === 'pending' && isPaid === false && attempts >= maxAttempts) {
                            console.log('✗ Timeout reached, payment not completed');
                            pollingActive = false;
                            self.showError(nc_public.i18n.payment_timeout);
                        } else if (status === 'pending') {
                            console.log('⟳ Payment still pending...');
                            setTimeout(checkStatus, 3000);
                        } else {
                            console.log('? Unknown status:', status);
                            if (attempts >= maxAttempts) {
                                pollingActive = false;
                                self.showError('Unable to determine payment status. Please contact support.');
                            } else {
                                setTimeout(checkStatus, 3000);
                            }
                        }
                    } else {
                        console.error('Invalid API response structure:', response);
                        if (attempts >= maxAttempts) {
                            pollingActive = false;
                            self.showError('Unable to verify payment status. Please contact support.');
                        } else {
                            setTimeout(checkStatus, 3000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Payment status check error:', error);
                    if (attempts >= maxAttempts) {
                        pollingActive = false;
                        self.showError('Unable to verify payment status. Please contact support.');
                    } else {
                        setTimeout(checkStatus, 3000);
                    }
                }
            });
        };

        checkStatus();
    };

    /**
     * Show step
     */
    Calculator.prototype.showStep = function(step) {
        this.$wrapper.find('.nc-step').addClass('nc-hidden');
        this.$wrapper.find('.nc-step-' + step).removeClass('nc-hidden');
        this.currentStep = step;

        // Reset Step 5 elements when showing
        if (step === 5) {
            this.$wrapper.find('.nc-pdf-download-link').addClass('nc-hidden').attr('href', '#');
            this.$wrapper.find('.nc-pdf-generating').removeClass('nc-hidden');
            this.$wrapper.find('.nc-email-form').addClass('nc-hidden');
        }
    };

    /**
     * Show PDF ready state
     */
    Calculator.prototype.showPdfReady = function() {
        var self = this;

        this.showStep(5);

        // Update icon
        this.$wrapper.find('.nc-generating-icon').removeClass('nc-generating-icon').addClass('nc-success-icon').text('✓');

        // Update title and message
        this.$wrapper.find('.nc-step-5 h2').text(nc_public.i18n.success || 'Success!');
        this.$wrapper.find('.nc-success-message').text(nc_public.i18n.pdf_ready || 'PDF is ready for download!');

        // Hide generating message
        this.$wrapper.find('.nc-pdf-generating').addClass('nc-hidden');

        // Show download link
        this.$wrapper.find('.nc-pdf-download-link')
            .attr('href', this.pdfUrl)
            .removeClass('nc-hidden');

        // Update email form title
        var emailFormTitle = this.isPaid
            ? (nc_public.i18n.send_report_and_receipt_to_email || 'Send Report and Receipt to Email?')
            : (nc_public.i18n.send_report_to_email || 'Send Report to Email?');
        this.$wrapper.find('.nc-email-form-title').text(emailFormTitle);

        // Show email form
        this.$wrapper.find('.nc-email-form').removeClass('nc-hidden');
    };

    /**
     * Get package name
     */
    Calculator.prototype.getPackageName = function(packageType) {
        switch (packageType) {
            case 'free':
                return 'Free Compatibility Report';
            case 'standard':
                return 'Standard Package Report';
            case 'premium':
                return 'Premium Package Report';
            default:
                return 'Compatibility Report';
        }
    };

    /**
     * Show error
     */
    Calculator.prototype.showError = function(message) {
        this.showStep(6);
        if (message) {
            this.$wrapper.find('.nc-error-message').text(message);
        }
    };

    /**
     * Show field error
     */
    Calculator.prototype.showFieldError = function($field, message) {
        $field.addClass('error');
        $field.siblings('.nc-error-message').text(message).removeClass('nc-hidden');
    };

    /**
     * Clear field error
     */
    Calculator.prototype.clearFieldError = function($field) {
        $field.removeClass('error');
        $field.siblings('.nc-error-message').text('').addClass('nc-hidden');
    };

    /**
     * Reset calculator to initial state
     */
    Calculator.prototype.resetCalculator = function() {
        var self = this;

        // Reset form
        this.$wrapper.find('.nc-calculator-form')[0].reset();

        // Uncheck checkboxes
        this.$wrapper.find('input[type="checkbox"]').prop('checked', false);

        // Clear all errors
        this.$wrapper.find('.nc-error-message').text('').addClass('nc-hidden');
        this.$wrapper.find('input, select, textarea').removeClass('error');

        // Reset data
        this.calculationData = {};
        this.selectedPackage = this.$wrapper.data('package') || 'auto';
        this.selectedTier = null;
        this.calculationId = null;
        this.secretCode = null;
        this.pdfUrl = null;
        this.lastSentEmail = null;

        // Remove package selection
        this.$wrapper.find('.nc-package').removeClass('nc-selected');

        // Reset email form
        var $emailForm = this.$wrapper.find('.nc-send-email-form');
        if ($emailForm.length) {
            $emailForm[0].reset();
            this.$wrapper.find('.nc-email-sent-message').addClass('nc-hidden');
            this.$wrapper.find('.nc-email-form').addClass('nc-hidden');
            var submitBtn = $emailForm.find('button[type="submit"]');
            submitBtn.prop('disabled', false).text('📧 ' + (nc_public.i18n.send_to_email || 'Send to Email'));
        }

        // Reset Step 5 state
        this.$wrapper.find('.nc-pdf-download-link').addClass('nc-hidden').attr('href', '#');
        this.$wrapper.find('.nc-pdf-generating').removeClass('nc-hidden');
        this.$wrapper.find('.nc-success-icon').removeClass('nc-success-icon').addClass('nc-generating-icon').text('⏳');
        this.$wrapper.find('.nc-step-5 h2').text(nc_public.i18n.in_progress || 'In Progress!');

        // Return to step 1
        this.showStep(1);

        console.log('Calculator #' + this.instanceId + ' reset to initial state');
    };

    /**
     * Check PDF status via polling
     */
    Calculator.prototype.checkPdfStatus = function() {
        var self = this;
        var attempts = 0;
        var maxAttempts = 15;
        var pollInterval = 2000;

        console.log('Starting PDF polling for URL:', this.pdfUrl);

        this.$wrapper.find('.nc-pdf-generating').html(nc_public.i18n.pdf_generating);

        var checkPdf = function() {
            attempts++;
            console.log('PDF check attempt ' + attempts + '/' + maxAttempts);

            $.ajax({
                url: self.pdfUrl,
                type: 'HEAD',
                timeout: 5000,
                success: function(data, textStatus, xhr) {
                    var contentType = xhr.getResponseHeader('Content-Type');

                    if (contentType && contentType.indexOf('application/pdf') !== -1) {
                        console.log('PDF is ready!');
                        self.showPdfReady();
                    } else {
                        if (attempts < maxAttempts) {
                            setTimeout(checkPdf, pollInterval);
                        } else {
                            console.error('PDF polling timeout');
                            var errorMsg = nc_public.i18n.pdf_generation_failed || 'PDF generation failed. Please try again.';
                            if (self.calculationId) {
                                errorMsg += ' ' + (nc_public.i18n.calculation_id_label || 'Calculation ID') + ': ' + self.calculationId;
                            }
                            self.showError(errorMsg);
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 425) {
                        console.log('PDF still generating (425)...');
                        if (attempts < maxAttempts) {
                            setTimeout(checkPdf, pollInterval);
                        } else {
                            console.error('PDF polling timeout (425 status)');
                            var errorMsg = nc_public.i18n.pdf_generation_failed || 'PDF generation failed. Please try again.';
                            if (self.calculationId) {
                                errorMsg += ' ' + (nc_public.i18n.calculation_id_label || 'Calculation ID') + ': ' + self.calculationId;
                            }
                            self.showError(errorMsg);
                        }
                    } else {
                        console.error('Error checking PDF status:', xhr.status);
                        var errorMsg = nc_public.i18n.pdf_check_error || 'Failed to check PDF status.';
                        if (self.calculationId) {
                            errorMsg += ' ' + (nc_public.i18n.calculation_id_label || 'Calculation ID') + ': ' + self.calculationId;
                        }
                        self.showError(errorMsg);
                    }
                }
            });
        };

        checkPdf();
    };

    /**
     * Handle email field change
     */
    Calculator.prototype.handleEmailChange = function($field) {
        var currentEmail = $field.val();
        var form = $field.closest('form');
        var submitBtn = form.find('button[type="submit"]');
        var successMessage = this.$wrapper.find('.nc-email-sent-message');

        if (this.lastSentEmail && currentEmail !== this.lastSentEmail) {
            submitBtn.prop('disabled', false).text('📧 ' + (nc_public.i18n.send_to_email || 'Send to Email'));
            successMessage.addClass('nc-hidden');
        }
    };

    /**
     * Handle send email
     */
    Calculator.prototype.handleSendEmail = function($form) {
        var self = this;
        var email = $form.find('input[type="email"]').val();

        if (!email || !this.isValidEmail(email)) {
            alert(nc_public.i18n.valid_email_required || 'Please enter a valid email address');
            return;
        }

        if (!this.secretCode) {
            alert('Secret code not found. Please recalculate.');
            return;
        }

        var submitBtn = $form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).text(nc_public.i18n.sending || 'Sending...');

        $.ajax({
            url: nc_public.ajax_url,
            type: 'POST',
            data: {
                action: 'nc_send_email',
                nonce: nc_public.nonce,
                secret_code: this.secretCode,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    self.lastSentEmail = email;
                    self.$wrapper.find('.nc-email-sent-message').removeClass('nc-hidden');
                    submitBtn.text(nc_public.i18n.sent || 'Sent!').prop('disabled', true);
                    console.log('Email sent successfully to:', email);
                } else {
                    alert(response.data.message || 'Failed to send email. Please try again.');
                    submitBtn.prop('disabled', false).text('📧 ' + (nc_public.i18n.send_to_email || 'Send to Email'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Email sending error:', error);
                alert('Failed to send email. Please try again.');
                submitBtn.prop('disabled', false).text('📧 ' + (nc_public.i18n.send_to_email || 'Send to Email'));
            }
        });
    };

    /**
     * Calculator Manager - manages all calculator instances
     */
    var CalculatorManager = {
        instances: [],

        init: function() {
            var self = this;
            $('.nc-calculator-wrapper').each(function() {
                var calculator = new Calculator($(this));
                self.instances.push(calculator);
            });
        },

        // Get calculator instance by ID or element
        getInstance: function(identifier) {
            if (typeof identifier === 'number') {
                return this.instances.find(function(calc) {
                    return calc.instanceId === identifier;
                });
            } else if (identifier instanceof jQuery) {
                return this.instances.find(function(calc) {
                    return calc.$wrapper.is(identifier);
                });
            }
            return null;
        },

        // Get first calculator instance (for backward compatibility with URL params)
        getFirst: function() {
            return this.instances[0] || null;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.nc-calculator-wrapper').length) {
            CalculatorManager.init();
        }

        // Handle return from payment
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('payment_success') === '1') {
            var paymentId = urlParams.get('payment_id');
            var calculationId = urlParams.get('calculation_id');

            // Clean URL
            if (window.history && window.history.replaceState) {
                var cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }

            // Use first calculator instance for payment callback
            var calculator = CalculatorManager.getFirst();
            if (calculator) {
                if (paymentId) {
                    calculator.showPendingStep(paymentId, calculationId);
                } else {
                    calculator.showSuccess('Your payment was successful!');
                }
            }
        } else if (urlParams.get('payment_cancelled') === '1') {
            // Clean URL
            if (window.history && window.history.replaceState) {
                var cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }

            var calculator = CalculatorManager.getFirst();
            if (calculator) {
                calculator.showError(nc_public.i18n.payment_cancelled || 'Payment was cancelled.');
            }
        }
    });

    // Export for global access
    window.NCCalculatorManager = CalculatorManager;
    window.NCCalculator = Calculator;

})(jQuery);
