/**
 * Calculator JavaScript for Numerology Compatibility Plugin
 * Рефакторенная версия без Stripe логики
 * plugins/numerology-compatibility/public/assets/js/calculator.js
 */

(function($) {
    'use strict';

    var CalculatorManager = {

        currentStep: 1,
        selectedPackage: null,
        selectedTier: null,
        calculationData: {},

        init: function() {
            this.bindEvents();

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
            $('.nc-select-package').on('click', function(e) {
                e.preventDefault();
                var packageType = $(this).data('package');
                var tier = $(this).data('tier');
                CalculatorManager.selectPackage(packageType, tier);
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

        selectPackage: function(packageType, tier) {
            this.selectedPackage = packageType;
            this.selectedTier = tier;

            // Update UI
            $('.nc-package').removeClass('nc-selected');
            $('.nc-package[data-package="' + packageType + '"]').addClass('nc-selected');

            // Process calculation based on package type
            if (packageType === 'free') {
                this.submitFreeCalculation();
            } else {
                // Для платных пакетов (standard/premium)
                this.submitPaidCalculation(tier);
            }
        },

        /**
         * Бесплатный расчет
         */
        submitFreeCalculation: function() {
            // Show processing
            this.showStep(3);
            this.updateProcessingMessage('Calculating your compatibility...', 'Please wait...');

            // Submit free calculation
            $.ajax({
                url: nc_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'nc_calculate_free',
                    ...this.calculationData,
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
                error: function(xhr, status, error) {
                    console.error('Free calculation error:', error);
                    CalculatorManager.showError('Failed to complete calculation. Please try again.');
                    CalculatorManager.showStep(1);
                }
            });
        },

        /**
         * Платный расчет - создание Checkout Session и редирект
         */
        submitPaidCalculation: function(tier) {
            // Show processing
            this.showStep(3);
            this.updateProcessingMessage('Creating payment session...', 'Please wait, you will be redirected to payment page...');

            // Submit paid calculation request
            $.ajax({
                url: nc_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'nc_calculate_paid',
                    tier: tier,
                    ...this.calculationData,
                    nonce: nc_public.nonce
                },
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        // Редирект на страницу оплаты бэкенда
                        console.log('Redirecting to checkout:', response.data.checkout_url);
                        window.location.href = response.data.checkout_url;
                    } else {
                        CalculatorManager.showError(response.data.message || 'Failed to create payment session');
                        CalculatorManager.showStep(1);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Paid calculation error:', error);
                    CalculatorManager.showError('Failed to create payment session. Please try again.');
                    CalculatorManager.showStep(1);
                }
            });
        },

        updateProcessingMessage: function(title, message) {
            $('.nc-processing-title').text(title);
            $('.nc-processing-message').text(message);
        },

        showSuccess: function(message) {
            this.showStep(4); // Step 4 теперь Success (раньше было 5)
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
                case 'standard':
                    return 'Standard Package Report';
                case 'premium':
                    return 'Premium Package Report';
                default:
                    return 'Compatibility Report';
            }
        },

        showError: function(message) {
            // TODO: Можно заменить на лучшую систему уведомлений
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

        // Handle return from payment (success callback от бэкенда)
        // Бэкенд редиректит обратно на сайт с параметром ?payment_success=1&calculation_id=xxx
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('payment_success') === '1') {
            // Показываем Success Step
            CalculatorManager.showSuccess('Your payment was successful! Check your email for the PDF report.');
        } else if (urlParams.get('payment_cancelled') === '1') {
            // Пользователь отменил оплату
            CalculatorManager.showError('Payment was cancelled. Please try again.');
        }
    });

    // Export for global access
    window.NCCalculatorManager = CalculatorManager;

})(jQuery);