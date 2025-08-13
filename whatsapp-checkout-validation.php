<?php
/**
 * Plugin Name: Validação de WhatsApp no Checkout
 * Plugin URI: https://relaxsolucoes.com.br/
 * Description: Verifica em tempo real se o número de telefone no checkout possui WhatsApp.
 * Version: 1.1.0
 * Author: Relax Soluções
 * Author URI: https://relaxsolucoes.com.br/
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.9
 * Text Domain: whatsapp-checkout-validation
 * Domain Path: /languages
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