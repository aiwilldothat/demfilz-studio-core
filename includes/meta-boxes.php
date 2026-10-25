<?php
/**
 * Meta boxes: project details, service icon, form entry viewer.
 *
 * @package Demfilz_Studio_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icons offered in the Service icon picker.
 *
 * The Demfilz Studio theme renders these names as inline SVGs; other
 * themes can map them however they like via the _dstudio_icon meta.
 *
 * @return array name => label
 */
function dstudio_service_icon_choices() {
	return array(
		'briefcase'  => __( 'Briefcase', 'demfilz-studio-core' ),
		'palette'    => __( 'Palette', 'demfilz-studio-core' ),
		'pen'        => __( 'Pen', 'demfilz-studio-core' ),
		'code'       => __( 'Code', 'demfilz-studio-core' ),
		'smartphone' => __( 'Smartphone', 'demfilz-studio-core' ),
		'globe'      => __( 'Globe', 'demfilz-studio-core' ),
		'chart'      => __( 'Chart', 'demfilz-studio-core' ),
		'megaphone'  => __( 'Megaphone', 'demfilz-studio-core' ),
		'search'     => __( 'Search', 'demfilz-studio-core' ),
		'camera'     => __( 'Camera', 'demfilz-studio-core' ),
		'video'      => __( 'Video', 'demfilz-studio-core' ),
		'rocket'     => __( 'Rocket', 'demfilz-studio-core' ),
		'shield'     => __( 'Shield', 'demfilz-studio-core' ),
		'gear'       => __( 'Gear', 'demfilz-studio-core' ),
		'layers'     => __( 'Layers', 'demfilz-studio-core' ),
		'lightbulb'  => __( 'Lightbulb', 'demfilz-studio-core' ),
		'chat'       => __( 'Chat', 'demfilz-studio-core' ),
		'users'      => __( 'Team', 'demfilz-studio-core' ),
		'wrench'     => __( 'Wrench', 'demfilz-studio-core' ),
		'target'     => __( 'Target', 'demfilz-studio-core' ),
	);
}

/**
 * Register the meta boxes.
 *
 * @return void
 */
