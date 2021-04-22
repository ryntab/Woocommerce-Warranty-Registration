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

//Admin: Metabox for serial display
add_action('add_meta_boxes', 'add_meta_boxes');
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

//Admin: Metabox content for serial display
function order_my_custom()
{
    global $wpdb;
    global $post;
    $order = wc_get_order($post->ID);
    $id =  $order->get_id();
    $firstName = get_post_meta($id, 'customer_first_name', true);

    $user_warranty = $wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_user_warranties` WHERE `order_id` = '$id'"));
    if ($user_warranty) {
        $registrationDate = $user_warranty[0]->registered_at;
        $registrationTime = $user_warranty[0]->registered_at;
        $claimedDate = $user_warranty[0]->registered_at;
        $claimedTime = $user_warranty[0]->registered_at;
        $registrationSerial = $user_warranty[0]->order_serial;
    }

    var_dump($registrationSerial);

    if (!$registrationSerial) {
        $serialExists = false;
        $noteBar = 'There is no serial registered yet for this order.';
    } else {
        $serialExists = true;
        $noteBar = 'Serial was registered for ' . $firstName .
            ' on: <span class="date-registered">' . $registrationDate .
            '</span> at:  <span class="time-registered">' .  $registrationTime . '</span>';
    }
    echo '<input type="text" data-serialExists="' . $serialExists . '" name="doors" id="serial-input" value="' . $registrationSerial . '"/>';
    echo '<button type="submit" id="save-serial"/>Register Serial</button>';
    echo '<p class="note-bar">' . $noteBar . '</p>';
    echo '<hidden id="theDate" value="' . $registrationDate . '"></hidden>';
    echo '<div class="edit-date">Edit Date</div>';
    echo '<div style="display:none" class="edit-date-time"><input autocomplete="off" type="text" id="datepicker"></div>';
    if ($claimedTime) {
        echo '<p class="note-bar">Warranty claimed by ' . $firstName . ' on: <span class="date-registered">' . $claimedDate . '</span> at:  <span class="time-registered">' . $claimedTime . '</span></p>';
    }
}

//Admin: Update customer warranty database.
function admin_set_serial_data()
{
    if (isset($_REQUEST)) {
        global $wpdb;
        $serial = strVal($_REQUEST['serial']);
        $orderID = $_REQUEST['postID'];

        $order = wc_get_order($orderID);
        $warranty_data = $wpdb->get_results("SELECT order_serial FROM wp_user_warranties WHERE order_id = '$orderID'");
        $oldserial = $warranty_data[0]->order_serial;

        if ($oldserial) {
            if ($serial != $oldserial) {
                $wpdb->update('wp_user_warranties', array('order_serial'=> $serial), array('order_id'=> $orderID) );
                $note = 'Warranty Serial Changed: ' . $oldserial . ' has been changed to ' . $serial . ' and registered for ' . get_post_meta($order->get_id(), 'customer_first_name', true) . ' ' . get_post_meta($order->get_id(), 'customer_last_name', true);
                $order->add_order_note($note);
            }
        } else {
            $wpdb->insert('wp_user_warranties', array('customer_id' => Null, 'order_id' => $orderID, 'order_serial' => $serial, 'registered_at' => gmdate('Y-m-d H:i:s'), 'claimed_at' => Null,
            ));
            $note = 'Warranty Serial: ' . $serial . ' has been registered for ' . get_post_meta($order->get_id(), 'customer_first_name', true) . ' ' . get_post_meta($order->get_id(), 'customer_last_name', true);
            $order->add_order_note($note);
        }
    }
    die();
}

//Admin: Return true if serial does not exist
function verify_serial()
{
    global $wpdb;
    $serial = $_REQUEST['keyword'];
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_user_warranties` WHERE `order_serial` = '$serial'"));
    (count($results) != 0) ? wp_send_json(true) : wp_send_json(false);
    die();
}

//Frontend: Checks if submitted serial number exists in the database, returns order number on success
function orderSerialValidity()
{
    if (isset($_REQUEST)) {
        global $wpdb;
        $serial = $_REQUEST['serial'];
        $results = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'registration_serial' AND meta_value='$serial'");

        if ($results[0]->post_id != 0) {
            $data['valid'] = true;
            $data['id'] = json_encode(intval($results[0]->post_id));
        } else {
            $data['valid'] = false;
        }
        wp_send_json($data);
    }
}

