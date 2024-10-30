<?php
/*
Plugin Name: CP Phone Link
Plugin URI: http://www.easycpmods.com
Description: CP Phone Link is a lightweight plugin that will add a link on user's phone number for the field you specify. Requires Classipress theme to be installed.
Author: EasyCPMods
Version: 1.3.2
Author URI: http://www.easycpmods.com
Text Domain: ecpm-cpl
*/

define('ECPM_CPL_NAME', 'CP Phone Link');
define('ECPM_CPL_VERSION', '1.3.2');
define('ECPM_CPL', 'ecpm-cpl');

register_activation_hook( __FILE__, 'ecpm_cpl_activate');
register_deactivation_hook( __FILE__, 'ecpm_cpl_deactivate');
register_uninstall_hook( __FILE__, 'ecpm_cpl_uninstall');

add_action('plugins_loaded', 'ecpm_cpl_plugins_loaded');
add_action('admin_init', 'ecpm_cpl_requires_version');
  
add_action('admin_menu', 'ecpm_cpl_create_menu_set', 11);
add_action('wp_enqueue_scripts', 'ecpm_cpl_enqueue_scripts');

add_filter('cp_ad_details_field', 'ecpm_cpl_link_field', 10, 3 );
add_filter('cp_formbuilder_field', 'ecpm_cpl_unlink_field', 10, 2 );

function ecpm_cpl_requires_version() {
  $allowed_apps = array('classipress');
  
  if ( defined(APP_TD) && !in_array(APP_TD, $allowed_apps ) ) { 
	  $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );
		
    if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "<strong>".$plugin_data['Name']."</strong> requires a AppThemes Classipress theme to be installed. Your Wordpress installation does not appear to have that installed. The plugin has been deactivated!<br />If this is a mistake, please contact plugin developer!<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
		}
	}
}

function ecpm_cpl_activate() {
  $ecpm_cpl_settings = get_option('ecpm_cpl_settings');
  if ( empty($ecpm_cpl_settings) ) {
    $ecpm_cpl_settings = array(
      'phone_field' => '',
      'link_desktop' => '',
      'link_mobile' => 'on',
      'phone_icon' => 'disabled',
      'icon_size' => '16',
      'hide_phone_desktop' => 'on',
      'hide_phone_mobile' => 'on',
      'hidden_text' => 'Click to show phone number',
      'tooltip_text' => 'Click to call',
      'opacity' => '100',
      'icon_margin' => array('0', '0', '0', '0'),
      'icon_position' => 'right',
      'icon_padding' => array('0', '0') 
    );
    update_option( 'ecpm_cpl_settings', $ecpm_cpl_settings );
  }
}

function ecpm_cpl_deactivate() {                                   
  ecpm_cpl_unlink_all();
}

function ecpm_cpl_uninstall() {                                   
  delete_option( 'ecpm_cpl_settings' );
}

function ecpm_cpl_plugins_loaded() {
  $dir = dirname(plugin_basename(__FILE__)).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR;
	load_plugin_textdomain(ECPM_CPL, false, $dir);
}

function ecpm_cpl_enqueue_scripts()	{
  wp_enqueue_script( 'ecpm_cpl_script', plugin_dir_url( __FILE__ ) . '/js/ecpm-cpl.js', array( 'jquery' ), false, true );
}


