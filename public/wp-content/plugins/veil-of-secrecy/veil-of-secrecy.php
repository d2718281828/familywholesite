<?php
/*
Plugin Name: Veil of Secrecy
Plugin URI:
Description: Hide the site behind a read password
Version: 0.1
Author: Derek Storkey
Author URI:
License: GPLv2 or later
Text Domain: gsgdomain
*/

// must let signin/registration through - test
// test the veil_of_secrecy function
// signin link didnt work
// provide a signout link? a function call for the template?
/// remember me not tested
// future - maybe add images?
// this should all be static really, since it has no function after the initialisation

class VeilOfSecrecy {

    protected $ok = false;

    public function __construct(){
        add_action("wp_head", array($this,"cutoff"), 0 );

        add_action("admin_init", array($this,"addSettings"));
    }

    public function cutoff($wholepage = false){
        $this->ok = $this->check();
        if ($this->ok) return;

        if ($wholepage) $this->echoHeader();
        $this->style();
        echo "</head><body class='vos-welcome-page'>";

        $this->welcomeMessage();

        echo '<div class="prompt">';
        echo $this->password_prompt();
        echo '</div>';

        echo "</body></html>";
        die();
    }
    protected function style(){
        $file = "/veil-of-secrecy/style.css";
        if (!file_exists(get_stylesheet_directory().$file)) return;
        $url = get_stylesheet_directory_uri();
        echo "<link rel='stylesheet' id='veil-of-secrecy'  href='$url$file' type='text/css' media='all' />";
    }
    protected function password_prompt(){
        $m = "";
        $login = wp_login_url();
        $m = "<form method='post'>";
	$m.= "<div class='vos-signin-box'>";
        $m.= "<p>For quick access to view the site, enter group password <input type='password' name='vos_password'> </p>";
        $m.= "<p><input type='checkbox' value='1' name='rememberme'> Remember me - only if this is your computer. If it is not your computer, close your browser when you have finished.</p>";
        $m.= "<p><input type='submit' value='Login to View'></p>";
	$m.= "</div>";
        $m.= "<p>To make changes to the site you must <a href='$login'>Sign In</a> with your own password.</p>";
        $m.="</form>";
        return $m;
    }
    protected function check(){
        //error_log("****** Checking <<<<");
        if (is_user_logged_in()) return true;

        if (isset($_REQUEST["vos_password"])) {
            $token = $this->makeToken($_REQUEST["vos_password"]);
            $save = true;
        } elseif (isset($_COOKIE["vos_password_token"])) {
            $token = $_COOKIE["vos_password_token"];
            $save = false;
        }
        else return false;


        $thetoken = get_site_option("vos_password");

        //error_log("****** Checking  - have a token =".$token." and a system setting token ".$thetoken);

        if ($token != $thetoken) return false;

        // save cookie for session
        if ($save){
            $expiry = isset($_REQUEST["rememberme"]) && $_REQUEST["rememberme"] ? time()+24*3600 : 0;   // 24 hours remembrance

            setcookie("vos_password_token", $token , $expiry , '/');
        }
        return true;

    }
    protected function echoHeader(){
        echo <<<HDR
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
HDR;

    }
    protected function makeToken($pass){
        $suf = "X^&@X";
        error_log("***** just checking ".substr($pass,-1));
        if (substr($pass,-5)==$suf) return $pass;    // dont do it twice.
        return hash("sha256", $pass.(defined("VOS_SALT") ? VOS_SALT : 'fjeiwnef&*ivnwie55fnv2ienfj5vi')).$suf;
    }
    protected function welcomeMessage(){
        $old = get_site_option('vos_welcome_page');
        if ($old==-1) return;
        
        $post = get_post($old);

        echo "<div class='welcome'>";
        echo do_shortcode($post->post_content);
        echo "</div>";
    }

    public function addSettings(){
        $settings_group = 'reading';     // same as page
        $setting_name1 = 'vos_password'; // = option name
        $setting_name2 = 'vos_welcome_page'; // = option name
        register_setting( $settings_group, $setting_name1 , array($this, "emptypassword"));
        register_setting( $settings_group, $setting_name2 );

        $settings_section = 'vos_settings_sect';
        $page = $settings_group;
        add_settings_section($settings_section, __( "Secrecy Veil", 'veil-of-secrecy' ), array($this, "writeSettings"), $page );
        add_settings_field($setting_name1,"Password for the veil", array($this,'writePassword'), $page,$settings_section );
        add_settings_field($setting_name2,"Choose a page which has welcome text", array($this,'selectWelcome'), $page,$settings_section );
    }

    public function writeSettings(){
        echo "<p>There are just two settings, the password, and a page containing a welcome message.</p>";
    }
    public function writePassword(){
        echo "<p><input name='vos_password' type='text' value=''> Enter new password here, blank to leave it unchanged.</p>";
    }
    public function selectWelcome(){
        global $wpdb;

        $s = "select ID, post_title from ".$wpdb->posts." where post_type = 'page' and post_status='publish';";
        $pages = $wpdb->get_results($s, ARRAY_A);

        $old = get_site_option('vos_welcome_page');
        echo '<p><select name="vos_welcome_page">';
        $sel = ($old==-1) ? ' selected' : '';
        echo '<option value="-1"'.$sel.'>None</option>';
        foreach($pages as $page){
            $sel = ($old == $page["ID"]) ? ' selected' : '';
            echo '<option value="'.$page["ID"].'"'.$sel.'>'.$page["post_title"].'</option>';
        }
        echo '</select>';
    }
    public function pass($arg){
        return $arg;
    }

    /**
     * setting registration sanitise callback: The pw is saved internally as a hashed token.
     * So we dont want to show it - an empty field here means dont change it. However the settings API is going to change it.
     * So we need to look up the old value and provide that, if the field is empty. Ifnot we hash it.
     * @param $pass
     * @return mixed|string
     */
    public function emptypassword($pass){
        $old = get_site_option('vos_password');
        if ($pass == '') return $old;
        return $this->makeToken($pass);
    }
}
$veil_of_secrecy = new VeilOfSecrecy();

/**
 * Function to be called in header.php, before anything else.
 * If the content is veiled, it will output a simple page header and the normal veil message.
 */
function veil_of_secrecy(){
    global $veil_of_secrecy;
    $veil_of_secrecy->cutoff(true);
}