//Frontend: Get the products in an order by the order number
function orderGetParts()
{
    $orderID = $_REQUEST['orderID'];
    $orderSerial = strval($_REQUEST['orderSerial']);
    $customerID = $_REQUEST['customerID'];
    $order = wc_get_order($orderID);
    $customersProducts = array();

    //Claim Warranty
    date_default_timezone_set('America/Chicago');

    update_post_meta($orderID, 'warranty_claimed', true);
    update_post_meta($orderID, 'claimed_date',  date('d-m-y'));
    update_post_meta($orderID, 'claimed_time',  date("h:i:s"));


    foreach ($order->get_items() as $item_key => $item) :
        $item_data    = $item->get_data();
        $product_name = $item_data['name'];
        $quantity     = $item_data['quantity'];
        $product = $item->get_product(); // Get the WC_Product object
        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'small');

        $warranty = get_post_meta($item_data['product_id'], '_warranty');

        $data['item']['product_name'] =  $product_name;
        $data['item']['quantity'] =   $quantity;
        $data['item']['image'] = $image_url;
        $data['item']['warranty'] = $warranty[0];
        array_push($customersProducts, $data);
    endforeach;


    global $wpdb;
    $wpdb->insert('wp_user_warranties', array(
        'customer_id' => $customerID,
        'order_id' => $orderID,
        'order_serial' => $orderSerial,
        'registered_at' => gmdate('Y-m-d H:i:s'),
        'claimed_at' => gmdate('Y-m-d H:i:s'),
    ));
    wp_send_json($customersProducts, true);
}


function warranty_get_product_warranty($product_id)
{
    $product = wc_get_product($product_id);

    if ($product && $product->is_type('variation')) {
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            $parent_product_id = $product->parent->id;
        } else {
            $parent_product_id = $product->get_parent_id();
        }

        if ('parent' == get_post_meta($parent_product_id, '_warranty_control', true)) {
            $warranty = get_post_meta($parent_product_id, '_warranty', true);
        } else {
            $warranty = get_post_meta($product_id, '_warranty', true);
        }
    } else {
        $warranty = get_post_meta($product_id, '_warranty', true);
    }

    if (!$warranty) {
        $category_warranties = get_option('wc_warranty_categories', array());
        $categories = wp_get_object_terms($product_id, 'product_cat');

        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {

                if (!empty($category_warranties[$category->term_id])) {
                    $warranty = $category_warranties[$category->term_id];
                    break;
                }
            }
        }
    }

    if (!$warranty) {
        $warranty   = warranty_get_default_warranty();
    }

    if (empty($warranty)) {
        $warranty = array(
            'type'  => 'no_warranty'
        );
    }

    if (empty($warranty['label'])) {
        $warranty['label'] = get_post_meta($product_id, '_warranty_label', true);
    }

    return apply_filters('get_product_warranty', $warranty, $product_id);
}

function warranty_get_default_warranty()
{
    $warranty = array(
        'type'              => get_option('warranty_default_type', 'no_warranty'),
        'label'             => get_option('warranty_default_label', ''),
        'length'            => get_option('warranty_default_length', 'lifetime'),
        'value'             => get_option('warranty_default_length_value', 0),
        'duration'          => get_option('warranty_default_length_duration', 'days'),
        'no_warranty_option' => get_option('warranty_default_addon_no_warranty', 'no'),
        'addons'            => get_option('warranty_default_addons', array()),
        'default'           => true
    );

    return apply_filters('get_default_warranty', $warranty);
}

add_action('wp_ajax_orderSerialValidity', 'orderSerialValidity');
add_action('wp_ajax_nopriv_orderSerialValidity', 'orderSerialValidity');
add_action('wp_ajax_admin_set_serial_data', 'admin_set_serial_data');
add_action('wp_ajax_nopriv_orderGetParts', 'orderGetParts');
add_action('wp_ajax_orderGetParts', 'orderGetParts');
add_action('wp_ajax_verify_serial', 'verify_serial');
add_action('wp_ajax_nopriv_verify_serial', 'verify_serial');