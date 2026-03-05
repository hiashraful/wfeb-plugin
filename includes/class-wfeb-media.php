<?php
/**
 * WFEB Media Helper Class
 *
 * Reusable media handling: file upload, cropped upload, upload zone component,
 * validation, and SVG placeholders.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Media
 */
class WFEB_Media {

	/**
	 * SVG placeholder icons.
	 *
	 * @var array
	 */
	private static $placeholders = array(
		'avatar'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
		'image'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
	);

	/**
	 * Get allowed MIME types for image uploads.
	 *
	 * @return array
	 */
	public static function get_allowed_mime_types() {
		return array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		);
	}

	/**
	 * Check if an attachment exists and is valid.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public static function attachment_exists( $attachment_id ) {
		if ( empty( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
			return false;
		}

		$attachment_id = absint( $attachment_id );

		if ( $attachment_id <= 0 ) {
			return false;
		}

		$post = get_post( $attachment_id );

		return $post && 'attachment' === $post->post_type;
	}

	/**
	 * Get SVG placeholder HTML.
	 *
	 * @param string $type Placeholder type (avatar, image).
	 * @return string HTML output.
	 */
	public static function get_placeholder_svg( $type = 'image' ) {
		$svg = isset( self::$placeholders[ $type ] ) ? self::$placeholders[ $type ] : self::$placeholders['image'];

		return '<div class="wfeb-placeholder-svg">' . $svg . '</div>';
	}

	/**
	 * Get image HTML with fallback to SVG placeholder.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 * @param string $type          Placeholder type.
	 * @param array  $attrs         Additional img attributes.
	 * @return string HTML output.
	 */
	public static function get_image( $attachment_id, $size = 'thumbnail', $type = 'image', $attrs = array() ) {
		if ( ! empty( $attachment_id ) && self::attachment_exists( $attachment_id ) ) {
			$image_url = wp_get_attachment_image_url( $attachment_id, $size );

			if ( $image_url ) {
				$default_attrs = array(
					'src'     => esc_url( $image_url ),
					'alt'     => '',
					'loading' => 'lazy',
				);

				$attrs = wp_parse_args( $attrs, $default_attrs );

				$attr_string = '';
				foreach ( $attrs as $key => $value ) {
					$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
				}

				return '<img' . $attr_string . '>';
			}
		}

		return self::get_placeholder_svg( $type );
	}

	/**
	 * Handle file upload via $_FILES.
	 *
	 * @param string $file_key Key in $_FILES array.
	 * @return array|WP_Error { id, url, thumb_url } on success, WP_Error on failure.
	 */
	public static function handle_upload( $file_key = 'file' ) {
		if ( ! isset( $_FILES[ $file_key ] ) ) {
			return new WP_Error( 'no_file', __( 'No file uploaded.', 'wfeb' ) );
		}

		$file = $_FILES[ $file_key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => __( 'File exceeds server limit.', 'wfeb' ),
				UPLOAD_ERR_FORM_SIZE  => __( 'File exceeds form limit.', 'wfeb' ),
				UPLOAD_ERR_PARTIAL    => __( 'File was only partially uploaded.', 'wfeb' ),
				UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'wfeb' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Missing temporary folder.', 'wfeb' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file.', 'wfeb' ),
				UPLOAD_ERR_EXTENSION  => __( 'Upload stopped by extension.', 'wfeb' ),
			);

			$message = isset( $error_messages[ $file['error'] ] )
				? $error_messages[ $file['error'] ]
				: __( 'Unknown upload error.', 'wfeb' );

			return new WP_Error( 'upload_error', $message );
		}

		// Validate file size (5MB max).
		$max_size = 5 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'File must be less than 5MB.', 'wfeb' ) );
		}

		// Validate file type.
		$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

		if ( ! $wp_filetype['type'] || ! in_array( $wp_filetype['type'], self::get_allowed_mime_types(), true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid file type. Only images are allowed (JPG, PNG, GIF, WebP).', 'wfeb' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( $file_key, 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wp_update_post( array(
			'ID'          => $attachment_id,
			'post_author' => get_current_user_id(),
		) );

		$url       = wp_get_attachment_url( $attachment_id );
		$thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		return array(
			'id'        => $attachment_id,
			'url'       => $url,
			'thumb_url' => $thumb_url ? $thumb_url : $url,
		);
	}

	/**
	 * Handle cropped image upload from base64 data.
	 *
	 * @param string $base64_data Base64 encoded image data.
	 * @param string $filename    Original filename.
	 * @return array|WP_Error { id, url, thumb_url } on success, WP_Error on failure.
	 */
	public static function handle_cropped_upload( $base64_data, $filename = 'cropped-image.jpg' ) {
		$data = explode( ',', $base64_data );
		if ( count( $data ) !== 2 ) {
			return new WP_Error( 'invalid_data', __( 'Invalid image data.', 'wfeb' ) );
		}

		$image_data = base64_decode( $data[1] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! $image_data ) {
			return new WP_Error( 'decode_failed', __( 'Failed to decode image.', 'wfeb' ) );
		}

		preg_match( '/data:image\/(\w+);base64/', $data[0], $matches );
		$extension = isset( $matches[1] ) ? $matches[1] : 'jpg';
		if ( 'jpeg' === $extension ) {
			$extension = 'jpg';
		}

		$filename = sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) . '-cropped.' . $extension );

		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['path'] . '/' . $filename;

		$counter = 1;
		while ( file_exists( $file_path ) ) {
			$filename  = sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) . '-' . $counter . '.' . $extension );
			$file_path = $upload_dir['path'] . '/' . $filename;
			$counter++;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $file_path, $image_data );
		if ( ! $result ) {
			return new WP_Error( 'save_failed', __( 'Failed to save image.', 'wfeb' ) );
		}

		$mime_types = array(
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
		);
		$mime_type = isset( $mime_types[ $extension ] ) ? $mime_types[ $extension ] : 'image/jpeg';

		$attachment = array(
			'post_mime_type' => $mime_type,
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_author'    => get_current_user_id(),
		);

		$attachment_id = wp_insert_attachment( $attachment, $file_path );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return $attachment_id;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		return array(
			'id'        => $attachment_id,
			'url'       => wp_get_attachment_url( $attachment_id ),
			'thumb_url' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
		);
	}

	/**
	 * Render reusable upload zone component.
	 *
	 * @param array $args {
	 *     @type string $type          Placeholder type (avatar, image).
	 *     @type string $input_name    Hidden input name attribute.
	 *     @type string $input_id      Hidden input id attribute.
	 *     @type int    $attachment_id Current attachment ID (0 for none).
	 *     @type string $button_text   Upload button label.
	 *     @type string $remove_text   Remove button label.
	 *     @type bool   $enable_crop   Whether to enable crop modal.
	 *     @type float  $crop_ratio    Aspect ratio for cropping.
	 * }
	 * @return string HTML output.
	 */
	public static function upload_zone( $args = array() ) {
		$defaults = array(
			'type'          => 'image',
			'input_name'    => 'image_id',
			'input_id'      => '',
			'attachment_id' => 0,
			'button_text'   => __( 'Upload Image', 'wfeb' ),
			'remove_text'   => __( 'Remove', 'wfeb' ),
			'enable_crop'   => false,
			'crop_ratio'    => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['input_id'] ) ) {
			$args['input_id'] = $args['input_name'];
		}

		$has_image = ! empty( $args['attachment_id'] ) && self::attachment_exists( $args['attachment_id'] );

		ob_start();
		?>
		<div class="wfeb-upload-zone" data-type="<?php echo esc_attr( $args['type'] ); ?>" data-input="<?php echo esc_attr( $args['input_id'] ); ?>" data-crop="<?php echo $args['enable_crop'] ? '1' : '0'; ?>" data-ratio="<?php echo esc_attr( $args['crop_ratio'] ); ?>">
			<div class="wfeb-upload-preview">
				<?php echo self::get_image( $args['attachment_id'], 'thumbnail', $args['type'] ); ?>
			</div>
			<div class="wfeb-upload-actions">
				<button type="button" class="wfeb-btn wfeb-btn--outline wfeb-btn--sm wfeb-upload-btn">
					<?php echo esc_html( $args['button_text'] ); ?>
				</button>
				<button type="button" class="wfeb-btn wfeb-btn--text wfeb-btn--sm wfeb-upload-remove-btn" style="<?php echo $has_image ? '' : 'display:none;'; ?>">
					<?php echo esc_html( $args['remove_text'] ); ?>
				</button>
			</div>
			<input type="file" accept="image/*" style="display:none;">
			<input type="hidden" name="<?php echo esc_attr( $args['input_name'] ); ?>" id="<?php echo esc_attr( $args['input_id'] ); ?>" value="<?php echo esc_attr( $args['attachment_id'] ); ?>">
			<div class="wfeb-upload-progress" style="display:none;">
				<div class="wfeb-progress-bar"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
