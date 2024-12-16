<?php
/**
 * Plugin Name: Hypercart - Contact Us Shortcode - O1
 * Description: Provides a [contactus] shortcode and a settings page with a rich text editor for company's contact details. Pre-populates from WooCommerce store address on first install. Allows shortcodes in classic and block-based widget areas.
 * Version: 1.0.6
 * Author: Hypercart
 * Author URI: https://kissplugins.com
 * Text Domain: hypercart-contactus
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hypercart_ContactUs_Shortcode {

    private $option_name = 'hypercart_contactus_info';

    public function __construct() {
        // Load plugin text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Hook to add admin menu (under Tools)
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Register shortcode
        add_shortcode('contactus', array($this, 'render_contact_info'));

        // Register plugin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Enable shortcodes in classic text widgets
        add_filter('widget_text', 'do_shortcode');
        add_filter('widget_text_content', 'do_shortcode');

        // Enable shortcodes in block-based widgets (WP 5.8+)
        add_filter('widget_block_content', array($this, 'enable_shortcodes_in_block_widgets'), 10, 3);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_settings_link'));
    }

    public function plugin_settings_link($links) {
        $settings_link = '<a href="/wp-admin/tools.php?page=hypercart-contactus-settings">' . __('Settings', 'hypercart-contactus') . '</a>';
        $links['settings'] = $settings_link; // Add the settings link at the end
        return $links;
    }

    /**
     * Load plugin text domain for i18n
     */
    public function load_textdomain() {
        load_plugin_textdomain('hypercart-contactus', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Runs on plugin activation
     */
    public static function activate_plugin() {
        $instance = new self();
        $current_content = get_option($instance->option_name, '');

        // Only populate if empty
        if (empty($current_content)) {
            // Check if WooCommerce is active
            if (class_exists('WooCommerce')) {
                $business_name = get_bloginfo('name');
                $address1 = get_option('woocommerce_store_address', '');
                $address2 = get_option('woocommerce_store_address_2', '');
                $city     = get_option('woocommerce_store_city', '');
                $postcode = get_option('woocommerce_store_postcode', '');
                $default_country = get_option('woocommerce_default_country', '');
                
                $country = $default_country;
                $state = '';
                if (strpos($default_country, ':') !== false) {
                    list($country, $state) = explode(':', $default_country);
                }
                
                // Assemble address format:
                // Business Name
                // Address line 1
                // Address line 2 (if exists)
                // City, State, ZIP/Postal Code
                // Country
                $formatted = $business_name . "\n" .
                             $address1 . "\n";
                if (!empty($address2)) {
                    $formatted .= $address2 . "\n";
                }
                
                $line3_parts = array_filter([$city, $state, $postcode]);
                $formatted .= implode(', ', $line3_parts) . "\n" . $country;
                
                // Update only if we have at least some meaningful data
                if (trim($formatted) !== '') {
                    update_option($instance->option_name, wp_kses_post($formatted));
                }
            }
        }
    }

    /**
     * Add settings page under "Tools"
     */
    public function add_settings_page() {
        add_management_page(
            __('Hypercart - Contact Us Shortcode', 'hypercart-contactus'), // Page title
            __('Contact Us', 'hypercart-contactus'),                       // Menu title
            'manage_options',                                              // Capability
            'hypercart-contactus-settings',                                // Menu slug
            array($this, 'settings_page_html')                             // Callback
        );
    }

    /**
     * Output the HTML for the settings page
     */
    public function settings_page_html() {
        if ( ! current_user_can('manage_options') ) {
            return;
        }

        // Check if the form is submitted
        if ( isset($_POST['hypercart_contactus_submit']) && check_admin_referer('hypercart_contactus_save', 'hypercart_contactus_nonce') ) {
            $raw_content = isset($_POST['hypercart_contactus_editor']) ? wp_unslash($_POST['hypercart_contactus_editor']) : '';
            $content = wp_kses_post($raw_content);
            update_option($this->option_name, $content);
            echo '<div class="updated"><p>' . __('Contact information saved successfully.', 'hypercart-contactus') . '</p></div>';
        }

        // Get the stored content
        $content = get_option($this->option_name, '');
        ?>
        <div class="wrap">
            <h1><?php _e('Hypercart - Contact Us Shortcode', 'hypercart-contactus'); ?></h1>
            <form method="post" action="">
                <?php
                wp_nonce_field('hypercart_contactus_save', 'hypercart_contactus_nonce');

                $editor_settings = array(
                    'textarea_name' => 'hypercart_contactus_editor',
                    'media_buttons' => true,
                    'teeny'         => false,
                    'quicktags'     => true,
                    'tinymce'       => array(
                        'toolbar1' => 'formatselect,bold,italic,link,unlink,bullist,numlist,blockquote,alignleft,aligncenter,alignright,spellchecker,wp_adv',
                        'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                    ),
                );

                // The editor content itself (user-generated) is not automatically translated. 
                // But the labels and buttons around it are translatable.
                wp_editor($content, 'hypercart_contactus_editor_id', $editor_settings);
                ?>
                <p>
                    <input type="submit" name="hypercart_contactus_submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'hypercart-contactus'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Register our settings (this ensures they are whitelisted)
     */
    public function register_settings() {
        register_setting('hypercart_contactus_settings_group', $this->option_name);
    }

    /**
     * Shortcode callback to render the contact info
     */
    public function render_contact_info() {
        $content = get_option($this->option_name, '');
        $output = wpautop($content);

        // Only append edit link for admin/editor users
        if ( is_user_logged_in() && ( current_user_can('manage_options') || current_user_can('edit_pages') ) ) {
            $edit_url = admin_url('tools.php?page=hypercart-contactus-settings');
            $output .= ' <a href="' . esc_url($edit_url) . '" style="font-size: 0.9em; text-decoration: underline;">' . __('Edit', 'hypercart-contactus') . '</a>';
        }

        return $output;
    }

    /**
     * Allow shortcodes to run in block-based widgets
     */
    public function enable_shortcodes_in_block_widgets($content, $widget, $args) {
        return do_shortcode($content);
    }
}

// Initialize the class
new Hypercart_ContactUs_Shortcode();

// Run activation hook to pre-populate on first activation
register_activation_hook(__FILE__, array('Hypercart_ContactUs_Shortcode', 'activate_plugin'));
