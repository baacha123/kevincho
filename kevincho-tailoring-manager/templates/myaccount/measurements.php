<?php
/**
 * My Measurements — WooCommerce My Account tab template.
 *
 * Displays the customer's profile and body measurement form with AJAX save.
 * This template is loaded by KCTM_My_Account_Endpoints::render_endpoint().
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id      = get_current_user_id();
$measurements = array();

if ( class_exists( 'KCTM_Measurement_Storage' ) ) {
	$measurements = KCTM_Measurement_Storage::get_measurements( $user_id );
}

// Current gender value (used to control bust field visibility).
$current_gender = isset( $measurements['gender'] ) && '' !== $measurements['gender']
	? $measurements['gender']
	: 'male';
?>

<div class="kctm-measurements-wrap">

	<h2><?php esc_html_e( 'My Measurements', 'kevincho-tailoring-manager' ); ?></h2>

	<p class="kctm-measurements-note">
		<?php
		printf(
			/* translators: %s: WhatsApp link text */
			esc_html__( 'All measurements are in centimeters (cm). Need help measuring? %s', 'kevincho-tailoring-manager' ),
			'<a href="https://wa.me/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Contact us on WhatsApp.', 'kevincho-tailoring-manager' ) . '</a>'
		);
		?>
	</p>

	<div class="kctm-message-container" id="kctm-message-container" style="display:none;" role="alert"></div>

	<form id="kctm-measurement-form" class="kctm-measurement-form" method="post" novalidate>

		<?php wp_nonce_field( 'kctm_save_measurements', 'kctm_measurements_nonce' ); ?>

		<!-- ============================================================
		     Profile Section
		     ============================================================ -->
		<fieldset class="kctm-fieldset kctm-fieldset-profile">
			<legend class="kctm-fieldset-legend"><?php esc_html_e( 'Profile', 'kevincho-tailoring-manager' ); ?></legend>

			<!-- Gender -->
			<div class="kctm-field kctm-field-gender">
				<label class="kctm-field-label"><?php esc_html_e( 'Gender', 'kevincho-tailoring-manager' ); ?> <span class="kctm-required">*</span></label>
				<div class="kctm-radio-group" id="kctm-gender-radios">
					<label class="kctm-radio-label">
						<input type="radio" name="measurements[gender]" value="male" <?php checked( $current_gender, 'male' ); ?>>
						<?php esc_html_e( 'Male', 'kevincho-tailoring-manager' ); ?>
					</label>
					<label class="kctm-radio-label">
						<input type="radio" name="measurements[gender]" value="female" <?php checked( $current_gender, 'female' ); ?>>
						<?php esc_html_e( 'Female', 'kevincho-tailoring-manager' ); ?>
					</label>
					<label class="kctm-radio-label">
						<input type="radio" name="measurements[gender]" value="child" <?php checked( $current_gender, 'child' ); ?>>
						<?php esc_html_e( 'Child', 'kevincho-tailoring-manager' ); ?>
					</label>
				</div>
			</div>

			<!-- Age -->
			<div class="kctm-field kctm-field-age">
				<label class="kctm-field-label" for="kctm-age">
					<?php esc_html_e( 'Age', 'kevincho-tailoring-manager' ); ?> <span class="kctm-required">*</span>
				</label>
				<input
					type="number"
					id="kctm-age"
					name="measurements[age]"
					class="kctm-input kctm-input-number"
					value="<?php echo esc_attr( isset( $measurements['age'] ) ? $measurements['age'] : '' ); ?>"
					min="1"
					max="120"
					step="1"
					placeholder="<?php esc_attr_e( 'e.g. 30', 'kevincho-tailoring-manager' ); ?>"
				>
			</div>

			<!-- Height -->
			<div class="kctm-field kctm-field-height">
				<label class="kctm-field-label" for="kctm-height">
					<?php esc_html_e( 'Height', 'kevincho-tailoring-manager' ); ?> <span class="kctm-required">*</span>
				</label>
				<div class="kctm-input-with-unit">
					<input
						type="number"
						id="kctm-height"
						name="measurements[height]"
						class="kctm-input kctm-input-number"
						value="<?php echo esc_attr( isset( $measurements['height'] ) ? $measurements['height'] : '' ); ?>"
						min="50"
						max="250"
						step="0.1"
						placeholder="<?php esc_attr_e( 'e.g. 175', 'kevincho-tailoring-manager' ); ?>"
					>
					<span class="kctm-unit-label"><?php esc_html_e( 'cm', 'kevincho-tailoring-manager' ); ?></span>
				</div>
			</div>

			<!-- Weight -->
			<div class="kctm-field kctm-field-weight">
				<label class="kctm-field-label" for="kctm-weight">
					<?php esc_html_e( 'Weight', 'kevincho-tailoring-manager' ); ?> <span class="kctm-required">*</span>
				</label>
				<div class="kctm-input-with-unit">
					<input
						type="number"
						id="kctm-weight"
						name="measurements[weight]"
						class="kctm-input kctm-input-number"
						value="<?php echo esc_attr( isset( $measurements['weight'] ) ? $measurements['weight'] : '' ); ?>"
						min="10"
						max="300"
						step="0.1"
						placeholder="<?php esc_attr_e( 'e.g. 70', 'kevincho-tailoring-manager' ); ?>"
					>
					<span class="kctm-unit-label"><?php esc_html_e( 'kg', 'kevincho-tailoring-manager' ); ?></span>
				</div>
			</div>

			<!-- Shoe Size -->
			<div class="kctm-field kctm-field-shoe-size">
				<label class="kctm-field-label" for="kctm-shoe-size">
					<?php esc_html_e( 'Shoe Size', 'kevincho-tailoring-manager' ); ?>
				</label>
				<input
					type="number"
					id="kctm-shoe-size"
					name="measurements[shoe_size]"
					class="kctm-input kctm-input-number"
					value="<?php echo esc_attr( isset( $measurements['shoe_size'] ) ? $measurements['shoe_size'] : '' ); ?>"
					min="15"
					max="55"
					step="0.5"
					placeholder="<?php esc_attr_e( 'e.g. 42', 'kevincho-tailoring-manager' ); ?>"
				>
			</div>

		</fieldset>

		<!-- ============================================================
		     Body Measurements — Upper Body
		     ============================================================ -->
		<fieldset class="kctm-fieldset kctm-fieldset-upper-body">
			<legend class="kctm-fieldset-legend"><?php esc_html_e( 'Upper Body', 'kevincho-tailoring-manager' ); ?></legend>

			<?php
			$upper_body_fields = array(
				array( 'key' => 'neck',           'label' => __( 'Neck', 'kevincho-tailoring-manager' ),           'min' => 20, 'max' => 60,  'required' => true ),
				array( 'key' => 'chest',          'label' => __( 'Chest', 'kevincho-tailoring-manager' ),          'min' => 50, 'max' => 180, 'required' => true ),
				array( 'key' => 'shoulder_width',  'label' => __( 'Shoulder Width', 'kevincho-tailoring-manager' ),  'min' => 25, 'max' => 70,  'required' => true ),
				array( 'key' => 'sleeve_length',   'label' => __( 'Sleeve Length', 'kevincho-tailoring-manager' ),   'min' => 30, 'max' => 90,  'required' => true ),
				array( 'key' => 'bicep',           'label' => __( 'Bicep', 'kevincho-tailoring-manager' ),           'min' => 15, 'max' => 60,  'required' => false ),
				array( 'key' => 'wrist',           'label' => __( 'Wrist', 'kevincho-tailoring-manager' ),           'min' => 10, 'max' => 30,  'required' => false ),
				array( 'key' => 'back_length',     'label' => __( 'Back Length', 'kevincho-tailoring-manager' ),     'min' => 25, 'max' => 80,  'required' => true ),
				array( 'key' => 'front_length',    'label' => __( 'Front Length', 'kevincho-tailoring-manager' ),    'min' => 25, 'max' => 80,  'required' => true ),
			);

			foreach ( $upper_body_fields as $field ) :
				$field_value = isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '';
				$field_id    = 'kctm-' . str_replace( '_', '-', $field['key'] );
			?>
				<div class="kctm-field kctm-field-<?php echo esc_attr( str_replace( '_', '-', $field['key'] ) ); ?>">
					<label class="kctm-field-label" for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( $field['required'] ) : ?>
							<span class="kctm-required">*</span>
						<?php endif; ?>
					</label>
					<div class="kctm-input-with-unit">
						<input
							type="number"
							id="<?php echo esc_attr( $field_id ); ?>"
							name="measurements[<?php echo esc_attr( $field['key'] ); ?>]"
							class="kctm-input kctm-input-number"
							value="<?php echo esc_attr( $field_value ); ?>"
							min="<?php echo esc_attr( $field['min'] ); ?>"
							max="<?php echo esc_attr( $field['max'] ); ?>"
							step="0.1"
						>
						<span class="kctm-unit-label"><?php esc_html_e( 'cm', 'kevincho-tailoring-manager' ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>

			<!-- Bust (Female only) -->
			<?php
			$bust_value  = isset( $measurements['bust'] ) ? $measurements['bust'] : '';
			$bust_hidden = ( 'female' !== $current_gender ) ? 'display:none;' : '';
			?>
			<div
				class="kctm-field kctm-field-bust kctm-field-gender-conditional"
				data-gender="female"
				style="<?php echo esc_attr( $bust_hidden ); ?>"
			>
				<label class="kctm-field-label" for="kctm-bust">
					<?php esc_html_e( 'Bust', 'kevincho-tailoring-manager' ); ?>
				</label>
				<div class="kctm-input-with-unit">
					<input
						type="number"
						id="kctm-bust"
						name="measurements[bust]"
						class="kctm-input kctm-input-number"
						value="<?php echo esc_attr( $bust_value ); ?>"
						min="50"
						max="180"
						step="0.1"
					>
					<span class="kctm-unit-label"><?php esc_html_e( 'cm', 'kevincho-tailoring-manager' ); ?></span>
				</div>
			</div>

		</fieldset>

		<!-- ============================================================
		     Body Measurements — Core
		     ============================================================ -->
		<fieldset class="kctm-fieldset kctm-fieldset-core">
			<legend class="kctm-fieldset-legend"><?php esc_html_e( 'Core', 'kevincho-tailoring-manager' ); ?></legend>

			<?php
			$core_fields = array(
				array( 'key' => 'waist', 'label' => __( 'Waist', 'kevincho-tailoring-manager' ), 'min' => 40, 'max' => 180, 'required' => true ),
				array( 'key' => 'hips',  'label' => __( 'Hips', 'kevincho-tailoring-manager' ),  'min' => 50, 'max' => 180, 'required' => true ),
			);

			foreach ( $core_fields as $field ) :
				$field_value = isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '';
				$field_id    = 'kctm-' . str_replace( '_', '-', $field['key'] );
			?>
				<div class="kctm-field kctm-field-<?php echo esc_attr( str_replace( '_', '-', $field['key'] ) ); ?>">
					<label class="kctm-field-label" for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( $field['required'] ) : ?>
							<span class="kctm-required">*</span>
						<?php endif; ?>
					</label>
					<div class="kctm-input-with-unit">
						<input
							type="number"
							id="<?php echo esc_attr( $field_id ); ?>"
							name="measurements[<?php echo esc_attr( $field['key'] ); ?>]"
							class="kctm-input kctm-input-number"
							value="<?php echo esc_attr( $field_value ); ?>"
							min="<?php echo esc_attr( $field['min'] ); ?>"
							max="<?php echo esc_attr( $field['max'] ); ?>"
							step="0.1"
						>
						<span class="kctm-unit-label"><?php esc_html_e( 'cm', 'kevincho-tailoring-manager' ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>

		</fieldset>

		<!-- ============================================================
		     Body Measurements — Lower Body
		     ============================================================ -->
		<fieldset class="kctm-fieldset kctm-fieldset-lower-body">
			<legend class="kctm-fieldset-legend"><?php esc_html_e( 'Lower Body', 'kevincho-tailoring-manager' ); ?></legend>

			<?php
			$lower_body_fields = array(
				array( 'key' => 'inseam',  'label' => __( 'Inseam', 'kevincho-tailoring-manager' ),  'min' => 30, 'max' => 100, 'required' => true ),
				array( 'key' => 'outseam', 'label' => __( 'Outseam', 'kevincho-tailoring-manager' ), 'min' => 50, 'max' => 130, 'required' => true ),
				array( 'key' => 'thigh',   'label' => __( 'Thigh', 'kevincho-tailoring-manager' ),   'min' => 30, 'max' => 90,  'required' => false ),
				array( 'key' => 'knee',    'label' => __( 'Knee', 'kevincho-tailoring-manager' ),    'min' => 20, 'max' => 60,  'required' => false ),
				array( 'key' => 'calf',    'label' => __( 'Calf', 'kevincho-tailoring-manager' ),    'min' => 20, 'max' => 60,  'required' => false ),
				array( 'key' => 'ankle',   'label' => __( 'Ankle', 'kevincho-tailoring-manager' ),   'min' => 15, 'max' => 40,  'required' => false ),
			);

			foreach ( $lower_body_fields as $field ) :
				$field_value = isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '';
				$field_id    = 'kctm-' . str_replace( '_', '-', $field['key'] );
			?>
				<div class="kctm-field kctm-field-<?php echo esc_attr( str_replace( '_', '-', $field['key'] ) ); ?>">
					<label class="kctm-field-label" for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( $field['required'] ) : ?>
							<span class="kctm-required">*</span>
						<?php endif; ?>
					</label>
					<div class="kctm-input-with-unit">
						<input
							type="number"
							id="<?php echo esc_attr( $field_id ); ?>"
							name="measurements[<?php echo esc_attr( $field['key'] ); ?>]"
							class="kctm-input kctm-input-number"
							value="<?php echo esc_attr( $field_value ); ?>"
							min="<?php echo esc_attr( $field['min'] ); ?>"
							max="<?php echo esc_attr( $field['max'] ); ?>"
							step="0.1"
						>
						<span class="kctm-unit-label"><?php esc_html_e( 'cm', 'kevincho-tailoring-manager' ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>

		</fieldset>

		<!-- ============================================================
		     Submit
		     ============================================================ -->
		<div class="kctm-form-actions">
			<button type="submit" class="kctm-btn kctm-btn-primary" id="kctm-save-measurements">
				<?php esc_html_e( 'Save Measurements', 'kevincho-tailoring-manager' ); ?>
			</button>
			<span class="kctm-spinner" id="kctm-spinner" style="display:none;"></span>
		</div>

	</form>

</div>
