<?php

/**
 *
 * Cette classe gère les champs les champs idPhoto et allowIdPhoto dans l'écran
 * d'admin de l'utilisateur
 *
 *
 */

namespace VjaDirectory\AdminPage;

use VjaDirectory\Users;

if (!defined('ABSPATH')) {
    die;
}

class UserPage
{
    // $user_id sets during upload phase
    // used by the unique_name callback to create the  file_name
    private $user_id;
    /**
     *
     *  initialise la classe
     *  permet que les champs additionnels soient affiches
     *  sur l'écran de saisie d'un nouvel utilisateur
     *  sur l'écran de saisie d'un utilisateur (différent de soi même)
     *
     */
    public function __construct()
    {
        // Internationalization
        load_plugin_textdomain('vja_dir', false, VJADIRECTORY_PLUGIN_PATH . '/languages/');
        // Loading JS
        add_action('admin_enqueue_scripts', array($this, 'additional_user_fields_script'));
        // Edit user profile - when a user is seeing an other user
        add_action('edit_user_profile', array($this, 'additional_user_fields'));
        // Edit user profile - when a user is seeing his/her profile
        add_action('show_user_profile', array($this, 'additional_user_fields'));
        // Create user
        add_action('user_new_form', array($this, 'additional_user_fields'));
        add_action('user_register', array($this, 'additional_user_fields_update'));
        // Hooks near top of profile page after update (if not current user)
        add_action('edit_user_profile_update', array($this, 'additional_user_fields_update'));
        // Hooks near top of profile page after update (if current user)
        add_action('personal_options_update', array($this, 'additional_user_fields_update'));
        // Ajax callback save-avatar
        add_action('wp_ajax_vja-directory-save-avatar', array($this, 'ajax_save_avatar'));
        // Ajax callback get-avatar
        add_action('wp_ajax_vja-directory-get-avatar', array($this, 'ajax_get_avatar'));
        // Ajax callback delete avatar
        add_action('wp_ajax_vja-directory-remove-avatar', array($this, 'ajax_remove_avatar'));
        // Hooks the upload query - and select only the correct media depending we are or not dealing with an avatar
        add_filter('ajax_query_attachments_args', array($this, 'query_attachments_args'), 99, 1);
        // the following hooks are only relevant, if we are in the specific VJA Avatar Upload
        // hence CadVJADirUploader is set (and by the way contains the user ID)
        if (isset($_POST['CadVJADirUploader'])) {
            // Hooks the upload dir in media_upload
            add_filter('upload_dir', array($this, 'change_upload_dir'), 999);
            // Hooks after an attachment (media) has been created
            add_action('add_attachment', array($this, 'add_attachment'));
            // Hooks the file attachment file name to change it accordingly with the username
            add_filter('sanitize_file_name', array($this, 'sanitize_file_name_callback'), 99, 2);
            // Hooks attachment data before they are written in the DB and modify them to reflect user information
            add_filter('wp_insert_attachment_data', array($this, 'my_set_avatar_meta'), 10, 2);
            // Hooks the image upload end and resize it
            add_filter('wp_handle_upload', array($this, 'resize_avatar'), 10, 2);
        }
    }


    /**
     *
     * Resize the upload image to 150 x 150 cropped,
     * this is the only useful size for an avartar image.
     *
     * @param array $upload - contains uploaded file information
     * @param array $context - not used
     * @return mixed
     *
     */
    function resize_avatar(array $upload, array$context) {
        $editor = wp_get_image_editor( $upload['file'] );
        if ( ! is_wp_error( $editor ) ) {
            $editor->resize(150, 150, true);
            $editor->save($upload['file']);
        }
        return $upload;
    }

