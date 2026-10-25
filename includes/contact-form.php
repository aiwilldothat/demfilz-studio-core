<?php
/**
 * Contact form: render, spam protection, storage, mail.
 *
 * No email address is ever printed on the public page; submissions are
 * emailed to the configured recipient and stored as Form Entries.
 *
 * @package Demfilz_Studio_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recipient for contact submissions (never displayed publicly).
 *
 * Uses the Demfilz Studio theme option when set, else the admin email.
 *
 * @return string
 */
function dstudio_contact_recipient() {
	$email = sanitize_email( (string) dstudio_core_mod( 'contact_recipient', '' ) );
	return $email ? $email : get_option( 'admin_email' );
}

/**
 * Success message shown after a submission.
 *
 * @return string
 */
function dstudio_core_success_message() {
	return (string) dstudio_core_mod( 'contact_success', __( 'Thanks for reaching out. We have received your message and will reply shortly.', 'demfilz-studio-core' ) );
}

/**
 * Validate and sanitize a submission.
 *
 * @param array $data Raw (unslashed) request data.
 * @return array { ok: bool, error: string, fields: array }
 */
function dstudio_contact_validate( $data ) {
	$name    = isset( $data['dstudio_name'] ) ? sanitize_text_field( $data['dstudio_name'] ) : '';
	$email   = isset( $data['dstudio_email'] ) ? sanitize_email( $data['dstudio_email'] ) : '';
	$phone   = isset( $data['dstudio_phone'] ) ? sanitize_text_field( $data['dstudio_phone'] ) : '';
	$message = isset( $data['dstudio_message'] ) ? sanitize_textarea_field( $data['dstudio_message'] ) : '';

	$fields = array(
		'name'    => $name,
		'email'   => $email,
		'phone'   => $phone,
		'message' => $message,
	);

	if ( '' === $name || strlen( $name ) > 120 ) {
		return array(
			'ok'     => false,
			'error'  => 'name',
			'fields' => $fields,
		);
	}

	if ( '' === $email || ! is_email( $email ) ) {
		return array(
			'ok'     => false,
			'error'  => 'email',
			'fields' => $fields,
		);
	}

	if ( strlen( $phone ) > 40 ) {
		return array(
			'ok'     => false,
			'error'  => 'phone',
			'fields' => $fields,
		);
	}

	if ( '' === $message || strlen( $message ) > 5000 ) {
		return array(
			'ok'     => false,
			'error'  => 'message',
			'fields' => $fields,
		);
	}

	return array(
		'ok'     => true,
		'error'  => '',
		'fields' => $fields,
	);
}

/**
 * Sliding-window rate limit.
 *
 * STANDING RULE: keyed on REMOTE_ADDR only. Never trust client-supplied
 * forwarded headers here; rotating them would mint fresh buckets and
 * defeat the limiter entirely.
 *
 * @param string $ip Remote address.
 * @return bool True when over the limit.
 */
function dstudio_contact_rate_limited( $ip ) {
	$key   = 'dstudio_rl_' . md5( (string) $ip );
	$count = (int) get_transient( $key );

	if ( $count >= 5 ) {
		return true;
	}

	set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

	return false;
}

/**
 * Redirect back to the form with a status flag.
 *
 * @param string $back   Referring URL.
 * @param string $status Status slug.
 * @return void
 */
function dstudio_contact_redirect( $back, $status ) {
	wp_safe_redirect( add_query_arg( 'dstudio_status', rawurlencode( $status ), $back ) . '#dstudio-contact' );
	exit;
}

/**
 * Handle a contact form submission (admin-post.php).
 *
 * @return void
 */
