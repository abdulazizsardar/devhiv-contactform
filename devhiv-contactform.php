<?php
/**
 * Plugin Name: Devhiv Contact Form
 * Plugin URI: /devhiv.com
 * Description: A contact form plugin with Name, Email, Phone Number, and Message fields.
 * Version: 1.0
 * Author: Devhiv
 * Author URI: /devhiv.com
 * Text Domain: devhiv-contactform
 * Domain Path: /languages
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('DevhivContactForm')) {
    class DevhivContactForm {
        public function __construct() {
            add_shortcode('devhiv_contactform', array($this, 'dhform_render_shortcode'));
            add_action('wp_enqueue_scripts', array($this, 'dhform_enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'dhform_enqueue_admin_scripts'));
            add_action('admin_menu', array($this, 'dhform_register_admin_menu'));
            add_action('admin_init', array($this, 'dhform_handle_admin_actions'));
            add_action('activated_plugin', array($this, 'dhform_redirect_on_activation'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'dhform_add_action_links'));

            add_action('wp_ajax_devhiv_contactform_edit_response', array($this, 'dhform_handle_ajax_edit_response'));
            add_action('wp_ajax_devhiv_contactform_delete_response', array($this, 'dhform_handle_ajax_delete_response'));

            register_activation_hook(__FILE__, array($this, 'activate'));
        }

        public function dhform_enqueue_scripts() {
            wp_enqueue_style('devhiv_contactform_style', plugins_url('/css/style.css', __FILE__));
        }
        public function dhform_enqueue_admin_scripts(){
            wp_enqueue_script('devhiv-contactform-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), null, true);
            wp_localize_script('devhiv-contactform-admin', 'devhivContactForm', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('devhiv_contactform_nonce')
            ));

        }

        // Ajax delete response handler
        public function dhform_handle_ajax_delete_response() {
            check_ajax_referer('devhiv_delete_response', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied.');
            }

            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                wp_send_json_error('Invalid item ID.');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'dh_cform_responses';
            $id = intval($_POST['id']);

            $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));

            if ($result) {
                wp_send_json_success('Response deleted successfully.');
            } else {
                wp_send_json_error('Failed to delete response.');
            }
        }

        public function dhform_handle_ajax_edit_response() {
        check_ajax_referer('devhiv_contactform_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }

        if (!isset($_POST['data'])) {
            wp_send_json_error('No data received.');
        }

        parse_str($_POST['data'], $data);

        global $wpdb;
        $table_name = $wpdb->prefix . 'dh_cform_responses';

        $id = intval($data['id']);
        $name = sanitize_text_field($data['name']);
        $email = sanitize_email($data['email']);
        $phone = sanitize_text_field($data['phone']);
        $message = sanitize_textarea_field($data['message']);

        $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
            ),
            array('id' => $id),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ),
            array('%d')
        );

        wp_send_json_success('Response updated successfully.');
    }

        public function dhform_redirect_on_activation($plugin) {
            if (plugin_basename(__FILE__) == $plugin) {
                wp_redirect(admin_url('admin.php?page=devhiv-contactform'));
                exit;
            }
        }
        public function dhform_add_action_links($links) {
            $link = sprintf("<a href='%s' style='color:#2324ff;'>%s</a>", admin_url('admin.php?page=devhiv-contactform'), __('Settings', 'devhiv-contactform'));
            array_push($links, $link);
            return $links;
        }

        public function dhform_register_admin_menu() {
            add_menu_page(
                __('Contact Form Responses', 'devhiv-contactform'),
                __('Contact Form', 'devhiv-contactform'),
                'manage_options',
                'devhiv-contactform',
                array($this, 'render_admin_dashboard')
            );
        }

        public function render_admin_dashboard() {
            include_once plugin_dir_path(__FILE__) . 'admin/admin-dashboard.php';
        }

       public function dhform_handle_admin_actions() {
            if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'devhiv-contactform') {
                return;
            }

            if (class_exists('Devhiv_ContactForm_List_Table')) {
                $wp_list_table = new Devhiv_ContactForm_List_Table();
                $wp_list_table->prepare_items();

                if ('delete' === $wp_list_table->current_action()) {
                    $wp_list_table->process_bulk_action();
                } elseif ('bulk-delete' === $wp_list_table->current_action()) {
                    $wp_list_table->process_bulk_action();
                }

                $wp_list_table->display();
            }
        }


        public function activate() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'dh_cform_responses';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(250),
                email VARCHAR(250),
                phone varchar(250),
                message varchar(250),
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        public function dhform_render_shortcode() {
            ob_start();
            $this->render_form();
            $this->handle_submission();
            return ob_get_clean();
        }

        private function render_form() {
            ?>
            <div class="dh-form-title">
            Tell us more about your project
            </div>
            <form id="devhiv-contactform" method="post" enctype="multipart/form-data">
                <p>
                    <label for="cf_name"><?php _e('Name*', 'devhiv-contactform'); ?></label>
                    <input type="text" id="cf_name" name="cf_name" required>
                </p>
                <p>
                    <label for="cf_email"><?php _e('Email*', 'devhiv-contactform'); ?></label>
                    <input type="email" id="cf_email" name="cf_email" required>
                </p>
                <p>
                    <label for="cf_phone"><?php _e("Website link ( leave blank if you don't have one )", 'devhiv-contactform'); ?></label>
                    <input type="tel" id="cf_phone" name="cf_phone" >
                </p>
                <p>
                    <label for="cf_message"><?php _e('A little bit about your project *', 'devhiv-contactform'); ?></label>
                    <textarea id="cf_message" name="cf_message" rows="12" required></textarea>
                </p>
                <p>
                    <input type="submit" name="cf_submitted" value="<?php _e('Get Started', 'devhiv-contactform'); ?>">
                </p>
            </form>
            <?php
            wp_nonce_field('devhiv_contactform_submit', 'devhiv_contactform_nonce');
        }

       
        private function handle_submission() {
            // Debug the form POST data
            // var_dump($_POST); // Uncomment to debug the form fields
            if (isset($_POST['cf_submitted'])) {
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
        
                // Validate the form fields before proceeding
                if (empty($_POST['cf_name']) || empty($_POST['cf_email']) || empty($_POST['cf_message'])) {
                    echo '<p>' . __('All fields are required.', 'devhiv-contactform') . '</p>';
                    return;
                }
        
                $name = sanitize_text_field($_POST['cf_name']);
                $email = sanitize_email($_POST['cf_email']);
                $phone = sanitize_text_field($_POST['cf_phone']);
                $message = sanitize_textarea_field($_POST['cf_message']);
                
        
        
                global $wpdb;
                $table_name = $wpdb->prefix . 'dh_cform_responses';
        
                // Attempt to insert the data into the database
                $inserted = $wpdb->insert(
                    $table_name,
                    array(
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'message' => $message
                    )
                );
        
                if ($inserted) {
                    // Send email notifications
                    $admin_email = get_option('admin_email');
                    $subject = __('New Contact Form Submission', 'devhiv-contactform');
                    $body = sprintf(
                        __("Name: %s\nEmail: %s\nPhone: %s\nMessage: %s", 'devhiv-contactform'),
                        $name, $email, $phone, $message
                    );
                    wp_mail($admin_email, $subject, $body);
        
                    $thank_you_subject = __('Thank you for contacting us', 'devhiv-contactform');
                    $thank_you_body = sprintf(
                        __("Dear %s,\n\nThank you for your message. We will get back to you shortly.\n\nBest regards,\nThe Team", 'devhiv-contactform'),
                        $name
                    );
                    wp_mail($email, $thank_you_subject, $thank_you_body);
        
                    echo '<p>' . __('Thank you for your message. We will get back to you shortly.', 'devhiv-contactform') . '</p>';
                } else {
                    // Capture the database error for debugging
                    $wpdb_error = $wpdb->last_error;
                    echo '<p>' . __('Failed to submit the form. Please try again later. Database error: ', 'devhiv-contactform') . $wpdb_error . '</p>';
                }
            }
        }
        
    }

    new DevhivContactForm();
}
