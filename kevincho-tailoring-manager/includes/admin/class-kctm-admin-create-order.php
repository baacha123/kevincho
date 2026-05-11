<?php
/**
 * Admin In-Store Order Creation
 *
 * Provides the interface and processing logic for creating new
 * WooCommerce orders from the admin panel for in-store customers.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Admin_Create_Order
 *
 * Handles rendering the in-store order creation form and processing
 * the form submission to create a WooCommerce order.
 */
class KCTM_Admin_Create_Order {

	/**
	 * Render the in-store order creation page.
	 *
	 * Loads the admin template file for the order creation form.
	 *
	 * @return void
	 */
	public static function render() {
		$template = KCTM_PLUGIN_DIR . 'templates/admin/create-order.php';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			self::render_inline();
		}
	}

	/**
	 * Inline fallback render if the template file is not found.
	 *
	 * @return void
	 */
	private static function render_inline() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'New In-Store Order', 'kevincho-tailoring-manager' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="kctm-create-order-form">
				<?php wp_nonce_field( 'kctm_create_order_nonce', 'kctm_nonce' ); ?>
				<input type="hidden" name="action" value="kctm_create_instore_order">

				<table class="form-table">
					<!-- Customer Picker -->
					<tr>
						<th scope="row">
							<label for="kctm_customer_id"><?php esc_html_e( 'Customer', 'kevincho-tailoring-manager' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<select name="customer_id" id="kctm_customer_id" class="kctm-customer-search" style="width:100%;max-width:400px;" required>
								<option value=""><?php esc_html_e( 'Search for a customer...', 'kevincho-tailoring-manager' ); ?></option>
							</select>
							<div id="kctm-customer-details" style="margin-top:10px;"></div>
						</td>
					</tr>
				</table>

				<!-- WooCommerce Products -->
				<h2><?php esc_html_e( 'Products', 'kevincho-tailoring-manager' ); ?></h2>
				<table class="widefat" id="kctm-order-items">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'kevincho-tailoring-manager' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Qty', 'kevincho-tailoring-manager' ); ?></th>
							<th style="width:120px;"><?php esc_html_e( 'Price', 'kevincho-tailoring-manager' ); ?></th>
							<th style="width:50px;"></th>
						</tr>
					</thead>
					<tbody id="kctm-items-body">
						<tr class="kctm-item-row">
							<td>
								<select name="items[0][product_id]" class="kctm-product-search" style="width:100%;">
									<option value=""><?php esc_html_e( 'Search product...', 'kevincho-tailoring-manager' ); ?></option>
								</select>
							</td>
							<td><input type="number" name="items[0][quantity]" value="1" min="1" class="small-text"></td>
							<td><input type="text" name="items[0][price]" value="" class="small-text" placeholder="<?php esc_attr_e( 'Auto', 'kevincho-tailoring-manager' ); ?>"></td>
							<td><button type="button" class="button kctm-remove-row">&times;</button></td>
						</tr>
					</tbody>
				</table>
				<p>
					<button type="button" class="button" id="kctm-add-item"><?php esc_html_e( 'Add Product Row', 'kevincho-tailoring-manager' ); ?></button>
				</p>

				<!-- Custom Line Items -->
				<h2><?php esc_html_e( 'Custom Items', 'kevincho-tailoring-manager' ); ?></h2>
				<table class="widefat" id="kctm-custom-items">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'kevincho-tailoring-manager' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Qty', 'kevincho-tailoring-manager' ); ?></th>
							<th style="width:120px;"><?php esc_html_e( 'Price', 'kevincho-tailoring-manager' ); ?></th>
							<th style="width:50px;"></th>
						</tr>
					</thead>
					<tbody id="kctm-custom-items-body">
					</tbody>
				</table>
				<p>
					<button type="button" class="button" id="kctm-add-custom-item"><?php esc_html_e( 'Add Custom Item', 'kevincho-tailoring-manager' ); ?></button>
				</p>

				<?php submit_button( __( 'Create Order', 'kevincho-tailoring-manager' ), 'primary', 'kctm_submit_order' ); ?>
			</form>
		</div>

		<script>
		jQuery(function($) {
			var itemIndex  = 1;
			var customIndex = 0;

			$('#kctm-add-item').on('click', function() {
				var row = '<tr class="kctm-item-row">' +
					'<td><select name="items[' + itemIndex + '][product_id]" class="kctm-product-search" style="width:100%;"><option value=""><?php echo esc_js( __( 'Search product...', 'kevincho-tailoring-manager' ) ); ?></option></select></td>' +
					'<td><input type="number" name="items[' + itemIndex + '][quantity]" value="1" min="1" class="small-text"></td>' +
					'<td><input type="text" name="items[' + itemIndex + '][price]" value="" class="small-text" placeholder="<?php echo esc_js( __( 'Auto', 'kevincho-tailoring-manager' ) ); ?>"></td>' +
					'<td><button type="button" class="button kctm-remove-row">&times;</button></td>' +
					'</tr>';
				$('#kctm-items-body').append(row);
				$('#kctm-items-body .kctm-product-search:last').select2({
					ajax: {
						url: typeof kctm_order !== 'undefined' ? kctm_order.ajax_url : ajaxurl,
						dataType: 'json',
						delay: 300,
						data: function(params) {
							return { action: 'kctm_search_products', term: params.term, _ajax_nonce: typeof kctm_order !== 'undefined' ? kctm_order.nonce : '' };
						},
						processResults: function(data) { return { results: data.data || [] }; }
					},
					minimumInputLength: 2,
					placeholder: '<?php echo esc_js( __( 'Search product...', 'kevincho-tailoring-manager' ) ); ?>'
				});
				itemIndex++;
			});

			$('#kctm-add-custom-item').on('click', function() {
				var row = '<tr class="kctm-custom-item-row">' +
					'<td><input type="text" name="custom_items[' + customIndex + '][name]" class="regular-text" required></td>' +
					'<td><input type="number" name="custom_items[' + customIndex + '][quantity]" value="1" min="1" class="small-text"></td>' +
					'<td><input type="text" name="custom_items[' + customIndex + '][price]" value="" class="small-text" required></td>' +
					'<td><button type="button" class="button kctm-remove-row">&times;</button></td>' +
					'</tr>';
				$('#kctm-custom-items-body').append(row);
				customIndex++;
			});

			$(document).on('click', '.kctm-remove-row', function() {
				$(this).closest('tr').remove();
			});
		});
		</script>
		<?php
	}

	/**
	 * Process the in-store order creation form.
	 *
	 * Hooked to `admin_post_kctm_create_instore_order`.
	 *
	 * @return void
	 */
	public static function process() {
		// Verify nonce.
		if ( ! isset( $_POST['kctm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kctm_nonce'] ) ), 'kctm_create_order_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'kevincho-tailoring-manager' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to create orders.', 'kevincho-tailoring-manager' ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;

		if ( ! $customer_id ) {
			wp_die( esc_html__( 'Please select a customer.', 'kevincho-tailoring-manager' ) );
		}

		$customer = get_userdata( $customer_id );
		if ( ! $customer ) {
			wp_die( esc_html__( 'Customer not found.', 'kevincho-tailoring-manager' ) );
		}

		// Create the order.
		$order = wc_create_order();

		if ( is_wp_error( $order ) ) {
			wp_die( esc_html( $order->get_error_message() ) );
		}

		// Set customer.
		$order->set_customer_id( $customer_id );

		// Set billing details from customer user data.
		$order->set_billing_first_name( $customer->first_name );
		$order->set_billing_last_name( $customer->last_name );
		$order->set_billing_email( $customer->user_email );

		$billing_phone = get_user_meta( $customer_id, 'billing_phone', true );
		if ( empty( $billing_phone ) ) {
			$billing_phone = get_user_meta( $customer_id, '_kctm_phone', true );
		}
		$order->set_billing_phone( $billing_phone );

		$billing_fields = array( 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country' );
		foreach ( $billing_fields as $field ) {
			$value  = get_user_meta( $customer_id, $field, true );
			$setter = 'set_' . $field;
			if ( method_exists( $order, $setter ) && $value ) {
				$order->$setter( $value );
			}
		}

		// Add WooCommerce product line items.
		$items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? $_POST['items'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( $items as $item_data ) {
			$product_id = isset( $item_data['product_id'] ) ? absint( $item_data['product_id'] ) : 0;
			$quantity   = isset( $item_data['quantity'] )   ? max( 1, absint( $item_data['quantity'] ) ) : 1;
			$price      = isset( $item_data['price'] ) && '' !== $item_data['price'] ? floatval( $item_data['price'] ) : null;

			if ( ! $product_id ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$item_id = $order->add_product( $product, $quantity );

			// Override price if manually set.
			if ( null !== $price && $item_id ) {
				$order_item = $order->get_item( $item_id );
				if ( $order_item ) {
					$order_item->set_subtotal( $price * $quantity );
					$order_item->set_total( $price * $quantity );
					$order_item->save();
				}
			}
		}

		// Add custom line items.
		$custom_items = isset( $_POST['custom_items'] ) && is_array( $_POST['custom_items'] ) ? $_POST['custom_items'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( $custom_items as $custom_data ) {
			$name     = isset( $custom_data['name'] )     ? sanitize_text_field( wp_unslash( $custom_data['name'] ) ) : '';
			$quantity = isset( $custom_data['quantity'] )  ? max( 1, absint( $custom_data['quantity'] ) )              : 1;
			$price    = isset( $custom_data['price'] )     ? floatval( $custom_data['price'] )                         : 0;

			if ( empty( $name ) ) {
				continue;
			}

			$item = new WC_Order_Item_Product();
			$item->set_name( $name );
			$item->set_quantity( $quantity );
			$item->set_subtotal( $price * $quantity );
			$item->set_total( $price * $quantity );
			$order->add_item( $item );
		}

		// Calculate totals.
		$order->calculate_totals();

		// Set status to confirmed.
		$order->set_status( 'kctm-confirmed' );

		// Mark as in-store order.
		$order->update_meta_data( '_kctm_order_type', 'instore' );

		// Save measurement snapshot.
		$snapshot = KCTM_Measurement_Storage::get_measurement_snapshot( $customer_id );
		if ( ! empty( $snapshot ) ) {
			KCTM_Order_Meta::save_snapshot_to_order( $order, $snapshot );
		}

		// Save the order.
		$order->save();

		// Add order note.
		$admin_user = wp_get_current_user();
		$admin_name = $admin_user->display_name ? $admin_user->display_name : $admin_user->user_login;

		$order->add_order_note(
			sprintf(
				/* translators: %s: admin user name */
				__( 'In-store order created by %s', 'kevincho-tailoring-manager' ),
				$admin_name
			)
		);

		// Redirect to the order edit page.
		$order_edit_url = $order->get_edit_order_url();
		wp_safe_redirect( add_query_arg( 'kctm_created', '1', $order_edit_url ) );
		exit;
	}
}

/* ── Hook the form processor ─────────────────────────────── */
add_action( 'admin_post_kctm_create_instore_order', array( 'KCTM_Admin_Create_Order', 'process' ) );
