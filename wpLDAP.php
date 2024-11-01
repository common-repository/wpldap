<?php
/*
Plugin Name: WordPress LDAP Authentication
Plugin URI: http://ashay.org/?page_id=133
Description: LDAP Authentication for Word Press. Tested on Wordpress 2.1
Version: 1.02
Author: Ashay Manjure
Author URI: http://ashay.org
*/


require_once("adLDAP.php");

/**
 * Adds the Menu for Wordpress Admin Panel
 *
 */
function wpldap_addmenu() 
{
	if(function_exists('add_options_page'))	{
		add_options_page('LDAP Authentication Options', 'wpLDAP Options', 9, basename(__FILE__), 'ldapOptionsPanel');
	}
}

/**
 * Adds the CSS which is used on the WP Options page
 *
 */
function wpldap_addcss()
{
	echo "<link rel='stylesheet' href='".get_settings('siteurl')."/wp-content/plugins/ldap_auth.css' media='screen' type='text/css' />";
}

/**
 * Stuff that goes in the wpLDAP options panel
 *
 */
function ldapOptionsPanel()
{
	if($_POST['ldapOptionsSave']) {
		update_option('ldapControllers',$_POST['ldapControllers']);
		update_option('ldapBaseDn', $_POST['ldapBaseDn']);
		update_option('ldapAccountSuffix', $_POST['ldapAccountSuffix']);
		update_option('ldapEnable', $_POST['ldapEnable']);
		update_option('ldapCreate', $_POST['ldapCreate']);
		
		echo "<div class='updated'><p>Saved Options!</p></div>";
	}
	
	$ldapControllers = get_option("ldapControllers");
	$ldapBaseDn = get_option("ldapBaseDn");
	$ldapAccountSuffix = get_option("ldapAccountSuffix");
	$ldapEnable = get_option("ldapEnable");
	$ldapCreate = get_option("ldapCreate");

	if($ldapEnable)	{
		$tChecked = "checked";
	} else {
		$fChecked = "checked";
	}
	
	if($ldapCreate)	{
		$tCreate = "checked";
	} else {
		$fCreate = "checked";
	}
		
	echo <<<LdapForm
	<div class="wrap">
	<h2>wpLDAP Options</h2>
	<form method="post" id="ldap_auth_options">
		<fieldset class="options">
		<div class="row">
			<span class="description">Domain Controllers (LDAP Server)</span>
			<span class="element">
				<input type='text' name='ldapControllers' value='$ldapControllers' /><br />
				<em>The name or IP address of the LDAP server(s). Separate multiple entries by a comma (,).</em>
			</span>
		</div>
        <br/>
		<div class="row">
			<span class="description">Base DN</span>
			<span class="element">
				<input type='text' name='ldapBaseDn' value='$ldapBaseDn' /><br />
				<em>The base DN for carrying out LDAP searches.</em>
			</span>
		</div>
        <br/>
		<div class="row">
			<span class="description">Account Suffix</span>
			<span class="element">
				<input type='text' name='ldapAccountSuffix' value='$ldapAccountSuffix' /><br />
				<em>Suffix needed to be appended to the username. e.g. @domain.com</em>
			</span>
		</div>
		<br/>
		<div class="row">
			<span class="description">Enable LDAP?</span>
			<span class="element">
				<input type='radio' name='ldapEnable' value='1' $tChecked/> Yes &nbsp;
				<input type='radio' name='ldapEnable' value='0' $fChecked/> No
			</span>
		</div>
		<br/>
		<div class="row">
			<span class="description">If the user does not exist in the system, create a <b>new</b> WordPress user from LDAP (when they sign in)?</span>
			<span class="element">
				<input type='radio' name='ldapCreate' value='1' $tCreate/> Yes &nbsp;
				<input type='radio' name='ldapCreate' value='0' $fCreate/> No
			</span>
		</div>
		
		<p class="submit"><input type="submit" name="ldapOptionsSave" value="Save" /></p>
		</fieldset>
	</form>
	</div>
LdapForm;
}

