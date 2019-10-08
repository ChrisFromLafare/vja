<?php

namespace VjaDirectory\Users;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Filter used to modify the user's avatar
class VjaAvatar {

    function __construct(){
        add_filter( 'get_avatar' , array($this, 'avatar') , 20 , 5 );
    }

    function avatar( $avatar, $id_or_email, $size, $default, $alt ) {
        $user = new VjaUser($id_or_email);
        $avatarUrl = $user->idPhotoUrl;
        $avatar = "<img alt='{$alt}' src='{$avatarUrl}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        return $avatar;
    }
}