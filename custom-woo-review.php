<?php

/*
---Plugin Name:  Custom Woocommerce Review
---Plugin URI:
---Description:  Custom review for woocommerce shop. It is work only on specific shops. Requirements: woocommerce 3.0+, ACF fields for review post type (key, rating, product_id, product_name, product_image_id).
---Version:      1.0
---Author:       Toni Teofilovic, Divertdigital
---Author URI:
*/

global $CWR;
$CWR = CWR::getInstance();

// Activation/deactivation hooks

register_activation_hook(__FILE__, array($CWR, 'cwr_install'));
register_deactivation_hook(__FILE__, array($CWR, 'clean_on_deactivate'));

//Register Post Type Review and Taxonomy Scope

function registerReviewType()
{
    //Register Post Type

    $labels = array(
        'name'                => _x( 'Reviews', 'Post Type General Name', 'cwr' ),
        'singular_name'       => _x( 'Reviews', 'Post Type Singular Name', 'cwr' ),
        'menu_name'           => __( 'Reviews', 'cwr' ),
        'name_admin_bar'      => __( 'Review', 'cwr' ),
        'parent_item_colon'   => __( 'Parent Review:', 'cwr' ),
        'all_items'           => __( 'All Reviews', 'cwr' ),
        'add_new_item'        => __( 'Add New Review', 'cwr' ),
        'add_new'             => __( 'Add Review', 'cwr' ),
        'new_item'            => __( 'New Review', 'cwr' ),
        'edit_item'           => __( 'Edit Review', 'cwr' ),
        'update_item'         => __( 'Update Review', 'cwr' ),
        'view_item'           => __( 'View Review', 'cwr' ),
        'search_items'        => __( 'Search Review', 'cwr' ),
        'not_found'           => __( 'Not found', 'cwr' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'cwr' ),
    );
    $args = array(
        'label'               => __( 'Reviews', 'cwr' ),
        'description'         => __( 'Reviews', 'cwr' ),
        'labels'              => $labels,
        'supports'            => array('editor'),
        'taxonomies'          => array(),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 6,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'rewrite' => array('slug' => 'reviews')
    );
    register_post_type( 'reviews', $args );

    //Register taxonomy

    $labels = array(
        'name'                       => _x( 'Scopes', 'Taxonomy General Name', 'cwr' ),
        'singular_name'              => _x( 'Scope', 'Taxonomy Singular Name', 'cwr' ),
        'menu_name'                  => __( 'Scope', 'cwr' ),
        'all_items'                  => __( 'All Items', 'cwr' ),
        'parent_item'                => __( 'Parent Item', 'cwr' ),
        'parent_item_colon'          => __( 'Parent Item:', 'cwr' ),
        'new_item_name'              => __( 'New Item Name', 'cwr' ),
        'add_new_item'               => __( 'Add New Item', 'cwr' ),
        'edit_item'                  => __( 'Edit Item', 'cwr' ),
        'update_item'                => __( 'Update Item', 'cwr' ),
        'view_item'                  => __( 'View Item', 'cwr' ),
        'separate_items_with_commas' => __( 'Separate items with commas', 'cwr' ),
        'add_or_remove_items'        => __( 'Add or remove items', 'cwr' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'cwr' ),
        'popular_items'              => __( 'Popular Items', 'cwr' ),
        'search_items'               => __( 'Search Items', 'cwr' ),
        'not_found'                  => __( 'Not Found', 'cwr' ),
        'no_terms'                   => __( 'No items', 'cwr' ),
        'items_list'                 => __( 'Items list', 'cwr' ),
        'items_list_navigation'      => __( 'Items list navigation', 'cwr' ),
    );
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
    );
    register_taxonomy( 'review_scope', array( 'reviews' ), $args );
}
add_action( 'init', 'registerReviewType' );

require_once 'helper.php';


/**
 * CWR
 *
 */
class CWR
{

    //region Singleton
    /** @var CWR */
    private static $instance;


    /**
     * Globals
     *
     * @object
     */
    private $wpdb;

    /**
     * Register DB version
     *
     * @var
     */
    private $db_version = '1.0';

    /**
     * Table name
     *
     * @var
     */
    private $review_table_name = 'custom_review';
    /**
     * Register ID of management page
     *
     * @var
     */
    private $menu_id;

