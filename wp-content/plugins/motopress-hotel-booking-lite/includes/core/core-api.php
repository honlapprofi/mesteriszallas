<?php

namespace MPHB\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Facade of the Hotel Booking core. Any code outside the core must
 * use it instead of inner objects and functions. All object caching
 * must be placed here!
 */
class CoreAPI {

	const WP_CACHE_GROUP = 'MPHB';


	public function __construct() {

		add_action(
			'plugins_loaded',
			function() {
				$this->addClearObjectCacheHooks();
			}
		);
	}

	private function addClearObjectCacheHooks() {

		add_action(
			'mphb_booking_status_changed',
			function( $booking ) {
				$roomTypeIds = array();
				foreach ( $booking->getReservedRooms() as $room ) {

					$roomTypeId = $room->getRoomTypeId();

					if ( ! in_array( $roomTypeId, $roomTypeIds ) ) {
						$roomTypeIds[] = $roomTypeId;
						wp_cache_delete( 'getBookedDaysForRoomType' . $roomTypeId, static::WP_CACHE_GROUP );
					}
				}
			},
			10,
			1
		);

		add_action(
			'save_post_' . MPHB()->postTypes()->room()->getPostType(),
			function( $wpPostId, $wpPost, bool $isUpdated ) {
				$room = MPHB()->getRoomRepository()->findById( $wpPostId );
				if ( $room ) {
					wp_cache_delete( 'getBookedDaysForRoomType' . $room->getRoomTypeId(), static::WP_CACHE_GROUP );
					wp_cache_delete( 'getActiveRoomsCountForRoomType' . $room->getRoomTypeId(), static::WP_CACHE_GROUP );
					wp_cache_delete( 'getBlockedRoomsCountsForRoomType' . $room->getRoomTypeId(), static::WP_CACHE_GROUP );
				}
			},
			10,
			3
		);

		add_action(
			'save_post_' . MPHB()->postTypes()->rate()->getPostType(),
			function( $wpPostId, $wpPost, bool $isUpdated ) {
				$rate = MPHB()->getRateRepository()->findById( $wpPostId );
				if ( $rate ) {
					wp_cache_delete( 'getDatesRatesForRoomType' . $rate->getRoomTypeId(), static::WP_CACHE_GROUP );
				}
			},
			10,
			3
		);

		add_action(
			'update_option_mphb_booking_rules_custom',
			function() {
				$allRoomTypes = MPHB()->getRoomTypeRepository()->findAll();
				foreach ( $allRoomTypes as $roomType ) {
					wp_cache_delete( 'getBlockedRoomsCountsForRoomType' . $roomType->getId(), static::WP_CACHE_GROUP );
				}
			}
		);
	}

	/**
	 * @return Entities\RoomType or null if nothing is found
	 */
	public function getRoomTypeById( int $roomTypeId ) {
		// we already have entities cache by id in repository!
		return MPHB()->getRoomTypeRepository()->findById( $roomTypeId );
	}

	/**
	 * @return array with [
	 *      'booked' => [ 'Y-m-d' => rooms count, ... ],
	 *      'check-ins' => [ 'Y-m-d' => rooms count, ... ],
	 *      'check-outs' => [ 'Y-m-d' => rooms count, ... ],
	 * ]
	 */
	public function getBookedDaysForRoomType( int $roomTypeOriginalId ) {

		$result = wp_cache_get( 'getBookedDaysForRoomType' . $roomTypeOriginalId, static::WP_CACHE_GROUP );

		if ( ! $result ) {
			$result = MPHB()->getRoomRepository()->getBookedDays( $roomTypeOriginalId );
			wp_cache_set( 'getBookedDaysForRoomType' . $roomTypeOriginalId, $result, static::WP_CACHE_GROUP );
		}
		return $result;
	}

	public function getActiveRoomsCountForRoomType( int $roomTypeOriginalId ) {

		$result = wp_cache_get( 'getActiveRoomsCountForRoomType' . $roomTypeOriginalId, static::WP_CACHE_GROUP );

		if ( ! $result ) {
			$result = RoomAvailabilityHelper::getActiveRoomsCountForRoomType( $roomTypeOriginalId );
			wp_cache_set( 'getActiveRoomsCountForRoomType' . $roomTypeOriginalId, $result, static::WP_CACHE_GROUP );
		}
		return $result;
	}

