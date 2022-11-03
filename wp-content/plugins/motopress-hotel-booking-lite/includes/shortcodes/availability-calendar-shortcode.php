<?php

namespace MPHB\Shortcodes;

class AvailabilityCalendarShortcode extends AbstractShortcode {

	protected $name = 'mphb_availability_calendar';

	/**
	 * @param array  $atts
	 * @param null   $content
	 * @param string $name
	 *
	 * @return string
	 */
	public function render( $atts, $content, $shortcodeName ) {

		$defaultAtts = array(
			'id'               => get_the_ID(),
			'monthstoshow'     => '',
			'display_price'    => MPHB()->settings()->main()->isRoomTypeCalendarShowPrices(),
			'truncate_price'   => MPHB()->settings()->main()->isRoomTypeCalendarTruncatePrices(),
			'display_currency' => MPHB()->settings()->main()->isRoomTypeCalendarShowPricesCurrency(),
			'class'            => '',
		);

		$atts = shortcode_atts( $defaultAtts, $atts, $shortcodeName );

		$roomType = MPHB()->getRoomTypeRepository()->findById( $atts['id'] );
		if ( ! $roomType ) {
			return '';
		}

		MPHB()->getPublicScriptManager()->addRoomTypeData( $atts['id'] );

		$calendarAtts = '';

		// It's not IDs, but also must be > 0
		$monthsToShow = \MPHB\Utils\ValidateUtils::validateCommaSeparatedIds( $atts['monthstoshow'] );

		if ( ! empty( $monthsToShow ) ) {
			// Must be only 1 or 2 numbers
			$monthsToShow  = array_slice( $monthsToShow, 0, 2 );
			$calendarAtts .= ' data-monthstoshow="' . esc_attr( join( ',', $monthsToShow ) ) . '"';
		}

		$calendarAtts .= ' data-is_show_prices="' . ( rest_sanitize_boolean( $atts['display_price'] ) ? 1 : 0 ) . '"';
		$calendarAtts .= ' data-is_truncate_prices="' . ( rest_sanitize_boolean( $atts['truncate_price'] ) ? 1 : 0 ) . '"';
		$calendarAtts .= ' data-is_show_prices_currency="' . ( rest_sanitize_boolean( $atts['display_currency'] ) ? 1 : 0 ) . '"';

		ob_start();

		do_action( 'mphb_sc_before_availability_calendar' );

		mphb_tmpl_the_room_type_calendar( $roomType, $calendarAtts );

		do_action( 'mphb_sc_after_availability_calendar' );

		$content = ob_get_clean();

		$wrapperClass = apply_filters( 'mphb_sc_availability_calendar_wrapper_classes', 'mphb_sc_availability_calendar-wrapper' );
		$wrapperClass = trim( $wrapperClass . ' ' . $atts['class'] );
		return '<div class="' . esc_attr( $wrapperClass ) . '">' . $content . '</div>';
	}

}