    /** @return CWR */
    public static function getInstance(){
        if (CWR::$instance == null)
            CWR::$instance = new CWR();
        return CWR::$instance;
    }

    /**
     * Plugin initialization
     *
     * @access public
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = &$wpdb;

        load_plugin_textdomain('cwr', false, '/custom-woo-review/localization');

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'admin_enqueues'));
        add_action('creating_review_notification', array($this, 'check_for_sending_notification'));
        add_action('woocommerce_payment_complete', array($this, 'save_order_data'));
        add_action('wp_footer', array($this, 'display_review_form'));
    }

    /**
     * Register table on plugin installation
     *
     * @access public
     */
    public function cwr_install()
    {
        // Create MySql Table

        $table_name = $this->wpdb->prefix . $this->review_table_name;
        $charset_collate = $this->wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                reg_date DATE NOT NULL,
                customer_name VARCHAR(64) NOT NULL,
                email VARCHAR(64) NOT NULL,
                product_id SMALLINT,
                product_name VARCHAR(64),
                api_key VARCHAR(64) NOT NULL,
                flag TINYINT,
                PRIMARY KEY  (id)
            ) $charset_collate;";

//        $sql = "DROP TABLE IF EXISTS $table_name;";
//        $this->wpdb->query($sql);

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option('cwr_db_version', $this->db_version);

        // Activate cron job for sending email notification to create review

