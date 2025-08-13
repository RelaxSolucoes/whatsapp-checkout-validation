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
        add_action( 'admin_post_wcv_test_connection', array( $this, 'handle_test_connection' ) );
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
        $connection_status = $this->is_configured() ? $this->check_connection() : null;
        ?>
        <div class="wrap wpwevo-panel" style="max-width: none;">
            <h1>⚙️ <?php esc_html_e( 'Validação de WhatsApp no Checkout - Configurações', 'whatsapp-checkout-validation' ); ?></h1>
            <div class="wpwevo-cta-box">
                <div class="wpwevo-cta-content">
                    <h3 class="wpwevo-cta-title">
                        <span class="wpwevo-cta-emoji">❌</span> Não tem uma API Evolution?
                    </h3>
                    <p class="wpwevo-cta-description">
                        <span class="wpwevo-cta-emoji">🎯</span> Envie mensagens automatizadas para seus clientes em minutos!<br>
                        <span class="wpwevo-cta-emoji">✨</span> Ative sua instância agora e aproveite todos os recursos premium do Whats Evolution.<br>
                        <span class="wpwevo-cta-emoji">🧭</span> Clique em <strong>"🚀 Teste Grátis Agora Mesmo!"</strong>, crie sua conta e receba suas <strong>Credenciais</strong>. Cole nas configurações e teste grátis por <strong>7 dias</strong>.
                    </p>
                </div>
                <a href="https://whats-evolution.vercel.app/"
                   class="wpwevo-cta-button" target="_blank" rel="noopener noreferrer">
                    <span class="wpwevo-cta-emoji">🚀</span> Teste Grátis Agora Mesmo!
                </a>
            </div>
            <form action="options.php" method="post" id="wcv-settings-form">
                <?php settings_fields( 'wcv_settings_group' ); ?>

                <!-- Card: Configuração da Evolution API -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 0; box-shadow: 0 4px 15px rgba(102,126,234,0.2); overflow: hidden; margin-top:20px;">
                    <div style="background: rgba(255,255,255,0.95); margin: 2px; border-radius: 10px; padding: 20px;">
                        <div style="display:flex; align-items:center; margin-bottom:20px;">
                            <div style="background:#667eea; color:#fff; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; margin-right:15px;">🔗</div>
                            <h3 style="margin:0; color:#2d3748; font-size:18px;"><?php esc_html_e( 'Configuração da Evolution API', 'whatsapp-checkout-validation' ); ?></h3>
                        </div>

                        <div style="display:grid; gap:20px;">
                            <div style="background:#f7fafc; padding:15px; border-radius:8px; border-left:4px solid #667eea;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#2d3748;">🌐 <?php esc_html_e( 'URL da API', 'whatsapp-checkout-validation' ); ?></label>
                                <input type="url" name="wcv_api_url" value="<?php echo esc_attr( get_option( 'wcv_api_url', '' ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" placeholder="https://sua-api.exemplo.com" required />
                                <p style="margin:8px 0 0; color:#4a5568; font-size:12px;"><?php esc_html_e( 'URL completa onde a Evolution API está instalada', 'whatsapp-checkout-validation' ); ?></p>
                            </div>
                            <div style="background:#f7fafc; padding:15px; border-radius:8px; border-left:4px solid #667eea;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#2d3748;">🔑 <?php esc_html_e( 'API Key', 'whatsapp-checkout-validation' ); ?></label>
                                <input type="text" name="wcv_api_key" value="<?php echo esc_attr( get_option( 'wcv_api_key', '' ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" placeholder="Sua chave de API" required />
                                <p style="margin:8px 0 0; color:#4a5568; font-size:12px;"><?php esc_html_e( 'Chave de API gerada nas configurações da Evolution API', 'whatsapp-checkout-validation' ); ?></p>
                            </div>
                            <div style="background:#f7fafc; padding:15px; border-radius:8px; border-left:4px solid #667eea;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#2d3748;">📱 <?php esc_html_e( 'Nome da Instância', 'whatsapp-checkout-validation' ); ?></label>
                                <input type="text" name="wcv_instance_name" value="<?php echo esc_attr( get_option( 'wcv_instance_name', '' ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" placeholder="Nome da sua instância" required />
                                <p style="margin:8px 0 0; color:#4a5568; font-size:12px;"><?php esc_html_e( 'Nome da instância criada na Evolution API', 'whatsapp-checkout-validation' ); ?></p>
                            </div>
                        </div>

                        <div style="margin-top:20px; display:flex; gap:10px; align-items:center;">
                            <button type="submit" class="button button-primary" style="background: linear-gradient(135deg,#667eea 0%, #764ba2 100%); border:none; padding:12px 24px; font-size:14px; border-radius:8px; color:#fff; cursor:pointer; box-shadow:0 4px 15px rgba(102,126,234,0.3);">
                                💾 <?php esc_html_e( 'Salvar Configurações', 'whatsapp-checkout-validation' ); ?>
                            </button>
                            <?php if ( $this->is_configured() ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wcv_test_connection' ), 'wcv_test_connection' ) ); ?>" style="background:#4a5568; color:#fff; text-decoration:none; padding:12px 20px; border-radius:8px; font-size:14px;">
                                    🧪 <?php esc_html_e( 'Testar Conexão', 'whatsapp-checkout-validation' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ( $connection_status ) : ?>
                            <div style="margin-top:15px; padding:12px; border-radius:8px; background: <?php echo $connection_status['success'] ? '#d4edda' : '#f8d7da'; ?>; border:1px solid <?php echo $connection_status['success'] ? '#c3e6cb' : '#f5c6cb'; ?>; color: <?php echo $connection_status['success'] ? '#155724' : '#721c24'; ?>;">
                                <span style="font-size:16px; margin-right:8px;"><?php echo $connection_status['success'] ? '✅' : '❌'; ?></span>
                                <?php echo esc_html( $connection_status['message'] ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card: Personalização da Validação (opcional) -->
                <div style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 12px; padding: 0; box-shadow: 0 4px 15px rgba(168,237,234,0.2); overflow: hidden; margin-top:20px;">
                    <div style="background: rgba(255,255,255,0.95); margin: 2px; border-radius: 10px; padding: 20px;">
                        <div style="display:flex; align-items:center; margin-bottom:15px;">
                            <div style="background:#a8edea; color:#2d3748; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; margin-right:15px;">💬</div>
                            <h3 style="margin:0; color:#2d3748; font-size:18px;"><?php esc_html_e( 'Personalização da Validação', 'whatsapp-checkout-validation' ); ?></h3>
                        </div>
                        <div style="display:grid; gap:15px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                                <input type="checkbox" name="wcv_checkout_enabled" value="yes" <?php checked( get_option( 'wcv_checkout_enabled', 'yes' ), 'yes' ); ?> style="transform:scale(1.1);"/>
                                <strong><?php esc_html_e( 'Ativar validação no checkout', 'whatsapp-checkout-validation' ); ?></strong>
                            </label>
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                                <input type="checkbox" name="wcv_checkout_show_modal" value="yes" <?php checked( get_option( 'wcv_checkout_show_modal', 'yes' ), 'yes' ); ?> style="transform:scale(1.1);"/>
                                <strong><?php esc_html_e( 'Exibir modal de confirmação', 'whatsapp-checkout-validation' ); ?></strong>
                            </label>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d3748;">✅ <?php esc_html_e( 'Mensagem de sucesso', 'whatsapp-checkout-validation' ); ?></label>
                                    <input type="text" name="wcv_validation_success_msg" value="<?php echo esc_attr( get_option( 'wcv_validation_success_msg', __( '✓ Número de WhatsApp válido', 'whatsapp-checkout-validation' ) ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" />
                                </div>
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d3748;">⚠️ <?php esc_html_e( 'Mensagem de erro', 'whatsapp-checkout-validation' ); ?></label>
                                    <input type="text" name="wcv_validation_error_msg" value="<?php echo esc_attr( get_option( 'wcv_validation_error_msg', __( '⚠ Este número não possui WhatsApp', 'whatsapp-checkout-validation' ) ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" />
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d3748;">📝 <?php esc_html_e( 'Título do modal', 'whatsapp-checkout-validation' ); ?></label>
                                    <input type="text" name="wcv_modal_title" value="<?php echo esc_attr( get_option( 'wcv_modal_title', __( 'Atenção!', 'whatsapp-checkout-validation' ) ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" />
                                </div>
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d3748;">🔘 <?php esc_html_e( 'Texto do botão do modal', 'whatsapp-checkout-validation' ); ?></label>
                                    <input type="text" name="wcv_modal_button_text" value="<?php echo esc_attr( get_option( 'wcv_modal_button_text', __( 'Prosseguir sem WhatsApp', 'whatsapp-checkout-validation' ) ) ); ?>" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;" />
                                </div>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <?php submit_button( __( 'Salvar Personalizações', 'whatsapp-checkout-validation' ), 'secondary', 'submit', false, array( 'style' => 'padding:10px 16px; border-radius:6px;' ) ); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    private function is_configured() {
        return (bool) ( get_option( 'wcv_api_url' ) && get_option( 'wcv_api_key' ) && get_option( 'wcv_instance_name' ) );
    }

    private function check_connection() {
        $base_url = trim( (string) get_option( 'wcv_api_url', '' ) );
        $api_key  = trim( (string) get_option( 'wcv_api_key', '' ) );
        $instance = trim( (string) get_option( 'wcv_instance_name', '' ) );
        if ( $base_url === '' || $api_key === '' || $instance === '' ) {
            return null;
        }
        $endpoint = rtrim( $base_url, '/' ) . '/instance/connectionState/' . rawurlencode( $instance );
        $response = wp_remote_get( $endpoint, array(
            'timeout' => 15,
            'headers' => array( 'apikey' => $api_key ),
        ) );
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }
        $status = (int) wp_remote_retrieve_response_code( $response );
        $body   = wp_remote_retrieve_body( $response );
        $data   = json_decode( $body, true );
        if ( 200 === $status && JSON_ERROR_NONE === json_last_error() && isset( $data['instance']['state'] ) ) {
            $open = ( $data['instance']['state'] === 'open' );
            return array( 'success' => $open, 'message' => $open ? __( 'Conexão ativa com a Evolution API.', 'whatsapp-checkout-validation' ) : __( 'Instância encontrada, porém desconectada.', 'whatsapp-checkout-validation' ) );
        }
        return array( 'success' => false, 'message' => __( 'Não foi possível validar a conexão.', 'whatsapp-checkout-validation' ) );
    }

    public function handle_test_connection() {
        check_admin_referer( 'wcv_test_connection' );
        $result = $this->check_connection();
        $type   = ( $result && ! empty( $result['success'] ) ) ? 'success' : 'error';
        $redirect = admin_url( 'admin.php?page=whatsapp-checkout-validation&connection=' . $type );
        wp_safe_redirect( $redirect );
        exit;
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