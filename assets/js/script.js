;(function ($) {
    'use strict'

    function renderTemplate(template, context) {
        if(typeof context == undefined) {
            context = {};
        }

        var compiledTemplate = Handlebars.compile($('#tpl-' + template).html());
        $('#scene').html(compiledTemplate(context));
    };

    function showServerFeedback(message) {
        $('#server-feedback').html(message).removeClass('d-none');
    };

    function hideServerFeedback() {
        $('#server-feedback').html('').addClass('d-none');
    };

    function validatePhoneNumber() {
        const errors = [
            "Please, enter a correct phone number.",
            "Please, enter a valid country code.",
            "Please, enter a longer phone number.",
            "Please, enter a shorter phone number.",
            "Please, enter a valid phone number."
        ];

        let iti = window.intlTelInputGlobals.getInstance(document.querySelector('#phone'));

        if ($('#phone').val().trim() && iti.isValidNumber()) {
            $('#phone').get(0).setCustomValidity("");
            $('#phone').parents('.mb-3').removeClass("is-invalid");
        } else {
            $('#phone').parents('.mb-3').addClass("is-invalid");
            $('#phone').get(0).setCustomValidity(errors[iti.getValidationError()]);
            $('#phone').parents('.mb-3').find('.invalid-feedback').html(errors[iti.getValidationError()]);
        }
    }

    function startTimer(duration, $display, callback) {
        var timer = duration, minutes, seconds;
        var refreshIntervalId = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);
    
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
    
            $display.html(minutes + ":" + seconds);
    
            if (--timer < 0) {
                clearInterval(refreshIntervalId);
                callback();
            }
        }, 1000);
    }

    function initRegistration() {
        renderTemplate('registration');

        intlTelInput(document.querySelector("#phone"), {
            preferredCountries: [],
            initialCountry: "auto",
            geoIpLookup: function (success, failure) {
                $.get("https://ipinfo.io", function () { }, "jsonp").always(function (resp) {
                    var countryCode = (resp && resp.country) ? resp.country : "bg";
                    success(countryCode);
                });
            },
            utilsScript: "node_modules/intl-tel-input/build/js/utils.js"
        });

        $('#phone').on("keyup", function () {
            if ($('#form').hasClass("was-validated")) {
                validatePhoneNumber();
            }
        });

        $('#form').on('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            hideServerFeedback();
            validatePhoneNumber();

            var $form = $(this);
            if ($form[0].checkValidity() === false) {
                $form.addClass('was-validated');
            } else {
                let email = $('#email').val().trim();
                let iti = window.intlTelInputGlobals.getInstance(document.querySelector('#phone'));
                let phone = iti.getNumber();
                let password = $('#password').val().trim();

                $.post( "rest.php?action=registration", {email: email, phone: phone, password: password}, function( data ) {
                    if(data.success) {
                        iti.destroy();
                        initVerification(phone);
                    } else {
                        showServerFeedback(data.error);
                    }
                }, "json").fail(function() {
                    showServerFeedback('An unknown error occured.');
                });
            }

        });
    }

    function initVerification(phone) {
        renderTemplate('verification', {'phone': phone});

        let attempts = 3;
        
        $('#form').on('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            hideServerFeedback();

            $('#submit').prop('disabled', true);

            if(attempts <= 0) {
                $('#submit').prop('disabled', true);

                showServerFeedback('You maxed out your verification attempts. A new verification code will be sent to your phone number in <span id="counter" class="fw-bold">01:00</span>.');
                startTimer(60, $('#counter'), function () {
                    attempts = 3;

                    let phone = $('#phone').val();
                    let verification_code = $('#verification_code').val().trim();

                    $.post( "rest.php?action=verification_renew", {phone: phone, verification_code: verification_code}, function( data ) {
                        if(data.success) {
                            hideServerFeedback();
                            $('#submit').prop('disabled', false);
                        } else {
                            showServerFeedback(data.error);
                            $('#submit').prop('disabled', false);
                        }
                    }, "json").fail(function() {
                        showServerFeedback('An unknown error occured.');
                        $('#submit').prop('disabled', false);
                    });
                });

                return;
            }

            var $form = $(this);
            if ($form[0].checkValidity() === false) {
                $form.addClass('was-validated');
                $('#submit').prop('disabled', false);
            } else {
                let phone = $('#phone').val();
                let verification_code = $('#verification_code').val().trim();

                $.post( "rest.php?action=verification", {phone: phone, verification_code: verification_code}, function( data ) {
                    if(data.success) {
                        initThankYou();
                    } else {
                        attempts--;
                        showServerFeedback(data.error);
                        $('#submit').prop('disabled', false);
                    }
                }, "json").fail(function() {
                    showServerFeedback('An unknown error occured.');
                    $('#submit').prop('disabled', false);
                });
            }
        });
    }

    function initThankYou() {
        renderTemplate('thank-you');
    }

    initRegistration();

})(jQuery);