function ecpm_cpl_get_phone_link($post_meta_val, $mobile_user = false){
  $ecpm_cpl_settings = get_option('ecpm_cpl_settings');
  
  $phone_html = '';
  $phone_style = ' style="display:inline-block"';
  $post_meta_out = '';
  $tooltip = '';
  
  $phone_icon = $ecpm_cpl_settings['phone_icon'];
  if ($phone_icon != 'disabled') {
    $icon_size = $ecpm_cpl_settings['icon_size'];
    $opacity = $ecpm_cpl_settings['opacity'];
    $icon_margin = $ecpm_cpl_settings['icon_margin'];
    $icon_position = $ecpm_cpl_settings['icon_position'];
    $icon_padding = $ecpm_cpl_settings['icon_padding'];
    
    if ($opacity == '')
      $opacity = '1';
    else
      $opacity = $opacity / 100; 

    if ($mobile_user && $ecpm_cpl_settings['link_mobile'] == 'on' || !$mobile_user && $ecpm_cpl_settings['link_desktop'] == 'on') {
      $tooltip = "title='".$ecpm_cpl_settings['tooltip_text']."'";
      $phone_html = '<img style="float:'.$icon_position.'; width:'.$icon_size.'px; height:'.$icon_size.'px; padding-left:'. esc_html($icon_padding[0]).'px; padding-right:'. esc_html($icon_padding[1]).'px; margin:'. esc_html($icon_margin[0]).'px '.esc_html($icon_margin[1]).'px '.esc_html($icon_margin[2]).'px '.esc_html($icon_margin[3]). 'px; opacity:'. $opacity.'" src="'. esc_url(plugins_url('images/phone-icon-'.$phone_icon.'.png', __FILE__)).'" '.$tooltip.'>';
    }  
  }  
  
  if ($mobile_user && $ecpm_cpl_settings['hide_phone_mobile'] == 'on' || !$mobile_user && $ecpm_cpl_settings['hide_phone_desktop'] == 'on') {
    $post_meta_out = '<a id="cpl_phone_info">'. $ecpm_cpl_settings['hidden_text'].'</a>';
    $phone_style = ' style="display:none"';
  }  
    
  $post_meta_out .= '<div id="cpl_phone_link" '.$phone_style.'>';
  
  if ($mobile_user && $ecpm_cpl_settings['link_mobile'] == 'on' || !$mobile_user && $ecpm_cpl_settings['link_desktop'] == 'on')
    $post_meta_out .= '<a href="tel:' . $post_meta_val . '">'.$phone_html.$post_meta_val.'</a>';
  else  
    $post_meta_out .= $phone_html.$post_meta_val;
 
  $post_meta_val .= '</div>';  
      
  return $post_meta_out;  
}


function ecpm_cpl_link_field($field, $post, $location){
	$ecpm_cpl_settings = get_option('ecpm_cpl_settings');
  
  if ( isset($field) && is_object($field) && property_exists($field, 'field_name') && $field->field_name == $ecpm_cpl_settings['phone_field'] ) {
    ecpm_cpl_unlink_field($field, $post);
    
    //if ( !wp_is_mobile() && $ecpm_cpl_settings['mobile_only'] == 'on' ) {
    //  return $field;
    //}  
  
		$post_meta_val = get_post_meta( $post->ID, $field->field_name, true ); 
    
    if ( $post_meta_val != '') {
      $post_meta_val = ecpm_cpl_get_phone_link($post_meta_val, wp_is_mobile());
      
      update_post_meta($post->ID, $field->field_name, $post_meta_val); 
    }  
  } 
  
  return $field;
}

function ecpm_cpl_unlink_field($field, $post){
	$ecpm_cpl_settings = get_option('ecpm_cpl_settings');

  if ( isset($field) && is_object($field) && property_exists($field, 'field_name') && $field->field_name == $ecpm_cpl_settings['phone_field'] ) {
		$post_meta_val = get_post_meta( $post->ID, $field->field_name, true ); 
  
    //if ( strchr($post_meta_val, 'tel:') || strchr($post_meta_val, $ecpm_cpl_settings['hidden_text']) ) {
      $post_meta_val = strip_tags($post_meta_val);
      $post_meta_val = str_replace($ecpm_cpl_settings['hidden_text'], '', $post_meta_val);
      
      update_post_meta($post->ID, $field->field_name, $post_meta_val); 
    //}  
  } 
  
  return $field;
}

