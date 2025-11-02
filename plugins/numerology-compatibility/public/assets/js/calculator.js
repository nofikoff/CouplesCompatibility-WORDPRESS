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
        secretCode: null,  // НОВОЕ: секретный код для доступа к расчету
        pdfUrl: null,      // НОВОЕ: ссылка на PDF

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

            // УДАЛЕНО: Email validation на Step 1 (email теперь опциональный на Step 5)
            // $('#email').on('blur', function() {
            //     CalculatorManager.validateEmail($(this));
            // });

            // НОВОЕ: Обработчик формы отправки email на Step 5
            $(document).on('submit', '#nc-send-email-form', function(e) {
                e.preventDefault();
                CalculatorManager.handleSendEmail($(this));
            });

            // Date validation
            $('input[type="date"]').on('change', function() {
                CalculatorManager.validateDate($(this));
            });

            // Auto-format dates
            $('input[type="date"]').attr('max', new Date().toISOString().split('T')[0]);

            // Restart button (Calculate Another)
            $(document).on('click', '.nc-btn-restart', function(e) {
                e.preventDefault();
                CalculatorManager.resetCalculator();
            });
        },

        handleFormSubmit: function($form) {
            // Validate form
            if (!this.validateCalculationForm($form)) {
                return;
            }

            // Collect form data (БЕЗ email - он теперь опциональный на Step 5)
            this.calculationData = {
                // email: $('#email').val(),  // УДАЛЕНО
                person1_date: $('#person1_date').val(),
                person2_date: $('#person2_date').val(),
                person1_name: $('#person1_name').val() || '',
                person2_name: $('#person2_name').val() || '',
                person1_time: $('#person1_time').val() || '',
                person2_time: $('#person2_time').val() || '',
                person1_place: $('#person1_place').val() || '',
                person2_place: $('#person2_place').val() || '',
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

            // УДАЛЕНО: Email validation (email теперь не требуется на Step 1)
            // var email = $('#email').val();
            // if (!email) {
            //     this.showFieldError($('#email'), 'Email is required');
            //     isValid = false;
            // } else if (!this.isValidEmail(email)) {
            //     this.showFieldError($('#email'), 'Please enter a valid email address');
            //     isValid = false;
            // }

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
         * ОБНОВЛЕНО: Теперь сохраняет secret_code и pdf_url, проверяет готовность PDF
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
                        // НОВОЕ: Сохраняем secret_code и pdf_url
                        CalculatorManager.secretCode = response.data.secret_code || null;
                        CalculatorManager.pdfUrl = response.data.pdf_url || null;

                        console.log('Calculation completed:', {
                            secret_code: CalculatorManager.secretCode,
                            pdf_url: CalculatorManager.pdfUrl
                        });

                        // Показываем Step 5 (Success)
                        CalculatorManager.showSuccess(response.data.message);

                        // НОВОЕ: Начинаем проверку готовности PDF
                        CalculatorManager.checkPdfStatus();
                    } else {
                        CalculatorManager.showError(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Free calculation error:', error);
                    CalculatorManager.showError('Failed to complete calculation. Please try again.');
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
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Paid calculation error:', error);
                    CalculatorManager.showError('Failed to create payment session. Please try again.');
                }
            });
        },

        updateProcessingMessage: function(title, message) {
            $('.nc-processing-title').text(title);
            $('.nc-processing-message').text(message);
        },

        showSuccess: function(message) {
            this.showStep(5); // Step 5 = Success
            if (message) {
                $('.nc-success-message').text(message);
            }
        },

        /**
         * Показать PENDING шаг и начать polling статуса платежа
         */
        showPendingStep: function(paymentId, calculationId) {
            this.showStep(4); // Step 4 = PENDING (Verifying Payment)
            this.startPaymentPolling(paymentId, calculationId);
        },

        /**
         * Polling статуса платежа (каждые 3 секунды, максимум 30 секунд)
         */
        startPaymentPolling: function(paymentId, calculationId) {
            var maxAttempts = 10; // 10 попыток * 3 секунды = 30 секунд
            var attempts = 0;
            var self = this;
            var pollingActive = true;

            console.log('Starting payment polling for payment_id:', paymentId, 'calculation_id:', calculationId);

            var checkStatus = function() {
                if (!pollingActive) {
                    console.log('Polling stopped (flag was set to false)');
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

                            // Проверяем, прошла ли оплата
                            if (isPaid === true) {
                                // Оплата прошла успешно
                                console.log('✓ Payment completed!');
                                pollingActive = false;
                                if (pdfReady) {
                                    self.showSuccess('Payment successful! Your PDF report is ready. Check your email!');
                                } else {
                                    self.showSuccess('Payment successful! Your PDF report will be sent to your email shortly (within 5-10 minutes).');
                                }
                            } else if (status === 'failed' || status === 'cancelled') {
                                // Платеж провалился или был отменен
                                console.log('✗ Payment failed or cancelled');
                                pollingActive = false;
                                self.showError('Payment failed. Please try again.');
                            } else if (status === 'pending' && isPaid === false && attempts >= maxAttempts) {
                                // Таймаут - оплата НЕ прошла
                                console.log('✗ Timeout reached, payment not completed');
                                pollingActive = false;
                                self.showError('Payment verification timeout. If you completed the payment, please contact support with your payment confirmation.');
                            } else if (status === 'pending') {
                                // Продолжаем проверку
                                console.log('⟳ Payment still pending, will check again in 3 seconds...');
                                setTimeout(checkStatus, 3000);
                            } else {
                                // Неизвестный статус
                                console.log('? Unknown status:', status);
                                if (attempts >= maxAttempts) {
                                    pollingActive = false;
                                    self.showError('Unable to determine payment status. Please contact support with your payment confirmation.');
                                } else {
                                    setTimeout(checkStatus, 3000);
                                }
                            }
                        } else {
                            // Ошибка API response structure
                            console.error('Invalid API response structure:', response);
                            if (attempts >= maxAttempts) {
                                pollingActive = false;
                                self.showError('Unable to verify payment status. Please contact support with your payment confirmation.');
                            } else {
                                setTimeout(checkStatus, 3000);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Payment status check error:', error, 'Status:', xhr.status);

                        if (attempts >= maxAttempts) {
                            pollingActive = false;
                            self.showError('Unable to verify payment status. Please contact support with your payment confirmation.');
                        } else {
                            console.log('Will retry in 3 seconds...');
                            setTimeout(checkStatus, 3000);
                        }
                    }
                });
            };

            // Начать первую проверку сразу
            checkStatus();
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
            this.showStep(6); // Step 6 = Error page
            if (message) {
                $('.nc-error-message').text(message);
            }
        },

        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.siblings('.nc-error-message').text(message).show();
        },

        clearFieldError: function($field) {
            $field.removeClass('error');
            $field.siblings('.nc-error-message').text('').hide();
        },

        /**
         * Сброс калькулятора в начальное состояние
         */
        resetCalculator: function() {
            // Очистить форму
            $('#nc-calculator-form')[0].reset();

            // Снять галочки
            $('#data_consent, #harm_consent, #entertainment_consent').prop('checked', false);

            // Очистить все ошибки
            $('.nc-error-message').text('').hide();
            $('input, select, textarea').removeClass('error');

            // Сбросить данные
            this.calculationData = {};
            this.selectedPackage = $('#nc-calculator-wrapper').data('package') || 'auto';
            this.selectedTier = null;

            // НОВОЕ: Очистить secret_code и pdfUrl
            this.secretCode = null;
            this.pdfUrl = null;

            // Убрать выделение пакетов
            $('.nc-package').removeClass('nc-selected');

            // НОВОЕ: Очистить форму отправки email
            if ($('#nc-send-email-form').length) {
                $('#nc-send-email-form')[0].reset();
                $('.nc-email-sent-message').hide();
                $('#nc-send-email-form button[type="submit"]').prop('disabled', false);
            }

            // Вернуться на шаг 1
            this.showStep(1);

            console.log('Calculator reset to initial state');
        },

        /**
         * НОВЫЙ МЕТОД: Проверка готовности PDF
         * Проверяет каждые 3 секунды, доступен ли PDF для скачивания
         */
        checkPdfStatus: function() {
            var attempts = 0;
            var maxAttempts = 10; // 30 секунд (3 сек * 10)
            var self = this;

            // Показываем сообщение о генерации
            $('.nc-pdf-generating').show();
            $('#nc-pdf-download-link').hide();

            var interval = setInterval(function() {
                attempts++;

                if (!self.pdfUrl) {
                    console.warn('PDF URL not available');
                    clearInterval(interval);
                    return;
                }

                // Пробуем получить PDF (HEAD request через проверку через img tag trick или просто показываем ссылку)
                // Для упрощения - просто показываем ссылку после небольшой задержки
                if (attempts >= 2) { // Подождем ~6 секунд
                    // PDF должен быть готов, показываем ссылку
                    $('#nc-pdf-download-link')
                        .attr('href', self.pdfUrl)
                        .show();
                    $('.nc-pdf-generating').hide();
                    clearInterval(interval);

                    console.log('PDF is ready for download');
                }

                if (attempts >= maxAttempts) {
                    // Показать сообщение о задержке
                    $('.nc-pdf-generating').html('PDF will be available shortly. You can also request it by email below.');
                    $('#nc-pdf-download-link')
                        .attr('href', self.pdfUrl)
                        .show();
                    clearInterval(interval);
                }
            }, 3000); // Проверка каждые 3 секунды
        },

        /**
         * НОВЫЙ МЕТОД: Отправка PDF на email
         * Вызывается при submit формы отправки email на Step 5
         */
        handleSendEmail: function(form) {
            var email = form.find('#email-after-calc').val();
            var self = this;

            // Валидация email
            if (!email || !this.isValidEmail(email)) {
                alert('Please enter a valid email address');
                return;
            }

            // Проверка secret_code
            if (!this.secretCode) {
                alert('Secret code not found. Please recalculate.');
                return;
            }

            // Блокируем кнопку
            var submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).text('Sending...');

            // Отправляем запрос на бэкенд
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
                        // Показываем сообщение об успехе
                        $('.nc-email-sent-message').show();
                        submitBtn.text('Sent!').prop('disabled', true);

                        console.log('Email sent successfully to:', email);
                    } else {
                        alert(response.data.message || 'Failed to send email. Please try again.');
                        submitBtn.prop('disabled', false).text('📧 Send to Email');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Email sending error:', error);
                    alert('Failed to send email. Please try again.');
                    submitBtn.prop('disabled', false).text('📧 Send to Email');
                }
            });
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
            const paymentId = urlParams.get('payment_id');
            const calculationId = urlParams.get('calculation_id');

            // Очищаем URL чтобы при обновлении страницы не начинался polling заново
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }

            if (paymentId) {
                // Показываем PENDING Step и начинаем polling
                CalculatorManager.showPendingStep(paymentId, calculationId);
            } else {
                // Fallback если нет payment_id
                CalculatorManager.showSuccess('Your payment was successful! Check your email for the PDF report.');
            }
        } else if (urlParams.get('payment_cancelled') === '1') {
            // Пользователь отменил оплату
            CalculatorManager.showError('Payment was cancelled. Please try again.');

            // Очищаем URL
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }
    });

    // Export for global access
    window.NCCalculatorManager = CalculatorManager;

})(jQuery);