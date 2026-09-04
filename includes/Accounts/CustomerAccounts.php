<?php
/**
 * Customer Account Auto-Registration.
 *
 * When a guest books directly (outside WooCommerce checkout, which has its
 * own account creation flow), this finds a matching WordPress user by
 * email or creates one, then emails them a "set your password" link via
 * WordPress core's own new-user notification.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Accounts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CustomerAccounts
 */
class CustomerAccounts {

	/**
	 * Find or create a WordPress user account for a booking customer.
	 *
	 * @param string $name Customer full name.
	 * @param string $email Customer email address.
	 * @return array {
	 *     @type int  $user_id WordPress user ID (0 on failure).
	 *     @type bool $is_new  Whether a new account was just created.
	 * }
	 */
	public static function get_or_create_customer( $name, $email ) {
		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return array( 'user_id' => 0, 'is_new' => false );
		}

		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			return array( 'user_id' => $existing->ID, 'is_new' => false );
		}

		$username = self::generate_unique_username( $email );
		$password = wp_generate_password( 20 );
		$name     = sanitize_text_field( $name );
		$name_parts = explode( ' ', trim( $name ), 2 );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $name ? $name : $username,
				'first_name'   => $name_parts[0] ?? '',
				'last_name'    => $name_parts[1] ?? '',
				'role'         => apply_filters( 'mb_engine_new_customer_role', 'subscriber' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return array( 'user_id' => 0, 'is_new' => false );
		}

		update_user_meta( $user_id, '_mb_customer_account', 1 );

		// WordPress core: emails the new user a link to set their own
		// password. We never see or store the generated password.
		wp_new_user_notification( $user_id, null, 'user' );

		return array( 'user_id' => $user_id, 'is_new' => true );
	}

	/**
	 * Generate a unique, sane username from an email address.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	private static function generate_unique_username( $email ) {
		$local = strtok( $email, '@' );
		$base  = sanitize_user( $local, true );

		if ( empty( $base ) ) {
			$base = 'customer';
		}

		$username = $base;
		$i        = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $i;
			++$i;
		}

		return $username;
	}
}