function ecpm_cpl_unlink_all(){
  global $wpdb;
  
  $ecpm_cpl_settings = get_option('ecpm_cpl_settings');
  $phone_field = $ecpm_cpl_settings['phone_field'];
  
  if ($phone_field == '')
    return;
  
  $sql = "SELECT * FROM $wpdb->postmeta pm WHERE pm.meta_key = '".$phone_field."'";
  $results = $wpdb->get_results($sql);
  
  foreach ($results as $result) {
    $post_meta_val = strip_tags($result->meta_value);
    $post_meta_val = str_replace($ecpm_cpl_settings['hidden_text'], '', $post_meta_val);
    
    update_post_meta($result->post_id, $result->meta_key, $post_meta_val);
  }
}

function ecpm_cpl_getFieldNames(){
  global $wpdb;
  
  $sql = "SELECT field_name FROM $wpdb->cp_ad_fields WHERE field_type = 'text box'";
  $results = $wpdb->get_results( $sql );

  return $results;
}

function ecpm_cpl_getFieldLabel($field_name){
  global $wpdb;
 
  $sql = "SELECT field_label FROM $wpdb->cp_ad_fields WHERE field_name = '".$field_name."'";
  $result = $wpdb->get_var( $sql );
  
  if (!isset($result))
    return $field_name;
  else  
    return $result;
} 
 
function ecpm_cpl_create_menu_set() {
  if ( is_plugin_active('easycpmods-toolbox/ecpm-toolbox.php') ) {
    $ecpm_etb_settings = get_option('ecpm_etb_settings');
    if ($ecpm_etb_settings['group_settings'] == 'on') {
      add_submenu_page( 'ecpm-menu', ECPM_CPL_NAME, ECPM_CPL_NAME, 'manage_options', 'ecpm_cpl_settings_page', 'ecpm_cpl_settings_page_callback' );
      return;
    }
  }
  add_options_page(ECPM_CPL_NAME, ECPM_CPL_NAME, 'manage_options', 'ecpm_cpl_settings_page', 'ecpm_cpl_settings_page_callback');
}    
  
