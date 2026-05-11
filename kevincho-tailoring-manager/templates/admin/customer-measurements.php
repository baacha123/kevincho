<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;
if ( ! $customer_id ) {
    echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'No customer specified.', 'kevincho-tailoring-manager' ) . '</p></div></div>';
    return;
}

$user = get_userdata( $customer_id );
if ( ! $user ) {
    echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Customer not found.', 'kevincho-tailoring-manager' ) . '</p></div></div>';
    return;
}

$measurements = KCTM_Measurement_Storage::get_measurements( $customer_id );
$profile_fields = KCTM_Measurement_Fields::get_profile_fields();
$gender = isset( $measurements['gender'] ) ? $measurements['gender'] : 'male';
$measurement_fields = KCTM_Measurement_Fields::get_fields_for_gender( $gender );
$customer_type = get_user_meta( $customer_id, '_kctm_customer_type', true );
?>
<div class="wrap kctm-admin-measurements">
    <h1>
        <?php printf( esc_html__( 'Measurements: %s %s', 'kevincho-tailoring-manager' ), esc_html( $user->first_name ), esc_html( $user->last_name ) ); ?>
        <?php if ( $customer_type === 'walkin' ) : ?>
            <span class="kctm-badge kctm-badge-walkin"><?php esc_html_e( 'Walk-in', 'kevincho-tailoring-manager' ); ?></span>
        <?php endif; ?>
    </h1>

    <p class="description">
        <?php echo esc_html( $user->user_email ); ?>
        <?php $phone = get_user_meta( $customer_id, '_kctm_phone', true ); if ( $phone ) echo '| ' . esc_html( $phone ); ?>
    </p>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Measurements saved successfully.', 'kevincho-tailoring-manager' ); ?></p></div>
    <?php endif; ?>

    <form method="post" id="kctm-admin-measurement-form" class="kctm-measurement-form">
        <input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer_id ); ?>">
        <?php wp_nonce_field( 'kctm_admin_save_measurements', 'kctm_measurements_nonce' ); ?>

        <!-- Profile Section -->
        <div class="kctm-section">
            <h2><?php esc_html_e( 'Profile', 'kevincho-tailoring-manager' ); ?></h2>
            <table class="form-table">
                <?php foreach ( $profile_fields as $field ) : ?>
                <tr>
                    <th><label for="kctm-<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                    <td>
                        <?php if ( $field['type'] === 'select' ) : ?>
                            <select name="measurements[<?php echo esc_attr( $field['key'] ); ?>]" id="kctm-<?php echo esc_attr( $field['key'] ); ?>" class="<?php echo $field['key'] === 'gender' ? 'kctm-gender-select' : ''; ?>">
                                <?php foreach ( $field['options'] as $val => $label ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '', $val ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="number" name="measurements[<?php echo esc_attr( $field['key'] ); ?>]" id="kctm-<?php echo esc_attr( $field['key'] ); ?>" value="<?php echo esc_attr( isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '' ); ?>" step="<?php echo esc_attr( isset( $field['step'] ) ? $field['step'] : '0.1' ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>">
                            <?php if ( ! empty( $field['unit'] ) ) : ?>
                                <span class="description"><?php echo esc_html( $field['unit'] ); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Body Measurements Section -->
        <?php
        $groups = array(
            'upper_body' => __( 'Upper Body', 'kevincho-tailoring-manager' ),
            'core'       => __( 'Core', 'kevincho-tailoring-manager' ),
            'lower_body' => __( 'Lower Body', 'kevincho-tailoring-manager' ),
        );
        $all_measurement_fields = KCTM_Measurement_Fields::get_measurement_fields();
        foreach ( $groups as $group_key => $group_label ) :
            $group_fields = array_filter( $all_measurement_fields, function( $f ) use ( $group_key ) {
                return $f['group'] === $group_key;
            });
            if ( empty( $group_fields ) ) continue;
        ?>
        <div class="kctm-section">
            <h2><?php echo esc_html( $group_label ); ?></h2>
            <table class="form-table">
                <?php foreach ( $group_fields as $field ) :
                    $gender_attr = '';
                    if ( ! in_array( 'male', $field['gender'] ) || ! in_array( 'female', $field['gender'] ) ) {
                        $gender_attr = ' data-gender="' . esc_attr( implode( ',', $field['gender'] ) ) . '"';
                    }
                ?>
                <tr<?php echo $gender_attr; ?>>
                    <th><label for="kctm-<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                    <td>
                        <input type="number" name="measurements[<?php echo esc_attr( $field['key'] ); ?>]" id="kctm-<?php echo esc_attr( $field['key'] ); ?>" value="<?php echo esc_attr( isset( $measurements[ $field['key'] ] ) ? $measurements[ $field['key'] ] : '' ); ?>" step="0.1" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>">
                        <span class="description"><?php echo esc_html( $field['unit'] ); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endforeach; ?>

        <?php submit_button( __( 'Save Measurements', 'kevincho-tailoring-manager' ) ); ?>
    </form>
</div>
