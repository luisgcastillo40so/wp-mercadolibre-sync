<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/rg-
 * @since      1.0.0
 *
 * @package    Wp_Mercadolibre_Sync
 * @subpackage Wp_Mercadolibre_Sync/admin
 * @author     Roberto García <roberto.jg@gmail.com>
 */

class Wp_Mercadolibre_Sync_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
  
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) { 
		$this->plugin_name = $plugin_name;
		$this->version = $version; 
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() { 

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-mercadolibre-sync-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() { 

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-mercadolibre-sync-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function session_init(){
		if(!session_id()){
			session_start();
		}
	}
	public function session_end(){
		session_destroy();
	}

	// Not used yet
	public function wp_dashboard_setup(){
		wp_add_dashboard_widget( 'custom_dashboard_widget', 'Custom Dashoard Widget', function() { 
			require_once plugin_dir_path( __FILE__ ) . 'partials/wp-mercadolibre-sync-admin-dashboard_widget-display.php'; 
		} );
	}  

	/**
	 * Adding the admin menu pages 
	 */

	public function admin_menu() {

		require_once plugin_dir_path( __FILE__ ) . 'partials/class-add_action-admin_menu.php'; 

		$pages = array(); 
		$pages[] = add_menu_page(
        __( 'Mercadolibre Sync', 'wp-mercadolibre-sync' ),
        __( 'Mercadolibre Sync', 'wp-mercadolibre-sync' ),
        'manage_options',
        $this->plugin_name,
        array( $this, 'wp_mercadolibre_sync_options_page' ),
        'dashicons-cart',
        80
    ); 

		$pages[] = add_submenu_page(
    	$this->plugin_name,
    	__( 'Modules', 'wp-mercadolibre-sync' ),
    	__( 'Modules', 'wp-mercadolibre-sync' ),
    	'manage_options',
    	$this->plugin_name.'-modules',
    	array( $this, 'wp_mercadolibre_sync_modules_page' )
    );

    $pages[] = add_submenu_page(
    	$this->plugin_name,
    	__( 'Tests', 'wp-mercadolibre-sync' ),
    	__( 'Tests', 'wp-mercadolibre-sync' ),
    	'manage_options',
    	$this->plugin_name.'-tests',
    	array( $this, 'wp_mercadolibre_sync_tests_page' )
    );
		
    // Do something on load-[PAGE]
    foreach($pages as $page){
	    add_action( 'load-' . $page, function( ){  
	    	// add admin body class
	    	add_filter('admin_body_class', array($this, 'admin_body_class') ); 
	    	// remove query args when save or similar
	    	add_filter('removable_query_args', array($this, 'admin_removable_query_args') ); 
	    });

    }

	}

	/**
	 * Add admin body class.
	 *
	 * @since    1.0.1
	 */
	public function admin_body_class($classes){
		return "$classes wp-mercadolibre-sync";
	}
	/**
	 * Remove query args on urls.
	 *
	 * @since    1.0.1
	 */
	public function admin_removable_query_args($args){
		/**
		 * An array list of query parameters removed by WordPress is returned by wp_removable_query_args()
		 * It includes a removable_query_args filter. Thus, we can add our plugin query parameters. 
		*/
		$args[] = 'refresh_token';
		$args[] = 'code';
		$args[] = 'access_token';
		$args[] = 'test_user';
		$args[] = 'post_test';
		
		return $args;
	}

	/**
	 * Fillter plugin action links on admin plugins page.
	 *
	 * @since    1.0.1
	 */
	public function plugin_action_links( $actions, $plugin_file ){ 

		static $plugin;

		if (!isset($plugin))
			$plugin = WP_PLUGIN_PATH;
			if ($plugin == $plugin_file) { 
				$settings = array('settings' => '<a href="options-general.php?page='.$this->plugin_name.'">' . __('Settings', 'wp-mercadolibre-sync') . '</a>');  
	    		$actions = array_merge($settings, $actions);  
			} 
		return $actions;
	}

	/**
	 * Set the admin noticies depending on, mosty, the API status reported.
	 *
	 * @since    1.0.0
	 */
	public function admin_notices(){
		$WPMLSync = wp_mercadolibre_sync_settings(); 
		$api_status = wp_mercadolibre_sync_get_api_status();
		$meli_code_array = wp_mercadolibre_sync_meli_code_array(); 

		$screen = get_current_screen();
		if(isset($screen) && $screen->parent_base == $this->plugin_name){

			if( $api_status == 5 ){
				?>
		    <div class="notice notice-success is-dismissible">
		        <p><?php _e( 'Token refreshed!!', 'wp-mercadolibre-sync' ); ?></p>
		    </div>
		    <?php
			}
			if( $api_status == 8 ){
				?>
		    <div class="notice notice-success is-dismissible">
		        <p><?php _e( 'Re-autorized API. Token refreshed!!', 'wp-mercadolibre-sync' ); ?></p>
		    </div>
		    <?php
			}
			if( $api_status == 4 ){
				?>
		    <div class="notice notice-success is-dismissible">
		        <p><?php _e( 'Perfect!! Plugin configured correctly.', 'wp-mercadolibre-sync' ); ?></p>
		    </div>
		    <?php
			}
			if( $api_status == 7 ){
				?>
		    <div class="notice notice-warning is-dismissible">
		        <p><?php _e( 'Plugin needs more settings.', 'wp-mercadolibre-sync' ); ?></p>
		    </div>
		    <?php
			}

		}
		
	}

	/**
	 * Initialize the admin settings
	 *
	 * @since    1.0.0
	 */
	public function admin_init_settings() {

		$api_status = 0; 
		
		$WPMLSync = wp_mercadolibre_sync_settings(); 

		if( array_filter($WPMLSync) ){

			$api_status = 1;
			$MELI = new Meli($WPMLSync['appId'], $WPMLSync['secretKey']);
			$Exception = '';

			if(isset($_GET['code']) || !empty($WPMLSync['access_token']) ) {
				
				$api_status = 2;

				// If code exist and session is empty
				if(isset($_GET['code']) && empty($WPMLSync['access_token'])) {
					// //If the code was in get parameter we authorize
					$api_status = 3;
					try{
						$user = $MELI->authorize($_GET["code"], $WPMLSync['redirectURI']);  
						$_seller_id = wp_mercadolibre_sync_get_seller_id($MELI, $user['body']->access_token); 

						wp_mercadolibre_sync_update_settings(array(
							'access_token' => $user['body']->access_token,
							'expires_in' => $user['body']->expires_in,
							'refresh_token' => $user['body']->refresh_token,
							'seller_id' => $_seller_id
						));

						$api_status = 4;
					}catch(Exception $e){
						$Exception .= $e->getMessage(). "\n";
					}
				} else {
					
					// Check if the access token is invalid checking time vs expires_in
					$_expire_test = isset($_GET['refresh_token']) ? true : false;
					if(isset($_GET['code'])){
						$_expire_test = true;
					}
					$_check_expires_in = !empty($WPMLSync['expires_in']) ? $WPMLSync['expires_in'] : time();
					if( ($_check_expires_in < time() && !empty($WPMLSync['auto_token']) ) || $_expire_test) {	
						try {
							// Make the refresh proccess 
 
							$refresh_MELI = new Meli($WPMLSync['appId'], $WPMLSync['secretKey'], $WPMLSync['access_token'], $WPMLSync['refresh_token']);

							$refresh = $refresh_MELI->refreshAccessToken();  
							$_seller_id = wp_mercadolibre_sync_get_seller_id($refresh_MELI, $refresh['body']->access_token);

							wp_mercadolibre_sync_update_settings(array(
								'access_token' => $refresh['body']->access_token,
								'expires_in' => $refresh['body']->expires_in,
								'refresh_token' => $refresh['body']->refresh_token,
								'seller_id' => $_seller_id
							));

							$api_status = 5;
							if(isset($_GET['code'])){
								$api_status = 8;
							}

							$refresh_public_count = (null !== get_option('wp_mercadolibre_sync_refresh_admin_count')) ? get_option('wp_mercadolibre_sync_refresh_admin_count') : 0;
							update_option('wp_mercadolibre_sync_refresh_admin_count', ($refresh_public_count + 1));


						} catch (Exception $e) {
						  	$Exception .= $e->getMessage(). "\n";
						}
					}else{ 
						$api_status = 6;
					}
				}  

			} else {
				$api_status = 7; 
			}
 
		}

		update_option('wp_mercadolibre_sync_status',$api_status);
		wp_mercadolibre_sync_debug('update_option::wp_mercadolibre_sync_status status: '.$api_status.'');
	}

	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function admin_register_settings() {  

		// Settings & Sections

		register_setting( 'wp_mercadolibre_sync_api', 'wp_mercadolibre_sync_settings', array($this, '_validate' ) ); 

		add_settings_section(
			'wp_mercadolibre_sync_settings_section', 
			'',  // Could be _x( '', 'wp-mercadolibre-sync' )
			array( $this, 'wp_mercadolibre_sync_settings_section_callback' ) , 
			$this->plugin_name
		); 
		add_settings_section(
			'wp_mercadolibre_sync_settings_section_advanced', 
			'', 
			array( $this, 'wp_mercadolibre_sync_settings_section_advanced_callback' ) , 
			$this->plugin_name.'-advanced'
		); 
		add_settings_section(
			'wp_mercadolibre_sync_settings_section_private', 
			'',
			array( $this, 'wp_mercadolibre_sync_settings_section_private_callback' ) , 
			$this->plugin_name.'-private'
		); 

		register_setting( 'wp_mercadolibre_sync_api', 'wp_mercadolibre_sync_curl_settings', array($this, '_validate' ) ); 

 	
 		// Settings & Sections END

		// Set user fields

		$fields = $this->_get_setting_fields();
		foreach($fields as $field){
			add_settings_field( 
				'wp_mercadolibre_sync_'.$field, 
				$field, 
				function() use ( $field ) {
					$options = get_option( 'wp_mercadolibre_sync_settings' );
					$value = isset($options['wp_mercadolibre_sync_'.$field]) ? $options['wp_mercadolibre_sync_'.$field] : ''; 
 
					if($field=='siteId'){ 
						$meli = new Meli('','');
						$sites = $meli->get('/sites');
						if( isset($sites['httpCode']) == 200 ) {
							?>
							<select class='wpmlsync__control' name='wp_mercadolibre_sync_settings[wp_mercadolibre_sync_<?php echo $field; ?>]'>
								<option value=0>Seleccionar País</option>
								<?php
								foreach ($sites['body'] as $site) { 
									if($value==$site->id){
										$selected = "selected";
									}else{
										$selected = "";
									}
									?><option value='<?php echo $site->id; ?>' <?php echo $selected; ?>><?php echo $site->name; ?></option><?php
								}
								?>
							</select>
							<?php
						} 
					}else{
						?>
						<input required type='text' name='wp_mercadolibre_sync_settings[wp_mercadolibre_sync_<?php echo $field; ?>]' value='<?php echo $value; ?>' class='wpmlsync__control'>
						<?php
					}
				} , 
				$this->plugin_name, 
				'wp_mercadolibre_sync_settings_section' 
			);
		} 

		// Set seller_id field TODO pass it to private fields array, has no utility to leave it alone

		add_settings_field( 
			'wp_mercadolibre_sync_seller_id',
			'seller_id',
			function() {   
				 $field = 'seller_id';
				 $options = get_option( 'wp_mercadolibre_sync_settings' );
					$value = isset($options['wp_mercadolibre_sync_'.$field]) ? $options['wp_mercadolibre_sync_'.$field] : ''; 
					?>
					<input readonly type='text' name='wp_mercadolibre_sync_settings[wp_mercadolibre_sync_<?php echo $field; ?>]' value='<?php echo $value; ?>' class='wpmlsync__control'>
					<?php  
			},
			$this->plugin_name.'-private',
				'wp_mercadolibre_sync_settings_section_private' 
		);  

		// Set private fields

		$private_fields = $this->_get_private_fields();
		foreach($private_fields as $field){
			add_settings_field( 
				'wp_mercadolibre_sync_'.$field, 
				$field, 
				function() use ( $field ) { 
					$options = get_option( 'wp_mercadolibre_sync_settings' );
					$value = isset($options['wp_mercadolibre_sync_'.$field]) ? $options['wp_mercadolibre_sync_'.$field] : ''; 
					?>
					<input readonly type='text' name='wp_mercadolibre_sync_settings[wp_mercadolibre_sync_<?php echo $field; ?>]' value='<?php echo $value; ?>' class='wpmlsync__control'>
					<?php  
				} , 
				$this->plugin_name.'-private',
				'wp_mercadolibre_sync_settings_section_private' 
			);
		} 

		/**
		 *
		 * 'auto_token'
		 * 
		 */
		add_settings_field( 
			'wp_mercadolibre_sync_auto_token',
			'auto_token',
			function() {  
					$field = 'auto_token';
					$options = get_option( 'wp_mercadolibre_sync_settings' ); 
					if(empty($options)){
						$checked = 'checked';
					}else{
						$checked = isset($options['wp_mercadolibre_sync_'.$field]) ? 'checked' : ''; 
					}
					?> 
					<label class="wpmlsync__label_control"><input type='checkbox' name='wp_mercadolibre_sync_settings[wp_mercadolibre_sync_<?php echo $field; ?>]' <?php echo $checked; ?> class='wpmlsync__checkbox'><span class=""><?php echo _e('Enable Auto Token?','wp-mercadolibre-sync'); ?> </span></label>
					<p><?php echo _e('Tokens exprie each 6 hours, enable this option to refresh it automaticly when needed.','wp-mercadolibre-sync'); ?></p>
					<?php  
			},
			$this->plugin_name.'-advanced',
				'wp_mercadolibre_sync_settings_section_advanced' 
		); 
		add_settings_field( 
			'wp_mercadolibre_sync_debug',
			'debug',
			function() {  
					$field = 'debug';
					$options = get_option( 'wp_mercadolibre_sync_settings' ); 
					if(empty($options)){
						$checked = '';
					}else{
						$checked = isset($options['wp_mercadolibre_sync_'.$field]) ? 'checked' : ''; 
					}
					?> 
					<label class="wpmlsync__label_control"><input type='checkbox' name='wp_mercadolibre_sync_settings[wp_mercadolibre_sync_<?php echo $field; ?>]' <?php echo $checked; ?> class='wpmlsync__checkbox'><span class=""><?php echo _e('Enable Debug?','wp-mercadolibre-sync'); ?> </span></label>
					<p><?php echo _e('A file will be created/updated at: wp-content/wp-mercadolibre-sync-debug.txt with debug information.','wp-mercadolibre-sync'); ?></p>
					<?php  
			},
			$this->plugin_name.'-advanced',
				'wp_mercadolibre_sync_settings_section_advanced' 
		); 

		add_settings_field( 
			'curl_ssl',
			'SSL_VERIFYPEER',
			function() { 
				  $options = get_option( 'wp_mercadolibre_sync_curl_settings', true ); 
					$checked = isset($options['curl_ssl']) ? 'checked' : ''; 
					?>
					<label class="wpmlsync__label_control"><input type='checkbox' name='wp_mercadolibre_sync_curl_settings[curl_ssl]' <?php echo $checked; ?> class='wpmlsync__checkbox'><span class=""><?php echo _e('Use SSL_VERIFYPEER?','wp-mercadolibre-sync'); ?> </span></label>
					<p><?php echo _e('If using localhost or no SSL actived, you can disable this for testings.','wp-mercadolibre-sync'); ?></p>
					<?php  
			},
			$this->plugin_name.'-advanced',
			'wp_mercadolibre_sync_settings_section_advanced' 
		); 
		

	}
	// admin_register_settings() END 

	/**
	 * Simple validate for input texts.
	 *
	 * @since    1.0.0
	 */
	public function _validate($input){
		$validated = $input;
    return $validated;
	} 
 

	public function wp_mercadolibre_sync_options_page_start_ev($args=array()){
		?>
		<div class="wrap wpmlsync__wrap">
			<div id="poststuff">
			  <div id="post-body" class="metabox-holder columns-1">

			  	<h1 class="wp-heading-inline"><?php echo $args['headline']; ?></h1>
					<div class="clear"></div>
					<br>
		<?php
	}
	public function wp_mercadolibre_sync_options_page_end_ev($args=array()){
		?>
				</div>
		  </div>
		</div>
		<?php
	}
	/**
	 * Callback for admin page
	 *
	 * @since    1.0.0
	 */
	public function wp_mercadolibre_sync_options_page( ) {  
		$this->wp_mercadolibre_sync_options_page_start_ev(array(
			'headline'=> __( 'WP Mercadolibre Sync', 'wp-mercadolibre-sync' ),
		));
		require_once plugin_dir_path( __FILE__ ) . 'partials/wp-mercadolibre-sync-admin-display.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/wp-mercadolibre-sync-admin-display-settings.php'; 
		$this->wp_mercadolibre_sync_options_page_end_ev();
	}

	/**
	 * Callback for test page
	 *
	 * @since    1.0.0
	 */
	public function wp_mercadolibre_sync_tests_page( ) { 
		$this->wp_mercadolibre_sync_options_page_start_ev(array(
			'headline'=> __( 'WP Mercadolibre Sync', 'wp-mercadolibre-sync' ).' > '.__( 'Tests', 'wp-mercadolibre-sync' ),
		));
		require_once plugin_dir_path( __FILE__ ) . 'partials/wp-mercadolibre-sync-admin-tests.php'; 
		$this->wp_mercadolibre_sync_options_page_end_ev(); 
	}

	/**
	 * Callback for modules page
	 *
	 * @since    1.0.1
	 */
	public function wp_mercadolibre_sync_modules_page( ) { 
		$this->wp_mercadolibre_sync_options_page_start_ev(array(
			'headline'=> __( 'WP Mercadolibre Sync', 'wp-mercadolibre-sync' ).' > '.__( 'Modules', 'wp-mercadolibre-sync' ),
		));
		require_once plugin_dir_path( __FILE__ ) . 'partials/wp-mercadolibre-sync-admin-modules.php'; 
		$this->wp_mercadolibre_sync_options_page_end_ev();  
	}
	
	/**
	 * Callback for settings section
	 *
	 * @since    1.0.0
	 */
	public function wp_mercadolibre_sync_settings_section_advanced_callback( ) {  
		?>
		<h2 class="wpmlsync__postbox-title"><?php echo __( 'Advanced Settings', 'wp-mercadolibre-sync' ); ?></h2>
		<p class='about-description'><?php echo __( 'We recommend using these options only for development. The option to activate Auto Token should always be active on production sites.', 'wp-mercadolibre-sync' ); ?></p>
		<?php
	}


	/**
	 * Callback for settings section
	 *
	 * @since    1.0.0
	 */
	public function wp_mercadolibre_sync_settings_section_private_callback( ) {  
		?>
		<h2 class="wpmlsync__postbox-title"><?php echo __( 'oAuth Data', 'wp-mercadolibre-sync' ); ?></h2>
		<p class='about-description'><?php echo __( 'These data are private, can be used to interact with the API. See the Mercado Libre API documentation for more information.', 'wp-mercadolibre-sync' ); ?></p>
		<br><span class='wpmlsync__badge'><span class="dashicons dashicons-warning"></span> <?php echo __( 'Never share this data.', 'wp-mercadolibre-sync' ); ?></span>
		<?php 
 
	}

	/**
	 * Callback for settings section
	 *
	 * @since    1.0.0
	 */
	public function wp_mercadolibre_sync_settings_section_callback( ) {  
		?>
		<h2 class="wpmlsync__postbox-title"><?php echo __( 'API Settings', 'wp-mercadolibre-sync' ); ?></h2>
		<p class='about-description'><?php echo __( 'These data must be exactly the same as those set in the Application created in Mercado Libre previously. ', 'wp-mercadolibre-sync' ); ?></p>
		<p class='about-description'>"siteId" <?php echo __('refers to the country of the user used for the application.', 'wp-mercadolibre-sync');?></p>
		<?php 
 
	}

	/**
	 * Get an array with all the public settings fields used. Required, user editable
	 *
	 * @since    1.0.0
	 */
	public function _get_setting_fields(){ 
		$fields = array('appId','secretKey','redirectURI','siteId'); 
		return $fields;  
	}
	/**
	 * Get an array with all the private settings fields used. Required, not editable by user.
	 *
	 * @since    1.0.0
	 */
	public function _get_private_fields(){ 
		$fields = array('access_token','expires_in','refresh_token'); 
		return $fields;  
	}

}