<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


class WPCF7_api_adv_admin
{
    public function __construct()
    {
        $this->register_hooks();
    }

    /**
     * Check if contact form 7 is active
     */
    public function verify_dependencies()
    {
        if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            add_action('admin_notices', array($this, 'wpcf7_api_adv_notice_cf7_not_active'));
        }
    }

    /**
     * admin_notices action: display warning
     */
    function wpcf7_api_adv_notice_cf7_not_active()
    {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('"Contact Form 7 To API Advanced" integrations requires "Contact Form 7" plugin to be installed and active', 'wpcf7-api-adv'); ?></p>
        </div>
        <?php
    }

    /**
     * Registers the required admin hooks
     * @return [type] [description]
     */
    public function register_hooks()
    {
        // Check if required plugins are active
        add_action('admin_init', array($this, 'verify_dependencies'));
        // before sending email to user actions
        add_action('wpcf7_before_send_mail', array($this, 'wpcf7_send_data_to_api'));
        // adds another tab to contact form 7 screen
        add_filter("wpcf7_editor_panels", array($this, "add_integrations_tab"), 1, 1);
        // actions to handle while saving the form
        add_action("wpcf7_save_contact_form", array($this, "save_contact_form_details"), 10, 1);

        add_filter("wpcf7_contact_form_properties", array($this, "add_sf_properties"), 10, 2);
    }

    /**
     * Sets the form additional properties
     * @param [type] $properties   [description]
     * @param [type] $contact_form [description]
     */
    function add_sf_properties($properties, $contact_form)
    {
        $properties["wpcf7_api_adv_data"] = isset($properties["wpcf7_api_adv_data"]) ? $properties["wpcf7_api_adv_data"] : array();
        return $properties;
    }

    /**
     * Adds a new tab on conract form 7 screen
     * @param [type] $panels [description]
     */
    function add_integrations_tab($panels)
    {
        $integration_panel = array(
            'title' => __('API Integration', 'wpcf7-api-adv'),
            'callback' => array($this, 'wpcf7_integrations')
        );

        $panels["wpcf7-api-adv"] = $integration_panel;
        return $panels;
    }

    /**
     * The admin tab display, settings and instructions to the admin user
     * @param  [type] $post [description]
     * @return [type]       [description]
     */
    function wpcf7_integrations($post)
    {
        $wpcf7_api_data = $post->prop('wpcf7_api_adv_data');

        $mail_tags = apply_filters('wpcf7_api_adv_collect_mail_tags', $post->collect_mail_tags(array("exclude" => array("all-fields"))));

        $wpcf7_api_data["base_url"] = isset($wpcf7_api_data["base_url"]) ? $wpcf7_api_data["base_url"] : '';
        $wpcf7_api_data["send_to_api"] = isset($wpcf7_api_data["send_to_api"]) ? !!$wpcf7_api_data["send_to_api"] : false;
        $wpcf7_api_data["stop_email"] = isset($wpcf7_api_data["stop_email"]) ? !!$wpcf7_api_data["stop_email"] : false;
        $wpcf7_api_data["method"] = isset($wpcf7_api_data["method"]) ? $wpcf7_api_data["method"] : 'GET';
        $wpcf7_api_data["query_headers"] = isset($wpcf7_api_data["query_headers"]) ? $wpcf7_api_data["query_headers"] : '';
        $wpcf7_api_data["query_body"] = isset($wpcf7_api_data["query_body"]) ? $wpcf7_api_data["query_body"] : '?foo=bar';
        $wpcf7_api_data["debug_log"] = isset($wpcf7_api_data["debug_log"]) ? !!$wpcf7_api_data["debug_log"] : false;

        $debug_url = get_option('wpcf7_api_adv_debug_url');
        $debug_result = get_option('wpcf7_api_adv_debug_result');
        $debug_params = get_option('wpcf7_api_adv_debug_params');
        ?>


        <h2><?php _e('API Integration', 'wpcf7-api-adv') ?></h2>

        <fieldset>
            <?php do_action('before_base_fields', $post); ?>

            <div class="cf7_row">
                <label for="wpcf7-sf-send_to_api">
                    <input type="checkbox" id="wpcf7-sf-send_to_api"
                           name="wpcf7-sf[send_to_api]" <?php checked($wpcf7_api_data["send_to_api"]) ?>/>
                    <?php _e('Send to API', 'wpcf7-api-adv') ?>
                </label>
            </div>

            <div class="cf7_row">
                <label for="wpcf7-sf-stop_email">
                    <input type="checkbox" id="wpcf7-sf-stop_email"
                           name="wpcf7-sf[stop_email]" <?php checked($wpcf7_api_data["stop_email"]) ?>/>
                    <?php _e('Do not send an e-mail', 'wpcf7-api-adv') ?>
                </label>
                <p class="description"><?php _e('If enabled, e-mails will not be sent upon form submission.') ?></p>
            </div>

            <div class="cf7_row">
                <label for="wpcf7-sf-base_url">
                    <?php _e('Base URL', 'wpcf7-api-adv'); ?>
                    <input type="text" id="wpcf7-sf-base_url" name="wpcf7-sf[base_url]" class="large-text"
                           value="<?php echo $wpcf7_api_data["base_url"]; ?>"/>
                </label>
            </div>

            <div class="cf7_row">
                <label for="wpcf7-sf-method">
                    <?php _e('Method', 'wpcf7-api-adv'); ?>
                    <select id="wpcf7-sf-method" name="wpcf7-sf[method]">
                        <option value="GET" <?php selected($wpcf7_api_data["method"], 'GET'); ?>>GET</option>
                        <option value="POST" <?php selected($wpcf7_api_data["method"], 'POST'); ?>>POST</option>
                    </select>
                </label>
            </div>

            <div class="cf7_row">
                <label for="wpcf7-sf-debug_log">
                    <input type="checkbox" id="wpcf7-sf-debug_log"
                           name="wpcf7-sf[debug_log]" <?php checked($wpcf7_api_data["debug_log"]) ?>/>
                    <?php _e('Debug log', 'wpcf7-api-adv'); ?>
                </label>
                <p class="description"><?php _e('If enabled, last API call result would be saved and displayed on this page below.') ?></p>
            </div>

            <?php do_action('after_base_fields', $post); ?>
        </fieldset>

        <h2><?php _e('Query Parameters', 'wpcf7-api-adv') ?></h2>

        <fieldset>
            <div class="cf7_row">
                <label for="wpcf7-sf-query_headers">
                    <?php _e('Query headers', 'wpcf7-api-adv') ?>
                    <textarea id="wpcf7-sf-query_headers" name="wpcf7-sf[query_headers]" cols="100" rows="4"
                        class="large-text code"><?php echo esc_html($wpcf7_api_data["query_headers"]) ?></textarea>
                </label>
            </div>

            <p class="description">
                <?php esc_html_e('Newline-delimited HTTP headers, usual HTTP format, e.g. Header: Header-Content.', 'wpcf7-api-adv') ?>
            </p>

            <div class="cf7_row">
                <label for="wpcf7-sf-query_body">
                    <?php _e('Query body', 'wpcf7-api-adv') ?>
                    <textarea id="wpcf7-sf-query_body" name="wpcf7-sf[query_body]" cols="100" rows="8"
                        class="large-text code"><?php echo esc_html($wpcf7_api_data["query_body"]) ?></textarea>
                </label>
            </div>

            <p class="description">
                <?php esc_html_e('If you use GET method, query body will be appended to the URL, use correct query delimeters like ? and &.', 'wpcf7-api-adv') ?>
                <br />
                <?php esc_html_e('You can use the following template tags (content will be url-encoded, dangerous characters stripped):') ?><br />
                [<?php echo join(']&nbsp;&nbsp;[', $mail_tags) ?>]
            </p>
        </fieldset>

        <?php if ($wpcf7_api_data['debug_log']): ?>
        <fieldset>
            <h3 class="debug_log_title"><?php _e('LAST API CALL', 'wpcf7-api-adv') ?></h3>

            <div class="debug_log">
                <h4><?php _e('Called URL', 'wpcf7-api-adv') ?>:</h4>
                <pre><?php echo trim(esc_attr($debug_url)) ?></pre>
                <h4><?php _e('Params', 'wpcf7-api-adv') ?>:</h4>
                <pre><?php print_r($debug_params) ?></pre>
                <h4><?php _e('Remote server result', 'wpcf7-api-adv') ?>:</h4>
                <pre><?php print_r($debug_result) ?></pre>
            </div>
        </fieldset>
        <?php endif;

    }

    /**
     * Saves the API settings
     * @param  [type] $contact_form [description]
     * @return [type]               [description]
     */
    public function save_contact_form_details($contact_form)
    {
        $properties = $contact_form->get_properties();
        $properties['wpcf7_api_adv_data'] = $_POST['wpcf7-sf'];
        $contact_form->set_properties($properties);
    }

    /**
     * The handler that will send the data to the api
     */
    public function wpcf7_send_data_to_api($WPCF7_ContactForm)
    {
        $wpcf7_data = $WPCF7_ContactForm->prop('wpcf7_api_adv_data');

        /* check if the form is marked to be sent via API */
        if (isset($wpcf7_data['send_to_api']) && $wpcf7_data['send_to_api']) {
            $record = array();
            $record['url'] = $wpcf7_data['base_url'];
            $record['query_body'] = $this->process_query_body(WPCF7_Submission::get_instance(), $wpcf7_data['query_body']);
            $record['query_headers'] = $wpcf7_data['query_headers'];

            if (isset($record["url"]) && $record["url"]) {
                do_action('wpcf7_api_adv_before_sent_to_api', $record);
                $response = $this->send_lead($record, $wpcf7_data['debug_log'], $wpcf7_data['method']);
                do_action('wpcf7_api_adv_after_sent_to_api', $record, $response);
            }

            // Skip sending mail if needed
            if ($wpcf7_data['stop_email']) {
                add_filter('wpcf7_skip_mail', '__return_true');
            }

            // If API call resulted in error
            if (is_wp_error($response)) {
                // Change the output message to an error
                // We need this because skipped mail always succeeds
                // Because sending mail is the last step, we can be sure that no validation errors, etc are overwritten
                // And if mail is not skipped, form still fails if API call fails
                add_filter('wpcf7_form_response_output', array($this, 'wpcf7_form_response_to_error'), 10, 4);
                add_filter('wpcf7_ajax_json_echo', array($this, 'wpcf7_rest_create_feedback_to_error'), 10, 2);
            }
        }
    }

    /**
     * Processes the query body - replace form tags with content
     */
    function process_query_body($submission, $query_body)
    {
        $submitted_data = $submission->get_posted_data();

        // Iterating submitted form data
        foreach ($submitted_data as $key => $value) {
            // Safety stripping
            $value = preg_replace('/\s*[^\w@\.,()\-\+]+\s*/u', ' ', $value);
            // Replacing query_body tags with content
            $query_body = preg_replace("/\[$key\]/i", $value, $query_body);
        }

        return $query_body;
    }

    /**
     * Send the lead using wp_remote
     * @param  [type]  $record [description]
     * @param  boolean $debug [description]
     * @param  string $method [description]
     * @return [type]          [description]
     */
    private function send_lead($record, $debug = false, $method = 'GET')
    {
        global $wp_version;

        $url = $record['url'];
        $query_body = $record['query_body'];

        $args = array(
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
            'blocking' => true,
            'headers' => array(),
            'cookies' => array(),
            'body' => null,
            'compress' => false,
            'decompress' => true,
            'sslverify' => true,
            'stream' => false,
            'filename' => null
        );

        // Query headers processing
        $query_headers = $record['query_headers'];
        if ($query_headers) {
            preg_match_all('/([[:graph:]]+)\s*:\s*([[:graph:] ]+)\s*/', $query_headers, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $args['headers'][$match[1]] = $match[2];
            }
        }

        if ($method == 'GET') {
            $args = apply_filters('wpcf7_api_adv_get_args', $args, $record);
            $url .= $query_body;
            $url = apply_filters('wpcf7_api_adv_get_url', $url, $record);
            $result = wp_remote_get($url, $args);
        } else {
            $args['body'] = $query_body;
            $args = apply_filters('wpcf7_api_adv_post_args', $args);
            $url = apply_filters('wpcf7_api_adv_post_url', $url);
            $result = wp_remote_post($url, $args);
        }

        if (!is_wp_error($result)) {
            $result['body'] = strip_tags($result['body']);
        }

        if ($debug) {
            update_option('wpcf7_api_adv_debug_url', $url);
            update_option('wpcf7_api_adv_debug_params', $args);
            update_option('wpcf7_api_adv_debug_result', $result);
        }

        return $result;
    }

    /*
     * Filter function for wpcf7_form_response_output.
     * Changes output to a generic error (fallback POST submit).
     */
    public function wpcf7_form_response_to_error($output, $class, $content, $WPCF7_ContactForm)
    {
        // Code here taken from WPCF7_ContactForm::form_response_output()
        $attrs = array(
            'class' => 'wpcf7-response-output wpcf7-mail-sent-ng',
            'role'  => 'alert',
        );
        $attrs = wpcf7_format_atts($attrs);
        $content = $WPCF7_ContactForm->message('mail_sent_ng');
        return sprintf('<div %1$s>%2$s</div>', $attrs, esc_html($content));
    }

    /*
     * Filter function for wpcf7_ajax_json_echo.
     * Changes output to a generic error (AJAX WP REST API).
     */
    public function wpcf7_rest_create_feedback_to_error($response, $result)
    {
        $response['status'] = 'mail_sent_ng';
        $WPCF7_ContactForm = WPCF7_ContactForm::get_current();
        $response['message'] = $WPCF7_ContactForm->message($response['status']);
        return $response;
    }
}