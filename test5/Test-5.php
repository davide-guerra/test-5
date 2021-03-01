<?php
/*
Plugin Name: Test 5
Description: This plugin generates a random WC product every 30 minutes.
Author: Davide Guerra
Version: 0.0.1
*/
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
 
// Create a custom 30 minutes schedule
function schedule_every_thirty_minutes( $schedules ) { 
    $schedules['every_thirty_minutes'] = array(
            'interval'  => 1800,
            'display'   => __( 'Every 30 Minutes', 'textdomain' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'schedule_every_thirty_minutes' );

// Create random products every 30 minutes
function create_random_products() {
	
	// Get long and short descriptions (uses Loripsum API)
	$long_description = file_get_contents('https://loripsum.net/api/3/medium/plaintext');
	$short_description = file_get_contents('https://loripsum.net/api/1/short/plaintext');

	// Get a random price and a random quantity;
	$price = mt_rand(500, 10000);
	$quantity = mt_rand(1, 15);
	
	// Get a list of available brands and colors terms
	$attribute_taxonomies = wc_get_attribute_taxonomies();
	$taxonomy_terms = array();
	if ($attribute_taxonomies) {
		foreach ($attribute_taxonomies as $tax){
			if (taxonomy_exists(wc_attribute_taxonomy_name($tax->attribute_name))){
				$taxonomy_terms[$tax->attribute_name] = get_terms(wc_attribute_taxonomy_name($tax->attribute_name), array('hide_empty' => false));
			}		
		}
	}
	$list_of_brands = $taxonomy_terms['brands'];
	$list_of_colors = $taxonomy_terms['colors'];
	
	// Randomly get a brand and a color
	$random_brand = $list_of_brands[array_rand($list_of_brands)]->name;
	$random_color = $list_of_colors[array_rand($list_of_colors)]->name;
	
	// Create a new product
	$product = new WC_Product();
	$product->set_name(uniqid('Product ', true));
	$product->set_description($long_description);
	$product->set_short_description($short_description);
	$product->set_regular_price($price);
	$product->set_sku(uniqid('SKU_'));
	$product->set_manage_stock(true);
	$product->set_stock_quantity($quantity);
	$product->set_weight(1);
	$product_id = $product->save();
	
	// Add brands and colors attribute to this product.
	$attributes_array = array(
		'pa_brands' => array(
			'name' => 'pa_brands',
			'value' => '',
			'is_visible' => '1',
			'is_taxonomy' => '1'
		),
		'pa_colors' => array(
			'name' => 'pa_colors',
			'value' => '',
			'is_visible' => '1',
			'is_taxonomy' => '1'
		)
	);
	update_post_meta($product_id, '_product_attributes', $attributes_array);

	// Set a random value to brands and colors attribute
	wp_set_object_terms($product_id, $random_brand, 'pa_brands' , true);
	wp_set_object_terms($product_id, $random_color, 'pa_colors', true);
	
	// Loop through all products in stock and select a random one
	$args = array(
		'posts_per_page'   => 1,
		'orderby'          => 'rand',
		'post_type'        => 'product',
		'tax_query' => array( array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array('outofstock'),
			'operator' => 'NOT IN'
		) ),
	); 
	
	$product_find = new WP_Query( $args );
	if ( $product_find->have_posts() ) {
		$random_product_ID = $product_find->post->ID;
	} 
	wp_reset_postdata();

	// Edit quantity of this randomly choosed product
	$random_product = new WC_Product($random_product_ID);
	$original_quantity = $random_product->get_stock_quantity($random_product_ID);
	$operator = array('+', '-');
	$random_operator = $operator[rand(0, 1)];
	switch ($random_operator) {
		case "+":
			$final_quantity = $original_quantity + 1;
			break;
		case "-":
			$final_quantity = $original_quantity - 1;
			break;
	}
	$random_product->set_stock_quantity($final_quantity);
	
	// If quantity is 0 set the product as out of stock.
	if($final_quantity == 0) {
		$random_product->set_stock_status('outofstock');
	}
	$random_product->save();

}
add_action('create_random_products', 'create_random_products');	
if ( ! wp_next_scheduled( 'create_random_products' ) ) {
    wp_schedule_event( time(), 'every_thirty_minutes', 'create_random_products' );
}