function dstudio_contact_handle() {
	$back = wp_get_referer();
	$back = $back ? $back : home_url( '/' );

	if ( ! isset( $_POST['dstudio_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dstudio_contact_nonce'] ) ), 'dstudio_contact' ) ) {
		dstudio_contact_redirect( $back, 'error' );
	}

	// Honeypot and minimum fill time: drop silently so bots learn nothing.
	$honeypot  = isset( $_POST['dstudio_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['dstudio_website'] ) ) ) : '';
	$timestamp = isset( $_POST['dstudio_ts'] ) ? absint( $_POST['dstudio_ts'] ) : 0;
	$elapsed   = time() - $timestamp;

	if ( '' !== $honeypot || $timestamp <= 0 || $elapsed < 3 || $elapsed > DAY_IN_SECONDS ) {
		dstudio_contact_redirect( $back, 'sent' );
	}

	// STANDING RULE: REMOTE_ADDR only, never forwarded headers.
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( dstudio_contact_rate_limited( $ip ) ) {
		dstudio_contact_redirect( $back, 'slow' );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every field is sanitized inside dstudio_contact_validate().
	$result = dstudio_contact_validate( wp_unslash( $_POST ) );
	if ( ! $result['ok'] ) {
		dstudio_contact_redirect( $back, 'invalid' );
	}

	$fields = $result['fields'];

	$entry_id = wp_insert_post(
		array(
			'post_type'    => 'dstudio_entry',
			'post_status'  => 'private',
			'post_title'   => $fields['name'],
			'post_content' => $fields['message'],
		),
		true
	);

	if ( ! is_wp_error( $entry_id ) && $entry_id ) {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '';
		update_post_meta( $entry_id, '_dstudio_email', $fields['email'] );
		update_post_meta( $entry_id, '_dstudio_phone', $fields['phone'] );
		update_post_meta( $entry_id, '_dstudio_ip', $ip );
		update_post_meta( $entry_id, '_dstudio_ua', $agent );
	}

	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	/* translators: 1: site name, 2: sender name. */
	$subject = sprintf( __( '[%1$s] New enquiry from %2$s', 'demfilz-studio-core' ), $site, $fields['name'] );

	$body  = __( 'New contact form enquiry', 'demfilz-studio-core' ) . "\n\n";
	$body .= __( 'Name:', 'demfilz-studio-core' ) . ' ' . $fields['name'] . "\n";
	$body .= __( 'Email:', 'demfilz-studio-core' ) . ' ' . $fields['email'] . "\n";
	if ( '' !== $fields['phone'] ) {
		$body .= __( 'Phone:', 'demfilz-studio-core' ) . ' ' . $fields['phone'] . "\n";
	}
	$body .= "\n" . __( 'Message:', 'demfilz-studio-core' ) . "\n" . $fields['message'] . "\n\n";
	$body .= __( 'Sent from:', 'demfilz-studio-core' ) . ' ' . home_url( '/' ) . "\n";

	// Name and email came through sanitize_text_field/sanitize_email, so no header injection is possible.
	$headers = array( 'Reply-To: ' . $fields['name'] . ' <' . $fields['email'] . '>' );

	wp_mail( dstudio_contact_recipient(), $subject, $body, $headers );

	dstudio_contact_redirect( $back, 'sent' );
}
add_action( 'admin_post_dstudio_contact', 'dstudio_contact_handle' );
add_action( 'admin_post_nopriv_dstudio_contact', 'dstudio_contact_handle' );

/**
 * Status notice above the form.
 *
 * @return void
 */
function dstudio_contact_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect flag.
	if ( ! isset( $_GET['dstudio_status'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect flag.
	$status = sanitize_key( wp_unslash( $_GET['dstudio_status'] ) );

	$messages = array(
		'sent'    => dstudio_core_success_message(),
		'slow'    => __( 'You have sent a few messages in a short time. Please wait a little while and try again.', 'demfilz-studio-core' ),
		'invalid' => __( 'Please complete all required fields with valid details and try again.', 'demfilz-studio-core' ),
		'error'   => __( 'Something went wrong sending your message. Please refresh the page and try again.', 'demfilz-studio-core' ),
	);

	if ( ! isset( $messages[ $status ] ) ) {
		return;
	}

	$type = 'sent' === $status ? 'success' : 'error';

	echo '<div class="form-notice form-notice-' . esc_attr( $type ) . '" role="status">' . esc_html( $messages[ $status ] ) . '</div>';
}

/**
 * Render the contact form.
 *
 * @return void
 */
function dstudio_contact_form() {
	?>
	<form id="dstudio-contact" class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php dstudio_contact_notice(); ?>
		<input type="hidden" name="action" value="dstudio_contact">
		<?php wp_nonce_field( 'dstudio_contact', 'dstudio_contact_nonce' ); ?>
		<input type="hidden" name="dstudio_ts" value="<?php echo esc_attr( (string) time() ); ?>">

		<p class="hp-field" aria-hidden="true">
			<label for="dstudio_website"><?php esc_html_e( 'Leave this field empty', 'demfilz-studio-core' ); ?></label>
			<input type="text" id="dstudio_website" name="dstudio_website" tabindex="-1" autocomplete="off" value="">
		</p>

		<div class="form-row">
			<p class="form-field">
				<label for="dstudio_name"><?php esc_html_e( 'Name', 'demfilz-studio-core' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input type="text" id="dstudio_name" name="dstudio_name" required maxlength="120" autocomplete="name">
			</p>
			<p class="form-field">
				<label for="dstudio_email"><?php esc_html_e( 'Email', 'demfilz-studio-core' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input type="email" id="dstudio_email" name="dstudio_email" required maxlength="120" autocomplete="email">
			</p>
		</div>

		<p class="form-field">
			<label for="dstudio_phone"><?php esc_html_e( 'Phone (optional)', 'demfilz-studio-core' ); ?></label>
			<input type="tel" id="dstudio_phone" name="dstudio_phone" maxlength="40" autocomplete="tel">
		</p>

		<p class="form-field">
			<label for="dstudio_message"><?php esc_html_e( 'Message', 'demfilz-studio-core' ); ?> <span class="req" aria-hidden="true">*</span></label>
			<textarea id="dstudio_message" name="dstudio_message" rows="6" required maxlength="5000"></textarea>
		</p>

		<p class="form-submit-row">
			<button type="submit" class="btn btn-solid btn-lg">
				<span><?php esc_html_e( 'Send Message', 'demfilz-studio-core' ); ?></span>
				<?php
				if ( function_exists( 'dstudio_the_icon' ) ) {
					dstudio_the_icon( 'send', 18, 'btn-icon' );
				}
				?>
			</button>
		</p>
	</form>
	<?php
}
