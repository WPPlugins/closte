<?php


use UAParser\Parser;

class Closte {


    private static $static_options;
    private static $static_blogid;
    private static $static_siteurl;
    private static $log_path = '';
    private static $debug_enabled = false;

    private static $static_uaparser = null;
   
    public static function instance() {
        new self();
    }


    public static function GetUaParser(){
        
        if(self::$static_uaparser == null){
            self::$static_uaparser = Parser::create(CLOSTE_DIR . '/inc/UAParser/regexes.php');
        }

        return self::$static_uaparser;
    }
   

    public function __construct() {


        $options = self::get_options();       
        self::$static_options = $options;     
        self::$static_blogid = get_current_blog_id();
        self::$static_siteurl = get_site_url( self::$static_blogid);
        self::$debug_enabled = $options["enable_debug"] == 1;


        $theme_root = get_theme_root();
		$content_dir = dirname($theme_root);
        self::$log_path = $content_dir . '/closte_debug.log';


        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) OR ( defined('DOING_CRON') && DOING_CRON) OR ( defined('DOING_AJAX') && DOING_AJAX) OR ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)) {
            //return;
        }    
       
        

        /* admin notices */
        add_action(
                'after_setup_theme', array(
            __CLASS__,
            'closte_autologin'
                )
        );

        /* Hooks */
        add_action(
                'admin_init', array(
            __CLASS__,
            'register_textdomain'
                )
        );
        add_action(
                'admin_init', array(
            'Closte_Settings',
            'register_settings'
                )
        );
        add_action(
                'admin_menu', array(
            'Closte_Settings',
            'add_settings_page'
                )
        );
        add_filter(
                'plugin_action_links_' . CLOSTE_BASE, array(
            __CLASS__,
            'add_action_link'
                )
        );

        /* admin notices */
        add_action(
                'all_admin_notices', array(
            __CLASS__,
            'closte_requirements_check'
                )
        );

        if ($options['remove_query_string']) {
            add_filter('script_loader_src', array(&$this, 'handle_script_style_loader_query_string'), 15, 1);
            add_filter('style_loader_src', array(&$this, 'handle_script_style_loader_query_string'), 15, 1);
        }

        if ($options['enable_cdn']) {
            add_filter('script_loader_src', array(&$this, 'handle_script_style_loader_cdn'), 20, 1);
            add_filter('style_loader_src', array(&$this, 'handle_script_style_loader_cdn'), 20, 1);

            add_action(
             'template_redirect', array(
         __CLASS__,
         'handle_rewrite_hook'
             )
     );
        }

        if ($options['bot_404_empty_response']) {        
            add_action( 'template_redirect',array(&$this, 'handle_bot_bandwidth') );
        }

     
    }

    function handle_script_style_loader_query_string($src) {
          

        $parts = explode('?ver', $src);
        self::debug_log('in:' . $src .' out:'.$parts[0],"Remove Quary String");
        return $parts[0];       
    }   

    function handle_script_style_loader_cdn($src) {
         
        $res = $src;
        $siteUrl = self::$static_siteurl;
        $cdnUrl = self::$static_options['url'];

        if($this->startsWith($src,$siteUrl)){      
            
            $res = str_replace($siteUrl,$cdnUrl,$src);

        }else if($this->startsWith($src,'//'.parse_url($siteUrl, PHP_URL_HOST))){ // //example.com/wp-content/file.css
                       
        
            $res = str_replace('//'.parse_url($siteUrl, PHP_URL_HOST),$cdnUrl,$src);


        }else if($this->startsWith($src,'/')){
         
            $res = $cdnUrl .$src;
        }

        self::debug_log('in:' . $src .' out:'.$res,"CDN SS Rewrite");

        return $res;
    }


    function handle_bot_bandwidth(){
     
        $log_prefix = 'Bot404-' . rand(999, 999999);
               
        self::debug_log('Starting',$log_prefix);
        
        if(is_404()){
            self::debug_log('is_404() == true',$log_prefix);
            if(is_user_logged_in() === false){
                $ua = $_SERVER['HTTP_USER_AGENT'];

                if($ua){
                    self::debug_log('UA:'.$ua,$log_prefix);
                    $result = $this ->GetUaParser()->parse($ua);
                    $family = $result->device->family;

                    $isSpider = strcasecmp('Spider',$family) == 0;

                    if($isSpider){
                        self::debug_log('UA is bot',$log_prefix);
                        header('HTTP/1.0 404 Not Found');
                        header('X-Closte-Request-Spider: true');
                        header('Vary: User-Agent',false);
                        echo "<head><title>404 Not Found</title></head>";
                        echo "<h1>Not Found</h1>";                  
                        exit();
                    }else{
                        self::debug_log('UA is not bot',$log_prefix);
                        header('X-Closte-Request-Spider: false');
                    }
                } 
            }else{
                header('X-Closte-Request-Spider: false');
            }
           
             
            

         

          

        }else{
            self::debug_log('is_404() == false',$log_prefix);
        }

        self::debug_log('Ending Bot404',$log_prefix);
    }


    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function add_action_link($data) {
        // check permission
        if (!current_user_can('manage_options')) {
            return $data;
        }

        return array_merge(
                $data, array(
            sprintf(
                    '<a href="%s">%s</a>', add_query_arg(
                            array(
                'page' => 'closte'
                            ), admin_url('options-general.php')
                    ), __("Settings")
            )
                )
        );
    }

    public static function handle_uninstall_hook() {
        if (function_exists( 'apcu_clear_cache' ) ) {
            apcu_clear_cache();
		}
        delete_option('closte');       
        self::SetObjectCache(false,false);
    }

    public static function handle_deactivation_hook() {
        if (function_exists( 'apcu_clear_cache' ) ) {
            apcu_clear_cache();
		}
        delete_option('closte');       
        self::SetObjectCache(false,false);
        
    }

    public static function handle_activation_hook() {


        if (function_exists( 'apcu_clear_cache' ) ) {
            apcu_clear_cache();
		}

        add_option(
                'closte', array(
            'enable_cdn' => '1',
            'url' => 'https://' . file_get_contents(dirname(constant('ABSPATH')) . '/conf/cdnurl'),
            'dirs' => 'wp-content,wp-includes',
            'excludes' => '.php',
            'relative' => '1',
            'remove_query_string' => '1',
            'enable_object_cache' => '1',
            'bot_404_empty_response' => '0'
                )
        );

       self::SetObjectCache(true,false);
    }

    private static function format_message($mesg,$tag)
	{
	
		$formatted = sprintf("%s [%s:%s] [%s] %s\n", date('r'), $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], $tag, $mesg);
		return $formatted;
	}

    public static function debug_log($mesg,$tag)
	{
        if(self::$debug_enabled){
            $formatted = self::format_message($mesg,$tag);
            file_put_contents(self::$log_path, $formatted, FILE_APPEND);
        }
		
	}

    public static function SetObjectCache($set, $oldValue){

        $wordpress_object_cache_file_location = ABSPATH . 'wp-content/object-cache.php';
        $object_cache_file_location = CLOSTE_DIR . '/inc/class-closte-object-cache.php';

        if($set == true){
            $object_cache_php = file_get_contents($object_cache_file_location);
            file_put_contents($wordpress_object_cache_file_location,$object_cache_php);   
            
            if($oldValue === false){
                if (function_exists( 'apcu_clear_cache' ) ) {
                    apcu_clear_cache();
                }
            }
         
        }else{

            if (function_exists( 'apcu_clear_cache' ) ) {
                apcu_clear_cache();
            }

            if (file_exists($wordpress_object_cache_file_location)) {

                $object_cache_php = file_get_contents($wordpress_object_cache_file_location);
                
                if (strpos($object_cache_php, 'closte') !== false) {
                    unlink($wordpress_object_cache_file_location);
                    apcu_clear_cache();
                }
            }  
        }
        
    }

    public static function closte_requirements_check() {
        // WordPress version check
        if (version_compare($GLOBALS['wp_version'], CLOSTE_MIN_WP, '<')) {



            show_message(
                    sprintf('<div class="error"><p>Closte is optimized for minimum WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).</p></div>', CLOSTE_MIN_WP)
                    );
        }

        if (!IS_CLOSTE) {
            show_message('<div class="error"><p>Closte plugin works only on closte cloud platform. Please visit <a href="https://closte.com" target="_blank">closte</a>.</p></div>');
        }
    }

    public static function register_textdomain() {

        load_plugin_textdomain(
                'closte', false, 'closte/lang'
        );
    }

    public static function get_options() {
        return wp_parse_args(
                get_option('closte'), array(
            'enable_cdn' => '1',
            'url' => 'https://' . file_get_contents(dirname(constant('ABSPATH')) . '/conf/cdnurl'),
            'dirs' => 'wp-content,wp-includes',
            'excludes' => '.php',
            'relative' => '1',
            'remove_query_string' => '1',
            'enable_object_cache' => '1',
            'enable_debug' => '0',
            'bot_404_empty_response' => '0'
                )
        );
    }

    public static function handle_rewrite_hook() {
        $options = self::get_options();     
      
        $excludes = array_map('trim', explode(',', $options['excludes']));

        $rewriter = new Closte_Rewriter(
                get_option('home'), $options['url'], $options['dirs'], $excludes, $options['relative']
        );
        ob_start(
                array(&$rewriter, 'rewrite')
        );
    }

    public static function closte_autologin() {


       

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && self::Is_Backend_LOGIN()) {


            if(isset($_POST["closte_auto_login_value"])){


                $debug_messages = array();
                $local_file = "";

                try {
                    $closte_auto_login_value = $_POST["closte_auto_login_value"];
                    $user_ip = $_SERVER["REMOTE_ADDR"];
                    $local_file = dirname(constant('ABSPATH')) . '/conf/cal_' . $user_ip;

                    

                    if (file_exists($local_file) && fileowner($local_file) === 0) {
                       
                        $file_content = file_get_contents($local_file);
                        $validUntil = date("F j Y g:i:s A T", self::ticks_to_time(explode(",", $file_content)[0])) ;
                        $file_value = explode(",", $file_content)[1];
                        $utc_now = date("F j Y g:i:s A T", time() - date("Z"));
                        $wp_user =explode(",", $file_content)[2];
                        $file_ip = explode(",", $file_content)[3];


                        array_push($debug_messages, ($file_value === $closte_auto_login_value) ? 'value is true' : 'false' );
                        array_push($debug_messages, ($validUntil > $utc_now) ? 'date is true' : 'false');
                        array_push($debug_messages, ($file_ip === $user_ip) ? 'ip is true' : 'false');
                       

                        if(($file_value === $closte_auto_login_value) && ($validUntil > $utc_now) && ($file_ip === $user_ip)){

                            array_push($debug_messages, "auto login requirements passed");

                            if ( username_exists($wp_user) ) {

                                if(is_user_logged_in()){
                                    wp_logout();
                                }
                                
                                //get user's ID
                                $user = get_user_by('login', $wp_user);
                                $user_id = $user->ID;
                                //login
                                wp_set_current_user($user_id, $wp_user);
                                wp_set_auth_cookie($user_id);
                                do_action('wp_login', $wp_user);
                                //redirect to home page after logging in (i.e. don't show content of www.site.com/?p=1234 )
                                wp_redirect( home_url() );
                                exit;


                            }else{
                                header( 'HTTP/1.0 503 Service Unavailable' );
                                header( 'Content-Type: text/plain; charset=UTF-8' );
                                echo '<strong>ERROR:</strong> User '.$wp_user.' does not exist. Go to site settings on <a href="https://closte.com">closte.com</a> and change auto login username.';
                                exit( 1 );
                            }

                           
                            
                        }
                        array_push($debug_messages, "auto login requirements not passed");
                    }
                }
                catch (Exception $e) {

                    self::debug_log('Exception:' . $e -> getMessage(),"Auto Login");
                 
                }finally{
                    if (file_exists($local_file)){
                        unlink($local_file);
                    }

                    self::debug_log('Messages:' . implode(",", $debug_messages),"Auto Login");
                 }

           



            }       
        }

     
    }

    function Is_Backend_LOGIN(){
        $ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);

        $included_files = get_included_files();

        return ((in_array($ABSPATH_MY.'wp-login.php', $included_files) || in_array($ABSPATH_MY.'wp-register.php', $included_files) ) || $GLOBALS['pagenow'] === 'wp-login.php' || $_SERVER['PHP_SELF']== '/wp-login.php');
    }

    

    function ticks_to_time($ticks) {
        return floor(($ticks - 621355968000000000) / 10000000);
    }
}