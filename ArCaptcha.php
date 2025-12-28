<?php
/**
 * Project:     ArCaptcha: A PHP class for creating and managing Arabic CAPTCHA images
 * File:        ArCaptcha.php
 *
 * Copyright (c) 2013, Mohammad Anini
 * The project is licensed under a Creative Commons BY-NC-SA 3.0 License.
 *
 * @link https://arcaptcha.anini.me/				ArCaptcha Arabic PHP CAPTCHA
 * @link https://arcaptcha.anini.me/download 		Download Latest Version
 * @link https://arcaptcha.anini.me/user-guide	 	Online Documentation
 * @link https://arcaptcha.anini.me/demo 			Generate Customized Code
 * @copyright 2013 Anini
 * @author Mohammad Anini <https://anini.me>
 * @version 1.0.0 (November 8, 2013)
 * @package ArCaptcha
 */

class ArCaptcha
{
	/**
	 * @var boolean whether to use transparent background. Defaults to true.
	 */
	public $transparent = true;
	/**
	 * @var hex the background color. For example, 0xC8F0F0.
	 * Defaults to 0xFFFFFF, meaning white color.
	 */
	public $back_color = 0xFFFFFF;
	/**
	 * @var hex the font color. For example, 0x55FF00. Defaults to 0xDCDCC8.
	 */
	public $noise_color = 0xDCDCC8;
	/**
	 * @var integer darkness level of the characters color.
	 * Minimum value is 1 and maximum value is 10. Defaults to 5.
	 */
	public $darkness_level = 5;
	/**
	 * @var string the TrueType font file. Defaults to DroidNaskh.ttf which is provided.
	 */
	public $font_file = '//assets/DroidNaskh.ttf';
	/**
	 * @var string the fixed verification code. When this is property is set,
	 * {@link getVerifyCode} will always return this value.
	 * This is mainly used in automated tests where we want to be able to reproduce
	 * the same verification code each time we run the tests.
	 * Defaults to null, meaning the verification code will be randomly generated.
	 */
	public $fixed_verify_code;
	/**
	 * @var integer number characters of the verify code.
	 * Minimum value is 3 and maximum value is 28. Defaults to 4.
	 */
	public $length = 4;
	/**
	 * @var integer font size of the captcha. Defaults to 20.
	 * The height and the width of the captcha will be calculated regarding to the
	 * font size.
	 */
	public $font_size = 20;
	/**
	 * @var boolean whether to draws distorted lines on the image. Defaults to true.
	 */
	public $draw_lines = true;
	/**
	 * @var integer number of distorted lines on the image. Defaults to 4.
	 */
	public $num_lines = 4;
	/**
	 * @var boolean whether to draws noise on the image. Defaults to true.
	 */
	public $draw_noise = true;
	/**
	 * @var boolean whether to add shadow to the letters. Defaults to true.
	 */
	public $shadow = false;
	/**
     * @var string the session name ArCaptcha should use, only set this if your
     * application uses a custom session name
     * @var string
     */
    public $session_name = null;
	/**
	 * @var array of the arabic letters.
	 */
	protected $arabic_letters;
	/**
	 * @var integer width of the captcha, it would be calculated using font_size and length.
	 * {@link calculateSize}
	 */
	protected $width;
	/**
	 * @var integer height of the captcha, it would be calculated using font_size.
	 * {@link calculateSize}
	 */
	protected $height;

    function __construct()
    {
    	$this->arabic_letters = array(
    		'أ','ب','ت','ث','ج','ح','خ','د','ذ','ر','ز','س','ش','ص','ض','ط','ظ','ع','غ','ف','ق','ك','ل','م','ن','ﮬ','و','ي'
    		);
    	// Probably optional since array_is randomized; this may be redundant
    	shuffle($this->arabic_letters);
    }

