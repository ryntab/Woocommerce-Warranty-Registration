<?php
/**
 * Plugin Name: Woo Wizard Product Registration
 * Version: 1.0.0
 * Plugin URI: http://www.gravityparamotors.com
 * Description: An integration between Formidable Pro, Woocommerce and last but not least Woocommerce Product Wizard. Oh boy....
 * Author: Ryan Taber
 * Author URI: http://www.gravityparamotors.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: wordpress-plugin-template
 * Domain Path: /lang/
 *
 * @package WordPress / Woocommerce / Formidable / Woocommerce Product Wizard
 * @author Ryan Taber
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



// Load plugin class files.
require_once 'includes/WWPR-template.php';
require_once 'includes/WWPR-template-settings.php';

// Load plugin libraries.
require_once 'includes/lib/WWPR-template-admin-api.php';
require_once 'includes/lib/WWPR-template-post-type.php';
require_once 'includes/lib/WWPR-template-taxonomy.php';
require_once 'includes/lib/WWPR-Woo-Wizard-Integration.php';


/**
 * Returns the main instance of WWPR to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WWPR
 */
function WWPR() {
	$instance = WWPR::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WWPR_Settings::instance( $instance );
	}

	return $instance;
}

WWPR();