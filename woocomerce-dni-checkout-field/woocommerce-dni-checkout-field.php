<?php
/*
Plugin Name: WooCommerce DNI Checkout Field by Nico Demarchi
Plugin URI: https://github.com/nicodemarchi/woocommerce-dni-checkout-field
Description: Añade un campo DNI en el checkout y la dirección de facturación de WooCommerce, y elimina los campos de Empresa y Número de IVA.
Version: 1.8
Author: Nico Demarchi
Author URI: https://github.com/nicodemarchi
License: GPLv2 or later
Text Domain: woocommerce-dni-checkout-field
*/

// Asegúrate de que el archivo no se acceda directamente
if (!defined('ABSPATH')) {
    exit;
}

// Añadir y ajustar campo DNI, eliminar Empresa y Número de IVA en checkout
add_filter('woocommerce_billing_fields', 'nd_adjust_billing_fields');

function nd_adjust_billing_fields($fields) {
    // Eliminar campos de Empresa y Número de IVA
    if (isset($fields['billing_company'])) {
        unset($fields['billing_company']);
    }
    if (isset($fields['billing_vat'])) {
        unset($fields['billing_vat']);
    }
    
    // Añadir campo DNI
    $fields['billing_dni'] = array(
        'type'        => 'text',
        'label'       => __('DNI', 'woocommerce-dni-checkout-field'),
        'placeholder' => _x('Tu DNI', 'placeholder', 'woocommerce-dni-checkout-field'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 25, // Prioridad para colocar debajo de nombre y apellidos
    );

    return $fields;
}

// Validar el campo DNI
add_action('woocommerce_checkout_process', 'nd_validate_dni_field');

function nd_validate_dni_field() {
    if (!$_POST['billing_dni']) {
        wc_add_notice(__('Por favor, ingresa tu DNI.', 'woocommerce-dni-checkout-field'), 'error');
    }
}

// Guardar el campo DNI en los datos del pedido
add_action('woocommerce_checkout_update_order_meta', 'nd_save_dni_field');

function nd_save_dni_field($order_id) {
    if (!empty($_POST['billing_dni'])) {
        update_post_meta($order_id, '_billing_dni', sanitize_text_field($_POST['billing_dni']));
    }
}

// Eliminar la visualización duplicada del DNI en el admin de WooCommerce
add_filter('woocommerce_order_get_formatted_billing_address', 'nd_remove_duplicate_dni_from_billing', 10, 2);

function nd_remove_duplicate_dni_from_billing($address, $order) {
    // Elimina el DNI duplicado del formato de dirección de facturación
    $address = preg_replace('/<br \/>DNI:.*/', '', $address);
    return $address;
}

// Mostrar el DNI en el admin de WooCommerce en el campo editable
add_action('woocommerce_admin_order_data_after_billing_address', 'nd_display_editable_dni_in_admin_order_meta', 10, 1);

function nd_display_editable_dni_in_admin_order_meta($order) {
    woocommerce_wp_text_input(array(
        'id' => '_billing_dni',
        'label' => __('DNI', 'woocommerce'),
        'value' => get_post_meta($order->get_id(), '_billing_dni', true),
    ));
}

// Guardar el DNI en la edición del pedido en el admin
add_action('woocommerce_process_shop_order_meta', 'nd_save_editable_dni_in_admin_order_meta', 10, 1);

function nd_save_editable_dni_in_admin_order_meta($order_id) {
    if (isset($_POST['_billing_dni'])) {
        update_post_meta($order_id, '_billing_dni', sanitize_text_field($_POST['_billing_dni']));
    }
}

// Incluir el DNI en los correos electrónicos
add_filter('woocommerce_email_order_meta_fields', 'nd_dni_email_order_meta_fields', 10, 3);

function nd_dni_email_order_meta_fields($fields, $sent_to_admin, $order) {
    $fields['dni'] = array(
        'label' => __('DNI'),
        'value' => get_post_meta($order->get_id(), '_billing_dni', true),
    );
    return $fields;
}

// Añadir DNI a la dirección de facturación y permitir edición en "Mi cuenta"
add_filter('woocommerce_customer_meta_fields', 'nd_add_dni_to_account_billing_fields');

function nd_add_dni_to_account_billing_fields($fields) {
    // Eliminar campos de Empresa y Número de IVA en "Mi cuenta"
    if (isset($fields['billing']['fields']['billing_company'])) {
        unset($fields['billing']['fields']['billing_company']);
    }
    if (isset($fields['billing']['fields']['billing_vat'])) {
        unset($fields['billing']['fields']['billing_vat']);
    }
    
    // Añadir campo DNI
    $fields['billing']['fields']['billing_dni'] = array(
        'label'       => __('DNI', 'woocommerce-dni-checkout-field'),
        'description' => '',
        'required'    => true,
        'class'       => 'form-row-wide',
        'show'        => true,
    );
    return $fields;
}

// Guardar DNI en la página de "Mi cuenta"
add_action('woocommerce_customer_save_address', 'nd_save_dni_billing_address', 10, 2);

function nd_save_dni_billing_address($customer_id, $load_address) {
    if ($load_address === 'billing') {
        if (isset($_POST['billing_dni'])) {
            update_user_meta($customer_id, 'billing_dni', sanitize_text_field($_POST['billing_dni']));
        }
    }
}

// Mostrar el DNI en la vista de dirección de facturación en el panel de usuario
add_filter('woocommerce_billing_fields', 'nd_display_dni_billing_field_in_user_account');

function nd_display_dni_billing_field_in_user_account($fields) {
    // Eliminar campos de Empresa y Número de IVA en vista de dirección
    if (isset($fields['billing_company'])) {
        unset($fields['billing_company']);
    }
    if (isset($fields['billing_vat'])) {
        unset($fields['billing_vat']);
    }
    
    // Añadir campo DNI
    $fields['billing_dni'] = array(
        'label'       => __('DNI', 'woocommerce-dni-checkout-field'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 25,
    );
    return $fields;
}
