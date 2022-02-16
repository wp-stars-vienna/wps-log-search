<?php

/**

 * @package WPS Custom Plugins

 */

/*

Plugin Name: WPS Search Logging

Plugin URI: https://wp-stars.com/

Description: Logs search and filter terms in db

Version: 4.1.7

Author: WP Stars, Will Nahmens

Author URI: https://wp-stars.com/

License: GPLv2 or later

Text Domain: wps_log_search

*/

global $wps_log_search_db_version;
$wps_log_search_db_version = '1.1';

function wps_log_search_install() {
	global $wpdb;
	global $wps_log_search_db_version;

	$table_name = $wpdb->prefix . 'wps_search_log';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		search_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		search_page text NOT NULL,
		search_term text NOT NULL,
        num_results int NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'wps_log_search_install_db_version', $wps_log_search_db_version );
}

register_activation_hook( __FILE__, 'wps_log_search_install' );


function wps_log_search_in_database($term, $post_type, $num_results = 0) {

    global $wpdb;
    $table = $wpdb->prefix.'wps_search_log';
    $data = array(
        'search_page' => $post_type,
        'search_term' => $term,
        'num_results' => $num_results
    );

    $wpdb->insert($table,$data);
    $insert_id = $wpdb->insert_id;

    return $insert_id;
	
}

add_action('admin_menu', 'wps_log_search_setup_menu');
 
function wps_log_search_setup_menu(){
    add_menu_page( 'WPS Log Search', 'WPS Log Search', 'manage_options', 'wps_log_search', 'wps_log_search_init' );
}
 
function wps_log_search_init(){
    ?>

    <h1>WPS Search Log</h1>
    <form method="post" id="download_form" action="">
		 	<input type="datetime-local" id="start-time"
                name="start_time" value=""
                min="" max="">
            <input type="datetime-local" id="end-time"
                name="end_time" value=""
                min="" max="">
            <input type="submit" name="download_csv" class="button-primary" value="Download Search Log" />
    </form>
    <?php
}

add_action('admin_init', 'wps_log_search_download');
function wps_log_search_download() {
    global $plugin_page;
    if (isset($_POST['download_csv']) && $plugin_page == 'wps_log_search') {

        global $wpdb;
		
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
    
        $sql = "SELECT * FROM {$wpdb->prefix}wps_search_log WHERE search_time >= '$start_time' and search_time <= '$end_time'";
    
        $rows = $wpdb->get_results($sql, 'ARRAY_A');
    
        if ($rows) {
    
            $csv_fields = array();
            $csv_fields[] = 'id';
            $csv_fields[] = 'search_time';
            $csv_fields[] = 'search_page';
            $csv_fields[] = 'search_term';
            $csv_fields[] = 'num_results';
    
            $current_time = date("Y-m-d-h:i:s");
            $output_filename = "wps_search_$current_time.csv";
            $output_handle = @fopen('php://output', 'w');
    
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-type: text/csv');
            header('Content-Disposition: attachment; filename=' . 
            $output_filename);
            header('Expires: 0');
            header('Pragma: public');
    
            $first = true;
           // Parse results to csv format
            foreach ($rows as $row) {
                // Add table headers
                if ($first) {
                   $titles = array();
                    foreach ($row as $key => $val) {
                        $titles[] = $key;
                    }
                    fputcsv($output_handle, $titles);
                    $first = false;
                }
                $leadArray = (array) $row; // Cast the Object to an array
                // Add row to file
                fputcsv($output_handle, $leadArray);
            }
            //echo '<a href="'.$output_handle.'">test</a>';
            // Close output file stream
            fclose($output_handle);
            die();
        }
    }
}
 