<?php 

/* 
Plugin Name: Plugin para administração do banco de dados
Plugin URI: http://www.gravityforms.com
Description: Plugin responsável pela administração com o banco de dados.
Version: 1.0
Author: Allan Roberto
Author URI: http://www.artezzo.com.br
*/


add_action('init', 'database_init');
function database_init() {
	wp_enqueue_script( 'custom-js', plugin_dir_url( __FILE__ )."js/custom.js",array('jquery'), '1.0');
	wp_localize_script( 'custom-js', 'database', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_ajax_nopriv_search_cep_ajax', 'search_cep_ajax' );
add_action( 'wp_ajax_search_cep_ajax', 'search_cep_ajax' );

function search_cep_ajax() {
	$cep = $_REQUEST['cep'];

	$webservice_url     = 'http://webservice.uni5.net/web_cep.php';
	$webservice_query    = array(
    
	    'auth'    => 'e916b34f53ad43206d7eca43edcac89b', 
	    'formato' => 'query_string', 
	    'cep'     => $cep 
	
	);

	$webservice_url .= '?';
	
	foreach($webservice_query as $get_key => $get_value){
	    $webservice_url .= $get_key.'='.urlencode($get_value).'&';
	}

	parse_str(file_get_contents($webservice_url), $resultado);

	switch($resultado['resultado']){  
	    case '2': 

	    	$output = array(
	    		"cidade" =>  $resultado['cidade'],
	    		"estado" => $resultado['uf'],
	    		"tipo_logradouro" => "Indisponível",
	    		"logradouro"      => "Indisponível",
	    		"bairro"          => "Indisponível",
	    		"status"          => 1
	    		);    
	    break;  
	      
	    case '1':  
	    	$output = array(
	    		"cidade"          =>  $resultado['cidade'],
	    		"estado"          => $resultado['uf'],
	    		"tipo_logradouro" => $resultado['tipo_logradouro'],
	    		"logradouro"      => $resultado['logradouro'],
	    		"bairro"          => $resultado['bairro'],
	    		"status"          => 1
	    		); 
	    break;  
	      
	    default:  
	        $output = array(
	    		"status"          => 0
	    		); 
	    break;  
	}

	echo json_encode($output);
	wp_die();
}