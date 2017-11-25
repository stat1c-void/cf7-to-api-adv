<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class QS_CF7_api_admin{


    /**
     * Holds the plugin options
     * @var [type]
     */
    private $options;

    /**
     * Holds athe admin notices class
     * @var [QS_Admin_notices]
     */
    private $admin_notices;

    /**
     * PLugn is active or not
     */
    private $plugin_active;


    public function __construct(){

        $this->admin_notices = new QS_Admin_notices();

        $this->register_hooks();

    }
    /**
     * Check if contact form 7 is active
     * @return [type] [description]
     */
    public function verify_dependencies(){
        if( ! is_plugin_active('contact-form-7/wp-contact-form-7.php') ){
            $notice = array(
                'id' => 'cf7-not-active',
                'type' => 'warning',
                'notice' => __( 'Contact form 7 api integrations requires CONTACT FORM 7 Plugin to be installed and active' ,'qs-cf7-api' ),
                'dismissable_forever' => false
            );

            $this->admin_notices->wp_add_notice( $notice );
        }
    }
    /**
     * Registers the required admin hooks
     * @return [type] [description]
     */
    public function register_hooks(){
        /**
         * Check if required plugins are active
         * @var [type]
         */
        add_action( 'admin_init', array( $this, 'verify_dependencies' ) );

        /*before sending email to user actions */
        add_action( 'wpcf7_before_send_mail', array( $this , 'qs_cf7_send_data_to_api' ) );

        /* adds another tab to contact form 7 screen */
        add_filter( "wpcf7_editor_panels" ,array( $this , "add_integrations_tab" ) , 1 , 1 );

        /* actions to handle while saving the form */
        add_action( "wpcf7_save_contact_form" ,array( $this , "qs_save_contact_form_details") , 10 , 1 );

        add_filter( "wpcf7_contact_form_properties" ,array( $this , "add_sf_properties" ) , 10 , 2 );
    }

    /**
     * Sets the form additional properties
     * @param [type] $properties   [description]
     * @param [type] $contact_form [description]
     */
    function add_sf_properties( $properties , $contact_form ){

        //add mail tags to allowed properties
        $properties["wpcf7_api_data"] = isset($properties["wpcf7_api_data"]) ? $properties["wpcf7_api_data"] : array();
        $properties["wpcf7_api_data_map"] = isset($properties["wpcf7_api_data_map"]) ? $properties["wpcf7_api_data_map"] : array();

        return $properties;
    }

    /**
     * Adds a new tab on conract form 7 screen
     * @param [type] $panels [description]
     */
    function add_integrations_tab($panels){

        $integration_panel = array(
            'title' => __( 'API Integration' , 'qs-cf7-api' ),
            'callback' => array( $this, 'wpcf7_integrations' )
        );

        $panels["qs-cf7-api-integration"] = $integration_panel;

        return $panels;

    }

    /**
     * The admin tab display, settings and instructions to the admin user
     * @param  [type] $post [description]
     * @return [type]       [description]
     */
    function wpcf7_integrations( $post ) {

        $wpcf7_api_data                = $post->prop( 'wpcf7_api_data' );
        $wpcf7_api_data_map            = $post->prop( 'wpcf7_api_data_map' );

        $mail_tags                     = apply_filters( 'qs_cf7_collect_mail_tags' , $post->collect_mail_tags(array("exclude"=>array("all-fields"))) );

        $wpcf7_api_data["base_url"]    = isset( $wpcf7_api_data["base_url"] ) ? $wpcf7_api_data["base_url"]         : '';
        $wpcf7_api_data["send_to_api"] = isset( $wpcf7_api_data["send_to_api"] ) ? $wpcf7_api_data["send_to_api"]   : '';
        $wpcf7_api_data["method"]      = isset( $wpcf7_api_data["method"] ) ? $wpcf7_api_data["method"]             : 'GET';
        $wpcf7_api_data["debug_log"]   = isset( $wpcf7_api_data["debug_log"] ) ? $wpcf7_api_data["debug_log"]       : false;
        $debug_url                     = get_option( 'qs_cf7_api_debug_url' );
        $debug_result                  = get_option( 'qs_cf7_api_debug_result' );
        $debug_params                  = get_option( 'qs_cf7_api_debug_params' );
        ?>


        <h2><?php echo esc_html( __( 'API Integration', 'qs-cf7-api' ) ); ?></h2>

        <fieldset>
            <?php do_action( 'before_base_fields' , $post ); ?>

            <div class="cf7_row">

                <label for="wpcf7-sf-send_to_api">
                    <input type="checkbox" id="wpcf7-sf-send_to_api" name="wpcf7-sf[send_to_api]" <?php checked( $wpcf7_api_data["send_to_api"] , "on" );?>/>
                    <?php _e( 'Send to api ?' , 'qs-cf7-api' );?>
                </label>

            </div>

            <div class="cf7_row">
                <label for="wpcf7-sf-base_url">
                    <?php _e( 'Base url' , 'qs-cf7-api' );?>
                    <input type="text" id="wpcf7-sf-base_url" name="wpcf7-sf[base_url]" class="large-text" value="<?php echo $wpcf7_api_data["base_url"];?>" />
                </label>
            </div>

            <div class="cf7_row">
                <label for="wpcf7-sf-method">
                    <?php _e( 'Method' , 'qs-cf7-api' );?>
                    <select id="wpcf7-sf-base_url" name="wpcf7-sf[method]">
                        <option value="GET" <?php selected( $wpcf7_api_data["method"] , 'GET');?>>GET</option>
                        <option value="POST" <?php selected( $wpcf7_api_data["method"] , 'POST');?>>POST</option>
                    </select>
                </label>
            </div>

            <?php do_action( 'after_base_fields' , $post ); ?>
        </fieldset>

        <h2><?php echo esc_html( __( 'Form fields', 'qs-cf7-api' ) ); ?></h2>

        <fieldset>
            <table>
                <tr>
                    <th><?php _e( 'Form fields' , 'qs-cf7-api' );?></th>
                    <th><?php _e( 'API Key' , 'qs-cf7-api' );?></th>
                </tr>

            <?php foreach( $mail_tags as $mail_tag) :?>

                <tr>
                    <th style="text-align:left;"><?php echo $mail_tag;?></th>
                    <td><input type="text" id="sf-<?php echo $mail_tag;?>" name="qs_wpcf7_api_map[<?php echo $mail_tag;?>]" class="large-text" value="<?php echo isset($wpcf7_api_data_map[$mail_tag]) ? $wpcf7_api_data_map[$mail_tag] : "";?>" /></td>
                </tr>

            <?php endforeach;?>

            </table>

            <label>
                <input type="checkbox" value="1" name="wpcf7-sf[debug_log]" <?php checked( $wpcf7_api_data['debug_log'] , '1' );?> />
                <?php _e( 'DEBUG LOG' , 'qs-cf7-api');?>
            </label>

            <?php if( $wpcf7_api_data['debug_log'] ):?>
                <h3 class="debug_log_title"><?php _e( 'LAST API CALL' , 'qs-cf7-api' );?></h3>
                <div class="debug_log">
                    <h4><?php _e( 'Called url' , 'qs-cf7-api' );?>:</h4>
                    <pre><?php echo trim(esc_attr( $debug_url ));?></pre>
                    <h4><?php _e( 'Params' , 'qs-cf7-api' );?>:</h4>
                    <pre><?php print_r( $debug_params );?></pre>
                    <h4><?php _e( 'Remote server result' , 'qs-cf7-api' );?>:</h4>
                    <pre><?php print_r( $debug_result );?></pre>
                </textarea>
            <?php endif;?>
        </fieldset>

        <?php

    }

   /**
     * Saves the API settings
     * @param  [type] $contact_form [description]
     * @return [type]               [description]
     */
    public function qs_save_contact_form_details( $contact_form ){

        $properties = $contact_form->get_properties();


        $properties['wpcf7_api_data']     = $_POST["wpcf7-sf"];
        $properties['wpcf7_api_data_map'] = $_POST["qs_wpcf7_api_map"];


       $contact_form->set_properties( $properties );


    }

    /**
     * The handler that will send the data to the api
     * @param  [type] $WPCF7_ContactForm [description]
     * @return [type]                    [description]
     */
    public function qs_cf7_send_data_to_api( $WPCF7_ContactForm ) {
        $submission = WPCF7_Submission::get_instance();

       $url         = $submission->get_meta( 'url' );

       $qs_cf7_data     = $WPCF7_ContactForm->prop( 'wpcf7_api_data' );
        $qs_cf7_data_map = $WPCF7_ContactForm->prop( 'wpcf7_api_data_map' );

        /* check if the form is marked to be sent via API */
        if( isset( $qs_cf7_data["send_to_api"] ) && $qs_cf7_data["send_to_api"] == "on" ){
            $record = $this->get_record( $submission , $qs_cf7_data_map );
            $record["url"]                = $qs_cf7_data["base_url"];

            if( isset( $record["url"] ) && $record["url"] ){

                do_action( 'qs_cf7_api_before_sent_to_api' , $record );

                $response = $this->send_lead( $record , $qs_cf7_data['debug_log'] , $qs_cf7_data['method']);

                do_action( 'qs_cf7_api_after_sent_to_api' , $record , $response );
            }
        }

    }
    /**
     * Convert the form keys to the API keys according to the mapping instructions
     * @param  [type] $submission      [description]
     * @param  [type] $qs_cf7_data_map [description]
     * @return [type]                  [description]
     */
    function get_record( $submission , $qs_cf7_data_map){

        $submited_data = $submission->get_posted_data();
        $record = array();

        foreach( $qs_cf7_data_map as $form_key => $qs_cf7_form_key){

            if( $qs_cf7_form_key ){

                $value = isset($submited_data[$form_key]) ? $submited_data[$form_key] : "";

                if( is_array( $value ) ){
                    $record["fields"][$qs_cf7_form_key] = $value ? true : false;
                }else{
                    $record["fields"][$qs_cf7_form_key] = apply_filters( 'set_record_value' , $value , $qs_cf7_form_key);
                }
            }

        }

        return $record;
    }
    /**
     * Send the lead using wp_remote
     * @param  [type]  $record [description]
     * @param  boolean $debug  [description]
     * @param  string  $method [description]
     * @return [type]          [description]
     */
    private function send_lead( $record , $debug = false , $method = 'GET' ){
        global $wp_version;

        $lead = $record["fields"];
        $url  = $record["url"];

        if( $method == 'GET' ){
            $args = array(
                'timeout'     => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
                'blocking'    => true,
                'headers'     => array(),
                'cookies'     => array(),
                'body'        => null,
                'compress'    => false,
                'decompress'  => true,
                'sslverify'   => true,
                'stream'      => false,
                'filename'    => null
            );

            $args        = apply_filters( 'qs_cf7_api_get_args' , $args , $record );
            $lead_string = http_build_query( $lead );
            $url         = strpos( '?' , $url ) ? $url.'&'.$lead_string : $url.'?'.$lead_string;

            $url         = apply_filters( 'qs_cf7_api_get_url' , $url, $record );

            $result = wp_remote_get( $url , $args );

        }else{
            $args = array(
                'timeout'     => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
                'blocking'    => true,
                'headers'     => array(),
                'cookies'     => array(),
                'body'        => $lead,
                'compress'    => false,
                'decompress'  => true,
                'sslverify'   => true,
                'stream'      => false,
                'filename'    => null
            );

            $args   = apply_filters( 'qs_cf7_api_get_args' , $args );

            $url    = apply_filters( 'qs_cf7_api_post_url' , $url );

            $result = wp_remote_post( $url , $args );
			
			$result['body'] = strip_tags( $result['body'] );
        }

        if( $debug ){
            update_option( 'qs_cf7_api_debug_url' , $record["url"] );
            update_option( 'qs_cf7_api_debug_params' , $lead );
            update_option( 'qs_cf7_api_debug_result' , $result );
        }
        return do_action('after_qs_cf7_api_send_lead' , $result , $record );

    }

}
