<?php
namespace PHPColor;

use InvalidArgumentException;

/**
 * Color utility and conversion
 *
 * Represents a color value, and converts between RGB/HSV/XYZ/Lab
 *
 * Example:
 * $color = new Color(0xffffff);
 *
 * ORIGINAL AUTHOR
 * @author Harold Asbridge <hasbridge@gmail.com>
 *
 * CHANGES BY
 * @author Alex Regan <alex.joseph.regan@gmail.com>
 */
class Color {
	
	/**
	 * Colors constants
	 *
	 * @var integer
	 */
	const TRANSPARENT = 0x7fffffff; // extended from original 16 HTML colors
	const AQUA        = 0x00ffff;
	const BLACK       = 0x000000;
	const BLUE        = 0x0000ff;
	const FUCHSIA     = 0xff00ff;
	const GRAY        = 0x080808;
	const GREEN       = 0x008000;
	const LIME        = 0x00ff00;
	const MAROON      = 0x800000;
	const NAVY        = 0x000080;
	const OLIVE       = 0x808000; // extended from original 16 HTML colors
	const ORANGE      = 0xff9900;
	const PURPLE      = 0x800080;
	const RED         = 0xff0000;
	const SILVER      = 0xc0c0c0;
	const TEAL        = 0x008080;
	const YELLOW      = 0xffff00;
	const WHITE       = 0xffffff;
	
	const HTML_BASE_16_COLORS = [
		self::AQUA,
		self::BLACK,
		self::BLUE,
		self::FUCHSIA,
		self::GRAY,
		self::GREEN,
		self::LIME,
		self::MAROON,
		self::NAVY,
		self::OLIVE,
		self::PURPLE,
		self::RED,
		self::SILVER,
		self::TEAL,
		self::YELLOW,
		self::WHITE,
	];
	
	/**
	 * @var int
	 */
	protected $color = 0;
	
	/**
	 * sRGB Matrix for RGB to XYZ conversion
	 */
	const sRGB_MATRIX = [
		'x' => [ 'r' => 0.4124564, 'g' => 0.3575761, 'b' => 0.1804375, ],
		'y' => [ 'r' => 0.2126729, 'g' => 0.7151522, 'b' => 0.0721750, ],
		'z' => [ 'r' => 0.0193339, 'g' => 0.1191920, 'b' => 0.9503041, ],
	];
	
	/**
	 * Initialize object
	 *
	 * @param int $colorAsInteger An integer color, such as a return value from imagecolorat()
	 */
	public function __construct( $colorAsInteger = NULL )
	{
		if ( $colorAsInteger )
		{
			$this->fromInt( $colorAsInteger );
		}
	}
	
	/**
	 * Init color from hex value
	 *
	 * @param string $hexValue
	 *
	 * @return static
	 */
	public function fromHex( $hexValue )
	{
		$this->color = hexdec( $hexValue );
		
		return $this;
	}
	
	/**
	 * Init color from integer RGB values
	 *
	 * @param int $red
	 * @param int $green
	 * @param int $blue
	 *
	 * @return static
	 */
	public function fromRgbInt( $red, $green, $blue )
	{
		$this->color = (int) (($red << 16) + ($green << 8) + $blue);
		
		return $this;
	}
	
	/**
	 * Init color from hex RGB values
	 *
	 * @param string $red
	 * @param string $green
	 * @param string $blue
	 *
	 * @return static
	 */
	public function fromRgbHex( $red, $green, $blue )
	{
		return $this->fromRgbInt( hexdec( $red ), hexdec( $green ), hexdec( $blue ) );
	}
	
	/**
	 * Init color from integer value
	 *
	 * @param int $intValue
	 *
	 * @return static
	 */
	public function fromInt( $intValue )
	{
		$this->color = (int) $intValue;
		
		return $this;
	}
	
	/**
	 * Convert color to hex
	 *
	 * @return string
	 */
	public function toHex()
	{
		return str_pad( dechex( $this->color ), 6, "0", STR_PAD_LEFT );
	}
	
