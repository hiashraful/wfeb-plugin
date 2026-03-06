<?php
/**
 * WFEB QR Code Generator
 *
 * Pure PHP QR code generator that outputs inline SVG.
 * No external libraries required. Supports byte mode encoding.
 * Uses error correction level L for compact output.
 *
 * Supports QR versions 1-10 (URLs up to ~270 characters).
 *
 * @package WFEB
 * @since   2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WFEB_QR {

	/** @var array GF(256) exponent table. */
	private static $gf_exp = array();

	/** @var array GF(256) log table. */
	private static $gf_log = array();

	/** @var bool Whether GF tables have been initialized. */
	private static $gf_init = false;

	/**
	 * Generate a QR code as an SVG string.
	 *
	 * @param string $data   The data to encode.
	 * @param int    $size   SVG width/height in pixels.
	 * @param int    $margin Quiet zone modules.
	 * @return string SVG markup.
	 */
	public static function svg( $data, $size = 100, $margin = 2 ) {
		if ( empty( $data ) ) {
			return '';
		}

		self::init_gf();

		$matrix = self::encode( $data );
		if ( empty( $matrix ) ) {
			return '';
		}

		$mc    = count( $matrix );
		$total = $mc + $margin * 2;
		$ms    = $size / $total;

		$rects = '';
		for ( $r = 0; $r < $mc; $r++ ) {
			for ( $c = 0; $c < $mc; $c++ ) {
				if ( ! empty( $matrix[ $r ][ $c ] ) ) {
					$x = round( ( $c + $margin ) * $ms, 2 );
					$y = round( ( $r + $margin ) * $ms, 2 );
					$w = round( $ms, 2 );
					$rects .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $w . '"/>';
				}
			}
		}

		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges">'
			. '<rect width="' . $size . '" height="' . $size . '" fill="#fff"/>'
			. '<g fill="#000">' . $rects . '</g></svg>';
	}

	private static function init_gf() {
		if ( self::$gf_init ) {
			return;
		}
		self::$gf_exp = array_fill( 0, 512, 0 );
		self::$gf_log = array_fill( 0, 256, 0 );
		$v = 1;
		for ( $i = 0; $i < 255; $i++ ) {
			self::$gf_exp[ $i ] = $v;
			self::$gf_log[ $v ] = $i;
			$v <<= 1;
			if ( $v >= 256 ) {
				$v ^= 0x11d;
			}
		}
		for ( $i = 255; $i < 512; $i++ ) {
			self::$gf_exp[ $i ] = self::$gf_exp[ $i - 255 ];
		}
		self::$gf_init = true;
	}

	private static function encode( $data ) {
		$version = self::get_min_version( strlen( $data ) );
		$size    = 17 + $version * 4;
		$matrix  = array_fill( 0, $size, array_fill( 0, $size, false ) );
		$rsv     = array_fill( 0, $size, array_fill( 0, $size, false ) );

		self::place_finder( $matrix, $rsv, 0, 0, $size );
		self::place_finder( $matrix, $rsv, $size - 7, 0, $size );
		self::place_finder( $matrix, $rsv, 0, $size - 7, $size );

		$ap = self::align_pos( $version );
		foreach ( $ap as $ar ) {
			foreach ( $ap as $ac ) {
				if ( ! $rsv[ $ar ][ $ac ] ) {
					self::place_align( $matrix, $rsv, $ar, $ac );
				}
			}
		}

		for ( $i = 8; $i < $size - 8; $i++ ) {
			if ( ! $rsv[6][ $i ] ) {
				$matrix[6][ $i ] = ( $i % 2 === 0 );
				$rsv[6][ $i ]   = true;
			}
			if ( ! $rsv[ $i ][6] ) {
				$matrix[ $i ][6] = ( $i % 2 === 0 );
				$rsv[ $i ][6]   = true;
			}
		}

		$matrix[ 4 * $version + 9 ][8] = true;
		$rsv[ 4 * $version + 9 ][8]    = true;

		for ( $i = 0; $i < 8; $i++ ) {
			$rsv[8][ $i ] = true;
			$rsv[ $i ][8] = true;
			$rsv[8][ $size - 1 - $i ] = true;
			$rsv[ $size - 1 - $i ][8] = true;
		}
		$rsv[8][8] = true;

		if ( $version >= 7 ) {
			for ( $i = 0; $i < 6; $i++ ) {
				for ( $j = 0; $j < 3; $j++ ) {
					$rsv[ $i ][ $size - 11 + $j ] = true;
					$rsv[ $size - 11 + $j ][ $i ] = true;
				}
			}
		}

		$data_bits = self::encode_data( $data, $version );
		$ec_bits   = self::add_ec( $data_bits, $version );
		self::place_data( $matrix, $rsv, $ec_bits, $size );

		$best_mask  = 0;
		$best_score = PHP_INT_MAX;
		$best       = $matrix;

		for ( $m = 0; $m < 8; $m++ ) {
			$test = $matrix;
			self::apply_mask( $test, $rsv, $m, $size );
			self::place_format( $test, $m, $size );
			if ( $version >= 7 ) {
				self::place_version( $test, $version, $size );
			}
			$score = self::penalty( $test, $size );
			if ( $score < $best_score ) {
				$best_score = $score;
				$best_mask  = $m;
				$best       = $test;
			}
		}

		return $best;
	}

	private static function get_min_version( $len ) {
		$caps = array( 17, 32, 53, 78, 106, 134, 154, 192, 230, 271 );
		foreach ( $caps as $v => $cap ) {
			if ( $len <= $cap ) {
				return $v + 1;
			}
		}
		return 10;
	}

	private static function encode_data( $data, $version ) {
		$bits = '0100'; // Byte mode.
		$cc   = $version <= 9 ? 8 : 16;
		$bits .= str_pad( decbin( strlen( $data ) ), $cc, '0', STR_PAD_LEFT );

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$bits .= str_pad( decbin( ord( $data[ $i ] ) ), 8, '0', STR_PAD_LEFT );
		}

		$total = self::data_cw( $version ) * 8;
		$rem   = $total - strlen( $bits );
		$bits .= str_repeat( '0', min( $rem, 4 ) );

		if ( strlen( $bits ) % 8 !== 0 ) {
			$bits .= str_repeat( '0', 8 - ( strlen( $bits ) % 8 ) );
		}

		$pad = array( '11101100', '00010001' );
		$idx = 0;
		while ( strlen( $bits ) < $total ) {
			$bits .= $pad[ $idx % 2 ];
			$idx++;
		}

		return $bits;
	}

	private static function data_cw( $v ) {
		$d = array( 19, 34, 55, 80, 108, 136, 156, 194, 232, 274 );
		return isset( $d[ $v - 1 ] ) ? $d[ $v - 1 ] : 274;
	}

	private static function total_cw( $v ) {
		$t = array( 26, 44, 70, 100, 134, 172, 196, 242, 292, 346 );
		return isset( $t[ $v - 1 ] ) ? $t[ $v - 1 ] : 346;
	}

	private static function ec_per_block( $v ) {
		$e = array( 7, 10, 15, 20, 26, 18, 20, 24, 30, 18 );
		return isset( $e[ $v - 1 ] ) ? $e[ $v - 1 ] : 18;
	}

	private static function block_info( $v ) {
		$info = array(
			1  => array( 1, 19, 0, 0 ),
			2  => array( 1, 34, 0, 0 ),
			3  => array( 1, 55, 0, 0 ),
			4  => array( 1, 80, 0, 0 ),
			5  => array( 1, 108, 0, 0 ),
			6  => array( 2, 68, 0, 0 ),
			7  => array( 2, 78, 0, 0 ),
			8  => array( 2, 97, 0, 0 ),
			9  => array( 2, 116, 0, 0 ),
			10 => array( 2, 68, 2, 69 ),
		);
		return isset( $info[ $v ] ) ? $info[ $v ] : $info[1];
	}

	private static function add_ec( $data_bits, $version ) {
		$codewords = array();
		for ( $i = 0; $i < strlen( $data_bits ); $i += 8 ) {
			$codewords[] = (int) bindec( substr( $data_bits, $i, 8 ) );
		}

		$bi  = self::block_info( $version );
		$ecn = self::ec_per_block( $version );

		$blocks    = array();
		$ec_blocks = array();
		$off       = 0;

		for ( $b = 0; $b < $bi[0]; $b++ ) {
			$block       = array_slice( $codewords, $off, $bi[1] );
			$blocks[]    = $block;
			$ec_blocks[] = self::rs_encode( $block, $ecn );
			$off += $bi[1];
		}
		if ( $bi[2] > 0 ) {
			for ( $b = 0; $b < $bi[2]; $b++ ) {
				$block       = array_slice( $codewords, $off, $bi[3] );
				$blocks[]    = $block;
				$ec_blocks[] = self::rs_encode( $block, $ecn );
				$off += $bi[3];
			}
		}

		$result   = '';
		$max_data = 0;
		foreach ( $blocks as $bl ) {
			$max_data = max( $max_data, count( $bl ) );
		}
		for ( $i = 0; $i < $max_data; $i++ ) {
			foreach ( $blocks as $bl ) {
				if ( $i < count( $bl ) ) {
					$result .= str_pad( decbin( $bl[ $i ] ), 8, '0', STR_PAD_LEFT );
				}
			}
		}
		for ( $i = 0; $i < $ecn; $i++ ) {
			foreach ( $ec_blocks as $ec ) {
				if ( $i < count( $ec ) ) {
					$result .= str_pad( decbin( $ec[ $i ] ), 8, '0', STR_PAD_LEFT );
				}
			}
		}

		$total_bits = self::total_cw( $version ) * 8;
		while ( strlen( $result ) < $total_bits ) {
			$result .= '0';
		}

		return $result;
	}

	private static function rs_encode( $data, $ec_count ) {
		$gen = self::gen_poly( $ec_count );
		$msg = array_merge( $data, array_fill( 0, $ec_count, 0 ) );

		for ( $i = 0; $i < count( $data ); $i++ ) {
			$coef = $msg[ $i ];
			if ( $coef !== 0 ) {
				$log_c = self::$gf_log[ $coef ];
				for ( $j = 0; $j < count( $gen ); $j++ ) {
					$msg[ $i + $j ] ^= self::$gf_exp[ ( $gen[ $j ] + $log_c ) % 255 ];
				}
			}
		}

		return array_slice( $msg, count( $data ) );
	}

	private static function gen_poly( $degree ) {
		$gen = array( 0 );
		for ( $i = 0; $i < $degree; $i++ ) {
			$new = array_merge( $gen, array( 0 ) );
			for ( $j = count( $gen ) - 1; $j >= 0; $j-- ) {
				$v = ( $gen[ $j ] + $i ) % 255;
				$new[ $j + 1 ] ^= self::$gf_exp[ $v ];
			}
			$gen = array();
			foreach ( $new as $val ) {
				$gen[] = ( $val === 0 ) ? 0 : self::$gf_log[ $val ];
			}
		}
		return $gen;
	}

	private static function place_finder( &$m, &$r, $row, $col, $size ) {
		$p = array(
			array(1,1,1,1,1,1,1),
			array(1,0,0,0,0,0,1),
			array(1,0,1,1,1,0,1),
			array(1,0,1,1,1,0,1),
			array(1,0,1,1,1,0,1),
			array(1,0,0,0,0,0,1),
			array(1,1,1,1,1,1,1),
		);
		for ( $dr = -1; $dr <= 7; $dr++ ) {
			for ( $dc = -1; $dc <= 7; $dc++ ) {
				$mr = $row + $dr;
				$mc = $col + $dc;
				if ( $mr < 0 || $mr >= $size || $mc < 0 || $mc >= $size ) continue;
				if ( $dr >= 0 && $dr < 7 && $dc >= 0 && $dc < 7 ) {
					$m[ $mr ][ $mc ] = (bool) $p[ $dr ][ $dc ];
				} else {
					$m[ $mr ][ $mc ] = false;
				}
				$r[ $mr ][ $mc ] = true;
			}
		}
	}

	private static function place_align( &$m, &$r, $cr, $cc ) {
		for ( $dr = -2; $dr <= 2; $dr++ ) {
			for ( $dc = -2; $dc <= 2; $dc++ ) {
				$m[ $cr + $dr ][ $cc + $dc ] = ( abs($dr) === 2 || abs($dc) === 2 || ($dr === 0 && $dc === 0) );
				$r[ $cr + $dr ][ $cc + $dc ] = true;
			}
		}
	}

	private static function align_pos( $v ) {
		if ( $v < 2 ) return array();
		$p = array(
			2 => array(6,18), 3 => array(6,22), 4 => array(6,26),
			5 => array(6,30), 6 => array(6,34), 7 => array(6,22,38),
			8 => array(6,24,42), 9 => array(6,26,46), 10 => array(6,28,50),
		);
		return isset( $p[$v] ) ? $p[$v] : array();
	}

	private static function place_data( &$m, &$r, $bits, $size ) {
		$bi  = 0;
		$len = strlen( $bits );

		for ( $right = $size - 1; $right >= 1; $right -= 2 ) {
			if ( $right === 6 ) $right = 5;

			for ( $vert = 0; $vert < $size; $vert++ ) {
				for ( $j = 0; $j < 2; $j++ ) {
					$col = $right - $j;

					// Determine direction: account for timing column skip.
					$col_pair = ( $right >= 6 ) ? $right + 1 : $right;
					$upward = ( intdiv( $size - 1 - $col_pair, 2 ) % 2 === 0 );
					$row = $upward ? ( $size - 1 - $vert ) : $vert;

					if ( $row < 0 || $row >= $size || $col < 0 || $col >= $size ) continue;
					if ( $r[ $row ][ $col ] ) continue;

					$m[ $row ][ $col ] = ( $bi < $len && $bits[ $bi ] === '1' );
					$bi++;
				}
			}
		}
	}

	private static function apply_mask( &$m, &$r, $mask, $size ) {
		for ( $row = 0; $row < $size; $row++ ) {
			for ( $col = 0; $col < $size; $col++ ) {
				if ( $r[ $row ][ $col ] ) continue;
				$inv = false;
				switch ( $mask ) {
					case 0: $inv = (($row + $col) % 2 === 0); break;
					case 1: $inv = ($row % 2 === 0); break;
					case 2: $inv = ($col % 3 === 0); break;
					case 3: $inv = (($row + $col) % 3 === 0); break;
					case 4: $inv = (((int)($row/2) + (int)($col/3)) % 2 === 0); break;
					case 5: $inv = (($row*$col)%2 + ($row*$col)%3 === 0); break;
					case 6: $inv = ((($row*$col)%2 + ($row*$col)%3) % 2 === 0); break;
					case 7: $inv = ((($row+$col)%2 + ($row*$col)%3) % 2 === 0); break;
				}
				if ( $inv ) $m[ $row ][ $col ] = ! $m[ $row ][ $col ];
			}
		}
	}

	private static function place_format( &$m, $mask, $size ) {
		$fd = ( 0x01 << 3 ) | $mask; // EC level L.
		$fe = $fd;
		for ( $i = 4; $i >= 0; $i-- ) {
			if ( $fe & ( 1 << ( $i + 10 ) ) ) $fe ^= ( 0x537 << $i );
		}
		$fb = ( ( $fd << 10 ) | $fe ) ^ 0x5412;

		$bits = array();
		for ( $i = 14; $i >= 0; $i-- ) {
			$bits[] = ( $fb >> $i ) & 1;
		}

		// Around top-left finder.
		$hp = array( array(8,0), array(8,1), array(8,2), array(8,3), array(8,4), array(8,5), array(8,7), array(8,8) );
		for ( $i = 0; $i < 8; $i++ ) {
			$m[ $hp[$i][0] ][ $hp[$i][1] ] = (bool) $bits[$i];
		}
		$vp = array( array(7,8), array(5,8), array(4,8), array(3,8), array(2,8), array(1,8), array(0,8) );
		for ( $i = 0; $i < 7; $i++ ) {
			$m[ $vp[$i][0] ][ $vp[$i][1] ] = (bool) $bits[8 + $i];
		}

		// Around other finders.
		for ( $i = 0; $i < 7; $i++ ) {
			$m[8][ $size - 7 + $i ] = (bool) $bits[8 + $i];
		}
		for ( $i = 0; $i < 8; $i++ ) {
			$m[ $size - 8 + $i ][8] = (bool) $bits[$i];
		}
	}

	private static function place_version( &$m, $v, $size ) {
		if ( $v < 7 ) return;
		$ve = $v;
		for ( $i = 5; $i >= 0; $i-- ) {
			if ( $ve & ( 1 << ( $i + 12 ) ) ) $ve ^= ( 0x1F25 << $i );
		}
		$vb = ( $v << 12 ) | $ve;
		for ( $i = 0; $i < 18; $i++ ) {
			$bit = ( $vb >> $i ) & 1;
			$r = (int)( $i / 3 );
			$c = $i % 3;
			$m[ $r ][ $size - 11 + $c ] = (bool) $bit;
			$m[ $size - 11 + $c ][ $r ] = (bool) $bit;
		}
	}

	private static function penalty( $m, $size ) {
		$p = 0;
		for ( $r = 0; $r < $size; $r++ ) {
			$cnt = 1;
			for ( $c = 1; $c < $size; $c++ ) {
				if ( $m[$r][$c] === $m[$r][$c-1] ) {
					$cnt++;
					if ( $cnt === 5 ) $p += 3;
					elseif ( $cnt > 5 ) $p += 1;
				} else { $cnt = 1; }
			}
		}
		for ( $c = 0; $c < $size; $c++ ) {
			$cnt = 1;
			for ( $r = 1; $r < $size; $r++ ) {
				if ( $m[$r][$c] === $m[$r-1][$c] ) {
					$cnt++;
					if ( $cnt === 5 ) $p += 3;
					elseif ( $cnt > 5 ) $p += 1;
				} else { $cnt = 1; }
			}
		}
		for ( $r = 0; $r < $size - 1; $r++ ) {
			for ( $c = 0; $c < $size - 1; $c++ ) {
				$v = $m[$r][$c];
				if ( $v === $m[$r][$c+1] && $v === $m[$r+1][$c] && $v === $m[$r+1][$c+1] ) {
					$p += 3;
				}
			}
		}
		return $p;
	}
}
