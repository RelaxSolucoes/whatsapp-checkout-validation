<?php
/**
 * WCV Admin Settings Class
 *
 * Handles plugin settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCV_Admin_Settings {
    /** @var WCV_Admin_Settings */
    private static $instance;

    /**
     * Gets the singleton instance.
     *
     * @return WCV_Admin_Settings
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Admin CSS for CTA and layout
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'whatsapp-checkout-validation' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'wcv-admin',
            WCV_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WCV_PLUGIN_VERSION
        );
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Validação WhatsApp', 'whatsapp-checkout-validation' ),
            __( 'Validação WhatsApp', 'whatsapp-checkout-validation' ),
            'manage_options',
            'whatsapp-checkout-validation',
            array( $this, 'settings_page' ),
            'dashicons-whatsapp',
            99
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Main toggles
        register_setting(
            'wcv_settings_group',
            'wcv_checkout_enabled',
            array( 'sanitize_callback' => array( $this, 'sanitize_yes_no' ) )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_checkout_show_modal',
            array( 'sanitize_callback' => array( $this, 'sanitize_yes_no' ) )
        );
        // Messages
        register_setting(
            'wcv_settings_group',
            'wcv_validation_success_msg',
            array( 'sanitize_callback' => 'sanitize_text_field' )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_validation_error_msg',
            array( 'sanitize_callback' => 'sanitize_text_field' )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_modal_title',
            array( 'sanitize_callback' => 'sanitize_text_field' )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_modal_button_text',
            array( 'sanitize_callback' => 'sanitize_text_field' )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_api_url',
            array( $this, 'sanitize_api_url' )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_api_key',
            array( $this, 'sanitize_api_key' )
        );
        register_setting(
            'wcv_settings_group',
            'wcv_instance_name',
            array( $this, 'sanitize_instance_name' )
        );
        // Country code/intl prefix
        // Register modal message (used in CTA modal)
        register_setting(
            'wcv_settings_group',
            'wcv_modal_message',
            array( 'sanitize_callback' => 'sanitize_textarea_field' )
        );

        add_settings_section(
            'wcv_settings_section',
            __( 'Configurações de Validação de WhatsApp', 'whatsapp-checkout-validation' ),
            null,
            'whatsapp-checkout-validation'
        );

        add_settings_field(
            'wcv_checkout_enabled',
            __( 'Ativar validação no checkout', 'whatsapp-checkout-validation' ),
            array( $this, 'checkout_enabled_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_checkout_show_modal',
            __( 'Exibir modal de confirmação', 'whatsapp-checkout-validation' ),
            array( $this, 'checkout_show_modal_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_validation_success_msg',
            __( 'Mensagem de sucesso', 'whatsapp-checkout-validation' ),
            array( $this, 'validation_success_msg_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_validation_error_msg',
            __( 'Mensagem de erro', 'whatsapp-checkout-validation' ),
            array( $this, 'validation_error_msg_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_modal_title',
            __( 'Título do modal', 'whatsapp-checkout-validation' ),
            array( $this, 'modal_title_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_modal_button_text',
            __( 'Texto do botão do modal', 'whatsapp-checkout-validation' ),
            array( $this, 'modal_button_text_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );

        // Existing fields
        // Order: API first
        add_settings_field(
            'wcv_api_url',
            __( 'URL da API', 'whatsapp-checkout-validation' ),
            array( $this, 'api_url_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_api_key',
            __( 'Chave da API', 'whatsapp-checkout-validation' ),
            array( $this, 'api_key_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
        add_settings_field(
            'wcv_instance_name',
            __( 'Nome da Instância', 'whatsapp-checkout-validation' ),
            array( $this, 'instance_name_callback' ),
            'whatsapp-checkout-validation',
            'wcv_settings_section'
        );
    }

    /**
     * Callback to render API URL field.
     */
    public function api_url_callback() {
        $url = get_option( 'wcv_api_url', '' );
        printf(
            '<input type="url" id="wcv_api_url" name="wcv_api_url" value="%s" class="regular-text" placeholder="%s" />',
            esc_attr( $url ),
            esc_attr__( 'https://www.suaevolutionapi.com.br', 'whatsapp-checkout-validation' )
        );
        echo '<p class="description">' . esc_html__( 'Digite apenas a URL base da API (ex: https://www.suaevolutionapi.com.br). O plugin completará o endpoint automaticamente.', 'whatsapp-checkout-validation' ) . '</p>';
    }

    /**
     * Callback to render API key field.
     */
    public function api_key_callback() {
        $key = get_option( 'wcv_api_key', '' );
        printf(
            '<input type="text" id="wcv_api_key" name="wcv_api_key" value="%s" class="regular-text" />',
            esc_attr( $key )
        );
        echo '<p class="description">' . esc_html__( 'Chave de acesso à API de validação', 'whatsapp-checkout-validation' ) . '</p>';
    }

    /**
     * Callback to render Instance Name field.
     */
    public function instance_name_callback() {
        $instance = get_option( 'wcv_instance_name', '' );
        printf(
            '<input type="text" id="wcv_instance_name" name="wcv_instance_name" value="%s" class="regular-text" placeholder="%s" />',
            esc_attr( $instance ),
            esc_attr__( 'SuaInstancia', 'whatsapp-checkout-validation' )
        );
        echo '<p class="description">' . esc_html__( 'Nome da instância para completar o endpoint (ex: SuaInstancia)', 'whatsapp-checkout-validation' ) . '</p>';
    }

    // intl_prefix removido por padronização visual e simplicidade de setup

    /**
     * Callback to render custom non-WhatsApp warning message field.
     */
    // Campo de mensagem não-WhatsApp removido; usamos apenas mensagem de erro configurável

    /**
     * Render the settings page.
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Configurações de Validação de WhatsApp no Checkout', 'whatsapp-checkout-validation' ); ?></h1>
            <div class="wpwevo-cta-box">
                <div class="wpwevo-cta-content">
                    <h3 class="wpwevo-cta-title">
                        <span class="wpwevo-cta-emoji">❌</span> Não tem uma API Evolution?
                    </h3>
                    <p class="wpwevo-cta-description">
                        <span class="wpwevo-cta-emoji">🎯</span> Envie mensagens automatizadas para seus clientes em minutos!<br>
                        <span class="wpwevo-cta-emoji">✨</span> Ative sua instância agora e aproveite todos os recursos premium do Whats Evolution.<br>
                        <span class="wpwevo-cta-emoji">🧭</span> Clique em <strong>"🚀 Teste Grátis Agora Mesmo!"</strong>, crie sua conta e receba a <strong>URL da API</strong>, <strong>Chave da API</strong> e <strong>Nome da Instância</strong>. Depois, cole nas configurações deste plugin e teste grátis por <strong>7 dias</strong>.
                    </p>
                </div>
                <a href="https://whats-evolution.vercel.app/"
                   class="wpwevo-cta-button" target="_blank" rel="noopener noreferrer">
                    <span class="wpwevo-cta-emoji">🚀</span> Teste Grátis Agora Mesmo!
                </a>
            </div>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wcv_settings_group' );
                settings_errors( 'wcv_settings_group' );
                do_settings_sections( 'whatsapp-checkout-validation' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize API URL setting.
     */
    public function sanitize_api_url( $value ) {
        $value = esc_url_raw( trim( $value ) );
        if ( empty( $value ) || ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
            add_settings_error(
                'wcv_settings_group',
                'invalid_url',
                __( 'URL inválida. Insira uma URL válida.', 'whatsapp-checkout-validation' ),
                'error'
            );
            return get_option( 'wcv_api_url' );
        }
        return $value;
    }

    /**
     * Sanitize API Key setting.
     */
    public function sanitize_api_key( $value ) {
        $value = sanitize_text_field( trim( $value ) );
        if ( empty( $value ) ) {
            add_settings_error(
                'wcv_settings_group',
                'invalid_key',
                __( 'Chave da API não pode estar vazia.', 'whatsapp-checkout-validation' ),
                'error'
            );
            return get_option( 'wcv_api_key' );
        }
        return $value;
    }

    public function checkout_enabled_callback() {
        $val = get_option( 'wcv_checkout_enabled', 'yes' );
        printf(
            '<label><input type="checkbox" name="wcv_checkout_enabled" value="yes" %s> %s</label>',
            checked( $val, 'yes', false ),
            esc_html__( 'Validar número de WhatsApp no checkout', 'whatsapp-checkout-validation' )
        );
    }

    public function checkout_show_modal_callback() {
        $val = get_option( 'wcv_checkout_show_modal', 'yes' );
        printf(
            '<label><input type="checkbox" name="wcv_checkout_show_modal" value="yes" %s> %s</label>',
            checked( $val, 'yes', false ),
            esc_html__( 'Exibir modal de confirmação quando não for WhatsApp', 'whatsapp-checkout-validation' )
        );
    }

    public function validation_success_msg_callback() {
        $val = get_option( 'wcv_validation_success_msg', __( '✓ Número de WhatsApp válido', 'whatsapp-checkout-validation' ) );
        printf(
            '<input type="text" id="wcv_validation_success_msg" name="wcv_validation_success_msg" value="%s" class="regular-text" />',
            esc_attr( $val )
        );
    }

    public function validation_error_msg_callback() {
        $val = get_option( 'wcv_validation_error_msg', __( '⚠ Este número não possui WhatsApp', 'whatsapp-checkout-validation' ) );
        printf(
            '<input type="text" id="wcv_validation_error_msg" name="wcv_validation_error_msg" value="%s" class="regular-text" />',
            esc_attr( $val )
        );
    }

    public function modal_title_callback() {
        $val = get_option( 'wcv_modal_title', __( 'Atenção!', 'whatsapp-checkout-validation' ) );
        printf(
            '<input type="text" id="wcv_modal_title" name="wcv_modal_title" value="%s" class="regular-text" />',
            esc_attr( $val )
        );
    }

    public function modal_button_text_callback() {
        $val = get_option( 'wcv_modal_button_text', __( 'Prosseguir sem WhatsApp', 'whatsapp-checkout-validation' ) );
        printf(
            '<input type="text" id="wcv_modal_button_text" name="wcv_modal_button_text" value="%s" class="regular-text" />',
            esc_attr( $val )
        );
    }

    /**
     * Sanitize yes/no checkbox values.
     */
    public function sanitize_yes_no( $value ) {
        return ( 'yes' === $value ) ? 'yes' : 'no';
    }

    /**
     * Sanitize international prefix.
     */
    public function sanitize_intl_prefix( $value ) {
        $value = preg_replace( '/\D+/', '', (string) $value );
        if ( empty( $value ) ) {
            $value = '55';
        }
        return $value;
    }

    /**
     * Sanitize and validate Instance Name via live API check.
     */
    public function sanitize_instance_name( $value ) {
        // Clean input
        $instance_raw = trim( wp_unslash( $value ) );
        $instance     = sanitize_text_field( $instance_raw );
        if ( empty( $instance ) ) {
            update_option( 'wcv_instance_validated', false );
            add_settings_error(
                'wcv_settings_group',
                'invalid_instance',
                __( 'Nome da instância não pode estar vazio.', 'whatsapp-checkout-validation' ),
                'error'
            );
            return get_option( 'wcv_instance_name' );
        }
        // Use posted API URL and key for validation, not stored options
        $post_url = isset( $_POST['wcv_api_url'] ) ? trim( wp_unslash( $_POST['wcv_api_url'] ) ) : '';
        $post_key = isset( $_POST['wcv_api_key'] ) ? trim( wp_unslash( $_POST['wcv_api_key'] ) ) : '';
        $base_url = esc_url_raw( $post_url );
        $api_key  = sanitize_text_field( $post_key );
        if ( empty( $base_url ) || empty( $api_key ) ) {
            update_option( 'wcv_instance_validated', false );
            add_settings_error(
                'wcv_settings_group',
                'invalid_instance',
                __( 'Para validar a instância, informe primeiro a URL e a chave da API.', 'whatsapp-checkout-validation' ),
                'error'
            );
            return get_option( 'wcv_instance_name' );
        }
        // Build and send request
        $endpoint = rtrim( $base_url, '/' ) . '/instance/connectionState/' . rawurlencode( $instance );
        $response = wp_remote_get( $endpoint, array(
            'timeout' => 15,
            'headers' => array( 'apikey' => $api_key ),
        ) );
        if ( is_wp_error( $response ) ) {
            update_option( 'wcv_instance_validated', false );
            add_settings_error(
                'wcv_settings_group',
                'conn_error',
                __( 'Erro ao conectar para validar instância: ', 'whatsapp-checkout-validation' ) . $response->get_error_message(),
                'error'
            );
            return get_option( 'wcv_instance_name' );
        }
        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body, true );
        if ( 200 === (int) $status_code && JSON_ERROR_NONE === json_last_error() && isset( $data['instance']['state'] ) && 'open' === $data['instance']['state'] ) {
            // Mark instance validated so the custom message can be edited
            update_option( 'wcv_instance_validated', true );
            add_settings_error(
                'wcv_settings_group',
                'instance_valid',
                __( 'Instância validada com sucesso.', 'whatsapp-checkout-validation' ),
                'updated'
            );
            return $instance;
        }
        // On any validation failure, clear validated flag and show error
        update_option( 'wcv_instance_validated', false );
        $error_message = __( 'Instância inválida ou não encontrada.', 'whatsapp-checkout-validation' );
        if ( is_array( $data ) && isset( $data['response']['message'][0] ) ) {
            $error_message = $data['response']['message'][0];
        }
        add_settings_error(
            'wcv_settings_group',
            'invalid_instance',
            sprintf( __( 'Erro ao validar instância: %s', 'whatsapp-checkout-validation' ), $error_message ),
            'error'
        );
        return get_option( 'wcv_instance_name' );
    }
} 