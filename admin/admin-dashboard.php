<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class devhiv_ContactForm_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => __('Response', 'devhiv-contactform'),
            'plural'   => __('Responses', 'devhiv-contactform'),
            'ajax'     => false
        ));
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        global $wpdb;
        $table_name = $wpdb->prefix . 'dh_cform_responses';
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id';
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'ASC';

        $offset = ($current_page - 1) * $per_page;
        $this->items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT $per_page OFFSET $offset");

        // Handle edit action
        if ('edit' === $this->current_action()) {
            $this->handle_edit();
        }

        // Handle delete action
        if ('delete' === $this->current_action()) {
            $this->handle_delete();
        }
    }

    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'id'        => __('ID', 'devhiv-contactform'),
            'name'      => __('Name', 'devhiv-contactform'),
            'email'     => __('Email', 'devhiv-contactform'),
            'phone'     => __('Phone', 'devhiv-contactform'),
            'message'   => __('Message', 'devhiv-contactform'),
            'edit'      => __('Edit', 'devhiv-contactform'),
            'delete'    => __('Delete', 'devhiv-contactform'),
        );
        return $columns;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'name':
            case 'email':
            case 'phone':
            case 'message':
                return $item->$column_name;
            case 'edit':
                return sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->id);
            case 'delete':
                return sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->id);
            default:
                return print_r($item, true); // Debugging output
        }
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id
        );
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'id'    => array('id', false),
            'name'  => array('name', false),
            'email' => array('email', false),
        );
        return $sortable_columns;
    }

    public function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => 'Delete'
        );
        return $actions;
    }

    public function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dh_cform_responses';

        if ('delete' === $this->current_action()) {
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id > 0) {
                $wpdb->delete($table_name, array('id' => $id), array('%d'));

                // Redirect to avoid reprocessing the delete action on page reload
                wp_redirect(admin_url('admin.php?page=devhiv-contactform'));
                exit;
            }
        }

        if ('bulk-delete' === $this->current_action()) {
            $ids = isset($_REQUEST['bulk-delete']) ? $_REQUEST['bulk-delete'] : array();

            if (!is_array($ids)) {
                $ids = array($ids);
            }

            foreach ($ids as $id) {
                $wpdb->delete($table_name, array('id' => intval($id)), array('%d'));
            }

            // Redirect to avoid reprocessing the delete action on page reload
            wp_redirect(admin_url('admin.php?page=devhiv-contactform'));
            exit;
        }
    }


    public function handle_edit() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dh_cform_responses';
        
        if (isset($_POST['submit'])) {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $message = $_POST['message'];
           

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
        }

        // Fetch the item to edit
        $id = $_GET['id'];
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        // Display edit form (simplified for demonstration)
        echo '<div class="edit-form">';
        echo '<form id="edit-form" method="post">';
        echo '<input type="hidden" name="id" value="' . esc_attr($item->id) . '">';
        echo '<label for="name">Name</label>';
        echo '<input type="text" name="name" value="' . esc_attr($item->name) . '">';
        echo '<label for="email">Email</label>';
        echo '<input type="text" name="email" value="' . esc_attr($item->email) . '">';
        echo '<label for="phone">Phone</label>';
        echo '<input type="text" name="phone" value="' . esc_attr($item->phone) . '">';
        echo '<label for="message">Message</label>';
        echo '<textarea name="message">' . esc_textarea($item->message) . '</textarea>';
        echo '<input type="submit" name="submit" value="Save">';
        echo '</form>';
         echo "</div>";
    }


    public function handle_delete() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dh_cform_responses';
        
        if (isset($_GET['id']) && is_numeric($_GET['id']) && check_admin_referer('devhiv_delete_response')) {
            $id = intval($_GET['id']);
            $wpdb->delete($table_name, array('id' => $id), array('%d'));

            // Redirect to avoid reprocessing the delete action on page reload
            wp_redirect(admin_url('admin.php?page=devhiv-contactform'));
            exit;
        }
    }

    public function column_delete($item) {
        $delete_nonce = wp_create_nonce('devhiv_delete_response');
        $delete_url = add_query_arg(array(
            'action' => 'delete',
            'id' => $item->id,
            '_wpnonce' => $delete_nonce,
        ), admin_url('admin.php?page=devhiv-contactform'));

        return sprintf('<a href="#" class="devhiv-delete-link" data-item-id="%d" data-nonce="%s">%s</a>', $item->id, $delete_nonce, __('Delete', 'devhiv-contactform'));
    }


}

function render_admin_dashboard() {
    $wp_list_table = new devhiv_ContactForm_List_Table();
    $wp_list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Contact Form Responses', 'devhiv-contactform'); ?></h1>
        <hr class="wp-header-end">

        <form id="response-filter" method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
            <?php
            $wp_list_table->display();
            ?>
        </form>
    </div>
    <?php
}

render_admin_dashboard();
?>