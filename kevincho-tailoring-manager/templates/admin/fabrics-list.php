<?php
/**
 * Admin Fabrics List Template
 *
 * Displays the Add/Edit Fabric form and a table listing all fabrics
 * with color swatches, pattern types, and inline actions.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fabrics = KCTM_Fabric_Catalog::get_fabrics( array( 'active_only' => false ) );

// Check for edit context.
$editing_fabric_id = isset( $_GET['edit_fabric'] ) ? absint( $_GET['edit_fabric'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$editing_fabric    = null;

if ( $editing_fabric_id && ! empty( $fabrics ) ) {
	foreach ( $fabrics as $f ) {
		if ( (int) $f->id === $editing_fabric_id ) {
			$editing_fabric = $f;
			break;
		}
	}
}

$success = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$deleted = isset( $_GET['deleted'] ) && '1' === $_GET['deleted']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$pattern_types = array(
	'solid'       => __( 'Solid', 'kevincho-tailoring-manager' ),
	'striped'     => __( 'Striped', 'kevincho-tailoring-manager' ),
	'checkered'   => __( 'Checkered', 'kevincho-tailoring-manager' ),
	'herringbone' => __( 'Herringbone', 'kevincho-tailoring-manager' ),
	'plaid'       => __( 'Plaid', 'kevincho-tailoring-manager' ),
);
?>
<div class="wrap">
	<h1 style="color:#402417;">
		<?php esc_html_e( 'Fabric Catalog', 'kevincho-tailoring-manager' ); ?>
	</h1>

	<?php if ( $success ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Fabric saved successfully.', 'kevincho-tailoring-manager' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $deleted ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Fabric deleted successfully.', 'kevincho-tailoring-manager' ); ?></p></div>
	<?php endif; ?>

	<!-- ── Add / Edit Fabric Form ──────────────────── -->
	<div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-bottom:20px;border-top:3px solid #c9a96e;">
		<h2 style="margin-top:0;color:#402417;">
			<?php echo $editing_fabric ? esc_html__( 'Edit Fabric', 'kevincho-tailoring-manager' ) : esc_html__( 'Add New Fabric', 'kevincho-tailoring-manager' ); ?>
		</h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'kctm_fabric_nonce', 'kctm_nonce' ); ?>
			<input type="hidden" name="action" value="kctm_save_fabric">
			<?php if ( $editing_fabric ) : ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $editing_fabric->id ); ?>">
			<?php endif; ?>

			<table class="form-table">
				<tr>
					<th><label for="fabric_name"><?php esc_html_e( 'Name', 'kevincho-tailoring-manager' ); ?></label></th>
					<td><input type="text" name="name" id="fabric_name" class="regular-text" value="<?php echo $editing_fabric ? esc_attr( $editing_fabric->name ) : ''; ?>" required></td>
				</tr>
				<tr>
					<th><label for="fabric_slug"><?php esc_html_e( 'Slug', 'kevincho-tailoring-manager' ); ?></label></th>
					<td>
						<input type="text" name="slug" id="fabric_slug" class="regular-text" value="<?php echo $editing_fabric ? esc_attr( $editing_fabric->slug ) : ''; ?>">
						<p class="description"><?php esc_html_e( 'Leave empty to auto-generate from name.', 'kevincho-tailoring-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="fabric_color_hex"><?php esc_html_e( 'Color', 'kevincho-tailoring-manager' ); ?></label></th>
					<td>
						<input type="color" name="color_hex" id="fabric_color_hex" value="<?php echo $editing_fabric && ! empty( $editing_fabric->color_hex ) ? esc_attr( $editing_fabric->color_hex ) : '#000000'; ?>" style="width:60px;height:36px;padding:2px;cursor:pointer;">
					</td>
				</tr>
				<tr>
					<th><label for="fabric_pattern_type"><?php esc_html_e( 'Pattern Type', 'kevincho-tailoring-manager' ); ?></label></th>
					<td>
						<select name="pattern_type" id="fabric_pattern_type">
							<?php foreach ( $pattern_types as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $editing_fabric ? $editing_fabric->pattern_type : 'solid', $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="fabric_swatch_url"><?php esc_html_e( 'Swatch Image', 'kevincho-tailoring-manager' ); ?></label></th>
					<td>
						<input type="text" name="swatch_url" id="fabric_swatch_url" class="regular-text" value="<?php echo $editing_fabric ? esc_attr( $editing_fabric->swatch_url ) : ''; ?>">
						<button type="button" class="button kctm-upload-image"><?php esc_html_e( 'Upload', 'kevincho-tailoring-manager' ); ?></button>
						<?php if ( $editing_fabric && ! empty( $editing_fabric->swatch_url ) ) : ?>
							<br><img src="<?php echo esc_url( $editing_fabric->swatch_url ); ?>" alt="" style="max-width:100px;max-height:100px;margin-top:10px;border:1px solid #ccd0d4;border-radius:4px;">
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label for="fabric_price_modifier"><?php esc_html_e( 'Price Modifier', 'kevincho-tailoring-manager' ); ?></label></th>
					<td><input type="number" name="price_modifier" id="fabric_price_modifier" class="small-text" step="0.01" value="<?php echo $editing_fabric ? esc_attr( $editing_fabric->price_modifier ) : '0'; ?>"></td>
				</tr>
				<tr>
					<th><label for="fabric_sort_order"><?php esc_html_e( 'Sort Order', 'kevincho-tailoring-manager' ); ?></label></th>
					<td><input type="number" name="sort_order" id="fabric_sort_order" class="small-text" value="<?php echo $editing_fabric ? esc_attr( $editing_fabric->sort_order ) : '0'; ?>" min="0"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_active" value="1" <?php checked( ! $editing_fabric || $editing_fabric->is_active ); ?>>
							<?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( $editing_fabric ? __( 'Update Fabric', 'kevincho-tailoring-manager' ) : __( 'Add Fabric', 'kevincho-tailoring-manager' ) ); ?>
			<?php if ( $editing_fabric ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-fabrics' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'kevincho-tailoring-manager' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<!-- ── Fabrics Table ───────────────────────────── -->
	<?php if ( empty( $fabrics ) ) : ?>
		<p><?php esc_html_e( 'No fabrics found. Add one above.', 'kevincho-tailoring-manager' ); ?></p>
	<?php else : ?>
		<div style="background:#fff;padding:15px 20px;border:1px solid #ccd0d4;border-radius:4px;">
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:40px;"><?php esc_html_e( 'Color', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Name', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Pattern', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Price Mod.', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Sort', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Active', 'kevincho-tailoring-manager' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'kevincho-tailoring-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $fabrics as $fabric ) : ?>
						<tr>
							<td>
								<div style="width:30px;height:30px;border-radius:4px;border:1px solid #ccd0d4;background-color:<?php echo esc_attr( ! empty( $fabric->color_hex ) ? $fabric->color_hex : '#cccccc' ); ?>;" title="<?php echo esc_attr( ! empty( $fabric->color_hex ) ? $fabric->color_hex : '' ); ?>"></div>
							</td>
							<td>
								<strong><?php echo esc_html( $fabric->name ); ?></strong>
								<br><small style="color:#666;"><?php echo esc_html( $fabric->slug ); ?></small>
							</td>
							<td><?php echo isset( $pattern_types[ $fabric->pattern_type ] ) ? esc_html( $pattern_types[ $fabric->pattern_type ] ) : esc_html( ucfirst( $fabric->pattern_type ) ); ?></td>
							<td><?php echo esc_html( number_format( (float) $fabric->price_modifier, 2 ) ); ?></td>
							<td><?php echo esc_html( $fabric->sort_order ); ?></td>
							<td>
								<?php if ( $fabric->is_active ) : ?>
									<span class="dashicons dashicons-yes" style="color:#00a32a;"></span>
								<?php else : ?>
									<span class="dashicons dashicons-no" style="color:#d63638;"></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=kctm-fabrics&edit_fabric=' . $fabric->id ) ); ?>"><?php esc_html_e( 'Edit', 'kevincho-tailoring-manager' ); ?></a>
								|
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kctm_delete_fabric&id=' . $fabric->id ), 'kctm_delete_fabric_' . $fabric->id ) ); ?>" class="delete" style="color:#d63638;" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this fabric?', 'kevincho-tailoring-manager' ) ); ?>');"><?php esc_html_e( 'Delete', 'kevincho-tailoring-manager' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<script>
jQuery(function($) {
	$('.kctm-upload-image').on('click', function(e) {
		e.preventDefault();
		var $input = $(this).prev('input');
		var frame = wp.media({
			title: '<?php echo esc_js( __( 'Select Image', 'kevincho-tailoring-manager' ) ); ?>',
			button: { text: '<?php echo esc_js( __( 'Use Image', 'kevincho-tailoring-manager' ) ); ?>' },
			multiple: false
		});
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.url);
		});
		frame.open();
	});
});
</script>
