<?php
/**
 * Created by PhpStorm.
 * User: Christian
 * Date: 13/01/2018
 * Time: 07:36
 */

namespace VjaDirectory\Users;


class VjaProfile
{
    protected $vjaUser;
    protected $fields = array();
    protected $errors = array();

    public static function addShortcode($atts)
    {
        $vjaProfile = new VjaProfile($atts);
        return $vjaProfile->processShortcode();
    }

    public function __construct($atts)
    {
        $atts = shortcode_atts( array(
            'userId' => get_current_user_id(),
        ),$atts,"VjaDirProfile");
        $this->vjaUser = new VjaUser($atts['userId']);
        $this->validator = new \GUMP('fr');
    }

    /**
     * Process the shortcode
     *
     * @output string the shortcode code
     */
    public  function processShortcode() {
        if (!is_user_logged_in()) {
            return _e("You must be logged in to edit your profile", 'vja_dir');
        }
        if ( !current_user_can('edit_users') && ($this->vjaUser->ID != get_current_user_id())) {
            return _e('You are not allowed to edit this profile', 'vja_dir');
        }
        if (isset($_POST['action']) && 'update'===$_POST['action']) {
            if ( empty($_POST['_wpnonce']) ||
                !wp_verify_nonce($_POST['_wpnonce'], 'vjaprofile'.$_POST['user'])
            ) {
                return wp_nonce_ays("");
            }
            $user = new VjaUser($_POST['user']);
            if (true === $this->validate() ) {
                $this->update($user);
            }
        }
        else {
            if (isset($_GET['member_id']) && 'edit'===$_GET['action']) {
                $user = new VjaUser($_GET['member_id']);
            }
            else {
                if ($this->vjaUser->is_family_member()) {
                    ob_start();
                    echo '<div id="vja_profile">';
                    echo '<legend>'.__('You are a family, choose which profile to edit','vja_dir').'</legend>';
                    echo '<ul class="vjaDir-family-member">';
                    $members = $this->vjaUser->get_family_members();
                    foreach ($members as $member) {
                        $vjamember = new VjaUser($member);
                        echo '<li><a href=".?action=edit&member_id='.$vjamember->ID.'">'.sprintf(__('Edit %s\'s profile','vja_dir'),$vjamember->first_name).'</a></li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    $div = ob_get_contents();
                    ob_clean();
                    return $div;
                }
                else {
                    $user = $this->vjaUser;
                }
            }
            $this->fill($user);
        }
        ob_start(); ?>
        <div id="vja_profile">
            <form name="form" method="post" action="." id="" class="form">
                <fieldset>
                    <legend><?php _e("Edit your information", "vja_dir") ?></legend>
                    <img class="alignleft size-thumbnail" src="<?php echo $user->idPhotoUrl ?> " alt="" width="150"
                         height="150">
                    <section class="vjadir-section1">
                        <label for="user_login" class="text"><?php _e("Identifier", "vja_dir") ?></label>
                        <div class="div_text">
                            <p class="noinput"><?php echo $user->user_login; ?></p>
                        </div>
                        <div class="div_checkbox">
                            <input type="checkbox" name="allow_publishphoto"
                                   value="1" <?php echo (isset($this->fields['allow_publishphoto']) && $this->fields['allow_publishphoto']) ? 'checked' : '' ?>><?php _e("Allow the publication of the photo") ?>
                        </div>
                    </section>
                    <section class="vjadir-section2">
                        <label for="first_name" class="text"><?php _e("Firstname", "vja_dir") ?><span
                                    class="req">*</span></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["first_name"])) echo '<p class="error" ' . $this->errors["first_name"] . " </p>"; ?>
                            <input name="first_name" type="text" id="first_name"
                                   value="<?php echo $this->fields["first_name"] ?>" class="textbox" required="">
                        </div>
                        <label for="last_name" class="text"><?php _e("Family name", "vja_dir") ?><span
                                    class="req">*</span></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["last_name"])) echo '<p class="error">' . $this->errors["last_name"] . " </p>"; ?>
                            <input name="last_name" type="text" id="last_name"
                                   value="<?php echo $this->fields["last_name"]; ?>" class="textbox" required="">
                        </div>
                        <label for="addr1" class="text"><?php _e("Address", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["addr1"])) echo '<p class="error">' . $this->errors["addr1"] . " </p>"; ?>
                            <input name="addr1" type="text" id="addr1" value="<?php echo $this->fields["addr1"]; ?>"
                                   class="textbox">
                        </div>
                        <label for="addr2" class="text"><?php _e("Address complement", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["addr2"])) echo '<p class="error">' . $this->errors["addr2"] . " </p>"; ?>
                            <input name="addr2" type="text" id="addr2" value="<?php echo $this->fields["addr2"]; ?>"
                                   class="textbox">
                        </div>
                        <label for="city" class="text"><?php _e("City", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["city"])) echo '<p class="error">' . $this->errors["city"] . " </p>"; ?>
                            <input name="city" type="text" id="city" value="<?php echo $this->fields["city"]; ?>"
                                   class="textbox">
                        </div>
                        <label for="zip" class="text"><?php _e("Zip Code", "vja_dir") ?><span
                                    class="req">*</span></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["zip"])) echo '<p class="error">' . $this->errors["zip"] . " </p>"; ?>
                            <input name="zip" type="text" id="zip" value="<?php echo $this->fields["zip"]; ?>"
                                   class="textbox" required="">
                        </div>
                        <label for="phone1" class="text"><?php _e("Phone number", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["phone1"])) echo '<p class="error">' . $this->errors["phone1"] . " </p>"; ?>
                            <input name="phone1" type="text" id="phone1" value="<?php echo $this->fields["phone1"]; ?>"
                                   class="textbox">
                        </div>
                        <label for="user_email" class="text"><?php _e("Email", "vja_dir") ?><span
                                    class="req">*</span></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["user_email"])) echo '<p class="error">' . $this->errors["user_email"] . " </p>"; ?>
                            <input name="user_email" type="text" id="user_email"
                                   value="<?php echo $this->fields["user_email"]; ?>" class="textbox"
                                   required="" <?php echo $user->is_primary_family_member() ? "" : "readonly" ?> >
                        </div>
                        <label for="cellphone" class="text"><?php _e("Cell Phone", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["cellphone"])) echo '<p class="error">' . $this->errors["cellphone"] . " </p>"; ?>
                            <input name="cellphone" type="text" id="cellphone"
                                   value="<?php echo $this->fields["cellphone"]; ?>" class="textbox">
                        </div>
                        <label for="birth_date" class="text"><?php _e("Birth date", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["birth_date"])) echo '<p class="error">' . $this->errors["birth_date"] . " </p>"; ?>
                            <input name="birth_date" type="text" id="birth_date"
                                   value="<?php echo $this->fields["birth_date"]; ?>" class="textbox">
                        </div>
                        <label for="sex" class="select"><?php _e("Sex", "vja_dir") ?></label>
                        <div class="div_select">
                            <?php if (isset($this->errors["sex"])) echo '<p class="error">' . $this->errors["sex"] . " </p>"; ?>
                            <select name="sex" id="sex" class="dropdown">
                                <option value="F" <?php echo "F" === $this->fields["sex"] ? 'selected="selected"' : ''; ?>><?php _e("Female", "vja_dir"); ?></option>
                                <option value="M" <?php echo "M" === $this->fields["sex"] ? 'selected="selected"' : ''; ?>><?php _e("Male", "vja_dir"); ?></option>
                            </select>
                        </div>
                        <label for="license_number" class="text"><?php _e("License number", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["licence_number"])) echo '<p class="error">' . $this->errors["licence_number"] . " </p>"; ?>
                            <input name="license_number" type="text" id="license_number"
                                   value="<?php echo $this->fields["license_number"]; ?>" class="textbox">
                        </div>
                        <label for="vma" class="text"><?php _e("MAS", "vja_dir") ?></label>
                        <div class="div_text">
                            <?php if (isset($this->errors["vma"])) echo '<p class="error">' . $this->errors["vma"] . " </p>"; ?>
                            <input name="vma" type="text" id="vma" value="<?php echo $this->fields["vma"]; ?>"
                                   class="textbox">
                        </div>
                        <label for="sport" class="select"><?php _e("Favorite sport", "vja_dir") ?></label>
                        <div class="div_select">
                            <?php if (isset($this->errors["sport"])) echo '<p class="error">' . $this->errors["sport"] . " </p>"; ?>
                            <select name="sport" id="sport" class="dropdown">
                                <option value="Course" <?php echo "Course" === $this->fields["sport"] ? 'selected="selected"' : ''; ?>><?php _e("Running", "vja_dir"); ?></option>
                                <option value="Marche" <?php echo "Marche" === $this->fields["sport"] ? 'selected="selected"' : ''; ?>><?php _e("Walking", "vja_dir"); ?></option>
                            </select>
                        </div>
                    </section>
                    <input name="action" type="hidden" value="update">
                    <input name="user" type="hidden" value="<?php echo $user->ID; ?>">
                    <?php wp_nonce_field("vjaprofile" . $user->ID); ?>
                    <div class="div_button">
                        <input name="submit" type="submit"
                               value=<?php _e("Update Profile", "vja_dir") ?> class="buttons">
                    </div>
                    <div class="req-text">
                        <span class="req">*</span><?php _e("Required Field", "vja_dir") ?>
                    </div>
                    <?php if ($user->is_family_member()) : ?>
                        <div class="vjaDir-return">
                            <a href=".">
                                <?php _e('< Return to the family members list', 'vja_dir'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </fieldset>
            </form>
        </div>
        <?php $form = ob_get_contents();
        ob_clean();
        return $form;
    }

    /**
     * Fill the fields array used by GUMP
     *
     * @param VjaUser
     */

    public function fill($vjaUser) {
        $this->fields['user_login']=$vjaUser->user_login;
        $this->fields['first_name']=$vjaUser->first_name;
        $this->fields['last_name']=$vjaUser->last_name;
        $this->fields['addr1']=$vjaUser->addr1;
        $this->fields['addr2']=$vjaUser->addr2;
        $this->fields['city']=$vjaUser->city;
        $this->fields['zip']=$vjaUser->zip;
        $this->fields['phone1']=$vjaUser->phone1;
        // gets the primary email whether the user is or not the primary family member
        $this->fields['user_email']=$vjaUser->email;
        $this->fields['cellphone']=$vjaUser->cellphone;
        $this->fields['birth_date']=$vjaUser->birth_date;
        $this->fields['license_number']=$vjaUser->license_number;
        $this->fields['vma']=$vjaUser->vma;
        $this->fields['sex']=$vjaUser->sex;
        $this->fields['sport']=$vjaUser->sport;
        $this->fields['allow_publishphoto']=$vjaUser->allowPublishIDPhoto;
    }

    /**
     * Process the form data - filter, sanitize and validate
     * 
     *
     * @output Boolean true if no error detected, false otherwise (errors array is filled with the error messages)
     */

    public function validate() {
        $validator = new \GUMP('fr');
        $validator->validation_rules(array(
            "first_name" => "required||alpha",
            "last_name"     => "required||alpha",
            "zip"           => "integer||max_len,5|min_len,5",
            "phone1"        => 'regex,/^(?:(?:\+\d\d?)[-\s]?(?:\(\d\)[-\s]?\d|\d)(?:[-\s]?\d){8})|(?:[-\s]?\d){10}$/',
            "user_email"     => "required||valid_email",
            "cellphone"     => 'regex,/^(?:(?:\+\d\d?)[-\s]?(?:\(\d\)[-\s]?\d|\d)(?:[-\s]?\d){8})|(?:[-\s]?\d){10}$/',
            "birth_date"    => "date,d/m/Y",
            "vma"           => "float",
            "sex"           => "contains_list,F;M",
            "sport"         => "contains_list,Course;Marche"
        ));
        $validator->filter_rules(array(
            "first_name"    => "trim",
            "last_name"     => "upper_case||trim",
            "addr1"         => "trim",
            "addr2"         => "trim",
            "city"          => "trim",
            "zip"           => "trim",
            "phone1"        => "trim",
            "user_email"    => "trim",
            "cellphone"     => "trim",
            "birth_date"    => "trim",
            "license_number" => "trim",
            "vma"           => "trim",
            "sex"           => "trim",
            "sport"         => "trim"
        ));
        $this->fields=$validator->sanitize($_POST, array(
            'user_login',
            'last_name',
            'first_name',
            'addr1',
            'addr2',
            'city',
            'zip',
            'phone1',
            'user_email',
            'cellphone',
            'birth_date',
            'license_number',
            'vma',
            'sex',
            'allow_publishphoto',
            'sport'
        ));
        // Use a modified version of Validator allowing an extra 'rules delimiter' parameter
        // This parameter prevents validation errors when for example a validation expression contains a '|'
        $data = $validator->run($this->fields, false, '||');
        if (false===$data) {
            $this->errors = $validator->get_errors_array();
            return false;
        }
        else {
            $this->fields = $data;
        }
        return true;
    }

    /**
     * Update user
     *
     * @param VjaUser $vjaUser
     *
     */
    public function update($vjaUser) {
        $family_ids = array();
        if ($vjaUser->is_family_member()) $family_ids = $vjaUser->get_family_members();
        $vjaUser->first_name=$this->fields['first_name'];
        $vjaUser->last_name=$this->fields['last_name'];
        $vjaUser->addr1=$this->fields['addr1'];
        $vjaUser->addr2=$this->fields['addr2'];
        $vjaUser->city=$this->fields['city'];
        $vjaUser->zip=$this->fields['zip'];
        $vjaUser->phone1=$this->fields['phone1'];
        $vjaUser->email=$this->fields['user_email'];
        $vjaUser->cellphone=$this->fields['cellphone'];
        $vjaUser->birth_date=$this->fields['birth_date'];
        $vjaUser->license_number=$this->fields['license_number'];
        $vjaUser->vma=$this->fields['vma'];
        $vjaUser->sex=$this->fields['sex'];
        $vjaUser->sport=$this->fields['sport'];
        $this->fields['allow_publishphoto']=isset($_POST['allow_publishphoto'])?true:false;
        $vjaUser->allowPublishIDPhoto=$this->fields['allow_publishphoto'];
        $vjaUser->save();
        foreach ($family_ids as $member_id) {
            if ($member_id != $vjaUser->ID){
                $member = new VjaUser($member_id);
                // If the email address has been modified - update all family members' address
                $member->email = $this->fields['user_email'];
                $member->addr1=$this->fields['addr1'];
                $member->addr2=$this->fields['addr2'];
                $member->city=$this->fields['city'];
                $member->zip=$this->fields['zip'];
                $member->save();
            }
        }
    }

}