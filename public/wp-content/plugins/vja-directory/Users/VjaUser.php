<?php
/**
 * Classe Utilisateur
 * 
 * 
 */
namespace VjaDirectory\Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VjaUser
 * @package VjaDirectory\Users
 * @property string sport
 * @property string email
 * @property-read string idPhotoUrl
 * @property-read string idPhotoHtml
 */
class VjaUser 
{
    private $user;

    /**
     * Create a new VJAUser
     *
     * @param object|int|string $user : A user ID, email ou WP_User
     *
     */
    public function __construct($user) {
        if ( $user instanceof \WP_User ) {
            $this->user = $user;
        }
        else if ( is_numeric($user) ) {
            $this->user = get_user_by('id',$user);
        }
        else if ( is_string($user) ) {
            $this->user = get_user_by('email', $user);
        }
        else {
            $this->user = new \WP_User();
        }
    }

    /**
     * Return the user's sport
     *
     * @access private
     * @return string
     *
     */
    private function get_sport() {
        switch (strtolower($this->user->Sport)) {
            case 'course':
                return __('Running', 'vja_dir');
                break;
            case 'marche':
                return __('Walking', 'vja_dir');
                break;
            default:
                return '';
        }
    }

    /**
     * Return the user's sport
     *
     * @access private
     * @param string
     *
     */
    private function set_sport($value) {
        switch (strtolower($value)) {
            case strtolower(__('Running', 'vja_dir')):
                $this->user->Sport = 'Course';
                break;
            case strtolower(__('Walking', 'vja_dir')):
                $this->user->Sport = 'Marche';
                break;
            default:
                $this->user->Sport = '';
        }
    }

    /**
     * Return the user's email
     * Decodes the email 
     * (in case of the user is part of a family account, the email is supposed to be
     * encoded as real email with @ replaced by # and domain as temp.vja.fr)
     * 
     * @access private
     * @return string
     * 
     */
    private function get_email() {
        if (!$this->is_primary_family_member())
//            return preg_replace('/(.+)#(.+)@temp\.vja\.fr.*/','$1@$2',$this->user->user_email);
            return preg_replace('/^\d+\.vjafamily\.(.+)$/','$1', $this->user->user_email);
        else
            return $this->user->user_email;
    }

    /**
     * Return the user's email
     * Encodes the email
     * If the user is part of a not the primary user of a family account, the email is
     * encoded as real email with @ replaced by # and domain as temp.vja.fr followed by the user ID (for unicity purpose)
     *
     * @access private
     * @param string email
     *
     *
     */
    private function set_email($email) {
        if (!$this->is_primary_family_member())
            $this->user->user_email = $this->user->ID.".vjafamily.".$email;
 //            $this->user->user_email = preg_replace('^/(.+)@(.+)$/','$1#$2@vja.fr.'.$this->user->ID,$email);
        else
            $this->user->user_email = $email;
    }

    /**
     * Returns the user's id picture Url
     * if there is not picture or image publishing isn't allowed by the user
     * a default male or female picture is returned
     * 
     * @access private
     * @return string
     * 
     */
    private function get_idPhotoUrl() {
        if ($this->user->allowPublishIDPhoto != 1 || $this->user->idPhoto === "") {
            $Avatar = get_option('VjaDirDefaultAvatar');
            // Publish a shadow photo
            if ($this->user->sex === 'F')
                return wp_get_attachment_image_url($Avatar['female']);
            else
                return wp_get_attachment_image_url($Avatar['male']);
        }
        return wp_get_attachment_image_url($this->user->idPhoto);
    }

    /**
     * Does nothing.
     *
     * @access private
     * @param mixed
     *
     */
    private function set_idPhotoUrl($value) {
        // don't store idPhotoUrl
    }

    /**
     * Returns the user's id picture Html
     * if there is not picture or image publishing isn't allowed by the user
     * a default male or female picture is returned
     * 
     * @access public
     * @param string|array $attr  
     * @param bool $checkAllow - if false doesn't check user's choice
     * @return string 
     * 
     */
    public function idPhotoHtml($attr="", $checkAllow=true) {
        if (($checkAllow && $this->user->allowPublishIDPhoto != 1) || $this->user->idPhoto === "") {
            $Avatar = get_option('VjaDirDefaultAvatar');
            $attr = wp_parse_args($attr);
            $attr = array_map( 'esc_attr', $attr );
            // Publish a shadow photo
            if ($this->user->sex === 'F')
                return wp_get_attachment_image($Avatar['female'],'thumbnail',false,$attr);
            else
                return wp_get_attachment_image($Avatar['male'],'thumbnail',false,$attr);
        }
        return wp_get_attachment_image($this->user->idPhoto,'thumbnail',false,$attr);
    }

