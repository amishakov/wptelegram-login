<?php
/**
 * The login handling functionality of the plugin.
 *
 * @link       https://t.me/manzoorwanijk
 * @since      1.0.0
 *
 * @package    WPTelegram\Login
 * @subpackage WPTelegram\Login\public
 */

namespace WPTelegram\Login\shared;

use WPTelegram\Login\includes\BaseClass;

/**
 * The login handling functionality of the plugin.
 *
 * The login handling functionality of the plugin.
 *
 * @package    WPTelegram\Login
 * @subpackage WPTelegram\Login\public
 * @author     Manzoor Wani <@manzoorwanijk>
 */
class LoginHandler extends BaseClass {

	/**
	 * Handle Telegram login on init
	 *
	 * @since    1.0.0
	 */
	public function telegram_login() {

		$bot_token = WPTG_Login()->options()->get( 'bot_token' );

		if ( ! $this->is_valid_login_request() || ! $bot_token ) {
			return;
		}

		do_action( 'wptelegram_login_init' );

		$input = $_GET; // phpcs:disable WordPress.Security.NonceVerification.Recommended

		// Remove any unwanted fields.
		$input = $this->filter_input_fields( $input );

		try {
			$auth_data = $this->get_authorization_data( $input );

			do_action( 'wptelegram_login_pre_save_user_data', $auth_data );

			$wp_user_id = $this->save_telegram_user_data( $auth_data );

			do_action( 'wptelegram_login_after_save_user_data', $wp_user_id, $auth_data );

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.Security.EscapeOutput
			wp_die( $e->getMessage(), __( 'Error:', 'wptelegram-login' ), array( 'back_link' => true ) );
		}

		$user = wp_get_current_user();

		if ( ! $user->exists() ) { // ! is user logged in

			do_action( 'wptelegram_login_before_user_login', $wp_user_id );

			// Login the user.
			wp_clear_auth_cookie();
			$user = wp_set_current_user( $wp_user_id );
			wp_set_auth_cookie( $wp_user_id, true );

			do_action( 'wptelegram_login_after_user_login', $wp_user_id );

			/**
			 * Fires after the user has successfully logged in.
			 *
			 * @since 1.3.4
			 *
			 * @param string  $user_login Username.
			 * @param WP_User $user       WP_User object of the logged-in user.
			 */
			do_action( 'wp_login', $user->user_login, $user );
			do_action( 'wptelegram_login', $user->user_login, $user );
		}

		$random_email = WPTG_Login()->options()->get( 'random_email' );

		if ( $random_email ) {
			$this->may_be_generate_email( $user );
		}

		do_action( 'wptelegram_login_before_redirect', $user );

		$this->redirect( $user );
	}