        if (!wp_next_scheduled('creating_review_notification')) {
            wp_schedule_event(time(), 'daily', 'creating_review_notification');
        }
    }

    /**
     * Remove Cron job on deactivation
     *
     * @access public
     */
    public function clean_on_deactivate()
    {
        wp_clear_scheduled_hook('creating_review_notification');
    }

    /**
     * Register the management page
     *
     * @access public
     * @since 1.0
     */
    function add_admin_menu()
    {
        $this->menu_id = add_management_page(
            __('Custom Woocommerce Reviews', 'cwr'),
            __('Custom Woocommerce Reviews', 'cwr'),
            'manage_options',
            'custom-woocommerce-reviews',
            array(&$this, 'cwr_interface'));
    }

    /**
     * Enqueue the needed Javascript and CSS
     *
     * @access public
     */
    function admin_enqueues()
    {
       wp_enqueue_script('cwr', plugin_dir_url(__FILE__) . 'assets/cwr.js', array('jquery'));
//        wp_enqueue_style('jquery-ui-regenthumbs', plugins_url('jquery-ui/redmond/jquery-ui-1.7.2.custom.css', __FILE__), array(), '1.7.2');
//        wp_enqueue_style('plugin-custom-style', plugins_url('style.css', __FILE__), array(), '2.0.1');
    }

    /**
     * Render admin management page
     *
     * @access public
     */
    public function cwr_interface()
    {
        //$this->save_order_data(142);
        echo '<h3> Client list prepared for review emailing. </h3>';
        $table_name = $this->wpdb->prefix . $this->review_table_name;
        $results = $this->wpdb->get_results("SELECT * FROM  $table_name WHERE flag = 0", ARRAY_A);
        echo '
        <table class="widefat fixed" cellspacing="0"><thead><tr>
        <th>Date</th><th>Name</th><th>Email</th><th>Bundle</th><th>Key</th>
        </tr></thead>
        <tbody>';
        foreach($results as $result){
            echo '<tr>';
            foreach($result as $key => $value){if (!in_array($key,['flag','1api_key','product_id','id']))echo '<td>' . $value . '</td>';}
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    /**
     * Set data Into DB Table from Woocommerce order
     *
     * @access public
     */
    public function save_order_data($order_id)
    {
        // Get data from order

        $outputs = array();
        $tmpArray = array();

        $atumorder = new WC_Order($order_id);

        // Get Customer data

        $atum_billing_address = $atumorder->get_address( 'billing' );
        $tmpArray['customer_name'] = htmlentities($atum_billing_address['first_name']) . ' ' . htmlentities($atum_billing_address['last_name']);
        if ($atum_billing_address['company']) $tmpArray['customer_name'] .= ' (' . $atum_billing_address['company'] .')';
        $tmpArray['reg_date'] = $atumorder->get_date_created();
        $tmpArray['email'] = $atum_billing_address['email'];
        $tmpArray['product_id'] = null;
        $tmpArray['product_name'] = null;
        $outputs[] = $tmpArray;

        // Get recipient data

        $products_meta = array();
        foreach ($atumorder->get_items() as $key => $product) {
            $product_meta=array();
            $product_meta['product_id'] = $product['product_id'];
            $product_meta['product_name'] = $product['name'];
            foreach ($product->get_formatted_meta_data() as $meta_line) {
                $product_meta[$meta_line->key] = $meta_line->value;
            }
            $dateparts = explode(' ', $product_meta['delivery-date']);
            //date format Wed 11 Oct 2017 - need to convert to format YYYY-MM-DD
            $months = array(
                'Jan'=>'01', 'Feb'=>'02', 'Mar'=>'03', 'Apr'=>'04', 'May'=>'05', 'Jun'=>'06',
                'Jul'=>'07', 'Aug'=>'08', 'Sep'=>'09', 'Oct'=>'10', 'Nov'=>'11', 'Dec'=>'12');
            $product_meta['delivery-date'] = $dateparts[3] . '-' . $months[$dateparts[2]] . '-' . $dateparts[1];
            $orderId=$product_meta['order-id'];
            $products_meta[$orderId][] = $product_meta;
        }

        foreach($products_meta as $orderId=>$ordered_products){
            $tmpArray = array();
            $tmpArray['customer_name'] = htmlentities($ordered_products[0]['who-send']) . ' ' . htmlentities($ordered_products[0]['surname']);
            $tmpArray['reg_date'] = $ordered_products[0]['delivery-date'];
            $tmpArray['email'] = ($ordered_products[0]['email'])?htmlentities($ordered_products[0]['email']):null;
            $tmpArray['product_id'] = $ordered_products[0]['product_id'];
            $tmpArray['product_name'] = $ordered_products[0]['product_name'];
            $outputs[] = $tmpArray;
        }

        // Save to table

        $table_name = $this->wpdb->prefix . $this->review_table_name;
        foreach($outputs as $output) {
            if ($output['email']) {
                $key = $this->generateRandomString();
                $this->wpdb->insert($table_name, array(
                    'reg_date' => $output['reg_date'],
                    'customer_name' => $output['customer_name'],
                    'email' => $output['email'],
                    'product_id' => $output['product_id'],
                    'product_name' => $output['product_name'],
                    'api_key' => $key,
                    'flag' => '0'
                ));
            }
        }
    }

    /**
     * Generating unique key
     *
     * @access private
     */
    private function generateRandomString($length = 64) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Check to send email notification to customer and recipient's
     *
     * @access public
     */
    function check_for_sending_notification()
    {
        $table_name = $this->wpdb->prefix . $this->review_table_name;
        $results = $this->wpdb->get_results("SELECT * FROM  $table_name WHERE flag = 0", ARRAY_A);
        foreach($results as $result){
            if (strtotime($result['reg_date'])<strtotime('-2 days') && !$result['product_id']) {
                //Send email to client
                //set flag to 1
            } elseif (strtotime($result['reg_date'])<strtotime('-6 days') && $result['product_id']){
                //Send email to recipient
                //set flag to 1
            }
        }
    }

    /**
     * Display review form
     *
     * @access public
     */
    function display_review_form(){

        // Check if review key exist

        if (!is_front_page() || !isset($_GET['review-key'])) return;

        // Check if key exist in table

        $key = preg_replace('/[^0-9a-zA-Z]/',"",$_GET['review-key']);
        $table_name = $this->wpdb->prefix . $this->review_table_name;
        $results = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM  $table_name WHERE api_key = %s",$key), ARRAY_A);
        if(!$results) return;

        // Query reviews to find if we have with this key (review was already written)

        $args = array(
            'post_type'		=> 'reviews',
            'meta_key'		=> 'key',
            'meta_value'	=> $key
        );
        $the_query = new WP_Query($args);
        if ($the_query->post_count>0) return;

        // Display review form

        require_once 'templates/form.php';

        // Save review
    }

    /**
     * Ajax called function to save review and redirect to home page
     *
     * @access public
     */
    function save_review(){

    }

















}