    /**
     * Does nothing.
     *
     * @access private
     * @param mixed
     *
     */
    private function set_idPhotoHtml($value) {
        // don't store idPhotoUrl
    }

    /**
     * Returns the html formatted entry for the user
     * 
     * @access public
     * @return string
     */
    public function entry(){
        $html = '<div class="Cad-contact">';
        $html .= '<div class="Cad-contact-idPhoto">';
        $html .= $this->idPhotoHtml(array('id'=>'Cad-Contact-idPhoto'));
        $html .= '</div>';
        $html .= '<div class="Cad-contact-info Cad-contact-sport-' . $this->sport . '">';
        $html .= '  <p class="Cad-contact-row-1">'; 
        $html .= '    <span class="Cad-contact-firstname">'. $this->first_name . '</span>';
        $html .= '    <span class="Cad-contact-lastname">'. $this->last_name . '</span>';
        $html .= '  </p>';
        $html .= '  <p class="Cad-contact-row-2">'; 
        $html .= '    <span class="Cad-contact-email">'. $this->email . '</span>';
        $html .= '  </p>';
        $html .= '  <p class="Cad-contact-row-3">';
        $phone = $this->user->cellphone ? $this->user->cellphone : $this->user->phone1;
        $html .= '    <span class="Cad-contact-phone">'. $phone . '</span>';
        $html .= '  </p>';
        $html .= '  <p class="Cad-contact-row-4">'; 
        $html .= '    <span class="Cad-contact-sport">'. $this->sport . '</span>';
        $html .= '  </p>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Checks whether the user belongs to a family
     *
     * @access public
     * @output boolean
     */
    public function is_family_member() {
        return $this->user->family_member;
    }


    /**
     * Checks whether the user is the primary account of a family
     *
     *
     * @access public
     * @output boolean
     */
    public function is_primary_family_member() {
        if ($this->user->family_member) {
            if (!preg_match('/^\d+\.vjafamily/', $this->user->user_email))
//                if (preg_match('/@temp\.vja\.fr/', $this->user->user_email))
                return true;
        }
        return false;
    }

    public function get_family_members(){
        /**
         * The WP_user's params
         *
         * @access public
         * @var string
         */
        // get primary family email
        $email = $this->get_email();
        // get primary family member's ID
        $primary_id = get_user_by('email', $email)->ID;
        // create a search string for the family members
        $email = "*.vjafamily."."$email" . "*";
//        $email = preg_replace('/^(.*)@(.*)$/','$1#$2*', $email);
        $args = array(
            'search' => $email,
            'search_columns' => array('email'),
            'fields' => 'ID',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'AND',
                    array(
                        'key' =>'family_member',
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => 'family_member',
                        'value' => 1,
                        'type' => 'numeric',
                        'compare' => '='
                    ),
                ),
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
               'first_name_clause' => array(
                    'key' => 'first_name',
                    'compare' => 'EXISTS'
                ),
            ),
            'orderby' => array(
                'first_name_clause' => 'ASC'
            ),
        );
        $query = new \WP_User_Query($args);
        $results = $query->get_results();
        // Add the primary family member at the head of the array
        array_unshift($results, $primary_id);
        return $results;
    }


    //Magic method get and set
    // use the local method if it exits otherwise try to get the corresponding user's attribute.

    public function __get($attr) {
        $f = 'get_'.$attr;
        if (method_exists(__CLASS__, $f)) {
            return $this->$f();
        }
        return $this->user->$attr;
    }

    public function __set($attr, $value) {
        $f = 'set_'.$attr;
        if (method_exists(__CLASS__, $f)) {
             return $this->$f($value);
        }
        $this->user->$attr = $value;
    }

    public function save() {
        wp_update_user($this->user);
        foreach (['cellphone','addr1', 'addr2','city','zip','phone1','birth_date',
                     'license_number','vma','Sport','sex','membership_year','allowPublishIDPhoto', 'family_member'] as $value) {
            update_user_meta($this->user->ID, $value, ($this->$value));

        }
    }
}

