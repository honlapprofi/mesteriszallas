<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2021 PublishPress
 *
 * WordPressBanners is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * WordPressBanners is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace PublishPress\WordPressBanners;

use Exception;

define('PP_WP_BANNERS_VERSION', '1.2.4');

class BannersMain
{

    /**
     * HTML output banner inviting to install another PublishPress plugin or advertise a feature
     *
     * @param string    $heading        Custom heading; disabled if blank
     * @param string    $title          Custom title; disabled if blank
     * @param array     $content        Content to display. e.g. Feature list or a single paragraph
     * @param string    $link           Link to apply to button and image
     * @param string    $link_title     Link title
     * @param string    $image          A filename from assets/images/ folder; disabled if blank
     *
     * @return void
     */
    public function pp_display_banner(
        $heading = '',
        $title = '',
        $contents = array(),
        $link = '',
        $link_title = '',
        $image = ''
    ) {
        if( !empty($heading) ) {
            ?>
			<p class="nav-tab-wrapper pp-recommendations-heading">
				<?php echo $heading ?>
			</p>
            <?php
        }
        ?>

		<div class="pp-sidebar-box">

            <?php
            if( !empty($title) ) {
                ?>
				<h3>
					<?php echo $title ?>
				</h3>
                <?php
            }

            if( count($contents) > 1 ) {
                ?>
				<ul>
                    <?php
                    foreach($contents as $content) {
                        ?>
    					<li>
    						<?php echo $content; ?>
    					</li>
                        <?php
                    }
                    ?>
				</ul>
                <?php
            } else {
                ?>
                <p><?php echo $contents[0] ?></p>
                <?php
            }
            ?>

			<p>
				<a class="button button-primary" href="<?php echo $link ?>">
					<?php echo $link_title ?>
				</a>
			</p>

            <?php
            if(!empty($image) && file_exists(__DIR__  . '/assets/images/' . $image)) {
                ?>
				<div class="pp-box-banner-image">
					<a href="<?php echo $link ?>">
						<img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/' . $image ?>" title="<?php echo $title ?>" />
					</a>
				</div>
                <?php
            }
            ?>

		</div>

		<?php
        wp_enqueue_style(
            'pp-wordpress-banners-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            false,
            PP_WP_BANNERS_VERSION
        );
	}
}
