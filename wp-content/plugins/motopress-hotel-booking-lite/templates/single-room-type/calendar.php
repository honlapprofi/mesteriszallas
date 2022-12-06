<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php

/**
 * @hooked \MPHB\Views\SingleRoomTypeView::_renderCalendarTitle - 10
 */
do_action( 'mphb_render_single_room_type_before_calendar' );
?>

<?php mphb_tmpl_the_room_type_calendar( 
	null, 
	' data-is_show_prices="' . ( MPHB()->settings()->main()->isRoomTypeCalendarShowPrices() ? 1 : 0 ) . '"' .
	' data-is_truncate_prices="' . ( MPHB()->settings()->main()->isRoomTypeCalendarTruncatePrices() ? 1 : 0 ) . '"' .
	' data-is_show_prices_currency="' . ( MPHB()->settings()->main()->isRoomTypeCalendarShowPricesCurrency() ? 1 : 0 ) . '"'
); ?>

<?php do_action( 'mphb_render_single_room_type_after_calendar' ); ?>