function dstudio_add_meta_boxes() {
	add_meta_box( 'dstudio-project-details', __( 'Project Details', 'demfilz-studio-core' ), 'dstudio_render_portfolio_box', 'dstudio_portfolio', 'side' );
	add_meta_box( 'dstudio-service-icon', __( 'Service Icon', 'demfilz-studio-core' ), 'dstudio_render_service_box', 'dstudio_service', 'side' );
	add_meta_box( 'dstudio-entry-details', __( 'Submission', 'demfilz-studio-core' ), 'dstudio_render_entry_box', 'dstudio_entry', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dstudio_add_meta_boxes' );

/**
 * Project details fields.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function dstudio_render_portfolio_box( $post ) {
	wp_nonce_field( 'dstudio_save_portfolio', 'dstudio_portfolio_nonce' );
	$client = (string) get_post_meta( $post->ID, '_dstudio_client', true );
	$year   = (string) get_post_meta( $post->ID, '_dstudio_year', true );
	$url    = (string) get_post_meta( $post->ID, '_dstudio_url', true );
	?>
	<p>
		<label for="dstudio_client"><strong><?php esc_html_e( 'Client', 'demfilz-studio-core' ); ?></strong></label>
		<input type="text" class="widefat" id="dstudio_client" name="dstudio_client" value="<?php echo esc_attr( $client ); ?>">
	</p>
	<p>
		<label for="dstudio_year"><strong><?php esc_html_e( 'Year', 'demfilz-studio-core' ); ?></strong></label>
		<input type="text" class="widefat" id="dstudio_year" name="dstudio_year" value="<?php echo esc_attr( $year ); ?>" placeholder="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" maxlength="4">
	</p>
	<p>
		<label for="dstudio_url"><strong><?php esc_html_e( 'Project URL', 'demfilz-studio-core' ); ?></strong></label>
		<input type="url" class="widefat" id="dstudio_url" name="dstudio_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://">
	</p>
	<?php
}

/**
 * Save project details.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function dstudio_save_portfolio_box( $post_id ) {
	if ( ! isset( $_POST['dstudio_portfolio_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dstudio_portfolio_nonce'] ) ), 'dstudio_save_portfolio' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$client = isset( $_POST['dstudio_client'] ) ? sanitize_text_field( wp_unslash( $_POST['dstudio_client'] ) ) : '';
	$year   = isset( $_POST['dstudio_year'] ) ? sanitize_text_field( wp_unslash( $_POST['dstudio_year'] ) ) : '';
	$url    = isset( $_POST['dstudio_url'] ) ? esc_url_raw( wp_unslash( $_POST['dstudio_url'] ) ) : '';

	if ( ! preg_match( '/^\d{4}$/', $year ) ) {
		$year = '';
	}

	dstudio_update_or_delete_meta( $post_id, '_dstudio_client', $client );
	dstudio_update_or_delete_meta( $post_id, '_dstudio_year', $year );
	dstudio_update_or_delete_meta( $post_id, '_dstudio_url', $url );
}
add_action( 'save_post_dstudio_portfolio', 'dstudio_save_portfolio_box' );

/**
 * Service icon picker.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function dstudio_render_service_box( $post ) {
	wp_nonce_field( 'dstudio_save_service', 'dstudio_service_nonce' );
	$current = (string) get_post_meta( $post->ID, '_dstudio_icon', true );
	?>
	<p>
		<label for="dstudio_icon"><strong><?php esc_html_e( 'Icon shown on the service card', 'demfilz-studio-core' ); ?></strong></label>
	</p>
	<select id="dstudio_icon" name="dstudio_icon" class="widefat">
		<option value=""><?php esc_html_e( 'Automatic', 'demfilz-studio-core' ); ?></option>
		<?php foreach ( dstudio_service_icon_choices() as $slug => $label ) : ?>
			<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Automatic cycles through a tasteful set based on the service order.', 'demfilz-studio-core' ); ?></p>
	<?php
}

/**
 * Save the service icon.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function dstudio_save_service_box( $post_id ) {
	if ( ! isset( $_POST['dstudio_service_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dstudio_service_nonce'] ) ), 'dstudio_save_service' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$icon = isset( $_POST['dstudio_icon'] ) ? sanitize_key( wp_unslash( $_POST['dstudio_icon'] ) ) : '';
	if ( '' !== $icon && ! array_key_exists( $icon, dstudio_service_icon_choices() ) ) {
		$icon = '';
	}

	dstudio_update_or_delete_meta( $post_id, '_dstudio_icon', $icon );
}
add_action( 'save_post_dstudio_service', 'dstudio_save_service_box' );

/**
 * Store meta, or remove the row when the value is empty.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param string $value   Meta value.
 * @return void
 */
function dstudio_update_or_delete_meta( $post_id, $key, $value ) {
	if ( '' === $value ) {
		delete_post_meta( $post_id, $key );
	} else {
		update_post_meta( $post_id, $key, $value );
	}
}

/**
 * Read-only view of a contact form entry.
 *
 * @param WP_Post $post Current entry.
 * @return void
 */
function dstudio_render_entry_box( $post ) {
	$rows = array(
		__( 'Email', 'demfilz-studio-core' )      => (string) get_post_meta( $post->ID, '_dstudio_email', true ),
		__( 'Phone', 'demfilz-studio-core' )      => (string) get_post_meta( $post->ID, '_dstudio_phone', true ),
		__( 'IP address', 'demfilz-studio-core' ) => (string) get_post_meta( $post->ID, '_dstudio_ip', true ),
		__( 'Browser', 'demfilz-studio-core' )    => (string) get_post_meta( $post->ID, '_dstudio_ua', true ),
	);
	?>
	<table class="widefat striped">
		<tbody>
		<?php foreach ( $rows as $label => $value ) : ?>
			<tr>
				<th style="width:140px;text-align:left;"><?php echo esc_html( $label ); ?></th>
				<td><?php echo esc_html( '' !== $value ? $value : '-' ); ?></td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th style="text-align:left;vertical-align:top;"><?php esc_html_e( 'Message', 'demfilz-studio-core' ); ?></th>
			<td><?php echo wp_kses( nl2br( esc_html( $post->post_content ) ), array( 'br' => array() ) ); ?></td>
		</tr>
		</tbody>
	</table>
	<?php
}
