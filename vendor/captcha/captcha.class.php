<?php
/*
 * Modified by cartpauj 2023
 * @class Phoca Captcha
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 */

if(!defined('ABSPATH')) { die("Cheating?"); }

class ModPhocaCaptcha {
  public static function displayCaptcha($rand_char) {
    // Padding for the characters
    $x10 = "          ";
    $x08 = "        ";
    $x06 = "      ";
    $x04 = "    ";
    $x02 = "  ";

    $rand_char_array = array(
      $rand_char[0] . $x10,
      $x02 . $rand_char[1] . $x08,
      $x04 . $rand_char[2] . $x06,
      $x06 . $rand_char[3] . $x04,
      $x08 . $rand_char[4] . $x02,
      $x10 . $rand_char[5]
    );

    $file_name  = self::getRandomImage();
    $image      = ImageCreateFromJPEG(__DIR__ . '/images/' . $file_name);

    foreach($rand_char_array as $key => $value) {
      $font_color = self::getRandomFontColor();
      $position_x = self::getRandomPositionX();
      $position_y = self::getRandomPositionY();
      $font_size  = self::getRandomFontSize();

      ImageString(
        $image,
        $font_size,
        $position_x,
        $position_y,
        $value,
        ImageColorAllocate (
          $image,
          $font_color[0],
          $font_color[1],
          $font_color[2]
        )
      );
    }

    header('Content-type: image/jpeg');
    ImageJPEG($image, NULL, 100);
    ImageDestroy($image);
  }

  public static function generateRandomChar($string_length) {
    $digit = array(/*0,1,*/2,3,4,/*5,*/6,7,8,9);
    $lower = array('a','b','c','d','e','f','g','h',/*'i',*/'j','k',/*'l',*/'m','n',/*'o',*/'p','q','r',/*'s',*/'t','u','v','w','x','y','z');
    $upper = array('A','B','C','D','E','F','G','H',/*'I',*/'J','K','L','M','N',/*'O',*/'P','Q','R',/*'S',*/'T','U','V','W','X','Y','Z');

    $char_group = array_merge($digit, $lower, $upper);

    srand((int)(microtime(true) * rand(10000,1000000)));

    $random_string = '';

    for($ch = 0; $ch < $string_length; $ch++) {
      $char_group_start   = 0;
      $char_group_end     = sizeof($char_group) - 1;
      $random_char_group  = rand($char_group_start, $char_group_end);
      $random_string      .= $char_group[$random_char_group];
    }

    return $random_string;
  }

  public static function getRandomImage() {
    $rand   = mt_rand(1, 6);
    $image  = '0' . $rand . '.jpg';

    return $image;
  }

  public static function getRandomPositionX() {
    $rand = mt_rand(2, 3);

    return $rand;
  }

  public static function getRandomPositionY() {
    $rand = mt_rand(1, 4);

    return $rand;
  }

  public static function getRandomFontSize() {
    $rand = mt_rand(8, 12);

    return $rand;
  }

  public static function getRandomFontColor() {
    $rand = mt_rand(1, 6);

    if($rand == 1) {$font_color[0] = 0;    $font_color[1] = 0;    $font_color[2] = 0;}
    if($rand == 2) {$font_color[0] = 0;    $font_color[1] = 0;    $font_color[2] = 153;}
    if($rand == 3) {$font_color[0] = 0;    $font_color[1] = 102;  $font_color[2] = 0;}
    if($rand == 4) {$font_color[0] = 102;  $font_color[1] = 51;   $font_color[2] = 0;}
    if($rand == 5) {$font_color[0] = 163;  $font_color[1] = 0;    $font_color[2] = 0;}
    if($rand == 6) {$font_color[0] = 0;    $font_color[1] = 82;   $font_color[2] = 163;}

    return $font_color;
  }

  public static function encryptString($plain_text, $password, $encoding = 'base64') {
    if($plain_text != null && $password != null) {
      $keysalt = openssl_random_pseudo_bytes(16);
      $key = hash_pbkdf2("sha512", $password, $keysalt, 20000, 32, true);
      $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-gcm"));
      $tag = "";
      $encrypted_string = openssl_encrypt($plain_text, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag, "", 16);
      return $encoding == "hex" ? bin2hex($keysalt.$iv.$encrypted_string.$tag) : ($encoding == "base64" ? base64_encode($keysalt.$iv.$encrypted_string.$tag) : $keysalt.$iv.$encrypted_string.$tag);
    }
    return '';
  }

  public static function decryptString($encrypted_string, $password, $encoding = 'base64') {
    if($encrypted_string != null && $password != null) {
      $encrypted_string = $encoding == "hex" ? hex2bin($encrypted_string) : ($encoding == "base64" ? base64_decode($encrypted_string) : $encrypted_string);
      $keysalt = substr($encrypted_string, 0, 16);
      $key = hash_pbkdf2("sha512", $password, $keysalt, 20000, 32, true);
      $ivlength = openssl_cipher_iv_length("aes-256-gcm");
      $iv = substr($encrypted_string, 16, $ivlength);
      $tag = substr($encrypted_string, -16);
      return openssl_decrypt(substr($encrypted_string, 16 + $ivlength, -16), "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
    return '';
  }
}
