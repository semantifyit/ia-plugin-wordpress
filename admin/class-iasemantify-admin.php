<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       author uri
 * @since      1.0.0
 *
 * @package    Iasemantify
 * @subpackage Iasemantify/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Iasemantify
 * @subpackage Iasemantify/admin
 * @author     Thibault Gerrier <thibault.gerrier@sti2.at>
 */
class Iasemantify_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	function write_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ia_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The iasemantify_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$path = plugin_dir_url( __FILE__ );
		// wp_enqueue_style( $this->plugin_name, $path . 'css/iasemantify-admin.css', array(), $this->version, 'all' );

		$curScreenId = get_current_screen()->id;
		$post_types = get_post_types(array(
			'public'   => true,
		));

		if ((array_key_exists ($curScreenId, $post_types) || $curScreenId == 'settings_page_iasemantify')) {
			wp_register_style( 'prefix_css_ia', 'https://cdn.jsdelivr.net/gh/semantifyit/instant-annotator/css/instantAnnotations.css' );
			wp_enqueue_style( 'prefix_css_ia' );

			wp_register_style( 'prefix_css_ia_plugin', $path . 'css/ia_plugin.css');
			wp_enqueue_style( 'prefix_css_ia_plugin' );
		}
	}
    

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in iasemantify_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The iasemantify_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        $path = plugin_dir_url(__FILE__ );

        wp_enqueue_script( $this->plugin_name, $path . 'js/iasemantify-admin.js', array( 'jquery' ), $this->version, false );
        
		$curScreenId = get_current_screen()->id;
		$post_types = get_post_types(array(
			'public'   => true,
		));

		if (array_key_exists ($curScreenId, $post_types) || $curScreenId == 'settings_page_iasemantify') {
			wp_register_script( 'prefix_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.js' );
			wp_enqueue_script( 'prefix_bootstrap', array( 'jquery' ) );

			wp_register_script( 'prefix_instantannotation', 'https://cdn.jsdelivr.net/gh/semantifyit/instant-annotator/dist/instantAnnotation.bundle.js', array( 'jquery' ), time() );
			//wp_register_script( 'prefix_instantannotation', 'http://localhost:8080/main.js', array( 'jquery' ), time() );

			wp_localize_script( 'prefix_instantannotation', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
			wp_enqueue_script( 'prefix_instantannotation' );
			
		}
		if ( $curScreenId == 'settings_page_iasemantify') {
			wp_register_script( 'prefix_login', $path . 'js/iasemantify-login.js', array( 'jquery' ), time()  );
			wp_enqueue_script( 'prefix_login' );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */

	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 */
		add_options_page( 'Instant Annotation Settings', 'Instant Annotation', 'manage_options', $this->plugin_name, array(
				$this,
				'display_plugin_setup_page'
			)
		);

		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				$this->plugin_name, // $id
				__( 'Instant Annotations' ), // $title
				array( $this, 'meta_boxes_display' ), // $callback
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */

	public function add_action_links( $links ) {
		/*
		*  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
		*/
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', $this->plugin_name ) . '</a>',
		);

		return array_merge( $settings_link, $links );

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */

	public function display_plugin_setup_page() {
		include_once( 'partials/iasemantify-admin-display.php' );
	}


	public function validate( $input ) {
		$valid           = array();
		$valid['websiteUID'] = $input['websiteUID'];
		$valid['websiteSecret'] = $input['websiteSecret'];
		return $valid;
	}

	public function options_update() {
		register_setting( $this->plugin_name, $this->plugin_name, array( $this, 'validate' ) );
	}

	function meta_boxes_display( $post ) {
		include_once 'partials/meta_boxes_display.php';
	}

	function iasemantify_get_default_ann_by_id_cb(){
	    if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
    			exit("No naughty business please");
    		}
    	$post_id = $_REQUEST["postid"];
        $response=new \stdClass();
    	$response->data=get_post_meta( $post_id, $this->plugin_name . "_ann_id", true );
    	$response->id=$post_id;
        $responseJSON = json_encode($response);
        wp_send_json( $responseJSON );
    	wp_die();
	}


    function iasemantify_migrate_annotations_get_cb(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
            exit("No naughty business please");
        }

        $ia_smtfy_base_url= 'https://semantify.it/';

        $annotations = $_REQUEST["annotations"];
        $uid = $_REQUEST["new_web_uid"];
        $secret = $_REQUEST["new_web_secret"];

        $secret= json_decode(json_encode(($secret)));
        $uid= json_decode(json_encode(($uid)));
        $result=[];
        foreach ($annotations as $a) {

            $ann=json_encode($a);
            $ann=json_decode($ann);
            $ann_uid=$ann->annotation_uid;

            $post_id=$ann->post_id;
            $hash=$ann->domainSpecification_hash;

            $urlGet=$ia_smtfy_base_url . "api/annotation/short/" . $ann_uid;
            $res = file_get_contents($urlGet);
            if($res == false){
                array_push($result,null);
            }else{
                $temp=new \stdClass();
                $temp->data=$res;
                $temp->ann_uid=$ann_uid;
                $temp->post_id=$post_id;
                $temp->hash=$hash;
                $temp->uid=$uid;
                $temp->secret=$secret;
                array_push($result,$temp);
            }

        }
        $responseJSON = json_encode($result);
        wp_send_json($responseJSON);
        wp_die();
    }

    function iasemantify_migrate_annotations_cleanup_cb()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], "my_user_vote_nonce")) {
            exit("No naughty business please");
        }


        $data=$_REQUEST["data"];
        foreach ($data as $d) {
            $dat=json_encode($d);
            $dat=json_decode($dat);
            //todo: delete from semantify default?
            $new_uid=$dat->new->UID;
            $old_uid=$dat->old->ann_uid;
            $ds_hash=$dat->old->hash;
            $post_id=$dat->old->post_id;
            $new_webs_uid=$dat->old->uid;
            $new_webs_secret=$dat->old->secret;

            //INSERT NEW
            $previousAnnotations = get_post_meta($post_id, $this->plugin_name . "_ann_id", true);
            $newAnnotations = $previousAnnotations . "," . $new_uid . ';' . $ds_hash . ';' . $new_webs_uid . ';' . $new_webs_secret;

            update_post_meta($post_id, $this->plugin_name . "_ann_id", $newAnnotations);

            //DELETE OLD
            $previousAnnotations = get_post_meta($post_id, $this->plugin_name . "_ann_id", true);
            $newAnnotations = str_replace("," . $old_uid . ';' . $ds_hash . ';Hkqtxgmkz;ef0a64008d0490fc4764c2431ca4797b', "", $previousAnnotations);

            update_post_meta($post_id, $this->plugin_name . "_ann_id", $newAnnotations);

            wp_die();


        }

    }




	function iasemantify_push_ann_cb(){
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
			exit("No naughty business please");
		}

		$previousAnnotations = get_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", true);
		$newAnnotations = $previousAnnotations . "," . $_REQUEST["ann_id"] . ';' . $_REQUEST["ds_hash"] . ';' . $_REQUEST["web_id"] . ';' . $_REQUEST["web_secret"];

		update_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", $newAnnotations);

		wp_die();
	}

	function iasemantify_multi_push_ann_cb(){
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
			exit("No naughty business please");
		}

		$previousAnnotations = get_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", true);

		$ann_ids = explode(",", $_REQUEST["ann_ids"]);
		$ds_hashes = explode(",", $_REQUEST["ds_hashes"]);


		$newAnnotations = $previousAnnotations;

		for ($i = 0; $i < count($ann_ids); $i++) {
			$newAnnotations = $newAnnotations . "," . $ann_ids[$i] . ';' . $ds_hashes[$i] . ';' . $_REQUEST["web_id"] . ';' . $_REQUEST["web_secret"];
		}

		update_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", $newAnnotations);

		wp_die();
	}

	function iasemantify_delete_ann_cb(){
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
			exit("No naughty business please");
		}

		$previousAnnotations = get_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", true);
		$newAnnotations = str_replace("," . $_REQUEST["ann_id"] . ';' . $_REQUEST["ds_hash"] . ';' . $_REQUEST["web_id"] . ';' . $_REQUEST["web_secret"], "", $previousAnnotations);

		update_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", $newAnnotations);

		wp_die();
	}

	function iasemantify_reset_page_cb(){
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
			exit("No naughty business please");
		}

		//update_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id", "");
		delete_post_meta($_REQUEST["post_id"], $this->plugin_name . "_ann_id");
		wp_die();
	}

	function iasemantify_setting_url_injection_cb(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
            exit("No naughty business please");
        }
        update_option('iasemantify_setting_url_injection', $_REQUEST['checked'] );

        wp_die();
    }

    function iasemantify_reset_all_cb() {
	    if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
		    exit("No naughty business please");
	    }
	    $this->write_log('resetting');
	    delete_post_meta_by_key($this->plugin_name . "_ann_id");
	    // just for old installations maybe?
	    delete_post_meta_by_key("ia" . "_ann_id");
	    $this->write_log('reset');
	    wp_die();
    }
}
