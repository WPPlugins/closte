<?php


class Closte_Settings
{


	

	public static function register_settings()
	{
		register_setting(
			'closte',
			'closte',
			array(
				__CLASS__,
				'validate_settings'
			)
		);
	}


	

	public static function validate_settings($data)
	{
    
     
        $current_options = Closte::get_options();
     
  
		$data = array(		
      'enable_cdn' => (int)($data['enable_cdn']),
      'url' => 'https://' . file_get_contents(dirname(constant('ABSPATH')) . '/conf/cdnurl'),
			'dirs'		=> esc_attr($data['dirs']),
			'excludes'	=> esc_attr($data['excludes']),
			'relative'	=> (int)($data['relative']),
			'enable_object_cache'		=> 0,
    	'remove_query_string'		=> (int)($data['remove_query_string']),
      'enable_object_cache'		=> (int)($data['enable_object_cache']),
        'enable_debug'		=> (int)($data['enable_debug']),
        'bot_404_empty_response' => (int)($data['bot_404_empty_response'])
		);
    

        Closte::SetObjectCache($data['enable_object_cache'] == 1,$current_options['enable_object_cache'] == 1);
    
    
 
    
    return $data;
	}
  
  
  


	

	public static function add_settings_page()
	{
		$page = add_options_page(
			'Closte',
			'Closte',
			'manage_options',
			'closte',
			array(
				__CLASS__,
				'settings_page'
			)
		);
	}


	

