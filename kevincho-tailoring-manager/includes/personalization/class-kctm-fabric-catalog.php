<?php
/**
 * KCTM Fabric Catalog
 *
 * Admin-facing CRUD for the fabric catalog. Fabrics represent the
 * available material choices a customer can select when ordering a
 * custom garment (e.g., Navy Wool, Charcoal Herringbone).
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KCTM_Fabric_Catalog
 *
 * Static CRUD interface for the kctm_fabrics database table.
 */
class KCTM_Fabric_Catalog {

	/**
	 * Retrieve fabrics from the catalog.
	 *
	 * Supports optional filtering by search term, pattern type, and
	 * active status. Results are ordered by sort_order then id.
	 *
	 * @since  1.0.0
	 * @param  array $filters {
	 *     Optional. Filters to narrow the result set.
	 *     @type string $search       Partial match against the fabric name.
	 *     @type string $pattern_type Exact match (solid, striped, checkered, herringbone, plaid).
	 *     @type bool   $active_only  If true, only return rows where is_active = 1. Default true.
	 * }
	 * @return array Array of fabric row objects.
	 */
	public static function get_fabrics( $filters = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_fabrics';

		$where   = array();
		$values  = array();

		// Active-only filter (default true).
		$active_only = isset( $filters['active_only'] ) ? (bool) $filters['active_only'] : true;

		if ( $active_only ) {
			$where[] = 'is_active = 1';
		}

		// Search filter — LIKE match on name.
		if ( ! empty( $filters['search'] ) ) {
			$where[] = 'name LIKE %s';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
		}

		// Pattern type filter — exact match.
		if ( ! empty( $filters['pattern_type'] ) ) {
			$where[] = 'pattern_type = %s';
			$values[] = sanitize_text_field( $filters['pattern_type'] );
		}

		$sql = "SELECT * FROM {$table}";

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql .= ' ORDER BY sort_order ASC, id ASC';

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieve a single fabric by ID.
	 *
	 * @since  1.0.0
	 * @param  int $id Fabric ID.
	 * @return object|null Fabric row object or null.
	 */
	public static function get_fabric( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_fabrics';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) )
		);
	}

	/**
	 * Insert or update a fabric.
	 *
	 * If `$data['id']` is set, the existing row is updated; otherwise a new
	 * row is inserted. The slug is auto-generated from the name when not
	 * provided.
	 *
	 * @since  1.0.0
	 * @param  array $data {
	 *     Fabric data.
	 *     @type int    $id             Optional. Existing fabric ID (triggers update).
	 *     @type string $name           Fabric name.
	 *     @type string $slug           Optional. URL-safe slug (auto-generated if empty).
	 *     @type string $color_hex      Optional. Hex color code. Default ''.
	 *     @type string $pattern_type   Optional. Pattern type (solid, striped, checkered, herringbone, plaid). Default 'solid'.
	 *     @type string $swatch_url     Optional. Swatch image URL. Default ''.
	 *     @type float  $price_modifier Optional. Price adjustment. Default 0.00.
	 *     @type int    $is_active      Optional. 1 = active, 0 = inactive. Default 1.
	 *     @type int    $sort_order     Optional. Display order. Default 0.
	 * }
	 * @return int|false The fabric ID on success, false on failure.
	 */
	public static function save_fabric( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_fabrics';

		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug = isset( $data['slug'] ) && '' !== $data['slug']
		        ? sanitize_title( $data['slug'] )
		        : sanitize_title( $name );

		$row = array(
			'name'           => $name,
			'slug'           => $slug,
			'color_hex'      => isset( $data['color_hex'] )      ? sanitize_hex_color_no_hash( $data['color_hex'] ) : '',
			'pattern_type'   => isset( $data['pattern_type'] )   ? sanitize_text_field( $data['pattern_type'] )     : 'solid',
			'swatch_url'     => isset( $data['swatch_url'] )     ? esc_url_raw( $data['swatch_url'] )               : '',
			'price_modifier' => isset( $data['price_modifier'] ) ? floatval( $data['price_modifier'] )              : 0.00,
			'is_active'      => isset( $data['is_active'] )      ? absint( $data['is_active'] )                     : 1,
			'sort_order'     => isset( $data['sort_order'] )     ? absint( $data['sort_order'] )                    : 0,
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%d' );

		// Update existing fabric.
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

		// Insert new fabric.
		$wpdb->insert( $table, $row, $formats );

		return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a fabric by ID.
	 *
	 * @since  1.0.0
	 * @param  int $id Fabric ID.
	 * @return bool True on success.
	 */
	public static function delete_fabric( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'kctm_fabrics';

		$wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );

		return true;
	}
}
