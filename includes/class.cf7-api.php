<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class WPCF7_api_adv
{
    /**
     * The plugin identifier.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name unique plugin id.
     */
    protected $plugin_name;

    /**
     * save the instance of the plugin for static actions.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $instance an instance of the class.
     */
    public static $instance;

    /**
     * a reference to the admin class.
     *
     * @since    1.0.0
     * @access   protected
     * @var      object
     */
    public $admin;

    /**
     * Define the plugin functionality.
     * set plugin name and version , and load dependencies
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->plugin_name = 'wpcf7-api-adv';
        $this->version = '1.0.0';
        $this->load_dependencies();

        // Create an instance of the admin class
        $this->admin = new WPCF7_api_adv_admin();
        $this->admin->plugin_name = $this->plugin_name;

        // save the instance for static actions
        self::$instance = $this;
    }

    public function init() {}

    /**
     * Loads the required plugin files
     */
    public function load_dependencies()
    {
        // admin main class
        require_once WPCF7_API_ADV_INCLUDES_PATH . 'class-cf7-api-admin.php';
    }

    /**
     * Get the current plugin instance
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}