	public function getDatesRatesForRoomType( int $roomTypeOriginalId ) {

		$result = wp_cache_get( 'getDatesRatesForRoomType' . $roomTypeOriginalId, static::WP_CACHE_GROUP );

		if ( ! $result ) {
			$roomType = $this->getRoomTypeById( $roomTypeOriginalId );
			$result   = null != $roomType ? $roomType->getDatesHavePrice() : array();
			wp_cache_set( 'getDatesRatesForRoomType' . $roomTypeOriginalId, $result, static::WP_CACHE_GROUP );
		}
		return $result;
	}

	public function getBlockedRoomsCountsForRoomType( int $roomTypeOriginalId ) {

		$result = wp_cache_get( 'getBlockedRoomsCountsForRoomType' . $roomTypeOriginalId, static::WP_CACHE_GROUP );

		if ( ! $result ) {
			$result = MPHB()->getRulesChecker()->customRules()->getBlockedRoomsCounts( $roomTypeOriginalId );
			wp_cache_set( 'getBlockedRoomsCountsForRoomType' . $roomTypeOriginalId, $result, static::WP_CACHE_GROUP );
		}
		return $result;
	}

	const ROOM_TYPE_AVAILABILITY_STATUS_AVAILABLE           = 'available';
	const ROOM_TYPE_AVAILABILITY_STATUS_NOT_AVAILABLE       = 'not-available';
	const ROOM_TYPE_AVAILABILITY_STATUS_BOOKED              = 'booked';
	const ROOM_TYPE_AVAILABILITY_STATUS_PAST                = 'past';
	const ROOM_TYPE_AVAILABILITY_STATUS_EARLIER_MIN_ADVANCE = 'earlier-min-advance';
	const ROOM_TYPE_AVAILABILITY_STATUS_LATER_MAX_ADVANCE   = 'later-max-advance';
	const ROOM_TYPE_AVAILABILITY_STATUS_BOOKING_BUFFER      = 'booking-buffer';

	/**
	 * @return array with: [
	 * 		'roomTypeStatus' => string - one of constant ROOM_TYPE_AVAILABILITY_STATUS_* above,
	 * 		'availableRoomsCount' => int,
	 * 		'isCheckInDate' => bool,
	 * 		'isCheckOutDate' => bool,
	 * 		'isStayInNotAllowed' => bool,
	 * 		'isCheckInNotAllowed' => bool,
	 * 		'isCheckOutNotAllowed' => bool,
	 * 		'isEarlierThanMinAdvanceDate' => bool,
	 * 		'isLaterThanMaxAdvanceDate' => bool
	 * ]
	 */
	public function getRoomTypeAvailabilityData( int $roomTypeOriginalId, \DateTime $date ) {

		return RoomAvailabilityHelper::getRoomTypeAvailabilityData( $roomTypeOriginalId, $date );
	}

	/**
	 * @return float room type minimal price for min days stay with taxes and fees
	 * @throws Exception if booking is not allowed for given date
	 */
	public function getMinRoomTypeBasePriceForDate( int $roomTypeOriginalId, \DateTime $startDate ) {

		$endDate = clone $startDate;
		$endDate = $endDate->modify( '+1 days' );

		return mphb_get_room_type_base_price( $roomTypeOriginalId, $startDate, $endDate );
	}

	/**
	 * @param array $atts with:
	 * 'decimal_separator' => string,
	 * 'thousand_separator' => string,
	 * 'decimals' => int, Number of decimals
	 * 'is_truncate_price' => bool, false by default
	 * 'currency_position' => string, Possible values: after, before, after_space, before_space
	 * 'currency_symbol' => string,
	 * 'literal_free' => bool, Use "Free" text instead of 0 price.
	 * 'trim_zeros' => bool, true by default
	 * 'period' => bool,
	 * 'period_title' => '',
	 * 'period_nights' => 1,
	 * 'as_html' => bool, true by default
	 */
	public function formatPrice( float $price, array $atts = array() ) {
		return mphb_format_price( $price, $atts );
	}
}