	/**
	 * Convert color to RGB array (integer values)
	 *
	 * @return array
	 */
	public function toRgbInt()
	{
		return [
			'red'   => (int) (255 & ($this->color >> 16)),
			'green' => (int) (255 & ($this->color >> 8)),
			'blue'  => (int) (255 & ($this->color)),
		];
	}
	
	/**
	 * Convert color to RGB array (hex values)
	 *
	 * @return array
	 */
	public function toRgbHex()
	{
		return array_map( function ( $item )
		{
			return dechex( $item );
		}, $this->toRgbInt() );
	}
	
	/**
	 * Get Hue/Saturation/Value for the current color
	 * (float values, slow but accurate)
	 *
	 * @return array
	 */
	public function toHsvFloat()
	{
		$rgb = $this->toRgbInt();
		
		$rgbMin = min( $rgb );
		$rgbMax = max( $rgb );
		
		$hsv = [
			'hue' => 0,
			'sat' => 0,
			'val' => $rgbMax,
		];
		
		// If v is 0, color is black
		if ( $hsv['val'] == 0 )
		{
			return $hsv;
		}
		
		// Normalize RGB values to 1
		$rgb['red'] /= $hsv['val'];
		$rgb['green'] /= $hsv['val'];
		$rgb['blue'] /= $hsv['val'];
		$rgbMin = min( $rgb );
		$rgbMax = max( $rgb );
		
		// Calculate saturation
		$hsv['sat'] = $rgbMax - $rgbMin;
		if ( $hsv['sat'] == 0 )
		{
			$hsv['hue'] = 0;
			
			return $hsv;
		}
		
		// Normalize saturation to 1
		$rgb['red']   = ($rgb['red'] - $rgbMin) / ($rgbMax - $rgbMin);
		$rgb['green'] = ($rgb['green'] - $rgbMin) / ($rgbMax - $rgbMin);
		$rgb['blue']  = ($rgb['blue'] - $rgbMin) / ($rgbMax - $rgbMin);
		$rgbMin       = min( $rgb );
		$rgbMax       = max( $rgb );
		
		// Calculate hue
		if ( $rgbMax == $rgb['red'] )
		{
			$hsv['hue'] = 0.0 + 60 * ($rgb['green'] - $rgb['blue']);
			if ( $hsv['hue'] < 0 )
			{
				$hsv['hue'] += 360;
			}
		}
		else if ( $rgbMax == $rgb['green'] )
		{
			$hsv['hue'] = 120 + (60 * ($rgb['blue'] - $rgb['red']));
		}
		else
		{
			$hsv['hue'] = 240 + (60 * ($rgb['red'] - $rgb['green']));
		}
		
		return $hsv;
	}
	
	/**
	 * Get HSV values for color
	 * (integer values from 0-255, fast but less accurate)
	 *
	 * @return array
	 */
	public function toHsvInt()
	{
		$rgb = $this->toRgbInt();
		
		$rgbMin = min( $rgb );
		$rgbMax = max( $rgb );
		
		$hsv = [
			'hue' => 0,
			'sat' => 0,
			'val' => $rgbMax,
		];
		
		// If value is 0, color is black
		if ( $hsv['val'] == 0 )
		{
			return $hsv;
		}
		
		// Calculate saturation
		$hsv['sat'] = round( 255 * ($rgbMax - $rgbMin) / $hsv['val'] );
		if ( $hsv['sat'] == 0 )
		{
			$hsv['hue'] = 0;
			
			return $hsv;
		}
		
		// Calculate hue
		if ( $rgbMax == $rgb['red'] )
		{
			$hsv['hue'] = round( 0 + 43 * ($rgb['green'] - $rgb['blue']) / ($rgbMax - $rgbMin) );
		}
		else if ( $rgbMax == $rgb['green'] )
		{
			$hsv['hue'] = round( 85 + 43 * ($rgb['blue'] - $rgb['red']) / ($rgbMax - $rgbMin) );
		}
		else
		{
			$hsv['hue'] = round( 171 + 43 * ($rgb['red'] - $rgb['green']) / ($rgbMax - $rgbMin) );
		}
		if ( $hsv['hue'] < 0 )
		{
			$hsv['hue'] += 255;
		}
		
		return $hsv;
	}
	
