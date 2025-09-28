/**
 * Authentication JavaScript for Numerology Compatibility Plugin
 */

(function($) {
    'use strict';

    var AuthManager = {

        init: function() {
            this.bindEvents();
            this.loadGoogleAPI();
        },

        bindEvents: function() {
            // Tab switching
            $('.nc-tab').on('click', function() {
                var tab = $(this).data('tab');
                AuthManager.switchTab(tab);
            });

            // Tab switching via links
            $('[data-tab]').on('click', function(e) {
                if ($(this).is('a')) {
                    e.preventDefault();
                    var tab = $(this).data('tab');
                    AuthManager.switchTab(tab);
                }
            });

            // Login form
            $('#nc-login-form').on('submit', function(e) {
                e.preventDefault();
                AuthManager.handleLogin($(this));
            });

            // Register form
            $('#nc-register-form').on('submit', function(e) {
                e.preventDefault();
                AuthManager.handleRegister($(this));
            });

            // Forgot password form
            $('#nc-forgot-form').on('submit', function(e) {
                e.preventDefault();
                AuthManager.handleForgotPassword($(this));
            });

            // Forgot password link
            $('.nc-forgot-password').on('click', function(e) {
                e.preventDefault();
                AuthManager.switchTab('forgot');
            });

            // Modal close
            $('.nc-modal-close, .nc-modal-overlay').on('click', function() {
                AuthManager.closeModal();
            });

            // Google Sign In
            $('#nc-google-signin, #nc-google-signup').on('click', function() {
                AuthManager.handleGoogleAuth();
            });

            // Real-time validation
            $('input[required]').on('blur', function() {
                AuthManager.validateField($(this));
            });

            // Password strength indicator
            $('#register_password').on('keyup', function() {
                AuthManager.checkPasswordStrength($(this).val());
            });

            // Password confirmation
            $('#register_password_confirmation').on('keyup', function() {
                AuthManager.checkPasswordMatch();
            });
        },

        switchTab: function(tab) {
            // Update tab buttons
            $('.nc-tab').removeClass('nc-tab-active');
            $('.nc-tab[data-tab="' + tab + '"]').addClass('nc-tab-active');

            // Update tab content
            $('.nc-tab-content').removeClass('nc-active').hide();
            $('.nc-tab-' + tab).addClass('nc-active').fadeIn();

            // Clear any error messages
            this.clearErrors();
        },

        handleLogin: function($form) {
            var data = $form.serialize();

            // Clear previous errors
            this.clearErrors();

            // Validate form
            if (!this.validateForm($form)) {
                return;
            }

            // Show loading
            this.showLoading($form);

            $.ajax({
                url: nc_public.ajax_url,
                type: 'POST',
                data: data + '&action=nc_register&nonce=' + nc_public.nonce,
                success: function(response) {
                    if (response.success) {
                        AuthManager.showSuccess('Registration successful! Redirecting...');
                        setTimeout(function() {
                            window.location.href = response.data.redirect || nc_public.dashboard_url;
                        }, 1000);
                    } else {
                        if (response.data.errors) {
                            // Show field-specific errors
                            $.each(response.data.errors, function(field, message) {
                                AuthManager.showFieldError($('#register_' + field), message);
                            });
                        } else {
                            AuthManager.showError(response.data.message || 'Registration failed');
                        }
                        AuthManager.hideLoading($form);
                    }
                },
                error: function() {
                    AuthManager.showError('An error occurred. Please try again.');
                    AuthManager.hideLoading($form);
                }
            });
        },

        handleForgotPassword: function($form) {
            var data = $form.serialize();

            // Clear previous errors
            this.clearErrors();

            // Validate form
            if (!this.validateForm($form)) {
                return;
            }

            // Show loading
            this.showLoading($form);

            $.ajax({
                url: nc_public.ajax_url,
                type: 'POST',
                data: data + '&action=nc_forgot_password&nonce=' + nc_public.nonce,
                success: function(response) {
                    if (response.success) {
                        AuthManager.showSuccess('Password reset link sent to your email!');
                        $form[0].reset();
                    } else {
                        AuthManager.showError(response.data.message || 'Failed to send reset link');
                    }
                    AuthManager.hideLoading($form);
                },
                error: function() {
                    AuthManager.showError('An error occurred. Please try again.');
                    AuthManager.hideLoading($form);
                }
            });
        },

        handleGoogleAuth: function() {
            if (typeof google === 'undefined' || !google.accounts) {
                this.showError('Google Sign-In is not available');
                return;
            }

            // Initialize Google Sign-In
            google.accounts.id.initialize({
                client_id: nc_public.google_client_id,
                callback: this.handleGoogleResponse.bind(this)
            });

            // Show the Google Sign-In prompt
            google.accounts.id.prompt();
        },

        handleGoogleResponse: function(response) {
            if (response.credential) {
                $.ajax({
                    url: nc_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'nc_google_auth',
                        token: response.credential,
                        nonce: nc_public.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            AuthManager.showSuccess('Google sign-in successful! Redirecting...');
                            setTimeout(function() {
                                window.location.href = response.data.redirect || nc_public.dashboard_url;
                            }, 1000);
                        } else {
                            AuthManager.showError(response.data.message || 'Google sign-in failed');
                        }
                    },
                    error: function() {
                        AuthManager.showError('An error occurred with Google sign-in');
                    }
                });
            }
        },

        loadGoogleAPI: function() {
            if (nc_public.google_client_id) {
                var script = document.createElement('script');
                script.src = 'https://accounts.google.com/gsi/client';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        },

        validateForm: function($form) {
            var isValid = true;

            $form.find('input[required]').each(function() {
                if (!AuthManager.validateField($(this))) {
                    isValid = false;
                }
            });

            return isValid;
        },

        validateField: function($field) {
            var value = $field.val().trim();
            var fieldType = $field.attr('type');
            var fieldName = $field.attr('name');

            // Clear previous error
            this.clearFieldError($field);

            // Check if empty
            if ($field.prop('required') && !value) {
                this.showFieldError($field, 'This field is required');
                return false;
            }

            // Email validation
            if (fieldType === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    this.showFieldError($field, 'Please enter a valid email address');
                    return false;
                }
            }

            // Password validation
            if (fieldName === 'password' && value) {
                if (value.length < 8) {
                    this.showFieldError($field, 'Password must be at least 8 characters');
                    return false;
                }
            }

            return true;
        },

        checkPasswordStrength: function(password) {
            var strength = 0;
            var $indicator = $('#password-strength-indicator');

            if (!$indicator.length) {
                $('#register_password').after('<div id="password-strength-indicator"></div>');
                $indicator = $('#password-strength-indicator');
            }

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[^a-zA-Z0-9]+/)) strength++;

            var strengthText = '';
            var strengthClass = '';

            switch (strength) {
                case 0:
                case 1:
                    strengthText = 'Weak';
                    strengthClass = 'weak';
                    break;
                case 2:
                case 3:
                    strengthText = 'Medium';
                    strengthClass = 'medium';
                    break;
                case 4:
                case 5:
                    strengthText = 'Strong';
                    strengthClass = 'strong';
                    break;
            }

            $indicator.html('Password strength: <span class="' + strengthClass + '">' + strengthText + '</span>');
        },

        checkPasswordMatch: function() {
            var password = $('#register_password').val();
            var confirmation = $('#register_password_confirmation').val();

            if (confirmation && password !== confirmation) {
                this.showFieldError($('#register_password_confirmation'), 'Passwords do not match');
            } else {
                this.clearFieldError($('#register_password_confirmation'));
            }
        },

        showModal: function() {
            $('#nc-auth-modal').fadeIn();
            $('body').addClass('nc-modal-open');
        },

        closeModal: function() {
            $('#nc-auth-modal').fadeOut();
            $('body').removeClass('nc-modal-open');
            this.clearErrors();
        },

        showLoading: function($form) {
            $form.find('button[type="submit"]').prop('disabled', true).addClass('loading');
        },

        hideLoading: function($form) {
            $form.find('button[type="submit"]').prop('disabled', false).removeClass('loading');
        },

        showError: function(message) {
            $('.nc-message-error').html(message).slideDown();
            setTimeout(function() {
                $('.nc-message-error').slideUp();
            }, 5000);
        },

        showSuccess: function(message) {
            $('.nc-message-success').html(message).slideDown();
            setTimeout(function() {
                $('.nc-message-success').slideUp();
            }, 5000);
        },

        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.siblings('.nc-error-message').html(message).show();
        },

        clearFieldError: function($field) {
            $field.removeClass('error');
            $field.siblings('.nc-error-message').html('').hide();
        },

        clearErrors: function() {
            $('.nc-error-message').html('').hide();
            $('input.error').removeClass('error');
            $('.nc-message-error, .nc-message-success').hide();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AuthManager.init();
    });

    // Export for global access
    window.NCAuthManager = AuthManager;

})(jQuery);