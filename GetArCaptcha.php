<?php
/**
 * Project:     ArCaptcha: A PHP class for creating and managing form Arabic CAPTCHA images<br />
 * File:        GetArCaptcha.php
 *
 * Copyright (c) 2013, Mohammad Anini
 * All rights reserved.
 *
 * @link http://arcaptcha.anini.me/en ArCaptcha Arabic PHP CAPTCHA
 * @link http://arcaptcha.anini.me/en/download Download Latest Version
 * @link http://arcaptcha.anini.me/en/docs Online Documentation
 * @link http://arcaptcha.anini.me/en/demo Generate Customized Code
 * @copyright 2013 Anini
 * @author Mohammad Anini <mohd.anini@gmail.com>
 * @version 1.0.0 (November 8, 2013)
 * @package ArCaptcha
 */

/**
 * Remove the "//" from the following line for debugging problems
 */
// error_reporting(E_ALL); ini_set('display_errors', 1);

require_once dirname(__FILE__) . '/ArCaptcha.php';

$img = new ArCaptcha();

/**
 * You can customize the image by making changes below - remove the "//" to uncomment
 */
// $img->transparent	= true; // boolean whether to use transparent background.
// $img->back_color		= 0xFFFFFF; // integer the background color. For example, 0xC8F0F0.
// $img->noise_color	= 0xDCDCC8; // integer the font color. For example, 0x55FF00.
// $img->darkness_level	= 5; // integer darkness level of the characters color. Minimum value is 1 and maximum value is 10.
// $img->font_file		= '//assets/DroidNaskh.ttf'; // string the TrueType font file path.
// $img->length			= 4; // integer number characters of the verify code. Minimum value is 3 and maximum value is 28.
// $img->font_size		= 20; // integer font size of the captcha.
// $img->draw_lines		= true; // boolean whether to draws distorted lines on the image.
// $img->num_lines		= 4; // integer number of distorted lines on the image.
// $img->draw_noise		= true; // boolean whether to draws noise on the image.
// $img->shadow			= false; // boolean whether to add shadow to the letters.
// $img->fixed_verify_code = null; // string the fixed verification code. For automated tests.

/**
 * Outputs the image and content headers to the browser
 */
$img->render();
