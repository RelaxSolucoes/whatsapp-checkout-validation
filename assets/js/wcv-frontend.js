/**
 * wcv-frontend.js
 * Frontend script for live validating WhatsApp numbers on checkout.
 */

;(function($) {
    $(function() {
        // Timer para debounce
        let typingTimer;
        const doneTypingInterval = 1000; // ms após parar de digitar

        const phoneField = $('#billing_phone');
        let isWhatsApp = null;
        let userConfirmedNonWhatsApp = false;

        // Se não tiver campo de telefone, sai
        if (!phoneField.length) {
            return;
        }

        // Elemento para mostrar resultados
        phoneField.after('<div id="wcv-validation-result" class="wcv-validation"></div>');
        const validationResult = $('#wcv-validation-result');

        // Modal de confirmação
        $('body').append(`
            <div id="wcv-confirmation-modal" class="wcv-modal" style="display:none;">
                <div class="wcv-modal__dialog">
                    <span id="wcv-close-modal" class="wcv-modal__close">&times;</span>
                    <h3 class="wcv-modal__title">${wcvData.i18n.attention}</h3>
                    <p>${wcvData.nonwhatsapp_msg}</p>
                    <div class="wcv-modal__actions">
                        <button id="wcv-proceed-order" class="wcv-btn wcv-btn--primary">${wcvData.i18n.proceed}</button>
                    </div>
                </div>
            </div>
        `);
        const whatsappModal = $('#wcv-confirmation-modal');

        // Eventos de modal
        $('#wcv-close-modal').on('click', function() {
            whatsappModal.hide();
        });
        $('#wcv-proceed-order').on('click', function() {
            userConfirmedNonWhatsApp = true;
            whatsappModal.hide();
            // Apenas fecha a modal; não submete o pedido automaticamente
        });

        // Formata número para padrão da API
        function formatPhoneNumber(phone) {
            phone = phone.replace(/\D/g, '');
            if (phone.length > 0 && !phone.startsWith(wcvData.intl_prefix)) {
                phone = wcvData.intl_prefix + phone;
            }
            return phone;
        }

        // Debounce keyup
        phoneField.on('keyup', function() {
            clearTimeout(typingTimer);
            isWhatsApp = null;
            userConfirmedNonWhatsApp = false;

            const phone = $(this).val();
            if (phone.length < 8) {
                validationResult.html('');
                return;
            }
            validationResult.html('<em>' + wcvData.i18n.checking + '</em>');
            typingTimer = setTimeout(function() {
                validateWhatsApp(phone);
            }, doneTypingInterval);
        });

        // Chamada AJAX para validar
        function validateWhatsApp(phone) {
            const formatted = formatPhoneNumber(phone);
            if (formatted.length < 10) {
                validationResult.html('<span class="wcv-text wcv-text--error">' + wcvData.i18n.incomplete + '</span>');
                isWhatsApp = null;
                return;
            }

            $.ajax({
                url: wcvData.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'validate_whatsapp_number',
                    phone: formatted,
                    security: wcvData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.is_whatsapp) {
                            isWhatsApp = true;
                            let msg = '<span class="wcv-text wcv-text--success">' + wcvData.i18n.valid + '</span>';
                            if (response.data.name) {
                                msg += '<div><small>' + wcvData.i18n.name + ': ' + response.data.name + '</small></div>';
                            }
                            validationResult.html(msg);
                        } else {
                            isWhatsApp = false;
                            validationResult.html('<span class="wcv-text wcv-text--warning">' + wcvData.i18n.not_whatsapp + '</span>');
                            if (wcvData.show_modal === 'yes') {
                                whatsappModal.show();
                            }
                        }
                    } else {
                        isWhatsApp = null;
                        const errorMsg = response.data && response.data.message ? response.data.message : wcvData.i18n.unknown_error;
                        validationResult.html('<span class="wcv-text wcv-text--warning">' + errorMsg + '</span>');
                        console.error('Erro na validação:', response);
                    }
                },
                error: function(xhr, status, error) {
                    isWhatsApp = null;
                    validationResult.html('<span class="wcv-text wcv-text--warning">' + wcvData.i18n.ajax_error + '</span>');
                    console.error('AJAX error:', status, error);
                }
            });
        }

        // Intercepta submit do checkout
        $('form.checkout').on('submit', function(e) {
            if (isWhatsApp === false && !userConfirmedNonWhatsApp) {
                e.preventDefault();
                if (wcvData.show_modal === 'yes') {
                    whatsappModal.show();
                }
                return false;
            }
            return true;
        });

        // Valida número já preenchido
        if (phoneField.val().length > 8) {
            setTimeout(function() {
                validateWhatsApp(phoneField.val());
            }, 500);
        }
    });
})(jQuery); 