	/**
	 * Get current color in XYZ format
	 *
	 * @return array
	 */
	public function toXyz()
	{
		$rgb = $this->toRgbInt();
		
		// Normalize RGB values to 1 (nominal energy range)
		$rgb = array_map( function ( $item )
		{
			return $item / 255;
		}, $rgb );
		
		$rgb = array_map( function ( $item )
		{
			$item = $item > 0.04045 ? pow( (($item + 0.055) / 1.055), 2.4 ) : $item / 12.92;
			
			return ($item * 100);
		}, $rgb );
		
		//Observer. = 2°, Illuminant = D65
		return [
			'x' => (
				($rgb['red'] * self::sRGB_MATRIX['x']['r'])
				+ ($rgb['green'] * self::sRGB_MATRIX['x']['g'])
				+ ($rgb['blue'] * self::sRGB_MATRIX['x']['b'])
			),
			'y' => (
				($rgb['red'] * self::sRGB_MATRIX['y']['r'])
				+ ($rgb['green'] * self::sRGB_MATRIX['y']['g'])
				+ ($rgb['blue'] * self::sRGB_MATRIX['y']['b'])
			),
			'z' => (
				($rgb['red'] * self::sRGB_MATRIX['z']['r'])
				+ ($rgb['green'] * self::sRGB_MATRIX['z']['g'])
				+ ($rgb['blue'] * self::sRGB_MATRIX['z']['b'])
			),
		];
	}
	
	/**
	 * Get color CIE-Lab values
	 *
	 * @return array
	 */
	public function toLabCie()
	{
		$xyz = $this->toXyz();
		
		//Observer = 2º, Illuminant=D65
		$xyz['x'] /= 95.047;
		$xyz['y'] /= 100;
		$xyz['z'] /= 108.883;
		
		$xyz = array_map( function ( $item )
		{
			if ( $item > 0.008856 )
			{
				return pow( $item, 1 / 3 );
			}
			else
			{
				return (7.787 * $item) + (16 / 116);
			}
		}, $xyz );
		
		$lab = [
			'l' => (116 * $xyz['y']) - 16,
			'a' => 500 * ($xyz['x'] - $xyz['y']),
			'b' => 200 * ($xyz['y'] - $xyz['z']),
		];
		
		return $lab;
	}
	
	/**
	 * Convert color to integer
	 *
	 * @return int
	 */
	public function toInt()
	{
		return $this->color;
	}
	
	/**
	 * Alias of toString()
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}
	
	/**
	 * Get color as string
	 *
	 * @return string
	 */
	public function toString()
	{
		$str = (string) $this->toHex();
		if ( strlen( $str ) < 6 )
		{
			$str = str_pad( $str, 6, '0', STR_PAD_LEFT );
		}
		
		return strtoupper( "#{$str}" );
	}
	
	/**
	 * Get the distance between this color and the given color
	 *
	 * @param Color $color
	 *
	 * @return int
	 */
	public function getDistanceRgbFrom( Color $color )
	{
		$rgb_1 = $this->toRgbInt();
		$rgb_2 = $color->toRgbInt();
		
		$redDiff   = abs( $rgb_1['red'] - $rgb_2['red'] );
		$greenDiff = abs( $rgb_1['green'] - $rgb_2['green'] );
		$blueDiff  = abs( $rgb_1['blue'] - $rgb_2['blue'] );
		
		// Sum of RGB differences
		$diff = $redDiff + $greenDiff + $blueDiff;
		
		return $diff;
	}
	
	/**
	 * Get distance from the given color using the Delta E method
	 *
	 * @param Color $color
	 *
	 * @return float
	 */
	public function getDistanceLabFrom( Color $color )
	{
		$lab_1 = $this->toLabCie();
		$lab_2 = $color->toLabCie();
		
		$l_diff = abs( $lab_2['l'] - $lab_1['l'] );
		$a_diff = abs( $lab_2['a'] - $lab_1['a'] );
		$b_diff = abs( $lab_2['b'] - $lab_1['b'] );
		
		$delta = sqrt( $l_diff + $a_diff + $b_diff );
		
		return $delta;
	}
	