    /**
	 * Gets the verification code.
	 * @param boolean $regenerate whether the verification code should be regenerated.
	 * @return string the verification code.
	 */
    public function getVerifyCode($regenerate=false)
    {
    	// Used for automated test
    	if ($this->fixed_verify_code !== null)
    	{
    		return $this->fixed_verify_code;
    	}

    	// Initialize session or attach to existing
    	if (!isset($_SESSION))
    	{
    		// No session has been started yet, which is needed for validation
    		if (!is_null($this->session_name) && trim($this->session_name) != '')
    		{
    			// Set session name if provided
    			session_name(trim($this->session_name));
    		}
    		session_start();
    	}
    	
		$key = $this->getSessionKey();
		if (!isset($_SESSION[$key]) || $regenerate)
		{
			$_SESSION[$key] = $this->generateVerifyCode();
		}
		$code = $_SESSION[$key];
		return $code;
    }

    /**
	 * Generates a new verification code.
	 * @return string the generated verification code
	 */
	protected function generateVerifyCode()
	{
		// Minimum length of the verify code is 3 letters
		if ($this->length < 3)
		{
			$this->length = 3;
		}
		// Maximum length of the verify code is the size of arabic_letters, which is 28 
		elseif ($this->length > sizeof($this->arabic_letters))
		{
			$this->length = sizeof($this->arabic_letters);
		}
		// Get random indexes of the arabic letters array
		$rand_indexes = array_rand($this->arabic_letters, $this->length);
		$code = '';
		foreach ($rand_indexes as $index)
		{
			$code .= $this->arabic_letters[$index] . ' ';
		}
		return $code;
	}

    /**
	 * Renders the CAPTCHA image based on the code.
	 */
    public function render()
    {
    	// Setting headers for the image
    	header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-type: image/png");

		// Validating the public options before using them
		//$this->validateOptions();

		// Calculating length value and cleaning fixed_verify_code if is set
    	if ($this->fixed_verify_code !== null)
    	{
    		$this->fixed_verify_code = preg_replace('/[\s]/u', '', $this->fixed_verify_code);
    		$this->length = mb_strlen($this->fixed_verify_code);
    		$this->fixed_verify_code = preg_replace('/(.)/u', '$1 ', $this->fixed_verify_code);
    		$this->fixed_verify_code = trim($this->fixed_verify_code);
    	}

		// Calculating the width and height of the generated captcha image
		$this->calculateSize();

		// Creating image
		$image = imagecreatetruecolor($this->width, $this->height);
		// Filling image with back_color
		imagefill($image, 0, 0, $this->back_color);

		if($this->transparent)
		{
			// Removing the back_color from the image (making it transparent)
			imagecolortransparent($image, $this->back_color);
		}

		if($this->draw_noise)
		{
			$this->drawNoise($image);
		}

		// Write verification code on the image
		$this->writeVerifyCode($image);

		if($this->draw_lines)
		{
			$this->drawLines($image);
		}

		// Using imagepng() results in clearer text compared with imagejpeg()
		imagepng($image);
		// Free memory
		imagedestroy($image);
    }

    /**
	 * Calculates the width and height of the captcha image using font_size and length.
	 */
	protected function calculateSize()
	{
		$this->width = ($this->font_size * 1.5) * $this->length + $this->font_size;
		$this->height = $this->font_size * 2.5;
	}

    /**
	 * Returns the session variable name used to store verification code.
	 * @return string the session variable name
	 */
	protected function getSessionKey()
	{
		return 'ArCaptcha.' . session_id();
	}

	/**
	 * Validates the input to see if it matches the generated code.
	 * @param string $input user input
	 * @return whether the input is valid
	 */
	public function validate($input)
	{
		$code  = $this->getVerifyCode();
		$input = preg_replace('/[ا]/u', 'أ', $input);
		$input = preg_replace('/[ه]/u', 'ﮬ', $input);
		$input = preg_replace('/\s/u', '', $input);
		$code  = preg_replace('/\s/u', '', $code);
		$valid = ($input === $code);
		return $valid;
	}

