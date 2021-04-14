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

if (!defined('ABSPATH')) {
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
function WWPR()
{
    $instance = WWPR::instance(__FILE__, '1.0.0');

    if (is_null($instance->settings)) {
        $instance->settings = WWPR_Settings::instance($instance);
    }

    return $instance;
}

WWPR();




function add_meta_boxes()
{
    add_meta_box(
        'woocommerce-product-registration',
        __('Warranty Registration Serial #'),
        'order_my_custom',
        'shop_order',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'add_meta_boxes');


function order_my_custom()
{
    global $post;
    $order = wc_get_order($post->ID);

    if (!get_post_meta($post->ID, 'registration_serial', true)) {
        $serialExists = false;
        $noteBar = 'There is no serial registered yet for this order.';
    } else {
        $serialExists = true;
        $noteBar = 'Serial was registered for ' . get_post_meta($order->get_id(), 'customer_first_name', true) . ' on: <span class="date-registered">' . get_post_meta($order->get_id(), 'registration_date', true) . '</span> at:  <span class="time-registered">' . get_post_meta($order->get_id(), 'registration_time', true) . '</span>';
    }
    echo '<input type="text" data-serialExists="' . $serialExists . '" name="doors" id="serial-input" value="' . get_post_meta($order->get_id(), 'registration_serial', true) . '"/>';
    echo '<button type="submit" id="save-serial"/>Register Serial</button>';
    echo '<p class="note-bar">' . $noteBar . '</p>';
    echo '<hidden id="theDate" value="' . get_post_meta($order->get_id(), 'registration_date', true) . '"></hidden>';
    echo '<div class="edit-date">Edit Date</div>';
    echo '<div style="display:none" class="edit-date-time"><input autocomplete="off" type="text" id="datepicker"></div>';
}


add_action('wp_ajax_example_ajax_request', 'example_ajax_request');
add_action('wp_ajax_orderSerialValidity', 'orderSerialValidity');






function example_ajax_request()
{
    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        $serial = $_REQUEST['serial'];
        $postID = $_REQUEST['postID'];

        $order = wc_get_order($postID);
        $oldserial = get_post_meta($postID, 'registration_serial', true);

        update_post_meta($postID, 'registration_time', $_REQUEST['time']);
        update_post_meta($postID, 'registration_date', $_REQUEST['date']);

        if ($oldserial) {
            if ($serial != $oldserial) {
                update_post_meta($postID, 'registration_serial', $serial);
                $note = 'Warranty Serial Changed: ' . $oldserial . ' has been changed to ' . $serial . ' and registered for ' . get_post_meta($order->get_id(), 'customer_first_name', true) . ' ' . get_post_meta($order->get_id(), 'customer_last_name', true);
                $order->add_order_note($note);
            }
        } else {
            update_post_meta($postID, 'registration_serial', $serial);
            $note = 'Warranty Serial: ' . $serial . ' has been registered for ' . get_post_meta($order->get_id(), 'customer_first_name', true) . ' ' . get_post_meta($order->get_id(), 'customer_last_name', true);
            $order->add_order_note($note);
        }
    }
    // Always die in functions echoing ajax content
    die();
}


function orderSerialValidity()
{
    if (isset($_REQUEST)) {
        $serial = $_REQUEST['serial'];

        $args = array(
            'meta_key' => 'registration_serial',
            'meta_value' => 'DXZ0065',
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        $posts = new WP_Query($args);
        
        foreach($posts as $post){
            $order = wc_get_order( $post->ID );
            var_dump($order);
        }
    
        // kick back results ##
        die();
    }
}


add_action('init', 'my_init');
function my_init(){
    $args = array(
        'meta_key' => 'registration_serial',
        'meta_value' => 'dawdwaddd',
        'post_status' => 'any',
        'posts_per_page' => -1
    );
    $posts = new WP_Query($args);
    
    foreach($posts as $post){
        $order = wc_get_order( $post->ID );
        var_dump($post->ID );
    }

}


