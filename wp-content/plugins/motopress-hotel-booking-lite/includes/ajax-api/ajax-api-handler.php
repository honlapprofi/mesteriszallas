<?php

namespace MPHB\AjaxApi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AjaxApiHandler {

	const AJAX_ACTION_CLASS_NAMES = array(
		'\MPHB\AjaxApi\GetRoomTypeCalendarData',
	);

	private static function getAjaxActionClassNames() {

		// use this filter to add custom ajax actions \MPHB\AjaxApi\Your_Action to the api in other plugins
		// if action in the Hotel Booking plugin then it must be added to the constant array above explicitly
		return apply_filters( 'mphb_ajax_api_action_class_names', static::AJAX_ACTION_CLASS_NAMES );
	}

	public function __construct() {

		if ( ! wp_doing_ajax() ) {
			return;
		}

		foreach ( static::getAjaxActionClassNames() as $ajaxActionClassName ) {

			$ajaxActionName = $ajaxActionClassName::getAjaxActionName();

			if ( $ajaxActionClassName::isActionForLoggedInUser() ) {

				add_action( 'wp_ajax_' . $ajaxActionName, array( $ajaxActionClassName, 'processAjaxRequest' ) );
			}

			if ( $ajaxActionClassName::isActionForGuestUser() ) {

				add_action( 'wp_ajax_nopriv_' . $ajaxActionName, array( $ajaxActionClassName, 'processAjaxRequest' ) );
			}
		}
	}

	/**
	 * @return array of [ action name => wp nonce ]
	 */
	public static function getAjaxActionWPNonceForLoggedInUser() {

		$wpNonces = array();

		foreach ( static::getAjaxActionClassNames() as $ajaxActionClassName ) {

			if ( $ajaxActionClassName::isActionForLoggedInUser() ) {

				$ajaxActionName = $ajaxActionClassName::getAjaxActionName();

				$wpNonces[ $ajaxActionName ] = wp_create_nonce( $ajaxActionName );
			}
		}

		return $wpNonces;
	}

	/**
	 * @return array of [ action name => wp nonce ]
	 */
	public static function getAjaxActionWPNonceForGuestUser() {

		$wpNonces = array();

		foreach ( static::getAjaxActionClassNames() as $ajaxActionClassName ) {

			if ( $ajaxActionClassName::isActionForGuestUser() ) {

				$ajaxActionName = $ajaxActionClassName::getAjaxActionName();

				$wpNonces[ $ajaxActionName ] = wp_create_nonce( $ajaxActionName );
			}
		}

		return $wpNonces;
	}
}
