<?php
/*
Plugin Name: Cartpauj Register Captcha
Description: Adds a captcha form to WordPress registration page to prevent SPAM registrations.
Version: 2.0.1
Author: cartpauj
Copyright: 2009-2023, cartpauj

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if(!defined('ABSPATH')) { die("Cheating?"); }

// Do nothing if we don't have GD or OpenSSL
if(!function_exists('ImageCreateFromJPEG') || !function_exists('openssl_random_pseudo_bytes')) { return; }

// Load up the Phoca class
include_once(__DIR__ . '/vendor/captcha/captcha.class.php');

// Generate an image via admin-ajax.php
function crcc_get_img() {
  $password = crcc_get_password(true);
  $encrypted_string = isset($_GET['encrypted_string']) ? sanitize_text_field(rawurldecode($_GET['encrypted_string'])) : false;
  if(empty($encrypted_string)) {
    _e('Error generating Captcha. Please refresh the page.');
    die();
  }
  $decrypted_string = ModPhocaCaptcha::decryptString($encrypted_string, $password);
  if(empty($decrypted_string)) {
    _e('Error generating Captcha. Please refresh the page.');
    die();
  }

  ModPhocaCaptcha::displayCaptcha($decrypted_string);
  status_header(200);
  nocache_headers();
  die();
}
add_action('wp_ajax_crcc_get_img', 'crcc_get_img');
add_action('wp_ajax_nopriv_crcc_get_img', 'crcc_get_img');

// Show captcha image and input on registration form
function crcc_add_to_form() {
  $password = crcc_get_password(true);
  $plaintext = ModPhocaCaptcha::generateRandomChar(6);
  $encrypted_string = rawurlencode(ModPhocaCaptcha::encryptString($plaintext, $password));
  ?>
    <p>
      <label for="crcc_code"><?php _e('Enter the characters below'); ?></label>
      <input type="text" name="crcc_code" id="crcc_code" class="input" size="20" required="required" autocomplete="off" />
      <br/>
      <img src="<?php echo admin_url('admin-ajax.php') . '?action=crcc_get_img&encrypted_string=' . $encrypted_string; ?>" />
      <input type="hidden" name="crcc_code_crypt" value="<?php echo $encrypted_string; ?>" />
    </p>
  <?php
}
add_action('register_form', 'crcc_add_to_form', 1000);

// Validate the captcha
function crcc_check_code($login, $email, $errors) {
  $password = crcc_get_password(true);

  $error_str = '<strong>' . __('Error') . ':</strong> ';

  if(!isset($_POST['crcc_code']) || empty($_POST['crcc_code'])) {
    $errors->add('crcc_error', $error_str . __('You must enter a valid CAPTCHA code.'));
    return;
  }

  $user_code = sanitize_text_field(trim($_POST['crcc_code']));
  $encrypted_string = sanitize_text_field(rawurldecode($_POST['crcc_code_crypt']));
  $decrypted_string = ModPhocaCaptcha::decryptString($encrypted_string, $password);

  if($user_code != $decrypted_string) {
    $errors->add('crcc_error', $error_str . __('The characters you entered did not match. Characters are case-sensitive. Please try again.'));
    return;
  }
}
add_action('register_post', 'crcc_check_code', 10, 3);

// Create and return the random password, rotates every 3 hours
function crcc_get_password($r = false) {
  $password = get_transient('crcc_crypt_password');

  if(empty($password)) {
    $password = wp_generate_password(30, false, false);
    set_transient('crcc_crypt_password', $password, 3 * HOUR_IN_SECONDS);
  }

  if($r) {
    return $password;
  }
}
add_action('plugins_loaded', 'crcc_get_password');
