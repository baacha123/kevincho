<?php
/**
 * Product Customizer — Garment personalization UI for WooCommerce product pages.
 *
 * Displays a Hockerty-inspired step-by-step customizer with selectable option
 * cards for each personalization group. Rendered by
 * KCTM_Personalization_Frontend::render_customizer() before the Add to Cart button.
 *
 * Expects the following variable to be set before inclusion:
 *   $groups — array of group objects, each with an `options` property (array of
 *             option objects). Retrieved via
 *             KCTM_Personalization_Options::get_groups_with_options().
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail if no groups are available.
if ( empty( $groups ) || ! is_array( $groups ) ) {
	return;
}
?>

<div class="kctm-personalizer" id="kctm-personalizer">

	<h3 class="kctm-personalizer-heading"><?php esc_html_e( 'Personalize Your Garment', 'kevincho-tailoring-manager' ); ?></h3>

	<?php foreach ( $groups as $group ) : ?>

		<?php
		// Skip groups with no options.
		if ( empty( $group->options ) ) {
			continue;
		}

		$group_slug    = isset( $group->slug )        ? $group->slug        : '';
		$group_title   = isset( $group->title )       ? $group->title       : '';
		$group_desc    = isset( $group->description )  ? $group->description  : '';
		$is_monogram   = ( 'monogram' === $group_slug );
		?>

		<div class="kctm-pz-group" data-group="<?php echo esc_attr( $group_slug ); ?>">

			<h4 class="kctm-pz-group-title"><?php echo esc_html( $group_title ); ?></h4>

			<?php if ( '' !== $group_desc ) : ?>
				<p class="kctm-pz-group-desc"><?php echo esc_html( $group_desc ); ?></p>
			<?php endif; ?>

			<div class="kctm-pz-options">

				<?php foreach ( $group->options as $option ) :

					$option_slug     = isset( $option->slug )           ? $option->slug           : '';
					$option_title    = isset( $option->title )          ? $option->title          : '';
					$option_image    = isset( $option->image_url )      ? $option->image_url      : '';
					$price_modifier  = isset( $option->price_modifier ) ? floatval( $option->price_modifier ) : 0.00;
					$is_default      = isset( $option->is_default )     ? absint( $option->is_default )       : 0;
					$input_name      = 'kctm_personalization[' . esc_attr( $group_slug ) . ']';
				?>

					<label class="kctm-pz-option-card">
						<input
							type="radio"
							name="<?php echo esc_attr( $input_name ); ?>"
							value="<?php echo esc_attr( $option_slug ); ?>"
							data-price-modifier="<?php echo esc_attr( $price_modifier ); ?>"
							data-group="<?php echo esc_attr( $group_slug ); ?>"
							<?php checked( $is_default, 1 ); ?>
						>
						<div class="kctm-pz-option-inner">

							<?php if ( '' !== $option_image ) : ?>
								<img
									src="<?php echo esc_url( $option_image ); ?>"
									alt="<?php echo esc_attr( $option_title ); ?>"
									class="kctm-pz-option-img"
									loading="lazy"
								>
							<?php else : ?>
								<div class="kctm-pz-option-placeholder">
									<span class="dashicons dashicons-format-image"></span>
								</div>
							<?php endif; ?>

							<span class="kctm-pz-option-title"><?php echo esc_html( $option_title ); ?></span>

							<?php if ( $price_modifier > 0 ) : ?>
								<span class="kctm-pz-option-price">
									+<?php echo wp_kses_post( wc_price( $price_modifier ) ); ?>
								</span>
							<?php endif; ?>

						</div>
					</label>

				<?php endforeach; ?>

			</div><!-- .kctm-pz-options -->

			<?php if ( $is_monogram ) : ?>
				<div class="kctm-pz-monogram-text" id="kctm-monogram-text-wrap" style="display:none;">
					<label class="kctm-pz-monogram-label" for="kctm-monogram-text">
						<?php esc_html_e( 'Your Initials (max 4 characters):', 'kevincho-tailoring-manager' ); ?>
						<input
							type="text"
							id="kctm-monogram-text"
							name="kctm_monogram_text"
							class="kctm-input kctm-input-text"
							maxlength="4"
							placeholder="<?php esc_attr_e( 'e.g. KC', 'kevincho-tailoring-manager' ); ?>"
						>
					</label>
				</div>
			<?php endif; ?>

		</div><!-- .kctm-pz-group -->

	<?php endforeach; ?>

	<!-- ============================================================
	     Personalization Summary
	     ============================================================ -->
	<div class="kctm-pz-summary" id="kctm-pz-summary">

		<h4 class="kctm-pz-summary-heading"><?php esc_html_e( 'Personalization Summary', 'kevincho-tailoring-manager' ); ?></h4>

		<table class="kctm-pz-summary-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Category', 'kevincho-tailoring-manager' ); ?></th>
					<th><?php esc_html_e( 'Selection', 'kevincho-tailoring-manager' ); ?></th>
					<th><?php esc_html_e( 'Price', 'kevincho-tailoring-manager' ); ?></th>
				</tr>
			</thead>
			<tbody id="kctm-pz-summary-body">
				<!-- Populated dynamically by JavaScript -->
			</tbody>
			<tfoot>
				<tr class="kctm-pz-summary-total-row">
					<td colspan="2"><strong><?php esc_html_e( 'Total Personalization', 'kevincho-tailoring-manager' ); ?></strong></td>
					<td><strong id="kctm-pz-summary-total"><?php echo wp_kses_post( wc_price( 0 ) ); ?></strong></td>
				</tr>
			</tfoot>
		</table>

	</div><!-- .kctm-pz-summary -->

	<!-- Hidden input for total personalization price modifier (calculated by JS). -->
	<input type="hidden" name="kctm_personalization_price_total" id="kctm-personalization-price-total" value="0">

</div><!-- .kctm-personalizer -->
