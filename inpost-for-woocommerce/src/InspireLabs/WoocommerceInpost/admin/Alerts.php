<?php

namespace InspireLabs\WoocommerceInpost\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.


class Alerts {

	/**
	 * @var array
	 */
	static $notice = array();

	/**
	 * @var array
	 */
	static $error = array();

	/**
	 * @var array
	 */
	static $success = array();

	/**
	 * @var bool
	 */
	static $alerts_rendered = false;

	/**
	 * Alerts constructor.
	 */
	public function __construct() {
		$this->print_alerts_once();
	}


	/**
	 * Short description
	 *
	 * @param array|string $notice
	 */
	public function add_notice( $notice ) {
		if ( is_array( $notice ) ) {
			self::$notice = array_merge( self::$notice, $notice );
		} else {
			self::$notice[] = $notice;
		}
	}

	/**
	 * Short description
	 *
	 * @param array|string $success
	 */
	public function add_success( $success ) {
		if ( is_array( $success ) ) {
			self::$success = array_merge( self::$success, $success );
		} else {
			self::$success[] = $success;
		}
	}

	/**
	 * Short description
	 *
	 * @param array|string $error
	 */
	public function add_error( $error ) {
		if ( is_array( $error ) ) {
			self::$error = array_merge( self::$error, $error );
		} else {
			self::$error[] = $error;
		}
	}

	/**
	 * Short description
	 */
	public function print_alerts_once() {
		if ( ! self::$alerts_rendered ) {
			add_action(
				'admin_notices',
				function () {
					$this->print_alerts();
					self::$alerts_rendered = true;
				}
			);
		}
	}

	public function print_alerts() {

		self::$error   = array_unique( self::$error );
		self::$success = array_unique( self::$success );
		self::$notice  = array_unique( self::$notice );

		foreach ( self::$success as $k => $v ) {
			if ( ! empty( $v ) ) {

				$output = sprintf(
					"<div class='notice notice-success'><p>%s</p></div>",
					$v
				);
				echo wp_kses(
					$output,
					array(
						'div' => array( 'class' => array() ),
						'p'   => array(),
						'b'   => array(),
						'br'  => array(),
					)
				);
			}
		}

		foreach ( self::$notice as $k => $v ) {
			if ( ! empty( $v ) ) {

				$output = sprintf(
					"<div class='notice notice-info'><p>%s</p></div>",
					$v
				);
				echo wp_kses(
					$output,
					array(
						'div' => array( 'class' => array() ),
						'p'   => array(),
						'b'   => array(),
						'br'  => array(),
					)
				);
			}
		}

		foreach ( self::$error as $k => $v ) {
			if ( ! empty( $v ) ) {

				$output = sprintf(
					"<div class='notice notice-error'><p>%s</p></div>",
					$v
				);
				echo wp_kses(
					$output,
					array(
						'div' => array( 'class' => array() ),
						'p'   => array(),
						'b'   => array(),
						'br'  => array(),
						'a'   => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
			}
		}

		self::$error   = array();
		self::$success = array();
		self::$notice  = array();
	}
}