	/**
     * Draws noise pixels on the image background
     * @param image $image
     */
    protected function drawNoise($image)
    {
    	for ($i = 0; $i < $this->width; $i++)
    	{
		    for ($j = 0; $j < $this->height; $j++)
		    {
		        if (mt_rand(0,1) == 1) imagesetpixel($image, $i, $j, $this->noise_color);
		    }
		}
    }

    /**
     * Writes verification code on the image
     * @param image $image
     */
    protected function writeVerifyCode($image)
    {
    	// writeVerifyCode function should always use a new verify code
    	$code = $this->getVerifyCode(true);
    	// Convert verify code to array
		$code = explode(' ', $code);
		// Write verification code on the image
		for ($i = 0; $i < $this->length; $i++)
		{
			// Random angel between -25 and 25
			$angle = mt_rand(-25, 25);
			// Random font size between (font_size - 2) and (font_size - 2)
			$rand_font_size = mt_rand($this->font_size - 2, $this->font_size + 2);
			$letter = $code[$i];
			// Get random dark color
			$rand_color = $this->getRandomColor();
			// Get x and y
			list($x, $y) = $this->getLetterPosition($i);
			if ($this->shadow)
			{
				// Add some black shadow to the letter
				imagettftext($image, $rand_font_size, $angle, $x+1, $y+1, 0X000000, $this->font_file, $letter);
			}
			// Write the letter
			imagettftext($image, $rand_font_size, $angle, $x, $y, $rand_color, $this->font_file, $letter);
		}
    }

    /**
     * Return a random dark color
     * @param integer $index of the letter
     * @return array of x and y
     */
    protected function getLetterPosition($index)
    {
    	$x = ($this->font_size / 2) + (($this->font_size * 1.5) * ($this->length - $index - 1));
    	$y = ($this->font_size * 1.5);
    	$posistion = array($x, $y);
    	return $posistion;
    }

	/**
     * Draws distorted lines on the image
     * @param image $image
     */
    protected function drawLines($image)
    {
        for ($line = 0; $line < $this->num_lines; ++ $line) {
            $x = $this->width * (1 + $line) / ($this->num_lines + 1);
            $x += (0.5 - $this->getFloatRand()) * $this->width / $this->num_lines;
            $y = mt_rand($this->height * 0.1, $this->height * 0.9);

            $theta = ($this->getFloatRand() - 0.5) * M_PI * 0.7;
            $w = $this->width;
            $len = mt_rand($w * 0.4, $w * 0.7);
            $lwid = mt_rand(0, 1);

            $k = $this->getFloatRand() * 0.6 + 0.2;
            $k = $k * $k * 0.5;
            $phi = $this->getFloatRand() * 6.28;
            $step = 0.5;
            $dx = $step * cos($theta);
            $dy = $step * sin($theta);
            $n = $len / $step;
            $amp = 1.5 * $this->getFloatRand() / ($k + 5.0 / $len);
            $x0 = $x - 0.5 * $len * cos($theta);
            $y0 = $y - 0.5 * $len * sin($theta);

            $ldx = round(- $dy * $lwid);
            $ldy = round($dx * $lwid);

            for ($i = 0; $i < $n; ++ $i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
                imagefilledrectangle($image, $x, $y, $x + $lwid, $y + $lwid, $this->noise_color);
            }
        }
    }

    /**
     * Return a random color
     * @return hex random dark color
     */
    function getRandomColor()
    {
    	$max = (11 - $this->darkness_level) * 25;
    	$min = (11 - $this->darkness_level) * 10;
    	$hex = sprintf('0X%02X%02X%02X', mt_rand($min, $max), mt_rand($min, $max), mt_rand($min, $max));
    	//$hex = sprintf('0X%02X%02X%02X', mt_rand(0, 127), mt_rand(0, 127), mt_rand(0, 127));
    	return $hex;
    }

    /**
     * Return a random float between 0 and 0.9999
     * @return float Random float between 0 and 0.9999
     */
    function getFloatRand()
    {
    	return 0.0001 * mt_rand(0, 9999);
    }
}
