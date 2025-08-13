<?php
/**
 * WCV Validator Class
 *
 * Handles frontend scripts and AJAX validation for WhatsApp numbers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCV_Validator {
    private static $instance;

    /**
     * Gets the singleton instance.
     *
     * @return WCV_Validator
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_validate_whatsapp_number', array( $this, 'validate_whatsapp_number_ajax' ) );
        add_action( 'wp_ajax_nopriv_validate_whatsapp_number', array( $this, 'validate_whatsapp_number_ajax' ) );
    }

    /**
     * Enqueue frontend script on checkout page.
     */
    public function enqueue_scripts() {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'wcv-frontend',
            WCV_PLUGIN_URL . 'assets/css/wcv-frontend.css',
            array(),
            WCV_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'wcv-frontend',
            WCV_PLUGIN_URL . 'assets/js/wcv-frontend.js',
            array( 'jquery' ),
            WCV_PLUGIN_VERSION,
            true
        );

        $intl_prefix = get_option( 'wcv_intl_prefix', '55' );
        wp_localize_script(
            'wcv-frontend',
            'wcvData',
            array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'nonce'           => wp_create_nonce( 'wcv_validation_nonce' ),
                'intl_prefix'     => $intl_prefix,
                'i18n'            => array(
                    'checking'      => __( 'Verificando número...', 'whatsapp-checkout-validation' ),
                    'incomplete'    => __( 'Número de telefone incompleto', 'whatsapp-checkout-validation' ),
                    'valid'         => __( '✓ Número de WhatsApp válido', 'whatsapp-checkout-validation' ),
                    'not_whatsapp'  => __( '⚠ Este número não possui WhatsApp', 'whatsapp-checkout-validation' ),
                    'unknown_error' => __( 'Não foi possível verificar o número', 'whatsapp-checkout-validation' ),
                    'ajax_error'    => __( 'Erro na verificação. Tente novamente.', 'whatsapp-checkout-validation' ),
                    'attention'     => __( 'Atenção!', 'whatsapp-checkout-validation' ),
                    'proceed'       => __( 'Prosseguir sem WhatsApp', 'whatsapp-checkout-validation' ),
                    'name'          => __( 'Nome', 'whatsapp-checkout-validation' ),
                ),
                // Custom non-WhatsApp warning message
                'nonwhatsapp_msg' => get_option( 'wcv_nonwhatsapp_msg', __( 'O número informado não é WhatsApp. Você não receberá a confirmação por mensagem.', 'whatsapp-checkout-validation' ) ),
            )
        );
    }

    /**
     * AJAX callback to validate WhatsApp number.
     */
    public function validate_whatsapp_number_ajax() {
        check_ajax_referer( 'wcv_validation_nonce', 'security' );

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

        if ( empty( $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Número de telefone não fornecido', 'whatsapp-checkout-validation' ) ) );
        }

        $api_url = get_option( 'wcv_api_url', '' );
        $api_key = get_option( 'wcv_api_key', '' );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API não configurada corretamente', 'whatsapp-checkout-validation' ) ) );
        }

        // Get instance name and build endpoint URL
        $instance = get_option( 'wcv_instance_name', '' );
        if ( empty( $instance ) ) {
            wp_send_json_error( array( 'message' => __( 'Instância não configurada corretamente', 'whatsapp-checkout-validation' ) ) );
        }
        $api_url = rtrim( $api_url, '/' ) . '/chat/whatsappNumbers/' . rawurlencode( $instance );
        /**
         * Filter the API URL for validation.
         *
         * @param string $api_url
         * @param string $phone
         */
        $api_url = apply_filters( 'wcv_validation_api_url', $api_url, $phone );

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey'       => $api_key,
            ),
            'body'    => wp_json_encode( array( 'numbers' => array( $phone ) ) ),
        );

        /**
         * Filter the request args for the validation call.
         *
         * @param array  $args
         * @param string $phone
         */
        $args = apply_filters( 'wcv_validation_request_args', $args, $phone );

        // Cache layer to reduce API calls (salt rotates on settings change)
        $salt      = (string) get_option( 'wcv_cache_salt', '' );
        $cache_key = 'wcv_whatsapp_' . md5( $salt . '|' . $phone );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( $cached );
        }

        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro ao conectar com a API', 'whatsapp-checkout-validation' ) . ': ' . $response->get_error_message() ) );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        if ( 200 !== (int) $status_code ) {
            wp_send_json_error( array( 'message' => sprintf( __( 'API retornou status %d', 'whatsapp-checkout-validation' ), $status_code ) ) );
        }

        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( array( 'message' => __( 'Resposta inválida da API (JSON)', 'whatsapp-checkout-validation' ) ) );
        }

        if ( is_array( $data ) && ! empty( $data[0] ) && isset( $data[0]['exists'] ) ) {
            $exists = (bool) $data[0]['exists'];

            $result = array(
                'is_whatsapp' => $exists,
                'number'      => isset( $data[0]['number'] ) ? $data[0]['number'] : '',
                'name'        => isset( $data[0]['name'] ) ? $data[0]['name'] : '',
            );

            /**
             * Filter the parsed validation result before returning/caching.
             *
             * @param array $result
             * @param array $raw_data
             */
            $result = apply_filters( 'wcv_validation_response', $result, $data );

            // Cache success result
            $ttl = (int) apply_filters( 'wcv_validation_cache_ttl', HOUR_IN_SECONDS * 12, $result );
            if ( $ttl > 0 ) {
                set_transient( $cache_key, $result, $ttl );
            }

            wp_send_json_success( $result );
        }

        wp_send_json_error( array( 'message' => __( 'Resposta inesperada da API', 'whatsapp-checkout-validation' ) ) );
    }
} 