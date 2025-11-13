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
                this.showFieldError($('#person1_date'), nc_public.i18n.birth_date_required);
                isValid = false;
            }

            if (!date2) {
                this.showFieldError($('#person2_date'), nc_public.i18n.birth_date_required);
                isValid = false;
            }

            // Validate consents
            if (!$('#data_consent').is(':checked')) {
                this.showError(nc_public.i18n.consent_data_required);
                isValid = false;
            }

            if (!$('#harm_consent').is(':checked')) {
                this.showError(nc_public.i18n.consent_harm_required);
                isValid = false;
            }

            if (!$('#entertainment_consent').is(':checked')) {
                this.showError(nc_public.i18n.consent_entertainment_required);
                isValid = false;
            }

            return isValid;
        },

        validateEmail: function($field) {
            var email = $field.val();

            if (!email) {
                this.showFieldError($field, nc_public.i18n.email_required);
                return false;
            }

            if (!this.isValidEmail(email)) {
                this.showFieldError($field, nc_public.i18n.email_invalid);
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
                this.showFieldError($field, nc_public.i18n.birth_date_future);
                return false;
            }

            var minDate = new Date('1900-01-01');
            if (date < minDate) {
                this.showFieldError($field, nc_public.i18n.birth_date_invalid);
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
            this.updateProcessingMessage(nc_public.i18n.calculating, nc_public.i18n.please_wait);

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
                    console.log('Free calculation response:', response);

                    if (response.success) {
                        // НОВОЕ: Сохраняем secret_code и pdf_url
                        CalculatorManager.secretCode = response.data.secret_code || null;
                        CalculatorManager.pdfUrl = response.data.pdf_url || null;

                        console.log('Calculation completed:', {
                            secret_code: CalculatorManager.secretCode,
                            pdf_url: CalculatorManager.pdfUrl,
                            full_response: response.data
                        });

                        // Показываем Step 5 (Success)
                        CalculatorManager.showSuccess(response.data.message);

                        // НОВОЕ: Начинаем проверку готовности PDF
                        // Проверяем что pdf_url не пустой (не null, не "", не undefined)
                        if (CalculatorManager.pdfUrl && CalculatorManager.pdfUrl.length > 0) {
                            console.log('Starting PDF status check...');
                            CalculatorManager.checkPdfStatus();
                        } else {
                            console.error('PDF URL is missing or empty in response!', {
                                pdf_url: CalculatorManager.pdfUrl,
                                response: response.data
                            });
                            $('.nc-pdf-generating').html(nc_public.i18n.pdf_url_missing);
                        }
                    } else {
                        CalculatorManager.showError(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Free calculation error:', error);
                    CalculatorManager.showError(nc_public.i18n.calculation_failed);
                }
            });
        },

        /**
         * Платный расчет - создание Checkout Session и редирект
         */
        submitPaidCalculation: function(tier) {
            // Show processing
            this.showStep(3);
            this.updateProcessingMessage(nc_public.i18n.creating_payment, nc_public.i18n.redirect_to_payment);

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
                        CalculatorManager.showError(response.data.message || nc_public.i18n.payment_failed);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Paid calculation error:', error);
                    CalculatorManager.showError(nc_public.i18n.payment_failed);
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

                                // НОВОЕ: Сохраняем secret_code и pdf_url из ответа API
                                self.secretCode = response.data.secret_code || null;
                                self.pdfUrl = response.data.pdf_url || null;

                                console.log('Payment data saved:', {
                                    secret_code: self.secretCode,
                                    pdf_url: self.pdfUrl,
                                    pdf_ready: pdfReady
                                });

                                if (pdfReady && self.pdfUrl) {
                                    // PDF готов - показываем Success с ссылкой на скачивание
                                    self.showSuccess(nc_public.i18n.payment_success_pdf_ready);

                                    // Сразу показываем ссылку на скачивание (без ожидания)
                                    $('#nc-pdf-download-link')
                                        .attr('href', self.pdfUrl)
                                        .show();
                                    $('.nc-pdf-generating').hide();
                                } else {
                                    // PDF еще генерируется
                                    self.showSuccess(nc_public.i18n.payment_success_generating);

                                    // Запускаем проверку готовности PDF
                                    self.checkPdfStatus();
                                }
                            } else if (status === 'failed' || status === 'cancelled') {
                                // Платеж провалился или был отменен
                                console.log('✗ Payment failed or cancelled');
                                pollingActive = false;
                                self.showError(nc_public.i18n.payment_cancelled);
                            } else if (status === 'pending' && isPaid === false && attempts >= maxAttempts) {
                                // Таймаут - оплата НЕ прошла
                                console.log('✗ Timeout reached, payment not completed');
                                pollingActive = false;
                                self.showError(nc_public.i18n.payment_timeout);
                            } else if (status === 'pending') {
                                // Продолжаем проверку
                                console.log('⟳ Payment still pending, will check again in 3 seconds...');
                                setTimeout(checkStatus, 3000);
                            } else {
                                // Неизвестный статус
                                console.log('? Unknown status:', status);
                                if (attempts >= maxAttempts) {
                                    pollingActive = false;
                                    self.showError(nc_public.i18n.payment_status_unknown);
                                } else {
                                    setTimeout(checkStatus, 3000);
                                }
                            }
                        } else {
                            // Ошибка API response structure
                            console.error('Invalid API response structure:', response);
                            if (attempts >= maxAttempts) {
                                pollingActive = false;
                                self.showError(nc_public.i18n.payment_verify_failed);
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
                    return nc_public.i18n.package_free;
                case 'standard':
                    return nc_public.i18n.package_standard;
                case 'premium':
                    return nc_public.i18n.package_premium;
                default:
                    return nc_public.i18n.package_default;
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
         * УПРОЩЕННАЯ проверка готовности PDF
         * Просто показываем ссылку сразу - пользователь сам решит когда кликать
         */
        checkPdfStatus: function() {
            var self = this;

            console.log('PDF URL:', self.pdfUrl);

            // Показываем сообщение что PDF генерируется
            $('.nc-pdf-generating').html(nc_public.i18n.pdf_generating);
            $('.nc-pdf-generating').show();

            // Показываем ссылку СРАЗУ
            $('#nc-pdf-download-link')
                .attr('href', self.pdfUrl)
                .show();

            console.log('PDF download link is ready');
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
                alert(nc_public.i18n.email_invalid_alert);
                return;
            }

            // Проверка secret_code
            if (!this.secretCode) {
                alert(nc_public.i18n.secret_code_missing);
                return;
            }

            // Блокируем кнопку
            var submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).text(nc_public.i18n.email_sending);

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
                        submitBtn.text(nc_public.i18n.email_sent).prop('disabled', true);

                        console.log('Email sent successfully to:', email);
                    } else {
                        alert(response.data.message || nc_public.i18n.email_failed);
                        submitBtn.prop('disabled', false).text('📧 Send to Email');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Email sending error:', error);
                    alert(nc_public.i18n.email_failed);
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
                CalculatorManager.showSuccess(nc_public.i18n.payment_success_email);
            }
        } else if (urlParams.get('payment_cancelled') === '1') {
            // Пользователь отменил оплату
            CalculatorManager.showError(nc_public.i18n.payment_cancelled);

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