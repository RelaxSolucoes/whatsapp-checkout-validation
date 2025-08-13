<?php
/**
 * Uninstall cleanup
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = array(
	'wcv_api_url',
	'wcv_api_key',
	'wcv_instance_name',
	'wcv_nonwhatsapp_msg',
	'wcv_instance_validated',
	'wcv_intl_prefix',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