	/**
	 * Check if the Telegram Login request is valid
	 *
	 * @since    1.0.0
	 *
	 * @return boolean
	 */
	public function is_valid_login_request() {

		if ( isset( $_GET['action'], $_GET['hash'], $_GET['auth_date'] ) && 'wptelegram_login' === $_GET['action'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Filter the input by removing any unwanted fields
	 * Especially in case of the query type permalinks.
	 *
	 * @since    1.0.0
	 *
	 * @param array $input The data passed.
	 *
	 * @return array
	 */
	public function filter_input_fields( $input ) {

		$desired_fields = array(
			'id'         => '',
			'first_name' => '',
			'last_name'  => '',
			'username'   => '',
			'photo_url'  => '',
			'auth_date'  => '',
			'hash'       => '',
		);

		return array_intersect_key( $input, $desired_fields );
	}

	/**
	 * Fetch the auth data based on the input.
	 *
	 * @since 1.0.0
	 *
	 * @param array $auth_data The input data.
	 *
	 * @throws Exception The exception.
	 *
	 * @return array
	 */
	public function get_authorization_data( $auth_data ) {

		$bot_token = WPTG_Login()->options()->get( 'bot_token' );

		$check_hash = $auth_data['hash'];
		unset( $auth_data['hash'] );

		$data_check_arr = array();
		foreach ( $auth_data as $key => $value ) {
			$data_check_arr[] = $key . '=' . $value;
		}
		// Sort in alphabetical order.
		sort( $data_check_arr );

		$data_check_string = implode( "\n", $data_check_arr );
		$secret_key        = hash( 'sha256', $bot_token, true );
		$hash              = hash_hmac( 'sha256', $data_check_string, $secret_key );

		if ( strcmp( $hash, $check_hash ) !== 0 ) {
			throw new Exception( __( 'Unauthorized! Data is NOT from Telegram', 'wptelegram-login' ) );
		}

		if ( ( time() - $auth_data['auth_date'] ) > 86400 ) {
			throw new Exception( __( 'Invalid! The data is outdated', 'wptelegram-login' ) );
		}
		return $auth_data;
	}

	/**
	 * Generate a random email address for the user if needed.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_User $user Current user.
	 */
	public function may_be_generate_email( $user ) {

		if ( $user->exists() && ! $user->user_email ) {
			$host = wp_parse_url( get_site_url(), PHP_URL_HOST );
			$host = apply_filters( 'wptelegram_login_random_email_host', $host, $user );

			$random_user = apply_filters( 'wptelegram_login_random_email_user', 'auto-generated', $user );

			$random_email = $this->unique_email( $random_user, $host );
			$random_email = apply_filters( 'wptelegram_login_random_email', $random_email, $user, $random_user, $host );

			wp_update_user(
				array(
					'ID'         => $user->ID,
					'user_email' => $random_email,
				)
			);
		}
	}

	/**
	 * Recursive function to generate a unique email.
	 *
	 * @since 1.6.0
	 *
	 * If the email already exists, will add a numerical suffix which will increase until a unique email is found.
	 *
	 * @param string $user Initial username for email.
	 * @param string $host Email host.
	 *
	 * @return string The unique email.
	 */
	public function unique_email( $user, $host ) {
		static $i;
		if ( is_null( $i ) ) {
			$i = 1;
		} else {
			$i++;
		}
		$email = sprintf( '%1$s@%2$s', $user, $host );
		if ( ! email_exists( $email ) ) {
			return $email;
		}
		$new_email = sprintf( '%1$s%2$s@%3$s', $user, $i, $host );
		if ( ! email_exists( $new_email ) ) {
			return $new_email;
		}
		return call_user_func( array( $this, __FUNCTION__ ), $user, $host );
	}

	/**
	 * Save/update user's data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The user data received from Telegram.
	 * @throws Exception The exception.
	 */
	public function save_telegram_user_data( $data ) {

		$data = array_map( 'htmlspecialchars', $data );

		// Check if the request is from a logged in user.
		$cur_user = wp_get_current_user();

		// Check if the user is signing in again.
		$ret_user = $this->is_returning_user( $data['id'] );

		if ( $cur_user->exists() ) { // Logged in user.

			// Signed in user and the Telegram user not same.
			if ( $ret_user instanceof WP_User && $cur_user->ID !== $ret_user->ID ) {
				throw new Exception( __( 'The Telegram User ID is already associated with another existing user. Please contact the admin', 'wptelegram-login' ) );
			}

			$wp_user_id = $this->save_user_data( $data, $cur_user->ID );

		} elseif ( $ret_user instanceof WP_User ) { // Existing logged out.

			$wp_user_id = $this->save_user_data( $data, $ret_user->ID );

		} else { // New user.

			// Whether to allow create new account.
			$disable_signup = WPTG_Login()->options()->get( 'disable_signup' );

			$disable_signup = (bool) apply_filters( 'wptelegram_login_disable_signup', $disable_signup, $data );

			if ( $disable_signup ) {

				throw new Exception( __( 'Sign up via Telegram is disabled. You must first create an account and connect it to Telegram to be able to use Telegram Login', 'wptelegram-login' ) );

			}

			$wp_user_id = $this->save_user_data( $data );
		}
		return $wp_user_id;
	}

	/**
	 * Whether the user is a returning user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $tg_user_id Telegram User ID.
	 *
	 * @return boolean|WP_User User object or false
	 */
	public function is_returning_user( $tg_user_id ) {
		$args  = array(
			'meta_key'   => WPTELEGRAM_USER_ID_META_KEY, // phpcs:ignore
			'meta_value' => $tg_user_id, // phpcs:ignore
			'number'     => 1,
		);
		$users = get_users( $args );
		if ( ! empty( $users ) ) {
			return reset( $users );
		}
		return false;
	}

	/**
	 * Save or update the user data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array    $data          The user details.
	 * @param  int|NULL $ex_wp_user_id Existing WP User ID.
	 *
	 * @throws Exception The exception.
	 *
	 * @return int|WP_Error The newly created user's ID or a WP_Error object if the user could not be created
	 */
	public function save_user_data( $data, $ex_wp_user_id = null ) {

		$data = apply_filters( 'wptelegram_login_save_user_data', $data, $ex_wp_user_id );

		// The data fields received.
		$id          = $data['id'];
		$first_name  = $data['first_name'];
		$last_name   = isset( $data['last_name'] ) ? $data['last_name'] : '';
		$tg_username = isset( $data['username'] ) ? $data['username'] : '';
		$photo_url   = isset( $data['photo_url'] ) ? $data['photo_url'] : '';
		$username    = $tg_username;

		if ( is_null( $ex_wp_user_id ) ) { // New user.

			// If no username, use the sanitized first_name and id.
			if ( empty( $username ) ) {
				$username = sanitize_user( $first_name . $id, true );
			}

			$unique_username = $this->unique_username( $username );

			$user_login = apply_filters( 'wptelegram_login_unique_username', $unique_username );

			$user_pass = wp_generate_password();

			$role = WPTG_Login()->options()->get( 'user_role' );

			$userdata = compact( 'user_pass', 'user_login', 'first_name', 'last_name', 'role' );

			$userdata = apply_filters( 'wptelegram_login_insert_user_data', $userdata );

			$wp_user_id = wp_insert_user( $userdata );

			do_action( 'wptelegram_login_after_insert_user', $wp_user_id, $userdata );

		} else { // Update.

			$ID = $ex_wp_user_id; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- Ignore  snake_case

			$userdata = compact( 'ID', 'first_name', 'last_name' );

			$userdata = apply_filters( 'wptelegram_login_update_user_data', $userdata );

			$wp_user_id = wp_update_user( $userdata );

			do_action( 'wptelegram_login_after_update_user', $wp_user_id, $userdata );
		}

		if ( is_wp_error( $wp_user_id ) ) {
			throw new Exception( __( 'Telegram sign in could not be completed.', 'wptelegram-login' ) . ' ' . $wp_user_id->get_error_message() );
		}

		// Save the telegram user ID and username.
		update_user_meta( $wp_user_id, WPTELEGRAM_USER_ID_META_KEY, $id );
		update_user_meta( $wp_user_id, WPTELEGRAM_USERNAME_META_KEY, $tg_username );

		if ( ! empty( $photo_url ) ) {
			$meta_key = WPTG_Login()->options()->get( 'avatar_meta_key' );
			if ( ! empty( $meta_key ) ) {
				update_user_meta( $wp_user_id, $meta_key, esc_url_raw( $photo_url ) );
			}
		}

		do_action( 'wptelegram_login_after_update_user_meta', $wp_user_id );

		return $wp_user_id;
	}

	/**
	 * Recursive function to generate a unique username.
	 *
	 * @since 1.0.0
	 *
	 * If the username already exists, will add a numerical suffix which will increase until a unique username is found.
	 *
	 * @param string $username The Telegram username.
	 *
	 * @return string The unique username.
	 */
	public function unique_username( $username ) {
		static $i;
		if ( is_null( $i ) ) {
			$i = 1;
		} else {
			$i++;
		}
		if ( ! username_exists( $username ) ) {
			return $username;
		}
		$new_username = sprintf( '%s%s', $username, $i );
		if ( ! username_exists( $new_username ) ) {
			return $new_username;
		}
		return call_user_func( array( $this, __FUNCTION__ ), $username );
	}

	/**
	 * Redirect the user to a proper location.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user The logged in user.
	 */
	private function redirect( $user ) {
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? remove_query_arg( 'reauth', wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore

		// Apply plugin specific filter.
		$redirect_to = apply_filters( 'wptelegram_login_user_redirect_to', $redirect_to, $user );

		if ( ( empty( $redirect_to ) || 'wp-admin/' === $redirect_to || admin_url() === $redirect_to ) ) {
			// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
			if ( is_multisite() && ! get_active_blog_for_user( $user->ID ) && ! is_super_admin( $user->ID ) ) {

				$redirect_to = user_admin_url();

			} elseif ( is_multisite() && ! $user->has_cap( 'read' ) ) {

				$redirect_to = get_dashboard_url( $user->ID );

			} elseif ( ! $user->has_cap( 'edit_posts' ) ) {

				$redirect_to = $user->has_cap( 'read' ) ? admin_url( 'profile.php' ) : home_url();

			}
			wp_safe_redirect( $redirect_to );
			exit();
		}
		wp_safe_redirect( $redirect_to );
		exit();
	}
}
