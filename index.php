<?php

namespace Taxonomy_Taxi;

if( is_admin() )
	require __DIR__.'/admin.php';

/**
*
*	@param string
*/
function autoload( $class ){
	if( strpos($class, __NAMESPACE__) !== 0 )
		return;

	$file = __DIR__ .'/lib/'. str_replace('\\', '/', $class) . '.php';
	if( file_exists($file) )
		require $file;

}
spl_autoload_register( __NAMESPACE__.'\autoload' );

/**
*	render a page into wherever
*	@param string
*	@param object|array
*/
function render( $_template, $vars = array() ){
	if( file_exists(__DIR__.'/views/'.$_template.'.php') )
		$_template_file = __DIR__.'/views/'.$_template.'.php';
	else
		return "<div>template missing: $_template</div>";
		
	extract( (array) $vars, EXTR_SKIP );
	
	ob_start();
	require $_template_file;
	$html = ob_get_contents();
	ob_end_clean();
	
	return $html;
}

function version(){
	$data = get_plugin_data( __DIR__.'/_plugin.php' );
	return $data['Version'];
}