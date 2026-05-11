<?php
/**
 * Suit Configurator — Hockerty-style full-page garment customizer.
 *
 * Rendered by the [kctm_suit_configurator] shortcode. Displays a split-screen
 * configurator with fabric selection, style options, and a live SVG suit preview.
 *
 * Variables expected:
 *   $fabrics         — array of fabric row objects from KCTM_Fabric_Catalog
 *   $style_groups    — array of personalization group objects (collar, sleeve, fit, pocket)
 *   $accent_groups   — array of personalization group objects (buttons, embroidery, lining, monogram)
 *   $base_price      — float, base suit price
 *   $currency_symbol — string, WooCommerce currency symbol
 *   $product_id      — int, the WooCommerce product ID
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="kctm-configurator-wrap" id="kctm-configurator">

	<!-- ============================================================
	     STEP TABS
	     ============================================================ -->
	<div class="kctm-configurator-header">
		<div class="kctm-step-tabs">
			<button class="kctm-step-tab active" data-step="fabric">
				<span class="kctm-step-num">1</span>
				<?php esc_html_e( 'FABRIC', 'kevincho-tailoring-manager' ); ?>
			</button>
			<span class="kctm-step-divider"></span>
			<button class="kctm-step-tab" data-step="style">
				<span class="kctm-step-num">2</span>
				<?php esc_html_e( 'STYLE', 'kevincho-tailoring-manager' ); ?>
			</button>
			<span class="kctm-step-divider"></span>
			<button class="kctm-step-tab" data-step="accents">
				<span class="kctm-step-num">3</span>
				<?php esc_html_e( 'ACCENTS', 'kevincho-tailoring-manager' ); ?>
			</button>
		</div>
	</div>

	<!-- ============================================================
	     MAIN BODY: SIDEBAR | PREVIEW | SUMMARY
	     ============================================================ -->
	<div class="kctm-configurator-body">

		<!-- ── SIDEBAR (options panel) ──────────────────────── -->
		<div class="kctm-configurator-sidebar">

			<!-- STEP 1: FABRIC -->
			<div class="kctm-step-panel active" data-panel="fabric">
				<h3 class="kctm-panel-title"><?php esc_html_e( 'Choose Your Fabric', 'kevincho-tailoring-manager' ); ?></h3>

				<!-- Search & Filter -->
				<div class="kctm-fabric-controls">
					<input type="text" class="kctm-fabric-search" id="kctm-fabric-search"
					       placeholder="<?php esc_attr_e( 'Search fabrics...', 'kevincho-tailoring-manager' ); ?>">
					<div class="kctm-fabric-filters">
						<button class="kctm-filter-btn active" data-filter="all"><?php esc_html_e( 'All', 'kevincho-tailoring-manager' ); ?></button>
						<button class="kctm-filter-btn" data-filter="solid"><?php esc_html_e( 'Solid', 'kevincho-tailoring-manager' ); ?></button>
						<button class="kctm-filter-btn" data-filter="striped"><?php esc_html_e( 'Striped', 'kevincho-tailoring-manager' ); ?></button>
						<button class="kctm-filter-btn" data-filter="checkered"><?php esc_html_e( 'Checkered', 'kevincho-tailoring-manager' ); ?></button>
						<button class="kctm-filter-btn" data-filter="herringbone"><?php esc_html_e( 'Herringbone', 'kevincho-tailoring-manager' ); ?></button>
						<button class="kctm-filter-btn" data-filter="plaid"><?php esc_html_e( 'Plaid', 'kevincho-tailoring-manager' ); ?></button>
					</div>
				</div>

				<!-- Fabric Grid -->
				<div class="kctm-fabric-grid" id="kctm-fabric-grid">
					<?php if ( ! empty( $fabrics ) ) : ?>
						<?php foreach ( $fabrics as $i => $fabric ) : ?>
							<div class="kctm-fabric-swatch<?php echo 0 === $i ? ' selected' : ''; ?>"
							     data-fabric-id="<?php echo esc_attr( $fabric->id ); ?>"
							     data-color="<?php echo esc_attr( $fabric->color_hex ); ?>"
							     data-pattern="<?php echo esc_attr( $fabric->pattern_type ); ?>"
							     data-name="<?php echo esc_attr( $fabric->name ); ?>"
							     data-price="<?php echo esc_attr( $fabric->price_modifier ); ?>"
							     title="<?php echo esc_attr( $fabric->name ); ?>">
								<?php if ( ! empty( $fabric->swatch_url ) ) : ?>
									<img src="<?php echo esc_url( $fabric->swatch_url ); ?>" alt="<?php echo esc_attr( $fabric->name ); ?>">
								<?php else : ?>
									<span class="kctm-swatch-color" style="background-color: <?php echo esc_attr( $fabric->color_hex ); ?>;"></span>
								<?php endif; ?>
								<span class="kctm-swatch-check"></span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="kctm-no-fabrics"><?php esc_html_e( 'No fabrics available.', 'kevincho-tailoring-manager' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="kctm-fabric-info" id="kctm-fabric-info">
					<span class="kctm-fabric-selected-name">
						<?php echo ! empty( $fabrics ) ? esc_html( $fabrics[0]->name ) : ''; ?>
					</span>
				</div>

				<div class="kctm-step-nav">
					<button class="kctm-btn kctm-btn-next" data-goto="style">
						<?php esc_html_e( 'Next: Style', 'kevincho-tailoring-manager' ); ?> &rarr;
					</button>
				</div>
			</div>

			<!-- STEP 2: STYLE -->
			<div class="kctm-step-panel" data-panel="style">
				<h3 class="kctm-panel-title"><?php esc_html_e( 'Customize Style', 'kevincho-tailoring-manager' ); ?></h3>

				<?php if ( ! empty( $style_groups ) ) : ?>
					<?php foreach ( $style_groups as $group ) : ?>
						<?php if ( empty( $group->options ) ) continue; ?>
						<div class="kctm-option-group" data-group="<?php echo esc_attr( $group->slug ); ?>">
							<h4 class="kctm-option-group-title"><?php echo esc_html( $group->title ); ?></h4>
							<div class="kctm-option-cards">
								<?php foreach ( $group->options as $option ) : ?>
									<label class="kctm-option-card">
										<input type="radio"
										       name="kctm_personalization[<?php echo esc_attr( $group->slug ); ?>]"
										       value="<?php echo esc_attr( $option->slug ); ?>"
										       data-group="<?php echo esc_attr( $group->slug ); ?>"
										       data-price="<?php echo esc_attr( floatval( $option->price_modifier ) ); ?>"
										       data-title="<?php echo esc_attr( $option->title ); ?>"
										       <?php checked( absint( $option->is_default ), 1 ); ?>>
										<div class="kctm-card-inner">
											<?php if ( ! empty( $option->image_url ) ) : ?>
												<img src="<?php echo esc_url( $option->image_url ); ?>" alt="<?php echo esc_attr( $option->title ); ?>">
											<?php else : ?>
												<div class="kctm-card-placeholder"></div>
											<?php endif; ?>
											<span class="kctm-card-title"><?php echo esc_html( $option->title ); ?></span>
											<?php if ( floatval( $option->price_modifier ) > 0 ) : ?>
												<span class="kctm-card-price">+<?php echo wp_kses_post( wc_price( $option->price_modifier ) ); ?></span>
											<?php endif; ?>
										</div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<div class="kctm-step-nav">
					<button class="kctm-btn kctm-btn-prev" data-goto="fabric">
						&larr; <?php esc_html_e( 'Back', 'kevincho-tailoring-manager' ); ?>
					</button>
					<button class="kctm-btn kctm-btn-next" data-goto="accents">
						<?php esc_html_e( 'Next: Accents', 'kevincho-tailoring-manager' ); ?> &rarr;
					</button>
				</div>
			</div>

			<!-- STEP 3: ACCENTS -->
			<div class="kctm-step-panel" data-panel="accents">
				<h3 class="kctm-panel-title"><?php esc_html_e( 'Finishing Touches', 'kevincho-tailoring-manager' ); ?></h3>

				<?php if ( ! empty( $accent_groups ) ) : ?>
					<?php foreach ( $accent_groups as $group ) : ?>
						<?php if ( empty( $group->options ) ) continue; ?>
						<?php $is_monogram = ( 'monogram' === $group->slug ); ?>
						<div class="kctm-option-group" data-group="<?php echo esc_attr( $group->slug ); ?>">
							<h4 class="kctm-option-group-title"><?php echo esc_html( $group->title ); ?></h4>
							<div class="kctm-option-cards">
								<?php foreach ( $group->options as $option ) : ?>
									<label class="kctm-option-card">
										<input type="radio"
										       name="kctm_personalization[<?php echo esc_attr( $group->slug ); ?>]"
										       value="<?php echo esc_attr( $option->slug ); ?>"
										       data-group="<?php echo esc_attr( $group->slug ); ?>"
										       data-price="<?php echo esc_attr( floatval( $option->price_modifier ) ); ?>"
										       data-title="<?php echo esc_attr( $option->title ); ?>"
										       <?php checked( absint( $option->is_default ), 1 ); ?>>
										<div class="kctm-card-inner">
											<?php if ( ! empty( $option->image_url ) ) : ?>
												<img src="<?php echo esc_url( $option->image_url ); ?>" alt="<?php echo esc_attr( $option->title ); ?>">
											<?php else : ?>
												<div class="kctm-card-placeholder"></div>
											<?php endif; ?>
											<span class="kctm-card-title"><?php echo esc_html( $option->title ); ?></span>
											<?php if ( floatval( $option->price_modifier ) > 0 ) : ?>
												<span class="kctm-card-price">+<?php echo wp_kses_post( wc_price( $option->price_modifier ) ); ?></span>
											<?php endif; ?>
										</div>
									</label>
								<?php endforeach; ?>
							</div>
							<?php if ( $is_monogram ) : ?>
								<div class="kctm-monogram-input" id="kctm-monogram-wrap" style="display:none;">
									<label for="kctm-monogram-text"><?php esc_html_e( 'Your Initials (max 4 characters):', 'kevincho-tailoring-manager' ); ?></label>
									<input type="text" id="kctm-monogram-text" name="kctm_monogram_text" maxlength="4"
									       placeholder="<?php esc_attr_e( 'e.g. KC', 'kevincho-tailoring-manager' ); ?>">
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<div class="kctm-step-nav">
					<button class="kctm-btn kctm-btn-prev" data-goto="style">
						&larr; <?php esc_html_e( 'Back', 'kevincho-tailoring-manager' ); ?>
					</button>
				</div>
			</div>

		</div><!-- .kctm-configurator-sidebar -->

		<!-- ── PREVIEW (center) ────────────────────────────── -->
		<div class="kctm-configurator-preview">
			<div class="kctm-preview-inner" id="kctm-suit-preview">
				<!-- Jacket -->
				<div class="kctm-suit-composite kctm-suit-jacket">
					<div class="kctm-suit-fabric-layer" id="kctm-suit-fabric"
					     style="background-color: <?php echo ! empty( $fabrics ) ? esc_attr( $fabrics[0]->color_hex ) : '#1b2a4a'; ?>;"></div>
					<img class="kctm-suit-shading-layer"
					     src="<?php echo esc_url( KCTM_PLUGIN_URL . 'assets/images/suit-shading.png' ); ?>"
					     alt="" aria-hidden="true">
				</div>
				<!-- Pants -->
				<div class="kctm-suit-composite kctm-suit-pants">
					<div class="kctm-suit-fabric-layer kctm-pants-fabric" id="kctm-pants-fabric"
					     style="background-color: <?php echo ! empty( $fabrics ) ? esc_attr( $fabrics[0]->color_hex ) : '#1b2a4a'; ?>;"></div>
					<img class="kctm-suit-shading-layer"
					     src="<?php echo esc_url( KCTM_PLUGIN_URL . 'assets/images/pants-shading.png' ); ?>"
					     alt="" aria-hidden="true">
				</div>
			</div>
		</div>

		<!-- ── SUMMARY (right rail) ────────────────────────── -->
		<div class="kctm-configurator-summary">
			<div class="kctm-summary-inner">
				<h3 class="kctm-summary-title"><?php esc_html_e( 'Your Custom Suit', 'kevincho-tailoring-manager' ); ?></h3>

				<div class="kctm-summary-items" id="kctm-summary-items">
					<div class="kctm-summary-row">
						<span class="kctm-summary-label"><?php esc_html_e( 'Fabric', 'kevincho-tailoring-manager' ); ?></span>
						<span class="kctm-summary-value" id="kctm-sum-fabric">
							<?php echo ! empty( $fabrics ) ? esc_html( $fabrics[0]->name ) : '&mdash;'; ?>
						</span>
					</div>
				</div>

				<div class="kctm-summary-divider"></div>

				<div class="kctm-summary-price">
					<div class="kctm-price-row">
						<span><?php esc_html_e( 'Base Price', 'kevincho-tailoring-manager' ); ?></span>
						<span id="kctm-base-price"><?php echo wp_kses_post( wc_price( $base_price ) ); ?></span>
					</div>
					<div class="kctm-price-row kctm-price-extras" id="kctm-price-extras" style="display:none;">
						<span><?php esc_html_e( 'Customization', 'kevincho-tailoring-manager' ); ?></span>
						<span id="kctm-extras-amount">+<?php echo wp_kses_post( wc_price( 0 ) ); ?></span>
					</div>
					<div class="kctm-price-row kctm-price-total">
						<span><?php esc_html_e( 'Total', 'kevincho-tailoring-manager' ); ?></span>
						<span id="kctm-total-price"><?php echo wp_kses_post( wc_price( $base_price ) ); ?></span>
					</div>
				</div>

				<button class="kctm-add-to-cart-btn" id="kctm-add-to-cart" type="button">
					<?php esc_html_e( 'ADD TO CART', 'kevincho-tailoring-manager' ); ?>
				</button>

				<div class="kctm-cart-message" id="kctm-cart-message"></div>
			</div>
		</div>

	</div><!-- .kctm-configurator-body -->

	<!-- Hidden data for JS -->
	<input type="hidden" id="kctm-product-id" value="<?php echo esc_attr( $product_id ); ?>">
	<input type="hidden" id="kctm-base-price-raw" value="<?php echo esc_attr( $base_price ); ?>">

</div><!-- .kctm-configurator-wrap -->