function ecpm_cpl_settings_page_callback() {
  $ecpm_cpl_settings = get_option('ecpm_cpl_settings');
	
  if ( current_user_can( 'manage_options' ) ) {
    if( isset( $_POST['ecpm_cpl_submit'] ) )
  	{
      $avail_icon_pos = array('left', 'right');
  
      ecpm_cpl_unlink_all();
  
      $ecpm_cpl_settings['phone_field'] = sanitize_text_field($_POST[ 'ecpm_cpl_phone_field' ]);
      
      if ( is_numeric(intval( $_POST[ 'ecpm_cpl_phone_icon' ] ) ) )
        $ecpm_cpl_settings['phone_icon'] = $_POST[ 'ecpm_cpl_phone_icon' ];
        
      if ( is_numeric(intval( $_POST[ 'ecpm_cpl_icon_size' ] ) ) )
        $ecpm_cpl_settings['icon_size'] = $_POST[ 'ecpm_cpl_icon_size' ];
        
      for ($i = 0; $i <= 3; $i++) {
        $ecpm_cpl_settings['icon_margin'][$i] = sanitize_text_field( $_POST[ 'ecpm_cpl_icon_margin_'.$i ] );
      } 
      
      $ecpm_cpl_settings['icon_padding'][0] = sanitize_text_field( $_POST[ 'ecpm_cpl_icon_padding_left' ] );
      $ecpm_cpl_settings['icon_padding'][1] = sanitize_text_field( $_POST[ 'ecpm_cpl_icon_padding_right' ] );
      
      if ( isset($_POST[ 'ecpm_cpl_icon_position' ]) && in_array($_POST[ 'ecpm_cpl_icon_position' ], $avail_icon_pos) )
        $ecpm_cpl_settings['icon_position'] = $_POST[ 'ecpm_cpl_icon_position' ];
        
      if ( is_numeric(intval( $_POST[ 'ecpm_cpl_opacity' ] ) ) ) {
        $ecpm_cpl_settings['opacity'] = $_POST[ 'ecpm_cpl_opacity' ];
        if ( intval($ecpm_cpl_settings['opacity']) > 100 || intval($ecpm_cpl_settings['opacity']) < 0 || $ecpm_cpl_settings['opacity'] == '' )
          $ecpm_cpl_settings['opacity'] = '100';   
      } 
  
      if ( isset($_POST[ 'ecpm_cpl_hide_phone_desktop' ]) && $_POST[ 'ecpm_cpl_hide_phone_desktop' ] == 'on' )
        $ecpm_cpl_settings['hide_phone_desktop'] = $_POST[ 'ecpm_cpl_hide_phone_desktop' ];
      else
        $ecpm_cpl_settings['hide_phone_desktop'] = '';
        
      if ( isset($_POST[ 'ecpm_cpl_hide_phone_mobile' ]) && $_POST[ 'ecpm_cpl_hide_phone_mobile' ] == 'on' )
        $ecpm_cpl_settings['hide_phone_mobile'] = $_POST[ 'ecpm_cpl_hide_phone_mobile' ];
      else
        $ecpm_cpl_settings['hide_phone_mobile'] = '';  
        
      $ecpm_cpl_settings['hidden_text'] = sanitize_text_field($_POST[ 'ecpm_cpl_hidden_text' ]);
      
      $ecpm_cpl_settings['tooltip_text'] = sanitize_text_field($_POST[ 'ecpm_cpl_tooltip_text' ]);  
        
      if ( isset($_POST[ 'ecpm_cpl_link_mobile' ]) && $_POST[ 'ecpm_cpl_link_mobile' ] == 'on' )
        $ecpm_cpl_settings['link_mobile'] = $_POST[ 'ecpm_cpl_link_mobile' ];
      else
        $ecpm_cpl_settings['link_mobile'] = '';
        
      if ( isset($_POST[ 'ecpm_cpl_link_desktop' ]) && $_POST[ 'ecpm_cpl_link_desktop' ] == 'on' )
        $ecpm_cpl_settings['link_desktop'] = $_POST[ 'ecpm_cpl_link_desktop' ];
      else
        $ecpm_cpl_settings['link_desktop'] = '';   
        
      update_option( 'ecpm_cpl_settings', $ecpm_cpl_settings );
      
      echo scb_admin_notice( __( 'Settings saved.', APP_TD ), 'updated' );
      
    }
  }
  
  $image_count = 0;
  $ecpm_cpl_images = array_diff(scandir(plugin_dir_path(__FILE__).'/images'), array('..', '.', ));
  foreach ($ecpm_cpl_images as $cpl_image) {
    if ( strpos($cpl_image, 'phone-icon-') === 0 ) // found on pos 0
      $image_count++;
  }
  
  ?>
	<div id="cplsetting">
	  <div class="wrap">
    <h1><?php echo _e('CP Phone Link', ECPM_CPL); ?></h1>
    <?php
      echo "<i>Plugin version: <u>".ECPM_CPL_VERSION."</u>";
      echo "<br>Plugin language file: <u>ecpm-cpl-".get_locale().".mo</u></i>";
      ?>
      <hr>
      <div id='cpl-container-left' style='float: left; margin-right: 285px;'>
      <form id='cplsettingform' method="post" action="">
				<table width="100%" cellspacing="0" cellpadding="10" border="0">
          <tr>
            <th align="left">
        			<label for="ecpm_cpl_phone_field"><?php echo _e('Phone field', ECPM_CPL); ?></label>
        		</th>

            <td>
              <select name="ecpm_cpl_phone_field">
                <option value="" <?php echo (!$ecpm_cpl_settings['phone_field'] ? 'selected':'') ;?>><?php echo _e('-- Disable --', ECPM_CPL); ?></option>
              <?php
            	  $ecpm_cpl_field_results = ecpm_cpl_getFieldNames();
                foreach ( $ecpm_cpl_field_results as $result ) {
                  $field_label = ecpm_cpl_getFieldLabel($result->field_name);
							  ?>
									<option value="<?php echo $result->field_name; ?>" <?php echo ($ecpm_cpl_settings['phone_field'] == $result->field_name ? 'selected':'') ;?>><?php echo esc_html($field_label); ?></option>
							  <?php
							  } 
              ?>
              </select> 
            </td>
            <td><span class="description"><?php echo _e('Select a field for phone number', ECPM_CPL); ?></span></td>
          </tr>
          
          <tr>
        	  <th align="left" valign="middle">
        			<label for="ecpm_cpl_phone_icon"><?php echo _e('Phone icon', ECPM_CPL); ?></label>
        		</th>
            <td>
              <table cellspacing="0" cellpadding="3" border="0"><tr>
              <td align="center"><?php echo _e('No icon', ECPM_CPL); ?></td>
              <?php
              for ($i = 1; $i<=$image_count; $i++) {
              ?>
                <td align="center"><img src="<?php echo plugins_url('images/phone-icon-'.$i.'.png', __FILE__);?>" style="margin-right:10px;"></td>
              <?php
              }
              ?>
              </tr><tr>
              <td align="center"><input type="radio" id="ecpm_cpl_phone_icon" name="ecpm_cpl_phone_icon" value="disabled" <?php echo ($ecpm_cpl_settings['phone_icon'] == 'disabled' ? 'checked':'') ;?>></td>
              <?php
              for ($i = 1; $i<=$image_count; $i++) {
              ?>
                <td align="center"><input type="radio" id="ecpm_cpl_phone_icon" name="ecpm_cpl_phone_icon" value="<?php echo $i;?>" <?php echo ($ecpm_cpl_settings['phone_icon'] == $i ? 'checked':'') ;?>></td>
              <?php
              }
              ?>   
              </tr></table>
            </td>
            <td valign="middle">		
              <span class="description"><?php _e( 'Select an icon to show' , ECPM_CPL ); ?></span>
  					</td>
          </tr>
          
           <tr>
        	  <th align="left" valign="top">
        			<label for="ecpm_cpl_icon_size"><?php echo _e('Icon size', ECPM_CPL); ?></label>
        		</th>
            <td>
              <select name="ecpm_cpl_icon_size" id="ecpm_cpl_icon_size" >
                <option value="24" <?php echo ($ecpm_cpl_settings['icon_size'] == '24' ? 'selected':'') ;?>>24 px</option>
                <option value="20" <?php echo ($ecpm_cpl_settings['icon_size'] == '20' ? 'selected':'') ;?>>20 px</option>
                <option value="16" <?php echo ($ecpm_cpl_settings['icon_size'] == '16' ? 'selected':'') ;?>>16 px</option>
                <option value="12" <?php echo ($ecpm_cpl_settings['icon_size'] == '12' ? 'selected':'') ;?>>12 px</option>
              </select>
            </td>
            <td valign="top">		
              <span class="description"><?php _e( 'Specify phone icon size' , ECPM_CPL ); ?></span>
  					</td>
          </tr>
          
          <tr>
  					<th align="left" valign="top">
  						<label for="ecpm_cpl_opacity"><?php echo _e('Icon transparency', ECPM_CPL ); ?></label>
  					</th>
  					<td>
		          <Input type='text' size='2' id='ecpm_cpl_opacity' Name='ecpm_cpl_opacity' value='<?php echo esc_html($ecpm_cpl_settings['opacity']);?>'>%
  				  </td>
            <td valign="top">		
              <span class="description"><?php _e( 'Set transparency of phone icon' , ECPM_CPL ); ?></span>
  					</td>
  				</tr>  

          <tr>
  					<th align="left">
  						<label for="ecpm_cpl_icon_position"><?php echo _e('Icon horizontal position', ECPM_CPL); ?></label>
  					</th>
  					<td valign="middle">
              <input type="radio" id="ecpm_cpl_icon_position" name="ecpm_cpl_icon_position" value="left" <?php echo ( esc_html($ecpm_cpl_settings['icon_position']) == 'left' ? 'checked':'') ;?>><?php echo _e('Left', ECPM_CPL); ?>
              &nbsp;&nbsp;
              <input type="radio" id="ecpm_cpl_icon_position" name="ecpm_cpl_icon_position" value="right" <?php echo ( esc_html($ecpm_cpl_settings['icon_position']) == 'right' ? 'checked':'') ;?>><?php echo _e('Right', ECPM_CPL); ?>
            </td>
            <td>  
              <span class="description"><?php _e( 'Where to show the phone icon' , ECPM_CPL ); ?></span>
  					</td>
  				</tr> 
          
          <tr>
  					<th align="left">
  						<label for="ecpm_cpl_icon_margin"><?php echo _e('Icon margin', ECPM_CPL); ?></label>
  					</th>
  					<td>
  						<table>
                <tr>
                  <td><?php echo _e('Top', ECPM_CPL); ?></td>
                  <td><?php echo _e('Right', ECPM_CPL); ?></td>
                  <td><?php echo _e('Bottom', ECPM_CPL); ?></td>
                  <td><?php echo _e('Left', ECPM_CPL); ?></td>
                </tr>
                <tr>
                <?php 
    						for ($mi = 0; $mi <= 3; $mi++){
    						?>
                 <td><Input type='text' size='1' id='ecpm_cpl_icon_margin_<?php echo $mi;?>' Name ='ecpm_cpl_icon_margin_<?php echo $mi;?>' value='<?php echo esc_html($ecpm_cpl_settings['icon_margin'][$mi]);?>'>px</td>
    						<?php
                }
                ?>
                </tr>
              </table>
  				  </td>
            <td>		
              <span class="description"><?php _e( 'Margin in pixels around the icon' , ECPM_CPL ); ?></span>
  					</td>
  				</tr>
          
          <tr>
  					<th align="left">
  						<label for="ecpm_cpl_icon_padding"><?php echo _e('Icon padding', ECPM_CPL); ?></label>
  					</th>
  					<td>
  						<table>
                <tr>
                  <td><?php echo _e('Left', ECPM_CPL); ?></td>
                  <td><?php echo _e('Right', ECPM_CPL); ?></td>
                </tr>
                <tr>
                 <td><Input type='text' size='1' id='ecpm_cpl_icon_padding_left' Name ='ecpm_cpl_icon_padding_left' value='<?php echo esc_html($ecpm_cpl_settings['icon_padding'][0]);?>'>px</td>
                 <td><Input type='text' size='1' id='ecpm_cpl_icon_padding_right' Name ='ecpm_cpl_icon_padding_right' value='<?php echo esc_html($ecpm_cpl_settings['icon_padding'][1]);?>'>px</td>
                </tr>
              </table>
  				  </td>
            <td>		
              <span class="description"><?php _e( 'Empty space on left and right size of icon' , ECPM_CPL ); ?></span>
  					</td>
  				</tr>
          
          <tr><td colspan="3"><hr></td></tr> 
          
          
          <tr>
        	  <th colspan="2" align="left">
        			<label for="ecpm_cpl_hide_phone"><?php echo _e('Click to show phone number', ECPM_CPL); ?></label>
        		</th>
            <td valign="top">	
              <span class="description"><?php _e( 'Would you like to hide phone number until user clicks on text?' , ECPM_CPL ); ?></span>
            </td>
          </tr>
          <tr>
            <th align="left">
        			<label for="ecpm_cpl_hide_phone_desktop"><?php echo _e('Enable on desktop', ECPM_CPL); ?></label>
        		</th>  
            <td><Input type='checkbox' id='ecpm_cpl_hide_phone_desktop' Name='ecpm_cpl_hide_phone_desktop' <?php echo ( $ecpm_cpl_settings['hide_phone_desktop'] == 'on' ? 'checked':'') ;?> ></td>
            <td valign="top">		
  					</td>
          </tr>
          <tr>
            <th align="left">
        			<label for="ecpm_cpl_hide_phone_mobile"><?php echo _e('Enable on mobile', ECPM_CPL); ?></label>
        		</th>  
            <td><Input type='checkbox' id='ecpm_cpl_hide_phone_mobile' Name='ecpm_cpl_hide_phone_mobile' <?php echo ( $ecpm_cpl_settings['hide_phone_mobile'] == 'on' ? 'checked':'') ;?> ></td>
            <td valign="top">		
  					</td>
          </tr>
          
          <tr>
        	  <th align="left">
        			<label for="ecpm_cpl_hidden_text"><?php echo _e('Hidden number text', ECPM_CPL); ?></label>
        		</th>
            <td><input type="text" size='40' name="ecpm_cpl_hidden_text" id="ecpm_cpl_hidden_text" value="<?php echo esc_html($ecpm_cpl_settings['hidden_text']);?>" /></td>
            <td valign="top">		
              <span class="description"><?php _e( 'Text to show instead of phone number' , ECPM_CPL ); ?></span>
  					</td>
          </tr>
          
          <tr><td colspan="3"><hr></td></tr>
          
          
          <tr>
        	  <th colspan="2" align="left">
        			<label for="ecpm_cpl_mobile_only"><?php echo _e('Create link on phone number', ECPM_CPL); ?></label>
        		</th>
            
            <td valign="top">		
                <span class="description"><?php _e( 'Phone number will get tel: link so user can click and dial' , ECPM_CPL ); ?></span>
  					</td>
          </tr>
          
          <tr>
            <th align="left">
        			<label for="ecpm_cpl_link_desktop"><?php echo _e('Enable on desktop', ECPM_CPL); ?></label>
        		</th>  
            <td><Input type='checkbox' id='ecpm_cpl_link_desktop' Name='ecpm_cpl_link_desktop' <?php echo ( $ecpm_cpl_settings['link_desktop'] == 'on' ? 'checked':'') ;?> ></td>
            <td valign="top">		
  					</td>
          </tr>
          <tr>
            <th align="left">
        			<label for="ecpm_cpl_link_mobile"><?php echo _e('Enable on mobile', ECPM_CPL); ?></label>
        		</th>  
            <td><Input type='checkbox' id='ecpm_cpl_link_mobile' Name='ecpm_cpl_link_mobile' <?php echo ( $ecpm_cpl_settings['link_mobile'] == 'on' ? 'checked':'') ;?> ></td>
            
            <td valign="top">		
  					</td>
          </tr>
          
          <tr>
        	  <th align="left">
        			<label for="ecpm_cpl_tooltip_text"><?php echo _e('Tooltip text', ECPM_CPL); ?></label>
        		</th>
            <td><input type="text" size='40' name="ecpm_cpl_tooltip_text" id="ecpm_cpl_tooltip_text" value="<?php echo esc_html($ecpm_cpl_settings['tooltip_text']);?>" /></td>
            <td valign="top">		
              <span class="description"><?php _e( 'Text to show when mouse over phone icon' , ECPM_CPL ); ?></span>
  					</td>
          </tr>
          
        </table>
        <hr>
        <p>  
				<input type="submit" id="ecpm_cpl_submit" name="ecpm_cpl_submit" class="button-primary" value="<?php _e('Save settings', ECPM_CPL); ?>" />
				</p>
			</form>
      </div>
      
      <div id='cpl-container-right' class='nocloud' style='border: 1px solid #e5e5e5; float: right; margin-left: -275px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 10px 10px 5px #888888; display: inline-block; width: 234px;'>
        <h3>Thank you for using</h3>
        <h2><?php echo ECPM_CPL_NAME;?></h2>
        <hr>
        <?php include_once( plugin_dir_path(__FILE__) ."/image_sidebar.php" );?>
      </div>
		</div>
	</div>
<?php
}
?>