    /**
     *
     * set attachment title and excerpt with value relevant with the associated user
     * @param array $data - the different pieces of information associated to the attachment
     * @param array $postarr
     * @return array $data - filtered data
     */
    function my_set_avatar_meta(array $data, array $postarr) {
        $user = new \WP_User($_POST['CadVJADirUploader']);
        if ($user) {
            $data['post_title'] = $user->first_name . " " . $user->last_name;
            $data['post_excerpt'] = sprintf(__('%1$s %2$s\'s Avatar image'), $user->first_name, $user->last_name);
        }

        return $data;
    }

    /**
     *
     *  Display the required additional fields
     *
     * @access public
     * @param WP_User $user the wp current object
     */
    function additional_user_fields($user)
    { ?>

        <h3><?php _e('User Avatar', 'vja_dir'); ?></h3>

        <table class="form-table">

            <tr>
                <th><label for="user_meta_image"><?php _e('A special image for each user', 'vja_dir'); ?></label></th>
                <td>
                    <!-- if a new page is being created, $user is the string "add-new-user" so don't try to get user ID. -->
                    <?php
                    if ($user instanceof \WP_User) {
                        $vjaUser = new Users\VjaUser($user);
                        // Gets the idPhoto
                        $contactPhotoHtml = $vjaUser->idPhotoHtml(array('id' => 'Cad-Contact-idPhoto'), false);
                        $allowPublishIDPhoto = $vjaUser->allowPublishIDPhoto ? 'checked' : '';
                        $contactAttachmentId = $vjaUser->idPhoto;
                    }
                    else {
                        $Avatar = get_option('VjaDirDefaultAvatar');
                        $contactPhotoHtml = wp_get_attachment_image($Avatar['male'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
                        $allowPublishIDPhoto = 'checked';
                        $contactAttachmentId = '';
                    }
                    echo $contactPhotoHtml;
                    ?>
                    <br/>
                    <!-- Outputs the text field and displays the URL of the image retrieved by the media uploader -->
                    <input type="hidden" name="Cad_Contact_AttachmentId" id="Cad_Contact_AttachmentId"
                           value="<?php echo $contactAttachmentId ?>" class="regular-text"/>
                    <!-- Outputs the save button -->
                    <div id='uploadimage-div'>
                        <input type='button' class="additional-user-image button-primary" value="<?php _e('Upload Image', 'vja_dir'); ?>" id="uploadimage"/><br />
                        <!-- Output two texts - the appropriated text will be shown in javascript, and the other hidded -->
                        <span id="cad-dir-no-img"
                              class="description"><?php _e('Upload an additional image for your user profile.', 'vja_dir'); ?></span>
                        <span id="cad-dir-with-img"
                              class="description"><?php _e('Change your user profile image.', 'vja_dir'); ?></span>
                    </div>
                    <!-- Outputs the remove button -->
                    <div id='removeimage-div'>
                        <input type='button' class="additional-user-image-remove button-primary"
                               value="<?php _e('Remove Image', 'textdomain'); ?>" id="removeimage"/><br/>
                        <span class="description"><?php _e('Remove your user profile image.', 'vja_dir'); ?></span>
                    </div>
                    <!-- Outputs the allow publish button -->
                    <input type='checkbox' value='1'
                           name='allowPublishIDPhoto' <?php echo $allowPublishIDPhoto; ?>> <?php _e('Allow to publish the image', 'vja_dir'); ?>
                    <br/>
                </td>
            </tr>

        </table><!-- end form-table -->
    <?php }

    /**
     *
     *  Update the additional fields
     *
     * @access public
     * @param int $user
     */
    function additional_user_fields_update($user)
    {
        if ( isset($_POST['Cad_Contact_AttachmentId'] ) &&
            ( '' != $_POST['Cad_Contact_AttachmentId']) ) {
            $this->update_avatar($user, $_POST['Cad_Contact_AttachmentId']);
        } else {
            $this->delete_avatar($user);
        }
        if ( isset($_POST['allowPublishIDPhoto']) ) {
            update_user_meta($user, 'allowPublishIDPhoto', $_POST['allowPublishIDPhoto']);
        } else {
            delete_user_meta($user, 'allowPublishIDPhoto');
        }
    }

    /**
     * Update avatar
     *
     * @access private
     * @param int $user_id
     * @param int $media_id new media Id
     */
    private function update_avatar($user_id, $media_id)
    {
        if (!isset($user_id)) return;
        $idPhoto = get_user_meta($user_id, "idPhoto", true);
        if ("" != $idPhoto && $idPhoto != $media_id) wp_delete_attachment($idPhoto);
        update_user_meta($user_id, 'idPhoto', $media_id);
    }

    /**
     * Remove avatar
     *
     * @access private
     * @param int $user_id
     * @param boolean $remove_meta - whether or not remove the meta key (depending it's an update or not)
     */
    private function delete_avatar($user_id)
    {
        if (!isset($user_id)) return;
        $idPhoto = get_user_meta($user_id, "idPhoto", true);
        delete_user_meta($user_id, "idPhoto");
        if ("" != $idPhoto) wp_delete_attachment($idPhoto);
    }

    /**
     * Save the avatar id as a meta
     * Process the ajax request
     *
     * @access public
     * @return JSON img html.
     */
    public function ajax_get_avatar(){
        $result = array(
            error => "",
            avatar_image => "",
        );
        // check required information and permissions
        if ( empty($_POST['_wpnonce']) ||
             !wp_verify_nonce($_POST['_wpnonce'], 'vja_dir_get_avatar_nonce' )
        ) {
            $result["error"] = __('You are not allowed to get the avatar', 'vja_dir');
            wp_send_json($result);
        }
        if ( empty($_POST['media_id']) ) {
            $Avatar = get_option('VjaDirDefaultAvatar');
            if ( empty($_POST['user_id']) ) {
                $result['avatar_image'] = wp_get_attachment_image($Avatar['male'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
            }
            else {
                $vjaUser = new Users\VjaUser($_POST['user_id']);
                if ('F' === $vjaUser->sex) {
                    $result['avatar_image'] = wp_get_attachment_image($Avatar['female'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
                }
                else {
                    $result['avatar_image'] = wp_get_attachment_image($Avatar['male'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
                }
            }
            wp_send_json($result);
        }
        $result['avatar_image'] = wp_get_attachment_image($_POST['media_id'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
        wp_send_json($result);
    }

    /**
     *  Process the remove avatar Ajax request
     *
     * @access public
     */
    public function ajax_remove_avatar() {
        $result = array(
            error => "",
            avatar_image => "",
        );
        // check required information and permissions
        if ( empty($_POST['_wpnonce']) ||
            !wp_verify_nonce($_POST['_wpnonce'], 'vja_dir_remove_avatar_nonce')
        ) {
            $result["error"] = __('You are not allowed to remove the avatar', 'vja_dir');
            wp_send_json($result);
        }
        $Avatar = get_option('VjaDirDefaultAvatar');
        $result['avatar_image'] = wp_get_attachment_image($Avatar['male'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
        if (isset($_POST['user_id'])) {
            $user = get_user_by('id',$_POST['user_id']);
            if (isset($user)) {
//                $this->delete_avatar($_POST['user_id'], true);
                $vjaUser = new Users\VjaUser($user);
                if ('F' === $vjaUser->sex) {
                    $result['avatar_image'] = wp_get_attachment_image($Avatar['female'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
//                    $result['avatar_id'] = $Avatar['female'];
                }
            }
        }
        wp_send_json($result);
    }

    /**
     * Creates a unique, meaningful file name for uploaded avatars.
     *
     * @param string $filename sanitized filename
     * @param string $rawfilename original filename
     * @return string Final filename
     */
    function sanitize_file_name_callback($filename, $rawfilename)    {
        static $allready_called = false;
        // The CadVJADirUploader parameter is set up in vja-dir-admin.js
        // If it is set the request comes from the plugin
        // and then it is safe to rename the file.
        // Furthermore it contains the user_id
        if (isset($_POST['CadVJADirUploader'])) {
            if ($allready_called) return($filename);
            $allready_called = true;
            $user = get_user_by('id', (int)$_POST['CadVJADirUploader']);

            $info = pathinfo($filename);
            $ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
            $filename = $user->user_login . $ext;
            if ($filename != $rawfilename) {
                $filename = sanitize_file_name($filename);
            }
        }
        return $filename;
    }

//    public function unique_filename_callback($dir, $name, $ext)
//    {
//        $user = get_user_by('id', (int)$this->user_id);
//        $name = sanitize_file_name($user->display_name);
//
//        // ensure no conflicts with existing file names
//        $number = 1;
//        while (file_exists($dir . "/{$name}_{$number}$ext")) {
//            $number++;
//        }
//        return "{$name}_{$number}$ext";
//    }
//
//
    /**
     * Enqueue scripts required for media uploader
     * @access public
     * @param string $hook_suffix page name
     *
     */
    public function additional_user_fields_script($hook_suffix)
    {
        if ('profile.php' != $hook_suffix && 'user-edit.php' != $hook_suffix && 'user-new.php' != $hook_suffix) return;
        if (!current_user_can('upload_files')) return;
        if ( 'profile.php' == $hook_suffix )
            $user_id = get_current_user_id();
        elseif ( 'user-new.php' == $hook_suffix )
            $user_id = 0;
        else
            $user_id = (int)$_GET['user_id'];
        $vja_params = array(
            'title' => __('Choose an Avatar', 'vja_dir'),
            'insert' => __('Set as VJA avatar', 'vja_dir'),
            'removeNonce' => wp_create_nonce('vja_dir_remove_avatar_nonce'),
            'mediaNonce' => wp_create_nonce('vja_dir_get_avatar_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'user_id' => $user_id,
        );
        wp_enqueue_media();
        wp_enqueue_script(
            'vja-directory',
            plugin_dir_url(trailingslashit(VJADIRECTORY_PLUGIN_PATH) . "js/vja-dir-admin.js") . "vja-dir-admin.js",
            array('jquery'),
            '',
            true
        );
        \wp_localize_script('vja-directory', 'vja_params', $vja_params);
        \wp_localize_script('vja-directory', 'MediaLibraryTaxonomyFilterData', array(
            'terms'     => get_terms( 'collection', array( 'hide_empty' => false ) ) ) );
    }

    /**
     * Set Upload Directory
     *
     * Sets the upload dir to vja_dir if appropriate
     *
     * @since 1.0
     * @param array $upload - contains the path and url information
     * @return array Upload directory information
     */
    function change_upload_dir($upload)
    {
        // The CadVJADirUploader parameter is set up in vja-dir-admin.js
        // If it is set the request comes from the plugin
        // and then it is safe to change the directory.
       if ( isset( $_POST['CadVJADirUploader'] )) {
            $upload['subdir'] = '/avatars';
            $upload['path'] = $upload['basedir'] . $upload['subdir'];
            $upload['url'] = $upload['baseurl'] . $upload['subdir'];
        }
        return $upload;
    }

    /**
     * Filter query parameters
     *
     * If CadVjaAvatar parameter exists, it means we are uploading an avatar
     * In that case, get only avatar (media having CadVjaAvatar has meta key)
     * If CadVjaAvatar parameter doesn't exist, we aren't uploading an avatar
     * In such a case, get all but avatar media
     *
     * @since 1.0
     * @args array $args - an array of query arguments
     * @return array query parameters
     */

    public function query_attachments_args($args){
        $args['meta_key']='CadVjaAvatar';
        if (isset($_POST[CadVJAAvatar])) {
            $this->CadVjaAvatar = true;
            $args['meta_compare'] = 'EXISTS';
        }
        else {
            $this->CadVjaAvatar = false;
            $args['meta_compare']='NOT EXISTS';
        }
        return $args;
    }

    /**
     * After the attachment has been created
     * Add the metakey CadVjaAvatar - it will be used to show only avatars in media uploader
     * @param int $post_id - attachment Id
     */
    public function add_attachment($post_id){
        // if CadVJADirUpload parameter is set
        // we are uploading an avatar -- add a meta allowing to retrieve it
        if (isset($_POST['CadVJADirUploader'])) {
            update_post_meta($post_id, "CadVjaAvatar",1);
        }
    }

}

    /**
     * Process the upload file ajax request
     *
     * @access public
     *
     */
/*

function upload_file()
{
    $result = array(
        error => "",
        avatar_url => "",
    );
    // check required information and permissions
    if (empty($_POST['user_id']) ||
        !current_user_can('upload_files') ||
        !current_user_can('edit_user', $_POST['user_id']) ||
        empty($_POST['_wpnonce']) ||
        !wp_verify_nonce($_POST['_wpnonce'], 'vja_dir_upload_nonce')
    ) {
        $result["error"] = __('You are not allowed to upload files', 'vja_dir');
        wp_send_json($result);
    }
    // check for uploaded files
    if (empty($_FILES['cad-dir-file']['name'])) {
        $result["error"] = __('No file selected or filename error', 'vja_dir');
        wp_send_json($result);
    }

    // need to be more secure since low privelege users can upload
    if (false !== strpos($_FILES['cad-dir-file']['name'], '.php')) {
        $result["error"] = __('Please upload a valid image file for the avatar.', 'vja_dir');
    }

    // front end (theme my profile etc) support
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    // allow developers to override file size upload limit for avatars
    // add_filter( 'upload_size_limit', array( $this, 'upload_size_limit' ) );

    $this->user_id = (int)$_POST['user_id']; // make user_id known to unique_filename_callback function
    $avatar = wp_handle_upload($_FILES['cad-dir-file'], array(
        'mimes' => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
        ),
        'test_form' => false,
        'unique_filename_callback' => array($this, 'unique_filename_callback'),
    ));

    // remove_filter( 'upload_size_limit', array( $this, 'upload_size_limit' ) );

    if (empty($avatar['file'])) {        // handle failures
        switch ($avatar['error']) {
            case 'File type does not meet security guidelines. Try another.' :
                $result["error"] = __('Please upload a valid image file for the avatar.', 'vja_dir');
                break;
            default :
                $result["error"] = __('There was an error uploading the avatar:', 'vja_dir') . esc_html($avatar['error']);
        }
    }

    //        $this->assign_new_user_avatar( $avatar['url'], $user_id );
    $this->save_avatar(array('url' => $avatar['url'], 'path' => $avatar['file']));
    $result['avatar_url'] = $avatar['url'];
    wp_send_json($result);
}
*/

/**
 * Save the avatar id as a meta
 * Process the ajax request
 *
 * @access public
 * @return JSON img html.
 */
/*    public function ajax_save_avatar(){
        $result = array(
            error => "",
            avatar_image => "",
            avatar_id => "",
        );
        // check required information and permissions
        if (empty($_POST['user_id']) ||
            empty($_POST['_wpnonce']) ||
            !wp_verify_nonce($_POST['_wpnonce'], 'vja_dir_save_avatar_nonce')
        ) {
            $result["error"] = __('You are not allowed to save the avatar', 'vja_dir');
            wp_send_json($result);
        }
        if (empty($_POST['user_id']) ||
            empty($_POST['media_id'])) {
            $result["error"] = __('Invalid parameters', 'vja_dir');
            wp_send_json($result);
        }
        $this->delete_avatar(false);
        update_user_meta($_POST['user_id'], "idPhoto", $_POST['media_id']);

        $result['avatar_image'] = wp_get_attachment_image($_POST['media_id'],'thumbnail',false,['id'=>'Cad-Contact-idPhoto']);
        $result['avatar_id'] = $_POST['media_id'];
        wp_send_json($result);
    }
*/

