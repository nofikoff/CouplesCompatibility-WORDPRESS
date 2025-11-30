/**
 * Result page JavaScript for [numerology_result] shortcode
 * Handles payment verification, PDF loading, and email sending
 */

(function($) {
    'use strict';

    /**
     * ResultPage class - handles the result page logic
     */
    function ResultPage($wrapper) {
        this.$wrapper = $wrapper;
        this.secretCode = $wrapper.data('secret-code') || '';
        this.paymentId = $wrapper.data('payment-id') || '';
        this.calculationId = $wrapper.data('calculation-id') || '';
        this.paymentSuccess = $wrapper.data('payment-success') === '1' || $wrapper.data('payment-success') === 1;
        this.pdfUrl = null;
        this.tier = 'free';
        this.isPaid = false;
        this.lastSentEmail = null;

        console.log('ResultPage initialized:', {
            secretCode: this.secretCode,
            paymentId: this.paymentId,
            calculationId: this.calculationId,
            paymentSuccess: this.paymentSuccess
        });

        this.init();
    }

    /**
     * Initialize result page
     */
    ResultPage.prototype.init = function() {
        var self = this;

        // Refresh nonce first (for cached pages)
        this.refreshNonce();

        // Bind email form
        this.$wrapper.on('submit', '#nc-result-email-form', function(e) {
            e.preventDefault();
            self.handleSendEmail($(this));
        });

        // Clean URL parameters
        this.cleanUrl();

        // Determine what to do based on URL parameters
        if (this.paymentSuccess && this.paymentId) {
            // After payment redirect - verify payment status
            this.startPaymentPolling();
        } else if (this.secretCode) {
            // Direct access with secret code - load calculation
            this.loadCalculation();
        }
        // Otherwise, the empty/cancelled state is already shown by PHP
    };

    /**
     * Clean URL parameters after reading them
     */
    ResultPage.prototype.cleanUrl = function() {
        if (window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.delete('payment_success');
            url.searchParams.delete('payment_id');
            url.searchParams.delete('calculation_id');
            url.searchParams.delete('payment_cancelled');
            // Keep the 'code' parameter for bookmarking
            window.history.replaceState({}, document.title, url.toString());
        }
    };

    /**
     * Show a specific state
     */
    ResultPage.prototype.showState = function(state) {
        this.$wrapper.find('.nc-result-state').addClass('nc-hidden');
        this.$wrapper.find('.nc-result-' + state).removeClass('nc-hidden');
    };

    /**
     * Start payment status polling
     */
    ResultPage.prototype.startPaymentPolling = function() {
        var self = this;
        var maxAttempts = 10;
        var attempts = 0;
        var pollingActive = true;

        console.log('Starting payment polling for payment_id:', this.paymentId);

        this.showState('loading');

        var checkStatus = function() {
            if (!pollingActive) {
                console.log('Polling stopped');
                return;
            }

            attempts++;
            console.log('Payment status check attempt', attempts + '/' + maxAttempts);

            $.ajax({
                url: nc_public.api_base_url + '/payments/' + self.paymentId + '/status',
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
                            console.log('Payment completed!');
                            pollingActive = false;

                            // Save data from response
                            self.calculationId = response.data.calculation_id || null;
                            self.secretCode = response.data.secret_code || null;
                            self.pdfUrl = response.data.pdf_url || null;
                            self.tier = response.data.tier || 'standard';
                            self.isPaid = true;

                            // Update URL with secret code for bookmarking
                            if (self.secretCode) {
                                var url = new URL(window.location.href);
                                url.searchParams.set('code', self.secretCode);
                                window.history.replaceState({}, document.title, url.toString());
                            }

                            if (pdfReady && self.pdfUrl) {
                                self.showPdfReady();
                            } else {
                                self.showState('generating');
                                self.checkPdfStatus();
                            }
                        } else if (status === 'failed' || status === 'cancelled') {
                            console.log('Payment failed or cancelled');
                            pollingActive = false;
                            self.showError(nc_public.i18n.payment_failed || 'Payment failed. Please try again.');
                        } else if (status === 'pending' && attempts >= maxAttempts) {
                            console.log('Timeout reached, payment not completed');
                            pollingActive = false;
                            self.showError(nc_public.i18n.payment_timeout || 'Payment verification timed out. Please contact support.');
                        } else {
                            console.log('Payment still pending...');
                            setTimeout(checkStatus, 3000);
                        }
                    } else {
                        console.error('Invalid API response:', response);
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
     * Load calculation by secret code
     * Uses HEAD request to PDF URL to check if calculation exists and PDF is ready
     */
    ResultPage.prototype.loadCalculation = function() {
        var self = this;

        console.log('Loading calculation for secret_code:', this.secretCode);
        this.showState('loading');

        // Build PDF URL from secret code
        this.pdfUrl = nc_public.api_base_url + '/calculations/' + this.secretCode + '/pdf';

        // Check if PDF exists and is ready via HEAD request
        $.ajax({
            url: this.pdfUrl,
            type: 'HEAD',
            timeout: 5000,
            success: function(data, textStatus, xhr) {
                var contentType = xhr.getResponseHeader('Content-Type');

                if (contentType && contentType.indexOf('application/pdf') !== -1) {
                    console.log('PDF is ready!');
                    self.showPdfReady();
                } else {
                    // PDF exists but not ready yet
                    self.showState('generating');
                    self.checkPdfStatus();
                }
            },
            error: function(xhr) {
                if (xhr.status === 425) {
                    // PDF is being generated
                    console.log('PDF is being generated (425)');
                    self.showState('generating');
                    self.checkPdfStatus();
                } else if (xhr.status === 404) {
                    // Calculation not found
                    console.log('Calculation not found (404)');
                    self.showError(nc_public.i18n.calculation_not_found || 'Calculation not found. Please check your link.');
                } else {
                    console.error('Error loading calculation:', xhr.status);
                    self.showError('Failed to load calculation. Please try again.');
                }
            }
        });
    };

    /**
     * Check PDF status via polling
     */
    ResultPage.prototype.checkPdfStatus = function() {
        var self = this;
        var attempts = 0;
        var maxAttempts = 15;
        var pollInterval = 2000;

        console.log('Starting PDF polling for URL:', this.pdfUrl);

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
                            self.showError(nc_public.i18n.pdf_generation_failed || 'PDF generation failed. Please try again.');
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 425) {
                        console.log('PDF still generating (425)...');
                        if (attempts < maxAttempts) {
                            setTimeout(checkPdf, pollInterval);
                        } else {
                            self.showError(nc_public.i18n.pdf_generation_failed || 'PDF generation taking too long. Please contact support.');
                        }
                    } else {
                        console.error('Error checking PDF status:', xhr.status);
                        self.showError(nc_public.i18n.pdf_check_error || 'Failed to check PDF status.');
                    }
                }
            });
        };

        checkPdf();
    };

    /**
     * Show PDF ready state
     */
    ResultPage.prototype.showPdfReady = function() {
        this.showState('success');

        // Set PDF download link
        this.$wrapper.find('#nc-result-pdf-link').attr('href', this.pdfUrl);

        // Show tier badge
        if (this.tier && this.tier !== 'free') {
            var tierLabel = this.tier.charAt(0).toUpperCase() + this.tier.slice(1);
            this.$wrapper.find('#nc-tier-badge .nc-tier-label').text(tierLabel + ' Report');
            this.$wrapper.find('#nc-tier-badge').removeClass('nc-hidden');
        }

        // Update email form title based on paid status
        if (this.isPaid) {
            this.$wrapper.find('.nc-email-form-title').text(
                nc_public.i18n.send_report_and_receipt_to_email || 'Send Report and Receipt to Email?'
            );
        }
    };

    /**
     * Show error state
     */
    ResultPage.prototype.showError = function(message) {
        this.showState('error');
        if (message) {
            this.$wrapper.find('.nc-result-error .nc-error-message').text(message);
        }
    };

    /**
     * Handle send email form
     */
    ResultPage.prototype.handleSendEmail = function($form) {
        var self = this;
        var email = $form.find('input[type="email"]').val();

        if (!email || !this.isValidEmail(email)) {
            alert(nc_public.i18n.valid_email_required || 'Please enter a valid email address');
            return;
        }

        if (!this.secretCode) {
            alert('Secret code not found. Please reload the page.');
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
                    alert(response.data?.message || 'Failed to send email. Please try again.');
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
     * Refresh nonce for cached pages
     */
    ResultPage.prototype.refreshNonce = function() {
        $.ajax({
            url: nc_public.ajax_url,
            type: 'POST',
            data: { action: 'nc_get_nonce' },
            success: function(response) {
                if (response.success && response.data.nonce) {
                    nc_public.nonce = response.data.nonce;
                    console.log('Nonce refreshed');
                }
            },
            error: function() {
                console.warn('Failed to refresh nonce');
            }
        });
    };

    /**
     * Validate email
     */
    ResultPage.prototype.isValidEmail = function(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    // Initialize when document is ready
    $(document).ready(function() {
        var $resultWrapper = $('#nc-result-wrapper');
        if ($resultWrapper.length) {
            new ResultPage($resultWrapper);
        }
    });

    // Export for global access
    window.NCResultPage = ResultPage;

})(jQuery);
