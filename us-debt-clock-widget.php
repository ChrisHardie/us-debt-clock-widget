<?php
/*
Plugin Name: U.S. Debt Clock Widget
Plugin URI: https://github.com/ChrisHardie/us-debt-clock-widget
Description: Display the U.S. national debt in a widget
Author: Chris Hardie
Version: 1.3
Author URI: https://chrishardie.com/
License: GPL2
*/

/*

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

defined( 'ABSPATH' ) or die( "Please don't try to run this file directly." );

class Debtclock_Widget extends WP_Widget {
	/**
	 * Register the widget with WordPress
	 */
	function __construct() {
		parent::__construct(
			'us_debtclock_widget', // base id
			esc_html__( 'U.S. Debt Clock Widget', 'us_debtclock_widget_domain' ), // name
			array( 'description' => __( 'A widget to display the U.S. national debt.', 'us_debtclock_widget_domain' ) )
		);
	}


	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	function widget( $args, $instance ) {

		// If we can't get a value for the current debt info, at least display something.
		if ( false === ( $debt_info = JCH_Debtclock::get_debt() ) ) {
			$debt_amount = 'UNAVAILABLE';
			$debt_amount_formatted = 'UNAVAILABLE';
		} elseif ( is_numeric( $debt_info['close_today'] ) ) {

			// The value from treasury.io is in millions, e.g. 1000 equals $1 billion.
			$debt_amount = $debt_info['close_today'] * 1000000;

			// For maximum effect let's display the big number, with commas, no cents.
			$debt_amount_formatted = number_format( $debt_amount, 0, '.', ',' );

			if ( $instance['animate_p'] ) {

				// Calculate how much the debt increased per second on average
				$debt_delta = ( ( ( $debt_info['close_today'] - $debt_info['open_today'] ) * 1000000 ) / 86400 );

				wp_enqueue_script( 'jquery' );

			}
		} else {
			$debt_amount = 'INVALID';
			$debt_amount_formatted = 'INVALID';
		}

		// Output the widget content
		echo $args['before_widget'];

		// Title
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		// User-defined introduction
		if ( ! empty( $instance['introduction'] ) ) {
			echo '<div class="us_debtclock_widget_introduction">';
			echo esc_html_e( $instance['introduction'] );
			echo '</div>';
		}

		// Actual debt amount, formatted (maybe replaced by JS actions below)
		echo '<div id="debtclock_amount" class="us_debtclock_widget_amount">$'
		     . esc_html( $debt_amount_formatted ) . '</div>';

		// If they want moving numbers and we're starting with a real number...
		if ( $instance['animate_p'] && is_numeric( $debt_amount ) ) {

			// Increment the amount by the per-second delta we calculated earlier
			echo "<script type='text/javascript'>";
			echo '
					var INTERVAL = 1; // refresh interval in seconds
					var INCREMENT = ' . esc_js( $debt_delta ) . ';  // increase per tick
					var START_VALUE = ' . esc_js( $debt_amount ) . "; // initial value when it's the start date
					var count = 0;

					jQuery(document).ready(function() {

						var msInterval = INTERVAL * 1000;
						var now = new Date();
						count = START_VALUE;

						window.setInterval( function(){

							count += INCREMENT;
							count_formatted = count.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, \"$1,\");
							jQuery('#debtclock_amount').html(\"$\" + count_formatted);

						}, msInterval);

					});
				";
			echo '</script>';
		}

		if ( ! is_numeric( $debt_amount ) ) {
			echo '<div id="debtclock_error" class="us_debtclock_widget_error">There was a
				problem fetching the data, please try again later.</div>';
		}

		// Give credit where it's due?
		if ( $instance['show_credit_p'] ) {
			echo '<p class="us_debtclock_widget_credit">
				<a class="us_debtclock_widget_credit_link" target="_blank" href=" ' . esc_html( $debt_info['url'] ) . '">Source</a>,
				via <a class="us_debtclock_widget_credit_link" target="_blank" href="http://treasury.io/">treasury.io</a>
			</p>';
		}

		echo $args['after_widget'];
	}


	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['introduction'] = ( ! empty( $new_instance['introduction'] ) ) ? strip_tags( $new_instance['introduction'] ) : '';
		$instance['animate_p'] = ! empty( $new_instance['animate_p'] ) ? 1 : 0;
		$instance['show_credit_p'] = ! empty( $new_instance['show_credit_p'] ) ? 1 : 0;

		return $instance;
	}

	/**
	 * Back-end form to manage a widget's options in wp-admin
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		}
		else {
			$title = esc_html__( 'U.S. National Debt', 'us_debtclock_widget_domain' );
		}
		if ( isset( $instance['introduction'] ) ) {
			$introduction = $instance['introduction'];
		}
		else {
			$introduction = esc_html__( 'The current U.S. national debt:', 'us_debtclock_widget_domain' );
		}

		// Will the value of the debt be animated to show change over time?
		$animate_p = isset( $instance['animate_p'] ) ? (bool) $instance['animate_p'] : false;

		// Should we show the source of the debt data?
		$show_credit_p = isset( $instance['show_credit_p'] ) ? (bool) $instance['show_credit_p'] : true;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       type="text" value="<?php echo esc_html( esc_attr( $title ) ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'introduction' ) ); ?>"><?php esc_html_e( 'Introduction Text:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'introduction' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'introduction' ) ); ?>"
			       type="text" value="<?php echo esc_attr( $introduction ); ?>">
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $animate_p ); ?> id="<?php echo esc_attr( $this->get_field_id( 'animate_p' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'animate_p' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'animate_p' ) ); ?>"><?php esc_html_e( 'Animate dollar amount with estimated change over time?' ); ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_credit_p ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_credit_p' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'show_credit_p' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_credit_p' ) ); ?>"><?php esc_html_e( 'Include credit link to data source?' ); ?></label>
		</p>

	<?php
	}
}

class JCH_Debtclock {
	function __construct() {
		add_action( 'init', array( $this, 'init' ), 1 );

		register_activation_hook( __FILE__, array( $this, 'us_debtclock_widget_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'us_debtclock_widget_deactivation' ) );

	}

	public function init() {

		// If the widget is active, enqueue needed CSS
		if ( is_active_widget( false, false, 'us_debtclock_widget' ) ) {
			add_action( 'wp_head', array( &$this, 'add_styles_and_scripts' ) );
		}

	}

	/**
	 * On plugin activation, schedule an hourly update of the debt data from the source.
	 */
	public function us_debtclock_widget_activation() {
		if ( ! wp_next_scheduled( 'us_debtclock_widget_event_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'us_debtclock_widget_event_hook' );
		}

		add_action( 'us_debtclock_widget_event_hook', 'get_debt' );
	}

	/**
	 * On plugin deactivation, remove all functions from the scheduled action hook.
	 */
	public function us_debtclock_widget_deactivation() {
		wp_clear_scheduled_hook( 'us_debtclock_widget_event_hook' );
		delete_transient( 'us_debtclock_widget_info' );
	}

	function add_styles_and_scripts() {
		wp_enqueue_style( 'debtclock_widget_style', plugins_url( 'style.css', __FILE__ ) );
	}

	/**
	 * Actually fetch the debt info from the remote source
	 */
	public static function get_debt( ) {

		global $us_debtclock_widget_info; // Check if it's in the runtime cache
		if ( empty( $us_debtclock_widget_info ) ) {
			$us_debtclock_widget_info = get_transient( 'us_debtclock_widget_info' ); // Check database
		}

		if ( ! empty( $us_debtclock_widget_info ) ) {
			return $us_debtclock_widget_info;
		}

		// Query treasury.io - see http://treasury.io/
		$treasury_api_url = 'http://api.treasury.io/cc7znvq/47d80ae900e04f2/sql/?q=';

		// Get the open and close date along with source URL for the total outstanding date, most recent, only one record
		$debt_sql = 'SELECT "close_today", "open_today", "url" FROM t3c WHERE "item_raw" = \'Total Public Debt Outstanding\' ORDER BY date DESC LIMIT 1';
		$encoded_debt_sql = urlencode( $debt_sql );

		/**
		 * Example request URL:
		 * http://api.treasury.io/cc7znvq/47d80ae900e04f2/sql/?q=SELECT+%22close_today%22%2C+%22open_today%22%2C+%22url%22+FROM+t3c+WHERE+%22item_raw%22+%3D+%27Total+Public+Debt+Outstanding%27+ORDER+BY+date+DESC+LIMIT+1
		 */
		$response = wp_remote_get( $treasury_api_url . $encoded_debt_sql );
		$data = wp_remote_retrieve_body( $response );

		/**
		 * Example return data:
		 * [{"close_today": 17899001.0, "open_today": 17898403.0, "url": "https://www.fms.treas.gov/fmsweb/viewDTSFiles?fname=14102300.txt&dir=w"}]
		 */

		// If it was empty, something went wrong
		if ( empty( $data ) ) {
			return false;
		}

		$us_debtclock_widget_info = reset( json_decode( $data, true ) ); // Load data into runtime cache

		// If it doesn't have the field data requested, something went wrong
		if ( ! $us_debtclock_widget_info['close_today'] ) {
			return false;
		}

		// If it's a real number, put it in a transient for later use
		if ( is_numeric( $us_debtclock_widget_info['close_today'] ) ) {
			set_transient( 'us_debtclock_widget_info', $us_debtclock_widget_info, 1 * 60 * 60 ); // Store in database for up to 1 hour
		} else {
			return false;
		}

		return $us_debtclock_widget_info;
	}

}

$jch_debtclock_widget = new JCH_Debtclock();

add_action( 'widgets_init', function() {
	register_widget( 'Debtclock_Widget' );
} );



?>
