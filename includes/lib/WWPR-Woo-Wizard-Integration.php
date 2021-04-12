<?php
/*
 * Woocommerce Product Wizard integration file.
 *
 * @package Woo Wizard Product Registration/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWPR_Woo_Wizard_Integration {

    public function init(){
        add_action( 'setup_theme', [$this, 'get_cart_hash_method'] );
    }
    
    public function get_cart_hash_method() {
        if ( class_exists('WCProductsWizard\Cart') ) {
            $WWPR_Cart = new WCProductsWizard\Cart;
            $WWPR_Storage = new WCProductsWizard\Storage;
            //$session = $WWPR_Storage->getSession('wcpw');
            //var_dump($session);
        }
    }

    public function dump_cart($WWPRCart){
        $WWPRCart->get(2786, $args = []);
    }
}

$WWPR_Integration = new WWPR_Woo_Wizard_Integration;
$WWPR_Integration->init();