	public static function settings_page()
	{ ?>
<div class="wrap">


    <?php        
      
        
        $cache = null;

        if(function_exists("apcu_cache_info")){
            $cache = apcu_cache_info();
        }
         



         if(IS_CLOSTE){    ?>


    <form method="post" action="options.php">
        <h2>CDN Settings</h2>
        <?php settings_fields('closte') ?>

        <?php $options = Closte::get_options() ?>





        <table class="form-table">


            <tr valign="top">
                <th scope="row">
                    <?php _e("Enable CDN", "closte"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="closte_enable_cdn">
                            <input type="checkbox" name="closte[enable_cdn]" id="closte_enable_cdn" value="1" <?php checked(1, $options['enable_cdn']) ?> />
                            <?php _e("Enable Google Cloud CDN.", "closte"); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row">CDN Url
            </th>
                <td>
                    <fieldset>
                        <label for="closte_url">
                            <input disabled="disabled" type="text" value="<?php echo $options['url']; ?>" size="64" class="regular-text code" />
                        </label>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php _e("Included Directories", "closte"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="CLOSTE_DIRs">
                            <input type="text" name="closte[dirs]" id="CLOSTE_DIRs" value="<?php echo $options['dirs']; ?>" size="64" class="regular-text code" />
                            <?php _e("Default: <code>wp-content,wp-includes</code>", "closte"); ?>
                        </label>

                        <p class="description">
                            <?php _e("Assets in these directories will be pointed to the CDN URL. Enter the directories separated by", "closte"); ?> <code>,</code>
                        </p>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php _e("Exclusions", "closte"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="closte_excludes">
                            <input type="text" name="closte[excludes]" id="closte_excludes" value="<?php echo $options['excludes']; ?>" size="64" class="regular-text code" />
                            <?php _e("Default: <code>.php</code>", "closte"); ?>
                        </label>

                        <p class="description">
                            <?php _e("Enter the exclusions (directories or extensions) separated by", "closte"); ?> <code>,</code>
                        </p>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php _e("Relative Path", "closte"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="closte_relative">
                            <input type="checkbox" name="closte[relative]" id="closte_relative" value="1" <?php checked(1, $options['relative']) ?> />
                            <?php _e("Enable CDN for relative paths (default: enabled).", "closte"); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php _e("Query String", "closte"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="closte_remove_query_string">
                            <input type="checkbox" name="closte[remove_query_string]" id="closte_remove_query_string" value="1" <?php checked(1, $options['remove_query_string']) ?> />
                            <?php _e("Remove query string from url, eq: ?ver=1.0.0", "closte"); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

              <tr valign="top">
                <th scope="row">
                    <?php _e("Bots 404", "closte"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="closte_bot_404_empty_response">
                            <input type="checkbox" name="closte[bot_404_empty_response]" id="closte_bot_404_empty_response" value="1" <?php checked(1, $options['bot_404_empty_response']) ?> />
                            <?php _e("Send empty responses to bots on 404 not found urls. (experimental)", "closte"); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>


        </table>
            
        <h2>Object Cache Settings</h2>

        <table class="form-table">
        

          <tr valign="top">
            <th scope="row">
              Object Cache
            </th>
            <td>
              <fieldset>
                <label for="closte_enable_object_cache">
                  <input type="checkbox" name="closte[enable_object_cache]" id="closte_enable_object_cache" value="1" <?php checked(1, $options['enable_object_cache']) ?> />
                  Enable object cache
                </label>
              </fieldset>
            </td>
          </tr>

            <?php
             function bsize($s) {
                 foreach (array('','K','M','G') as $i => $k) {
                     if ($s < 1024) break;
                     $s/=1024;
                 }
                 return sprintf("%5.1f %sBytes",$s,$k);
             }
            
            ?>

          <?php    if(function_exists("apcu_cache_info")){ 
          
                       $time = time();
                       $mem=apcu_sma_info();
                       $mem_size = $mem['num_seg']*$mem['seg_size'];
                       $mem_avail= $mem['avail_mem'];
                       $mem_used = $mem_size-$mem_avail;
                     //  $seg_size = bsize($mem['seg_size']);
                       $req_rate_user = sprintf("%.2f", $cache['num_hits'] ? (($cache['num_hits']+$cache['num_misses'])/($time-$cache['start_time'])) : 0);
                       $hit_rate_user = sprintf("%.2f", $cache['num_hits'] ? (($cache['num_hits'])/($time-$cache['start_time'])) : 0);
                       $miss_rate_user = sprintf("%.2f", $cache['num_misses'] ? (($cache['num_misses'])/($time-$cache['start_time'])) : 0);
                       $insert_rate_user = sprintf("%.2f", $cache['num_inserts'] ? (($cache['num_inserts'])/($time-$cache['start_time'])) : 0);

                       $number_vars = $cache['num_entries'];
                       $size_vars = bsize($cache['mem_size']);

                       $used = bsize($mem_used).sprintf(" (%.1f%%)",$mem_used *100/$mem_size);
                       $free =bsize($mem_avail).sprintf(" (%.1f%%)",$mem_avail*100/$mem_size);

                       $hitPercent = $cache['num_hits'].@sprintf(" (%.1f%%)",$cache['num_hits']*100/($cache['num_hits']+$cache['num_misses']));
                       $missPercent = $cache['num_misses'].@sprintf(" (%.1f%%)",$cache['num_misses']*100/($cache['num_hits']+$cache['num_misses']));

                       $hints = ini_get_all("apcu")["apc.entries_hint"]["local_value"];
          
          ?>
                  <tr valign="top">
            <th scope="row">
              Stats
            </th>
            <td>
              
                <?php
                       echo <<<EOB
		
		<table cellspacing=0 style='background:#fff'>
		<tbody>

<tr class=tr-0><td class=td-0>Used</td><td>{$used}</td></tr>
<tr class=tr-0><td class=td-0>Free</td><td>{$free}</td></tr>



    		<tr class=tr-0><td class=td-0>Cached Variables</td><td>$number_vars ($size_vars)</td></tr>
			<tr class=tr-1><td class=td-0>Hits</td><td>{$cache['num_hits']}  {$hitPercent}</td></tr>
			<tr class=tr-0><td class=td-0>Misses</td><td>{$cache['num_misses']} {$missPercent}</td></tr>
			<tr class=tr-1><td class=td-0>Request Rate (hits, misses)</td><td>$req_rate_user cache requests/second</td></tr>
			<tr class=tr-0><td class=td-0>Hit Rate</td><td>$hit_rate_user cache requests/second</td></tr>
			<tr class=tr-1><td class=td-0>Miss Rate</td><td>$miss_rate_user cache requests/second</td></tr>
			<tr class=tr-0><td class=td-0>Insert Rate</td><td>$insert_rate_user cache requests/second</td></tr>
            <tr class=tr-1><td class=td-0>Entries Hint</td><td>{$hints}</td></tr>
			<tr class=tr-1><td class=td-0>Cache full count</td><td>{$cache['expunges']}</td></tr>
		</tbody>
		</table>
		

	
EOB;
                ?>

            </td>
          </tr>
        <?php   } ?>
       
          
          
        </table>

         <h2>Other</h2>

        <table class="form-table">
        

          <tr valign="top">
            <th scope="row">
              Debug
            </th>
            <td>
              <fieldset>
                <label for="closte_enable_debug">
                  <input type="checkbox" name="closte[enable_debug]" id="closte_enable_debug" value="1" <?php checked(1, $options['enable_debug']) ?> />
                  Enable Debug (use only if you have problem, it may slow down your site)
                </label>
              </fieldset>
            </td>
          </tr>
          
          
        </table>




        <?php submit_button() ?>
    </form>

    <?php }else{ ?>
    <p>Closte CDN is not supported on staging enviroment.</p>

    <?php } ?>



</div><?php
	}
}