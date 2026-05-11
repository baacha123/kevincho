<?php
/**
 * KCTM Measurement Fields Registry
 *
 * Defines all profile and body measurement fields used throughout the plugin.
 * Labels are bilingual (English / French) for Cameroon customers.
 *
 * @package KevinCho_Tailoring_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KCTM_Measurement_Fields {

	public static function get_profile_fields() {
		return array(
			array(
				'key'      => 'gender',
				'label'    => 'Gender / Genre',
				'group'    => 'profile',
				'type'     => 'select',
				'unit'     => '',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 0,
				'max'      => 0,
				'options'  => array(
					'male'   => 'Male / Homme',
					'female' => 'Female / Femme',
					'child'  => 'Child / Enfant',
				),
				'required' => true,
			),
			array(
				'key'      => 'age',
				'label'    => 'Age / Age',
				'group'    => 'profile',
				'type'     => 'number',
				'unit'     => '',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 1,
				'max'      => 120,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'height',
				'label'    => 'Height / Taille',
				'group'    => 'profile',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 50,
				'max'      => 250,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'weight',
				'label'    => 'Weight / Poids',
				'group'    => 'profile',
				'type'     => 'number',
				'unit'     => 'kg',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 10,
				'max'      => 300,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'shoe_size',
				'label'    => 'Shoe Size / Pointure',
				'group'    => 'profile',
				'type'     => 'number',
				'unit'     => '',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 15,
				'max'      => 55,
				'options'  => array(),
				'required' => false,
			),
		);
	}

	public static function get_measurement_fields() {
		return array(
			// Head / Tete
			array(
				'key'      => 'head',
				'label'    => 'Head / Tete',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 40,
				'max'      => 70,
				'options'  => array(),
				'required' => false,
			),
			// Agbada Length / Longueur Agbada
			array(
				'key'      => 'agbada_length',
				'label'    => 'Agbada Length / Longueur Agbada',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 80,
				'max'      => 200,
				'options'  => array(),
				'required' => false,
			),
			// Upper Body / Haut du corps
			array(
				'key'      => 'neck',
				'label'    => 'Neck / Cou',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 20,
				'max'      => 60,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'chest',
				'label'    => 'Chest / Poitrine',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 50,
				'max'      => 180,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'shoulder_width',
				'label'    => 'Shoulder Width / Largeur Epaules',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 25,
				'max'      => 70,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'sleeve_length',
				'label'    => 'Sleeve Length / Longueur Manche',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 30,
				'max'      => 90,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'bicep',
				'label'    => 'Bicep / Biceps',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 15,
				'max'      => 60,
				'options'  => array(),
				'required' => false,
			),
			array(
				'key'      => 'wrist',
				'label'    => 'Wrist / Poignet',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 10,
				'max'      => 30,
				'options'  => array(),
				'required' => false,
			),
			array(
				'key'      => 'back_length',
				'label'    => 'Back Length / Longueur Dos',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 25,
				'max'      => 80,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'front_length',
				'label'    => 'Front Length / Longueur Devant',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 25,
				'max'      => 80,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'bust',
				'label'    => 'Bust / Buste',
				'group'    => 'upper_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'female' ),
				'min'      => 50,
				'max'      => 180,
				'options'  => array(),
				'required' => false,
			),

			// Core / Tronc
			array(
				'key'      => 'waist',
				'label'    => 'Waist / Tour de Taille',
				'group'    => 'core',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 40,
				'max'      => 180,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'stomach',
				'label'    => 'Stomach / Ventre',
				'group'    => 'core',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 40,
				'max'      => 200,
				'options'  => array(),
				'required' => false,
			),
			array(
				'key'      => 'hips',
				'label'    => 'Hips / Hanches',
				'group'    => 'core',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 50,
				'max'      => 180,
				'options'  => array(),
				'required' => true,
			),

			// Lower Body / Bas du corps
			array(
				'key'      => 'inseam',
				'label'    => 'Inseam / Entrejambe',
				'group'    => 'lower_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 30,
				'max'      => 100,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'outseam',
				'label'    => 'Outseam / Couture Exterieure',
				'group'    => 'lower_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 50,
				'max'      => 130,
				'options'  => array(),
				'required' => true,
			),
			array(
				'key'      => 'thigh',
				'label'    => 'Thigh / Cuisse',
				'group'    => 'lower_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 30,
				'max'      => 90,
				'options'  => array(),
				'required' => false,
			),
			array(
				'key'      => 'knee',
				'label'    => 'Knee / Genou',
				'group'    => 'lower_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 20,
				'max'      => 60,
				'options'  => array(),
				'required' => false,
			),
			array(
				'key'      => 'calf',
				'label'    => 'Calf / Mollet',
				'group'    => 'lower_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 20,
				'max'      => 60,
				'options'  => array(),
				'required' => false,
			),
			array(
				'key'      => 'ankle',
				'label'    => 'Ankle / Cheville',
				'group'    => 'lower_body',
				'type'     => 'number',
				'unit'     => 'cm',
				'gender'   => array( 'male', 'female', 'child' ),
				'min'      => 15,
				'max'      => 40,
				'options'  => array(),
				'required' => false,
			),
		);
	}

	public static function get_fields_for_gender( $gender ) {
		$gender = sanitize_text_field( $gender );

		if ( ! in_array( $gender, array( 'male', 'female', 'child' ), true ) ) {
			$gender = 'male';
		}

		$all_fields      = self::get_all_fields();
		$filtered_fields = array();

		foreach ( $all_fields as $field ) {
			if ( in_array( $gender, $field['gender'], true ) ) {
				$filtered_fields[] = $field;
			}
		}

		return $filtered_fields;
	}

	public static function get_all_fields() {
		return array_merge( self::get_profile_fields(), self::get_measurement_fields() );
	}
}
