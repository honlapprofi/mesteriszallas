<?php

namespace MPHB\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class RoomAvailabilityHelper {

	private function __construct() {}

	public static function getActiveRoomsCountForRoomType( int $roomTypeOriginalId ) {

		return MPHB()->getRoomPersistence()->getCount(
			array(
				'room_type_id' => $roomTypeOriginalId,
				'post_status'  => 'publish',
			)
		);
	}

	public static function getAvailableRoomsCountForRoomType( int $roomTypeOriginalId, \DateTime $date ) {

		$availableRoomsCount = MPHB()->getCoreAPI()->getActiveRoomsCountForRoomType( $roomTypeOriginalId );
		$formattedDate       = $date->format( 'Y-m-d' );

		$bookedDays = MPHB()->getCoreAPI()->getBookedDaysForRoomType( $roomTypeOriginalId );

		if ( ! empty( $bookedDays['booked'][ $formattedDate ] ) ) {
			$availableRoomsCount = $availableRoomsCount - $bookedDays['booked'][ $formattedDate ];
		}

		$blokedRoomsCount = MPHB()->getCoreAPI()->getBlockedRoomsCountsForRoomType( $roomTypeOriginalId );

		if ( ! empty( $blokedRoomsCount[ $formattedDate ] ) ) {
			$availableRoomsCount = $availableRoomsCount - $blokedRoomsCount[ $formattedDate ];
		}
		return $availableRoomsCount;
	}


	public static function getRoomTypeAvailabilityStatus( int $roomTypeOriginalId, \DateTime $date ) {

		if ( $date < ( new \DateTime() )->setTime( 0, 0, 0 ) ) {
			return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_PAST;
		}

		$bookedDays       = MPHB()->getCoreAPI()->getBookedDaysForRoomType( $roomTypeOriginalId );
		$activeRoomsCount = MPHB()->getCoreAPI()->getActiveRoomsCountForRoomType( $roomTypeOriginalId );
		$formattedDate    = $date->format( 'Y-m-d' );

		if ( ! empty( $bookedDays['booked'][ $formattedDate ] ) && $bookedDays['booked'][ $formattedDate ] >= $activeRoomsCount ) {
			return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_BOOKED;
		}

		if ( ! MPHB()->getRulesChecker()->reservationRules()->verifyMinAdvanceReservationRule( $date, $date, $roomTypeOriginalId ) ) {
			return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_EARLIER_MIN_ADVANCE;
		}

		if ( ! MPHB()->getRulesChecker()->reservationRules()->verifyMaxAdvanceReservationRule( $date, $date, $roomTypeOriginalId ) ) {
			return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_LATER_MAX_ADVANCE;
		}

		$datesRates = MPHB()->getCoreAPI()->getDatesRatesForRoomType( $roomTypeOriginalId );

		if ( ! in_array( $formattedDate, $datesRates ) ) {
			return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_NOT_AVAILABLE;
		}

		if ( 0 >= static::getAvailableRoomsCountForRoomType( $roomTypeOriginalId, $date ) ) {
			return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_NOT_AVAILABLE;
		}

		return CoreAPI::ROOM_TYPE_AVAILABILITY_STATUS_AVAILABLE;
	}


	public static function getRoomTypeAvailabilityData( int $roomTypeOriginalId, \DateTime $date ) {

		$bookedDays    = MPHB()->getCoreAPI()->getBookedDaysForRoomType( $roomTypeOriginalId );
		$formattedDate = $date->format( 'Y-m-d' );

		$reservationRules = MPHB()->getRulesChecker()->reservationRules();
		$customRules      = MPHB()->getRulesChecker()->customRules();

		$dateAfterForStayInCheck = clone $date;
		$dateAfterForStayInCheck = $dateAfterForStayInCheck->modify( '+1 day' );

		$result = array(
			'roomTypeStatus'              => self::getRoomTypeAvailabilityStatus( $roomTypeOriginalId, $date ),
			'availableRoomsCount'         => self::getAvailableRoomsCountForRoomType( $roomTypeOriginalId, $date ),
			'isCheckInDate'               => ! empty( $bookedDays['check-ins'][ $formattedDate ] ),
			'isCheckOutDate'              => ! empty( $bookedDays['check-outs'][ $formattedDate ] ),
			'isStayInNotAllowed'          => ! $customRules->verifyNotStayInRestriction( $date, $dateAfterForStayInCheck, $roomTypeOriginalId ),
			'isCheckInNotAllowed'         => ! $customRules->verifyNotCheckInRestriction( $date, $date, $roomTypeOriginalId ) ||
				! $reservationRules->verifyCheckInDaysReservationRule( $date, $date, $roomTypeOriginalId ),
			'isCheckOutNotAllowed'        => ! $customRules->verifyNotCheckOutRestriction( $date, $date, $roomTypeOriginalId ) ||
				! $reservationRules->verifyCheckOutDaysReservationRule( $date, $date, $roomTypeOriginalId ),
			'isEarlierThanMinAdvanceDate' => ! $reservationRules->verifyMinAdvanceReservationRule( $date, $date, $roomTypeOriginalId ),
			'isLaterThanMaxAdvanceDate'   => ! $reservationRules->verifyMaxAdvanceReservationRule( $date, $date, $roomTypeOriginalId ),
		);

		return $result;
	}
}
