<?php
/**
 * Plugin Name: KISS Plugins - Contact Us Shortcode
 * Description: Provides a [contactus] shortcode and a settings page with a rich text editor for company's contact details. Allows shortcodes in classic and block-based widget areas.
 * Version: 1.0.4
 * Author: Hypercart
 * Author URI: https://kissplugins.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hypercart_ContactUs_Shortcode {

    private $option_name = 'hypercart_contactus_info';

    public function __construct() {
        // Hook to add admin menu (under Tools now)
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
    }

    /**
     * Add settings page under "Tools"
     */
    public function add_settings_page() {
        add_management_page(
            'Hypercart - Contact Us Shortcode', // Page title
            'Contact Us',                       // Menu title
            'manage_options',                   // Capability
            'hypercart-contactus-settings',     // Menu slug
            array($this, 'settings_page_html')  // Callback
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
            echo '<div class="updated"><p>Contact information saved successfully.</p></div>';
        }

        // Get the stored content
        $content = get_option($this->option_name, '');
        ?>
        <div class="wrap">
            <h1>Hypercart - Contact Us Shortcode</h1>
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

                wp_editor($content, 'hypercart_contactus_editor_id', $editor_settings);
                ?>
                <p>
                    <input type="submit" name="hypercart_contactus_submit" class="button button-primary" value="Save Changes">
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
            $output .= ' <a href="' . esc_url($edit_url) . '" style="font-size: 0.9em; text-decoration: underline;">Edit</a>';
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

new Hypercart_ContactUs_Shortcode();
