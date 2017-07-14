<?php
/*
Plugin Name: Closte
Plugin URI: https://closte.com
Description: High performance one click implementation of object cache and Google Cloud CDN for Closte cloud platform users.
Version: 2.1.5.1
Author: Closte LLC
Author URI: https://closte.com
*/

defined('ABSPATH') OR exit;


/* constants */
define('CLOSTE_FILE', __FILE__);
define('CLOSTE_DIR', dirname(__FILE__));
define('CLOSTE_BASE', plugin_basename(__FILE__));
define('CLOSTE_MIN_WP', '4.0');
define('IS_CLOSTE', file_exists(dirname(constant('ABSPATH')) . '/conf/cdnurl'));

/* loader */
add_action(
	'plugins_loaded',
	array(
		'Closte',
		'instance'
	)
);


/* uninstall */
register_uninstall_hook(
	__FILE__,
	array(
		'Closte',
		'handle_uninstall_hook'
	)
);


/* activation */
register_activation_hook(
	__FILE__,
	array(
		'Closte',
		'handle_activation_hook'
	)
);


/* deactivation */
register_deactivation_hook(
	__FILE__,
	array(
		'Closte',
		'handle_deactivation_hook'
	)
);


/* autoload init */
spl_autoload_register('Closte_autoload');


 function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

/* autoload funktion */
function Closte_autoload($class) {
	if ( in_array($class, array('Closte', 'Closte_Rewriter', 'Closte_Settings')) ) {
		require_once(
			sprintf(
				'%s/inc/%s.class.php',
				CLOSTE_DIR,
				strtolower($class)
			)
		);
	}


    


     if(startsWith($class,'UAParser')) {

        $class = str_replace('\\', '/', $class);
        $path =    sprintf('%s/inc/%s.php',CLOSTE_DIR,$class);
        require_once($path);
    }

   

   
   

}

?>