	/**
	 * Get distance from the given HEX color using the Delta E method
	 *
	 * @param $colorAsHex
	 *
	 * @return float
	 */
	public function getDeltaEFromHex( $colorAsHex )
	{
		return $this->getDistanceLabFrom( static::newFromHex( $colorAsHex ) );
	}
	
	/**
	 * Get distance from the given RGB color using the Delta E method
	 *
	 * @param $red
	 * @param $green
	 * @param $blue
	 *
	 * @return float
	 */
	public function getDeltaEFromRgb( $red, $green, $blue )
	{
		return $this->getDistanceLabFrom( static::newFromRgb( $red, $green, $blue ) );
	}
	
	/**
	 *  Get distance from the given RGB Integer color using the Delta E method
	 *
	 * @param $colorAsInteger
	 *
	 * @return float
	 */
	public function getDeltaEFromRgbInt( $colorAsInteger )
	{
		return $this->getDistanceLabFrom( static::newFromInt( $colorAsInteger ) );
	}
	
	/**
	 * Detect if color is gray-scale
	 *
	 * @param int @threshold
	 *
	 * @return bool
	 */
	public function isGrayScale( $threshold = 16 )
	{
		$rgb = $this->toRgbInt();
		
		// Get min and max rgb values, then difference between them
		$rgbMin = min( $rgb );
		$rgbMax = max( $rgb );
		$diff   = $rgbMax - $rgbMin;
		
		return $diff < $threshold;
	}
	
	/**
	 * Get the first closest matching color from the given array of colors
	 *
	 * @param array $colors array of integers or Color objects
	 *
	 * @return mixed the array key of the matched color
	 */
	public function getClosestMatch( array $colors )
	{
		$matchDist = 10000;
		$matchKey  = NULL;
		
		foreach ( $colors as $key => $color )
		{
			if ( ! $color instanceof Color ) $color = new Color( $color );
			
			$dist = $this->getDistanceLabFrom( $color );
			
			if ( $dist < $matchDist )
			{
				$matchDist = $dist;
				$matchKey  = $key;
			}
		}
		
		return $matchKey;
	}
	
	/**
	 * Init color from integer color.
	 *
	 * @param $colorAsInteger
	 *
	 * @return static
	 */
	public static function newFromInt( $colorAsInteger )
	{
		if ( gettype( $colorAsInteger ) !== 'integer' )
		{
			$type = gettype( $colorAsInteger );
			throw new InvalidArgumentException( "Expected an integer value, received type: [`{$type}`]" );
		}
		
		return new static( $colorAsInteger );
	}
	
	/**
	 * @param $colorAsHex
	 *
	 * @return static
	 */
	public static function newFromHex( $colorAsHex )
	{
		$instance = new static;
		
		return $instance->fromHex( $colorAsHex );
	}
	
	/**
	 * Init color from integer RGB values
	 *
	 * @param int $red
	 * @param int $green
	 * @param int $blue
	 *
	 * @return static
	 */
	public static function newFromRgb( $red, $green, $blue )
	{
		if ( gettype( $red ) !== 'integer' || gettype( $green ) !== 'integer' || gettype( $blue ) !== 'integer' )
		{
			$redType   = gettype( $red );
			$greenType = gettype( $green );
			$blueType  = gettype( $blue );
			
			throw new InvalidArgumentException(
				"Expected RGB values to be integers. Types received: red [`{$redType}`], green [`{$greenType}`], blue [`{$blueType}`]."
			);
		}
		
		$instance = new static;
		
		return $instance->fromRgbInt( $red, $green, $blue );
	}
	
	/**
	 * Init color from hex RGB values
	 *
	 * @param string $red
	 * @param string $green
	 * @param string $blue
	 *
	 * @return mixed
	 */
	public static function newFromRgbHex( $red, $green, $blue )
	{
		$instance = new static;
		
		return $instance->fromRgbHex( $red, $green, $blue );
	}
}
