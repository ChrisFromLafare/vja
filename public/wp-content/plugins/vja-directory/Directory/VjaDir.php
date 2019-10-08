<?php

namespace VjaDirectory\Directory;

use VjaDirectory\Users;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class VjaDir {

    protected static $instance;
    protected $init_page = false;

    public static function init()
    {
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    /**
     *  Templates used by backbone for displaying User Directory
     *
     */

    public function get_templates()
    {
        if ($this->init_page) :?>
            <script type="text/template" id="vjadir-directory-tmpl">
                <div class="Cad-directory-header">
                    <select id="sport" name="sport" class="dropdown">
                        <option value=""><?php _e("All members", "vja_dir"); ?></option>
                        <option value="Course"><?php _e("Runners", "vja_dir"); ?></option>
                        <option value="Marche"><?php _e("Walkers", "vja_dir"); ?></option>
                    </select>
                    <select id="perpage" name="perpage" class="dropdown">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    <select id="searchwhere" name="searchwhere" class="dropdown">
                        <option value="contains"><?php _e("Contains", "vja_dir"); ?></option>
                        <option value="startswith"><?php _e("Starts with", "vja_dir"); ?></option>
                        <option value="endswith"><?php _e("Ends with", "vja_dir"); ?></option>
                    </select>
                    <input type="search" id="search" name="search" class="textbox" placeholder="<?php _e('Search...', 'vja_dir') ?>">
                </div>
                <div class="members-container"></div>
                <div class="Cad-directory-footer">
                </div>
            </script>

            <script type="text/template" id="vjadir-members-tmpl">
            </script>

            <script type="text/template" id="vjadir-contact-tmpl">
                <div class="Cad-contact-idPhoto">
                    <img width="150" height="150"
                         src="<%- avatarsrc %>"
                         class="attachment-thumbnail size-thumbnail" alt="" id="Cad-Contact-idPhoto">
                </div>
                <div class="Cad-contact-info Cad-contact-sport-<%- sport %>">
                    <p class="Cad-contact-row-1">
                        <span class="Cad-contact-firstname"><%- firstname %></span>
                        <span class="Cad-contact-lastname"><%- lastname %></span>
                    </p>
                    <p class="Cad-contact-row-2">
                        <span class="Cad-contact-email"><%- email %></span>
                    </p>
                    <p class="Cad-contact-row-3">
                        <span class="Cad-contact-phone"><%- phone %></span>
                    </p>
                    <p class="Cad-contact-row-4">
                        <span class="Cad-contact-sport"><%- sport %></span>
                    </p>
                    <?php if (current_user_can('manage_options')): ?>
                        <p class="Cad-contact-row-5">
                            <span class="Cad-contact-birthdate"><%- birthdate %></span>
                        </p>
                        <p class="Cad-contact-row-6">
                            <span class="Cad-contact-license"><?php _e("License number:", 'vja_dir'); ?> <%- license %></span>
                        </p>
                        <p class="Cad-contact-row-7">
                            <span class="Cad-contact-addr"><%- addr %></span>
                        </p>
                        <p class="Cad-contact-row-8">
                            <span class="Cad-contact-zipcode"><%- zipcode %> <%- city %></span>
                        </p>
                    <?php endif ?>
                </div>
            </script>
            <script type="text/template" id="vjadir-error-tmpl">
                <div class="Cad-error">
                    <p>
                        <?php _e("Oops, An error has occured...", "vja_dir") ?>
                    </p>
                    <p>
                        <%- errnum %> - <%- errtext %>
                    </p>
                </div>
            </script>
        <?php endif;
    }

	/**
	 * The maximum number of users returned by the user_request
	 *
	 * @access public
	 * @var int
	 */
    public $page_length = 10;

    /**
     * Filter users by sport
     * by default all sports other values Course, Marche
     *
     * @access public
     * @var string
     */
    public $sport_filter = '';

    /**
     * Filter users
     * by default no filter
     *
     * @access public
     * @var string
     */
    public $search_pattern = '';

    /**
     * Filter modifier
     * by default contains (other values: startwith, endwith)
     *
     * @access public
     * @var string
     */
    public $search_where = 'contains';

    /**
     * The requested page (offset from the first user)
     * 
     * @access public
     * @var int
     */
    public $page_nb = 1;

    /**
     * The total number of users
     * 
     * @access private
     * @var int;
     */
    private $total_users_number;

    /**
     * The number of pages
     * 
     * @access private
     * @var int;
     */
    private $page_number;
    
    /**
     * The WP_user's params
     * 
     * @access private
     * @var string
     */
    private $args;

    public function __construct() {
        // Initialization
        add_action('wp_footer', array($this,'get_templates'));
        add_action('wp_ajax_vja_dir_members', array($this,'ajax_vja_dir_members'));
        add_action('wp_enqueue_scripts', array($this,'enqueue_script'));
        $this->args = array(
            'meta_key' => 'last_name',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'account_locked',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'account_locked',
                        'value' => 0,
                        'type' => 'numeric',
                        'compare' => '='
                    ),
                ),
                array(
                    'key' => 'Sport',
                    'compare' => 'LIKE',
                    'value' => $this->sport_filter,
                ),
                'last_name_clause' => array(
                    'key' => 'last_name',
                    'compare' => 'EXISTS'
                ),
                'first_name_clause' => array(
                    'key' => 'first_name',
                    'compare' => 'EXISTS'
                ),
            ),
            'count_total' => 'true',
            'orderby' => array(
                'last_name_clause' => 'ASC',
                'first_name_clause' => 'ASC'
            ),
        );


    }

    /**
     * Register the scripts and also the vars share with JS
     *
     */
    public function enqueue_script() {

    }

    /**
     * Register the CSS 
     * 
     * @access private
     * 
     */
    public function register_css() {
        $plugin_url = VJADIRECTORY_PLUGIN_PATH;
         wp_enqueue_style( 'cad-directory-style', $plugin_url . 'css/cad-directory-style.css' );
    }

    /**
     *  prepare args for fetching users
     *
     * @access private
     */
    private function prepare_args() {
        $this->args = array(
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'account_locked',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'account_locked',
                        'value' => 0,
                        'type' => 'numeric',
                        'compare' => '='
                    ),
                ),
                array(
                    'key' => 'Sport',
                    'compare' => 'LIKE',
                    'value' => $this->sport_filter,
                ),
                'last_name_clause' => array(
                    'key' => 'last_name',
                    'compare' => 'EXISTS'
                ),
                'first_name_clause' => array(
                    'key' => 'first_name',
                    'compare' => 'EXISTS'
                ),
            ),
            'count_total' => 'true',
            'orderby' => array(
                'last_name_clause' => 'ASC',
                'first_name_clause' => 'ASC'
            ),
        );
        // Paged and number may be updated before use respectively with the desired page number et the page length
        $this->args['paged']=1;
        $this->args['number']=1;
        if ("" != $this->search_pattern) {
            switch ($this->search_where) {
                case 'startswith':
                    $regexp = '^'.$this->search_pattern;
                    break;
                case 'endswith':
                    $regexp = $this->search_pattern . '$';
                    break;
                default:
                    $regexp = $this->search_pattern;
            }
            array_push($this->args['meta_query'], [
                'relation'=> 'OR',
                ['key' => 'first_name', 'compare' => 'REGEXP', 'value' => $regexp],
                ['key' => 'last_name', 'compare' => 'REGEXP', 'value' => $regexp]
            ]);
        }

    }

    /** fetch a page of users
     * 
     * @access public
     * @param int $page the requested page number
     * @return Users\VjaUsers
     */
    public function get_users($page = 1) {
        if (! is_numeric($page)) return false;
        $page = intval($page);
        $this->prepare_args();
        $this->args['paged']=$page;
        $this->args['number']=$this->page_length;
        /** @var WP_User_Query $user_query */
        $user_query = new \WP_User_Query($this->args);
        $this->total_users_number = $user_query->get_total();
        return new Users\VjaUsers($user_query->get_results());
    }

    /**
     * Get the total number of users
     * 
     * @access public
     * @return int
     */
    public function get_users_total() {
        if (! isset($this->total_users_number)) {
            $this->prepare_args();
            $user_query = new \WP_User_Query($this->args);
            $this->total_users_number = $user_query->get_total();
        }
        return $this->total_users_number;
    }

    /**
     * Get the number of pages
     * 
     * @access public
     * @return int
     */
    public function get_total_pages() {
        return ceil($this->get_users_total() / $this->page_length);
    }


    /**
     * Init the directory page (allowing to load the templates in the page footer)
     *
     * @access public
     */
     public function init_page() {
         $this->init_page = true;
     }

     /**
      * Process the ajax request filling the collection of contacts
      *
      * @access public
      * @return string[]|void the response as a json collection
      *
      */
     public function ajax_vja_dir_members() {
         check_ajax_referer('cad-directory','nonce');
         $array_contact = array();
         if (isset($_POST['sport'])) $this->sport_filter = $_POST['sport'];
         if (isset($_POST['pagelength'])) $this->page_length = $_POST['pagelength'];
         if (isset($_POST['searchpattern'])) $this->search_pattern = sanitize_text_field($_POST['searchpattern']);
         $this->search_pattern = preg_replace('/[^a-zA-Z0-1]/', "", $this->search_pattern);
         if (isset($_POST['searchwhere'])) $this->search_where = $_POST['searchwhere'];
         $this->page_nb = (isset($_POST['page']) && is_numeric($_POST['page']))? $_POST['page']:1;
         if ($this->page_nb < 1)
             $this->page_nb = 1;
         elseif ($this->page_nb > $this->get_total_pages())
             $this->page_nb = $this->get_total_pages();
         if (($this->page_nb) > 0) {
             $contacts = $this->get_users($this->page_nb);
             foreach ($contacts as $contact) {
                 $contact_temp = array();
                 $contact_temp['firstname'] = $contact->first_name;
                 $contact_temp['lastname'] = $contact->last_name;
                 $contact_temp['email'] = $contact->email;
                 $contact_temp['phone'] = $contact->cellphone;
                 $contact_temp['avatarsrc'] = $contact->idPhotoUrl;
                 if ($contact_temp['phone'] == '') $contact_temp['phone'] = $contact->phone1;
                 $contact_temp['sport'] = $contact->Sport;
                 $contact_temp['sex'] = $contact->sex;
                 // Only for administrators
                 if (current_user_can('manage_options')) {
                     $contact_temp['birthdate'] = $contact->birth_date;
                     $contact_temp['addr'] = $contact->addr1;
                     $contact_temp['zipcode'] = $contact->zip;
                     $contact_temp['city'] = $contact->city;
                     $contact_temp['license'] = $contact->license_number;
                 }
                 array_push($array_contact, $contact_temp);
             }
         }
         else {
             // The page number must be greater than 0
             $this->page_nb = 1;
         }
         $response = [
                "currentpage" => intval($this->page_nb),
                "maxpage" => $this->get_total_pages(),
                "members" => $array_contact
             ];
         wp_send_json_success($response, JSON_NUMERIC_CHECK);
     }
}