if (!function_exists('wp_login') ) {
    function wp_login($username, $password, $already_md5 = false)
    {
    	global $wpdb, $error;
    
    	if(!$username) {
    		return false;
    	}
    
    	if(!$password) {
    		$error = __('<strong>Error</strong>: The password field is empty.');
    		return false;
    	}
    	
    	$ldapControllers = get_option("ldapControllers");
    	$ldapBaseDn = get_option("ldapBaseDn");
    	$ldapAccountSuffix = get_option("ldapAccountSuffix");
    	$ldapEnable = get_option("ldapEnable");
    	$ldapCreate = get_option("ldapCreate");
    	$ldapCookieMarker = get_option("ldapCookieMarker");
    	
    	
    	if($ldapEnable && !$ldapCookieMarker) {
    		update_option("ldapCookieMarker", "LDAP");
    		$ldapCookieMarker = get_option("ldapCookieMarker");
    	}
    	
    	/**
    	 * Get the login object. We will use it for first user insertion or when the LDAP option is not 
    	 * activated.
    	 */
    	$login = $wpdb->get_row("SELECT ID, user_login, user_pass FROM $wpdb->users WHERE user_login = '$username'");
    
    	//admin users are not authenticated through LDAP
    	if (($ldapEnable) && ($username != "admin")) 
    	{
    	   /* Set up the options for the adLDAP Class */
    	   if(!empty($ldapAccountSuffix)){	
	   	       $adOptions = 
	   	           array( "account_suffix" => $ldapAccountSuffix, 
	   	                   "base_dn" => $ldapBaseDn, 
	   	                   "domain_controllers" => explode(",", $ldapControllers)
	   	                 ); 
	       } else { 
	   	       $adOptions = 
	   	               array(  "base_dn" => $ldapBaseDn, 
	   	                       "domain_controllers" => explode(",", $ldapControllers)
	   	                      ); 
	       }	   
	   // If already_md5 is TRUE, then we're getting the user/password from the cookie. As we don't want to store LDAP passwords in any
   		// form, we've already replaced the password with the hashed username and LDAP_COOKIE_MARKER
    		if ($already_md5) 
    		{
    			if ($password == md5($username).md5($ldapCookieMarker))
    			{
    				return true;
    			}
    		}
    		
    		// No cookie, so have to authenticate them via LDAP
    		$objLDAP = new adLDAP($adOptions);
    		
    		if($objLDAP->authenticate($username,$password)){
    		    //the user is a valid user. now see if he/she exists in the system.
    		    // if the user does not exist and the option says to create a new user on first signon
    		    // create the new user and then return true
    		    if(!$login && $ldapCreate) {
                    require_once( ABSPATH . WPINC . '/registration.php');
                    //Get User Details from the LDAP Server.
                    $userData = $objLDAP->user_info($username);
                    $userLogin = sanitize_user( $username );
                    $userEmail = apply_filters( 'user_registration_email', $userData[0]["mail"][0]); 
                    $user_id = wp_create_user( $userLogin, $password, $userEmail);
                    return true;
    		    } else {
    		      return true;    
    		    }
    		    
    		} 
    		else if ($login) 
    		{ 
    		    // If the password is already_md5, it has been double hashed.
    			// Otherwise, it is plain text.
    			if ( ($already_md5 && $login->user_login == $username && md5($login->user_pass) == $password) || ($login->user_login == $username && $login->user_pass == md5($password)) ) 
    			{
    				return true;
    			} 
    			else 
    			{
    				$error = __('<strong>Error</strong>: Incorrect password.');
    				$pwd = '';
    				return false;
    			}
    		}
    	    else 
            {
    		    $error = __('<strong>Error</strong>: Could not Authenticate user. Please check credentials');
                $pwd = '';
                return false;
    		}
    		
    	} // if (LDAP_ENABLED)
    	else 
    	{
    		if (!$login) 
    		{
    			$error = __('<strong>Error</strong>: Wrong login.');
    			return false;
    		} 
    		else 
    		{
    			// If the password is already_md5, it has been double hashed.
    			// Otherwise, it is plain text.
    			if ( ($already_md5 && $login->user_login == $username && md5($login->user_pass) == $password) || ($login->user_login == $username && $login->user_pass == md5($password)) ) 
    			{
    				return true;
    			} 
    			else 
    			{
    				$error = __('<strong>Error</strong>: Incorrect password.');
    				$pwd = '';
    				return false;
    			}
    		}
    	}
    }
}

if ( !function_exists('wp_setcookie') ) :
function wp_setcookie($username, $password, $already_md5 = false, $home = '', $siteurl = '') 
{
	$ldapCookieMarker = get_option("ldapCookieMarker");
	$ldapEnable = get_option("ldapEnable");
	
	if(($ldapEnable) && ($username != "admin"))
	{
		$password = md5($username).md5($ldapCookieMarker);
	}
	else 
	{
		if(!$already_md5)
		{
			$password = md5( md5($password) ); // Double hash the password in the cookie.
		}
	}

	if(empty($home))
	{
		$cookiepath = COOKIEPATH;
	}
	else
	{
		$cookiepath = preg_replace('|https?://[^/]+|i', '', $home . '/' );
	}

	if ( empty($siteurl) ) 
	{
		$sitecookiepath = SITECOOKIEPATH;
		$cookiehash = COOKIEHASH;
	} 
	else 
	{
		$sitecookiepath = preg_replace('|https?://[^/]+|i', '', $siteurl . '/' );
		$cookiehash = md5($siteurl);
	}

	setcookie('wordpressuser_'. $cookiehash, $username, time() + 31536000, $cookiepath);
	setcookie('wordpresspass_'. $cookiehash, $password, time() + 31536000, $cookiepath);

	if ( $cookiepath != $sitecookiepath ) 
	{
		setcookie('wordpressuser_'. $cookiehash, $username, time() + 31536000, $sitecookiepath);
		setcookie('wordpresspass_'. $cookiehash, $password, time() + 31536000, $sitecookiepath);
	}
}
endif;

add_action('admin_menu', 'wpldap_addmenu');
add_action('admin_head', 'wpldap_addcss');
?>
