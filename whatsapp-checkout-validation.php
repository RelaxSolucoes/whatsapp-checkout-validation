<?php
/**
 * Plugin Name: WP Checkout Validation
 * Plugin URI: https://github.com/RelaxSolucoes/whatsapp-checkout-validation
 * Description: Verifica em tempo real se o número de telefone no checkout possui WhatsApp.
 * Version: 1.1.0
 * Author: Relax Soluções
 * Author URI: https://relaxsolucoes.online/
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.9
 * Domain Path: /languages
 * GitHub Plugin URI: RelaxSolucoes/whatsapp-checkout-validation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Versão do plugin
if ( ! defined( 'WCV_PLUGIN_VERSION' ) ) {
    define( 'WCV_PLUGIN_VERSION', '1.1.0' );
}
// Diretório do plugin
if ( ! defined( 'WCV_PLUGIN_DIR' ) ) {
    define( 'WCV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
// URL do plugin
if ( ! defined( 'WCV_PLUGIN_URL' ) ) {
    define( 'WCV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Carrega classes principais
require_once WCV_PLUGIN_DIR . 'includes/class-wcv-validator.php';
require_once WCV_PLUGIN_DIR . 'includes/admin/class-wcv-admin-settings.php';

/**
 * Carrega arquivos de tradução.
 */
function wcv_load_textdomain() {
    load_plugin_textdomain(
        'whatsapp-checkout-validation',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'wcv_load_textdomain' );

/**
 * Inicializa o plugin.
 */
function wcv_init() {
    WCV_Validator::get_instance();
    WCV_Admin_Settings::get_instance();
}
add_action( 'plugins_loaded', 'wcv_init' ); 

/**
 * Verifica dependências na ativação.
 */
function wcv_activate() {
    // WooCommerce ativo?
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', (array) get_option( 'active_plugins', array() ) ), true ) &&
        ! ( function_exists( 'is_multisite' ) && is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', (array) get_site_option( 'active_sitewide_plugins', array() ) ) )
    ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'Este plugin requer WooCommerce ativo.', 'whatsapp-checkout-validation' ) );
    }

    // Opções padrão
    if ( get_option( 'wcv_intl_prefix', '' ) === '' ) {
        update_option( 'wcv_intl_prefix', '55' );
    }
    if ( ! get_option( 'wcv_cache_salt' ) ) {
        update_option( 'wcv_cache_salt', (string) time() );
    }
}
register_activation_hook( __FILE__, 'wcv_activate' );

/**
 * Link rápido para configurações na lista de plugins.
 */
function wcv_plugin_action_links( $links ) {
    $settings_url = admin_url( 'admin.php?page=whatsapp-checkout-validation' );
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configurações', 'whatsapp-checkout-validation' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcv_plugin_action_links' );

/**
 * Clean up on uninstall via uninstall.php
 * Kept empty here to document the uninstall flow.
 */

/**
 * ===== AUTO-UPDATE VIA GITHUB (plugin-update-checker) =====
 */
function wcv_init_auto_updater() {
	// Define o repositório GitHub deste plugin (org/repo)
	if ( ! defined( 'WCV_GITHUB_REPO' ) ) {
		define( 'WCV_GITHUB_REPO', 'RelaxSolucoes/whatsapp-checkout-validation' );
	}

	// Carrega a lib somente se ainda não tiver sido carregada por outro plugin
	if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
		// Usa a cópia local
		$local_lib = WCV_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
		if ( file_exists( $local_lib ) ) {
			require_once $local_lib;
		}
	}

	if ( class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
		$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/' . WCV_GITHUB_REPO,
			__FILE__,
			'whatsapp-checkout-validation'
		);
		// Define a branch padrão utilizada para releases
		if ( method_exists( $updateChecker, 'setBranch' ) ) {
			$updateChecker->setBranch( 'main' );
		}

		// Opcional: checagem manual via hook admin
		// add_action( 'admin_init', function() use ( $updateChecker ) { $updateChecker->checkForUpdates(); } );
	}
}
add_action( 'init', 'wcv_init_auto_updater' );
/**
 * ===== FIM AUTO-UPDATE =====
 */

/**
 * Declara compatibilidade com HPOS e Cart/Checkout Blocks (WooCommerce)
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * Admin notice quando faltam configurações obrigatórias.
 */
function wcv_admin_missing_config_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    // Mostra em telas de plugins e configurações
    if ( $screen && ! in_array( $screen->id, array( 'plugins', 'settings_page_whatsapp-checkout-validation' ), true ) ) {
        // Ainda assim exibe globalmente para garantir visibilidade
    }
    $api_url  = get_option( 'wcv_api_url', '' );
    $api_key  = get_option( 'wcv_api_key', '' );
    $instance = get_option( 'wcv_instance_name', '' );
    if ( empty( $api_url ) || empty( $api_key ) || empty( $instance ) ) {
        $settings_url = admin_url( 'admin.php?page=whatsapp-checkout-validation' );
        echo '<div class="notice notice-warning"><p>' . wp_kses_post( sprintf( __( 'Validação WhatsApp: configure a URL, Chave e Instância da API para habilitar a validação. <a href="%s">Abrir configurações</a>.', 'whatsapp-checkout-validation' ), esc_url( $settings_url ) ) ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'wcv_admin_missing_config_notice' );

/**
 * Ao atualizar configurações críticas, rotaciona o salt do cache.
 */
function wcv_bump_cache_salt() {
    update_option( 'wcv_cache_salt', (string) time() );
}
add_action( 'update_option_wcv_api_url', 'wcv_bump_cache_salt' );
add_action( 'update_option_wcv_api_key', 'wcv_bump_cache_salt' );
add_action( 'update_option_wcv_instance_name', 'wcv_bump_cache_salt' );
add_action( 'update_option_wcv_intl_prefix', 'wcv_bump_cache_salt' );