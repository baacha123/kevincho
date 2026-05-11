<?php
/**
 * KCTM Personalization Options
 *
 * Admin-facing CRUD for garment personalization groups and their options.
 * Groups represent customization categories (e.g., Collar Style, Sleeve Style)
 * and options are the individual choices within each group.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Personalization_Options
 *
 * Static CRUD interface for the kctm_personalization_groups and
 * kctm_personalization_options database tables.
 */
class KCTM_Personalization_Options {

	/* ================================================================
	 * Groups
	 * ============================================================= */

	/**
	 * Retrieve all personalization groups.
	 *
	 * @since  1.0.0
	 * @param  bool $active_only If true, only return groups where is_active = 1.
	 * @return array Array of group row objects.
	 */
	public static function get_groups( $active_only = true ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_personalization_groups';
		$sql   = "SELECT * FROM {$table}";

		if ( $active_only ) {
			$sql .= ' WHERE is_active = 1';
		}

		$sql .= ' ORDER BY sort_order ASC, id ASC';

		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieve a single personalization group by ID.
	 *
	 * @since  1.0.0
	 * @param  int $id Group ID.
	 * @return object|null Group row object or null.
	 */
	public static function get_group( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_personalization_groups';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) )
		);
	}

	/**
	 * Insert or update a personalization group.
	 *
	 * If `$data['id']` is set, the existing row is updated; otherwise a new
	 * row is inserted. The slug is auto-generated from the title when not
	 * provided.
	 *
	 * @since  1.0.0
	 * @param  array $data {
	 *     Group data.
	 *     @type int    $id          Optional. Existing group ID (triggers update).
	 *     @type string $title       Group title.
	 *     @type string $slug        Optional. URL-safe slug (auto-generated if empty).
	 *     @type string $description Optional. Group description.
	 *     @type int    $sort_order  Optional. Display order. Default 0.
	 *     @type int    $is_active   Optional. 1 = active, 0 = inactive. Default 1.
	 *     @type string $applies_to  Optional. Product type scope. Default 'all'.
	 * }
	 * @return int|false The group ID on success, false on failure.
	 */
	public static function save_group( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_personalization_groups';

		$title       = isset( $data['title'] )       ? sanitize_text_field( $data['title'] )       : '';
		$slug        = isset( $data['slug'] ) && '' !== $data['slug']
		               ? sanitize_title( $data['slug'] )
		               : sanitize_title( $title );
		$description = isset( $data['description'] )  ? sanitize_textarea_field( $data['description'] ) : '';
		$sort_order  = isset( $data['sort_order'] )    ? absint( $data['sort_order'] )   : 0;
		$is_active   = isset( $data['is_active'] )     ? absint( $data['is_active'] )    : 1;
		$applies_to  = isset( $data['applies_to'] )    ? sanitize_text_field( $data['applies_to'] ) : 'all';

		$row = array(
			'title'       => $title,
			'slug'        => $slug,
			'description' => $description,
			'sort_order'  => $sort_order,
			'is_active'   => $is_active,
			'applies_to'  => $applies_to,
		);

		$formats = array( '%s', '%s', '%s', '%d', '%d', '%s' );

		// Update existing group.
		if ( ! empty( $data['id'] ) ) {
			$id = absint( $data['id'] );

			$wpdb->update(
				$table,
				$row,
				array( 'id' => $id ),
				$formats,
				array( '%d' )
			);

			return $id;
		}

		// Insert new group.
		$wpdb->insert( $table, $row, $formats );

		return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a personalization group and all its child options.
	 *
	 * @since  1.0.0
	 * @param  int $id Group ID.
	 * @return bool True on success.
	 */
	public static function delete_group( $id ) {
		global $wpdb;

		$id = absint( $id );

		$groups_table  = $wpdb->prefix . 'kctm_personalization_groups';
		$options_table = $wpdb->prefix . 'kctm_personalization_options';

		// Delete child options first.
		$wpdb->delete( $options_table, array( 'group_id' => $id ), array( '%d' ) );

		// Delete the group.
		$wpdb->delete( $groups_table, array( 'id' => $id ), array( '%d' ) );

		return true;
	}

	/* ================================================================
	 * Options
	 * ============================================================= */

	/**
	 * Retrieve all options for a given group.
	 *
	 * @since  1.0.0
	 * @param  int  $group_id    The parent group ID.
	 * @param  bool $active_only If true, only return options where is_active = 1.
	 * @return array Array of option row objects.
	 */
	public static function get_options( $group_id, $active_only = true ) {
		global $wpdb;

		$table    = $wpdb->prefix . 'kctm_personalization_options';
		$group_id = absint( $group_id );

		$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE group_id = %d", $group_id );

		if ( $active_only ) {
			$sql .= ' AND is_active = 1';
		}

		$sql .= ' ORDER BY sort_order ASC, id ASC';

		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieve a single personalization option by ID.
	 *
	 * Named `get_option_item` to avoid collision with the WordPress
	 * core `get_option()` function.
	 *
	 * @since  1.0.0
	 * @param  int $id Option ID.
	 * @return object|null Option row object or null.
	 */
	public static function get_option_item( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_personalization_options';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) )
		);
	}

	/**
	 * Insert or update a personalization option.
	 *
	 * If `$data['id']` is set, the existing row is updated; otherwise a new
	 * row is inserted.
	 *
	 * @since  1.0.0
	 * @param  array $data {
	 *     Option data.
	 *     @type int    $id             Optional. Existing option ID (triggers update).
	 *     @type int    $group_id       Parent group ID.
	 *     @type string $title          Option title.
	 *     @type string $slug           Optional. URL-safe slug (auto-generated if empty).
	 *     @type string $description    Optional. Option description.
	 *     @type string $image_url      Optional. Preview image URL.
	 *     @type float  $price_modifier Optional. Price adjustment. Default 0.00.
	 *     @type int    $is_default     Optional. 1 = default selection. Default 0.
	 *     @type int    $sort_order     Optional. Display order. Default 0.
	 *     @type int    $is_active      Optional. 1 = active, 0 = inactive. Default 1.
	 * }
	 * @return int|false The option ID on success, false on failure.
	 */
	public static function save_option( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_personalization_options';

		$title = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$slug  = isset( $data['slug'] ) && '' !== $data['slug']
		         ? sanitize_title( $data['slug'] )
		         : sanitize_title( $title );

		$row = array(
			'group_id'       => isset( $data['group_id'] )       ? absint( $data['group_id'] )                   : 0,
			'title'          => $title,
			'slug'           => $slug,
			'description'    => isset( $data['description'] )    ? sanitize_textarea_field( $data['description'] ) : '',
			'image_url'      => isset( $data['image_url'] )      ? esc_url_raw( $data['image_url'] )              : '',
			'price_modifier' => isset( $data['price_modifier'] ) ? floatval( $data['price_modifier'] )            : 0.00,
			'is_default'     => isset( $data['is_default'] )     ? absint( $data['is_default'] )                  : 0,
			'sort_order'     => isset( $data['sort_order'] )     ? absint( $data['sort_order'] )                  : 0,
			'is_active'      => isset( $data['is_active'] )      ? absint( $data['is_active'] )                   : 1,
		);

		$formats = array( '%d', '%s', '%s', '%s', '%s', '%f', '%d', '%d', '%d' );

		// Update existing option.
		if ( ! empty( $data['id'] ) ) {
			$id = absint( $data['id'] );

			$wpdb->update(
				$table,
				$row,
				array( 'id' => $id ),
				$formats,
				array( '%d' )
			);

			return $id;
		}

		// Insert new option.
		$wpdb->insert( $table, $row, $formats );

		return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a single personalization option.
	 *
	 * @since  1.0.0
	 * @param  int $id Option ID.
	 * @return bool True on success.
	 */
	public static function delete_option( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_personalization_options';

		$wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );

		return true;
	}

	/* ================================================================
	 * Combined Queries
	 * ============================================================= */

	/**
	 * Retrieve all groups with their nested options.
	 *
	 * This is the primary method used by the frontend customizer. Each group
	 * object will have an additional `options` property containing an array
	 * of its child option objects.
	 *
	 * @since  1.0.0
	 * @param  bool $active_only If true, only return active groups and options.
	 * @return array Array of group objects, each with an `options` property.
	 */
	public static function get_groups_with_options( $active_only = true ) {
		$groups = self::get_groups( $active_only );

		foreach ( $groups as $group ) {
			$group->options = self::get_options( $group->id, $active_only );
		}

		return $groups;
	}

	/* ================================================================
	 * Product-Level Personalization Flag
	 * ============================================================= */

	/**
	 * Check whether a WooCommerce product has personalization enabled.
	 *
	 * @since  1.0.0
	 * @param  int $product_id WooCommerce product ID.
	 * @return bool True if personalization is enabled for this product.
	 */
	public static function is_product_personalizable( $product_id ) {
		return 'yes' === get_post_meta( absint( $product_id ), '_kctm_personalizable', true );
	}

	/**
	 * Enable or disable personalization for a WooCommerce product.
	 *
	 * @since  1.0.0
	 * @param  int  $product_id WooCommerce product ID.
	 * @param  bool $enabled    True to enable, false to disable.
	 * @return void
	 */
	public static function set_product_personalizable( $product_id, $enabled = true ) {
		$product_id = absint( $product_id );

		if ( $enabled ) {
			update_post_meta( $product_id, '_kctm_personalizable', 'yes' );
		} else {
			delete_post_meta( $product_id, '_kctm_personalizable' );
		}
	}
}
