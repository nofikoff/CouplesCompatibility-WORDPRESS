/**
 * Calculator JavaScript for Numerology Compatibility Plugin
 * plugins/numerology-compatibility/public/assets/js/calculator.js
 */

(function($) {
    'use strict';

    var CalculatorManager = {

        currentStep: 1,
        selectedPackage: null,
        calculationData: {},
        stripe: null,
        elements: null,
        sessionId: null,

        init: function() {
            this.bindEvents();
            this.initializeStripe();

            // Get initial package from data attribute
            this.selectedPackage = $('#nc-calculator-wrapper').data('package') || 'auto';
        },

        bindEvents: function() {
            // Form submission
            $('#nc-calculator-form').on('submit', function(e) {
                e.preventDefault();
                CalculatorManager.handleFormSubmit($(this));
            });

            // Package selection
            $('.nc-select-package').on('click', function() {
                CalculatorManager.selectPackage($(this).data('package'));
            });

            // Payment submission
            $('#nc-submit-payment').on('click', function() {
                CalculatorManager.handlePayment();
            });

            // Email validation
            $('#email').on('blur', function() {
                CalculatorManager.validateEmail($(this));
            });

            // Date validation
            $('input[type="date"]').on('change', function() {
                CalculatorManager.validateDate($(this));
            });

            // Auto-format dates
            $('input[type="date"]').attr('max', new Date().toISOString().split('T')[0]);
        },

        handleFormSubmit: function($form) {
            // Validate form
            if (!this.validateCalculationForm($form)) {
                return;
            }

            // Collect form data
            this.calculationData = {
                email: $('#email').val(),
                person1_date: $('#person1_date').val(),
                person2_date: $('#person2_date').val(),
                person1_name: $('#person1_name').val(),
                person2_name: $('#person2_name').val(),
                person1_time: $('#person1_time').val(),
                person2_time: $('#person2_time').val(),
                person1_place: $('#person1_place').val(),
                person2_place: $('#person2_place').val(),
                data_consent: $('#data_consent').is(':checked'),
                harm_consent: $('#harm_consent').is(':checked'),
                entertainment_consent: $('#entertainment_consent').is(':checked')
            };

            // Check if package selection is needed
            if (this.selectedPackage === 'auto') {
                this.showStep(2); // Show package selection
            } else {
                this.processCalculation();
            }
        },

        validateCalculationForm: function($form) {
            var isValid = true;

            // Validate email
            var email = $('#email').val();
            if (!email) {
                this.showFieldError($('#email'), 'Email is required');
                isValid = false;
            } else if (!this.isValidEmail(email)) {
                this.showFieldError($('#email'), 'Please enter a valid email address');
                isValid = false;
            }

            // Validate dates
            var date1 = $('#person1_date').val();
            var date2 = $('#person2_date').val();

            if (!date1) {
                this.showFieldError($('#person1_date'), 'Birth date is required');
                isValid = false;
            }

            if (!date2) {
                this.showFieldError($('#person2_date'), 'Birth date is required');
                isValid = false;
            }

            // Validate consents
            if (!$('#data_consent').is(':checked')) {
                this.showError('You must confirm you have permission to use this data');
                isValid = false;
            }

            if (!$('#harm_consent').is(':checked')) {
                this.showError('You must agree not to use this information to harm others');
                isValid = false;
            }

            if (!$('#entertainment_consent').is(':checked')) {
                this.showError('You must acknowledge this is for entertainment purposes');
                isValid = false;
            }

            return isValid;
        },

        validateEmail: function($field) {
            var email = $field.val();

            if (!email) {
                this.showFieldError($field, 'Email is required');
                return false;
            }

            if (!this.isValidEmail(email)) {
                this.showFieldError($field, 'Please enter a valid email address');
                return false;
            }

            this.clearFieldError($field);
            return true;
        },

        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        validateDate: function($field) {
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
        },

        selectPackage: function(packageType) {
            this.selectedPackage = packageType;

            // Update UI
            $('.nc-package').removeClass('nc-selected');
            $('.nc-package[data-package="' + packageType + '"]').addClass('nc-selected');

            // Process calculation
            this.processCalculation();
        },

        processCalculation: function() {
            // Add package type to calculation data
            this.calculationData.package_type = this.selectedPackage;

            // If free package, process immediately
            if (this.selectedPackage === 'free') {
                this.submitCalculation();
            } else {
                // Show payment step for paid packages
                this.createPaymentIntent();
            }
        },

        createPaymentIntent: function() {
            // Show processing
            this.showStep(4);

            // Make AJAX request to create payment intent
            $.ajax({
                url: nc_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'nc_create_payment',
                    ...this.calculationData,
                    nonce: nc_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CalculatorManager.showPaymentStep(response.data);
                    } else {
                        CalculatorManager.showError(response.data.message);
                        CalculatorManager.showStep(1);
                    }
                },
                error: function() {
                    CalculatorManager.showError('An error occurred. Please try again.');
                    CalculatorManager.showStep(1);
                }
            });
        },

        showPaymentStep: function(paymentData) {
            this.sessionId = paymentData.session_id;
            this.showStep(3);

            // Update summary
            $('.nc-item-name').text(this.getPackageName(this.selectedPackage));
            $('.nc-item-price').text('$' + paymentData.amount);
            $('.nc-email-display').text(this.calculationData.email);

            // Initialize Stripe payment element
            if (this.stripe && paymentData.client_secret) {
                this.initializePaymentElement(paymentData.client_secret);
            }
        },

        initializeStripe: function() {
            if (nc_public.stripe_key) {
                this.stripe = Stripe(nc_public.stripe_key);
            }
        },

        initializePaymentElement: function(clientSecret) {
            if (!this.stripe) {
                this.showError('Payment system not initialized');
                return;
            }

            const appearance = {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#6B46C1',
                }
            };

            this.elements = this.stripe.elements({ clientSecret, appearance });

            const paymentElement = this.elements.create('payment');
            paymentElement.mount('#nc-stripe-payment-element');
        },

        handlePayment: async function() {
            if (!this.stripe || !this.elements) {
                return;
            }

            // Show loading
            $('#nc-submit-payment').prop('disabled', true);
            $('.nc-btn-text').hide();
            $('.nc-btn-loading').show();

            const { error } = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: window.location.href + '?payment_success=1&session_id=' + this.sessionId,
                },
                redirect: 'if_required'
            });

            if (error) {
                // Show error
                $('.nc-payment-errors').text(error.message);
                $('#nc-submit-payment').prop('disabled', false);
                $('.nc-btn-text').show();
                $('.nc-btn-loading').hide();
            } else {
                // Payment succeeded, process calculation
                this.submitCalculation();
            }
        },

        submitCalculation: function() {
            // Show processing
            this.showStep(4);

            // Submit calculation
            $.ajax({
                url: nc_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'nc_calculate',
                    ...this.calculationData,
                    session_id: this.sessionId,
                    nonce: nc_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CalculatorManager.showSuccess(response.data.message);
                    } else {
                        CalculatorManager.showError(response.data.message);
                        CalculatorManager.showStep(1);
                    }
                },
                error: function() {
                    CalculatorManager.showError('Failed to complete calculation');
                    CalculatorManager.showStep(1);
                }
            });
        },

        showSuccess: function(message) {
            this.showStep(5);
            if (message) {
                $('.nc-success-message').text(message);
            }
        },

        showStep: function(step) {
            $('.nc-step').hide();
            $('.nc-step-' + step).fadeIn();
            this.currentStep = step;
        },

        getPackageName: function(packageType) {
            switch (packageType) {
                case 'free':
                    return 'Free Compatibility Report';
                case 'light':
                    return 'Light Package Report';
                case 'pro':
                    return 'Pro Package Report';
                default:
                    return 'Compatibility Report';
            }
        },

        showError: function(message) {
            // You can replace this with a better notification system
            alert(message);
        },

        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.siblings('.nc-error-message').text(message).show();
        },

        clearFieldError: function($field) {
            $field.removeClass('error');
            $field.siblings('.nc-error-message').text('').hide();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#nc-calculator-wrapper').length) {
            CalculatorManager.init();
        }

        // Handle return from Stripe redirect
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('payment_success') === '1') {
            const sessionId = urlParams.get('session_id');
            if (sessionId) {
                CalculatorManager.sessionId = sessionId;
                CalculatorManager.submitCalculation();
            }
        }
    });

    // Export for global access
    window.NCCalculatorManager = CalculatorManager;

})(jQuery);