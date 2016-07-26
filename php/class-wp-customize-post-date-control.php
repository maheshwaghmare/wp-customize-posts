<?php
/**
 * Customize Post Date Control Class
 *
 * @package WordPress
 * @subpackage Customize
 */

/**
 * Class WP_Customize_Post_Date_Control
 */
class WP_Customize_Post_Date_Control extends WP_Customize_Dynamic_Control {

	/**
	 * Type of control, used by JS.
	 *
	 * @access public
	 * @var string
	 */
	public $type = 'post_date';

	/**
	 * Get the data to export to the client via JSON.
	 *
	 * @return array Array of parameters passed to the JavaScript.
	 */
	public function json() {
		$exported = parent::json();
		$exported['month_choices'] = $this->get_month_choices();
		$exported['date_inputs'] = array(
			'month' => null,
			'date' => 2,
			'year' => 4,
			'hour' => 2,
			'min' => 2,
		);
		return $exported;
	}

	/**
	 * Render the Underscore template for this control.
	 *
	 * @access protected
	 * @codeCoverageIgnore
	 */
	protected function content_template() {
		$data = $this->json();
		?>
		<#
			_.defaults( data, <?php echo wp_json_encode( $data ) ?> );
			data.input_id = 'input-' + String( Math.random() );
		#>
		<span class="customize-control-title"><label for="{{ data.input_id }}">{{ data.label }}</label></span>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{ data.description }}</span>
		<# } #>

		<# _.each( data.date_inputs, function( width, type ) { #>
			<# if ( 'month' === type  ) { #>
				<select class="date-input {{ type }}">
					<# _.each( data.month_choices, function( choice ) { #>
						<#
							if ( _.isObject( choice ) && ! _.isUndefined( choice.text ) && ! _.isUndefined( choice.value ) ) {
								text = choice.text;
								value = choice.value;
							}
						#>
						<option value="{{ value }}">{{ text }}</option>
					<# } ); #>
				</select>
			<# } else { #>
				<input
					type="text"
					size="{{ width }}"
					maxlength="{{width}}""
					autocomplete="off"
					class="date-input {{ type }}"
					value=""
					/>
					<# if ( 'year' === type ) { #>
						&nbsp;@&nbsp;
						<# } #>
			<# } #>
		<# }); #>
		<input
			id="{{ data.input_id }}"
			type="text" <!-- This will become type="hidden" -->
			<# _.each( data.input_attrs, function( value, key ) { #>
				{{{ key }}}="{{ value }}"
			<# } ) #>
			<# if ( data.setting_property ) { #>
				data-customize-setting-property-link="{{ data.setting_property }}"
			<# } #>
			/>
		<?php
	}

	/**
	 * Generate options for the month Select.
	 *
	 * Based on touch_time().
	 *
	 * @see touch_time()
	 *
	 * @return array
	 */
	public function get_month_choices() {
		global $wp_locale;
		$months = array();
		for ( $i = 1; $i < 13; $i = $i + 1 ) {
			$month_number = zeroise( $i, 2 );
			$month_text = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );

			/* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
			$months[ $i ]['text'] = sprintf( __( '%1$s-%2$s', 'customize-posts' ), $month_number, $month_text );
			$months[ $i ]['value'] = $month_number;
		}
		return $months;
	}
}
