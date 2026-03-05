<?php
/**
 * WFEB Admin Settings Template
 *
 * Displays the settings page with 5 tabbed sections:
 * General, WooCommerce, Email, Exam, Certificate.
 *
 * Variables available from WFEB_Admin_Settings::render():
 * - $tabs, $settings, $active_tab
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url = admin_url( 'admin.php?page=wfeb-settings' );
?>
<div class="wfeb-wrap">

	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'Settings', 'wfeb' ); ?></h1>
	</div>

	<div class="wfeb-settings-wrap">

		<!-- Sidebar Navigation -->
		<nav class="wfeb-settings-nav">
			<?php foreach ( $tabs as $tab_slug => $tab_label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_slug, $base_url ) ); ?>"
					class="wfeb-settings-nav-item<?php echo $active_tab === $tab_slug ? ' active' : ''; ?>"
					<?php if ( 'exam' === $tab_slug ) : ?>data-tooltip="Configure scoring thresholds for achievement levels" data-tooltip-pos="bottom"<?php endif; ?>>
					<?php echo esc_html( $tab_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<!-- Settings Content -->
		<div class="wfeb-settings-content">

			<?php // ============================================================ ?>
			<?php // GENERAL TAB ?>
			<?php // ============================================================ ?>
			<?php if ( 'general' === $active_tab ) : ?>
			<form method="post" action="" class="wfeb-settings-form" id="wfeb-settings-general">
				<?php wp_nonce_field( 'wfeb_save_settings', 'wfeb_settings_nonce' ); ?>
				<input type="hidden" name="wfeb_tab" value="general" />

				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h2><?php esc_html_e( 'General Settings', 'wfeb' ); ?></h2>
					</div>
					<div class="wfeb-card-body">

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_coach_approval_mode">
								<?php esc_html_e( 'Coach Approval Mode', 'wfeb' ); ?>
							</label>
							<select id="wfeb_coach_approval_mode" name="wfeb_coach_approval_mode" class="wfeb-form-select">
								<option value="manual" <?php selected( $settings['coach_approval_mode'], 'manual' ); ?>>
									<?php esc_html_e( 'Manual Approval', 'wfeb' ); ?>
								</option>
								<option value="auto" <?php selected( $settings['coach_approval_mode'], 'auto' ); ?>>
									<?php esc_html_e( 'Auto Approve', 'wfeb' ); ?>
								</option>
							</select>
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Manual requires admin approval. Auto approves coaches immediately on registration.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_cert_prefix">
								<?php esc_html_e( 'Certificate Prefix', 'wfeb' ); ?>
							</label>
							<input type="text" id="wfeb_cert_prefix" name="wfeb_cert_prefix"
								value="<?php echo esc_attr( $settings['cert_prefix'] ); ?>"
								class="wfeb-form-input" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Prefix for certificate numbers (e.g., WFEB-1000).', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_cert_start">
								<?php esc_html_e( 'Certificate Start Number', 'wfeb' ); ?>
							</label>
							<input type="number" id="wfeb_cert_start" name="wfeb_cert_start"
								value="<?php echo esc_attr( $settings['cert_start'] ); ?>"
								class="wfeb-form-input" min="1" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Starting number for the first certificate issued.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_logo">
								<?php esc_html_e( 'WFEB Logo', 'wfeb' ); ?>
							</label>
							<input type="url" id="wfeb_logo" name="wfeb_logo"
								value="<?php echo esc_attr( $settings['wfeb_logo'] ); ?>"
								class="wfeb-form-input" />
							<button type="button" class="wfeb-btn wfeb-btn--sm wfeb-admin-upload-btn" data-target="#wfeb_logo">
								<?php esc_html_e( 'Upload Logo', 'wfeb' ); ?>
							</button>
							<?php if ( ! empty( $settings['wfeb_logo'] ) ) : ?>
								<div class="wfeb-logo-preview">
									<img src="<?php echo esc_url( $settings['wfeb_logo'] ); ?>" alt="<?php esc_attr_e( 'WFEB Logo', 'wfeb' ); ?>" />
								</div>
							<?php endif; ?>
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Logo displayed on certificates and dashboard.', 'wfeb' ); ?>
							</p>
						</div>

					</div>
				</div>

				<div class="wfeb-settings-save">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-admin-save-settings">
						<?php esc_html_e( 'Save Settings', 'wfeb' ); ?>
					</button>
				</div>
			</form>
			<?php endif; ?>

			<?php // ============================================================ ?>
			<?php // WOOCOMMERCE TAB ?>
			<?php // ============================================================ ?>
			<?php if ( 'woocommerce' === $active_tab ) : ?>
			<form method="post" action="" class="wfeb-settings-form" id="wfeb-settings-woocommerce">
				<?php wp_nonce_field( 'wfeb_save_settings', 'wfeb_settings_nonce' ); ?>
				<input type="hidden" name="wfeb_tab" value="woocommerce" />

				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h2><?php esc_html_e( 'WooCommerce Settings', 'wfeb' ); ?></h2>
					</div>
					<div class="wfeb-card-body">

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_credit_product_id" data-tooltip="WordPress post ID of the WooCommerce product for exam credits">
								<?php esc_html_e( 'Credit Product', 'wfeb' ); ?>
							</label>
							<input type="number" id="wfeb_credit_product_id" name="wfeb_credit_product_id"
								value="<?php echo esc_attr( $settings['credit_product_id'] ); ?>"
								class="wfeb-form-input" min="0" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'WooCommerce product ID used for certificate credit purchases.', 'wfeb' ); ?>
							</p>
							<?php
							if ( ! empty( $settings['credit_product_id'] ) && function_exists( 'wc_get_product' ) ) {
								$product = wc_get_product( absint( $settings['credit_product_id'] ) );
								if ( $product ) {
									echo '<p class="wfeb-form-description"><strong>'
										. esc_html__( 'Current product:', 'wfeb' )
										. '</strong> '
										. esc_html( $product->get_name() )
										. ' - ' . wp_kses_post( $product->get_price_html() )
										. '</p>';
								}
							}
							?>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_credit_price" data-tooltip="Sets the price per credit on the WooCommerce product and coach dashboard">
								<?php esc_html_e( 'Credit Price', 'wfeb' ); ?>
							</label>
							<input type="number" id="wfeb_credit_price" name="wfeb_credit_price"
								value="<?php echo esc_attr( $settings['credit_price'] ); ?>"
								class="wfeb-form-input"
								step="1" min="0"
								placeholder="<?php esc_attr_e( 'e.g., 25.00', 'wfeb' ); ?>" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Price per credit. Updates the WooCommerce product price and the coach dashboard display.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_credit_checkout_page_id"
								data-tooltip="The WordPress page that shows the custom credit checkout form">
								<?php esc_html_e( 'Credit Checkout Page', 'wfeb' ); ?>
							</label>
							<?php
							$pages = get_pages();
							?>
							<select id="wfeb_credit_checkout_page_id" name="wfeb_credit_checkout_page_id" class="wfeb-form-select">
								<option value=""><?php esc_html_e( '— Select a page —', 'wfeb' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo absint( $page->ID ); ?>"
										<?php selected( $settings['credit_checkout_page_id'], $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Checkout page for coaches.', 'wfeb' ); ?>
							</p>
						</div>

					</div>
				</div>

				<div class="wfeb-settings-save">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-admin-save-settings">
						<?php esc_html_e( 'Save Settings', 'wfeb' ); ?>
					</button>
				</div>
			</form>
			<?php endif; ?>

			<?php // ============================================================ ?>
			<?php // EMAIL TAB ?>
			<?php // ============================================================ ?>
			<?php if ( 'email' === $active_tab ) : ?>
			<form method="post" action="" class="wfeb-settings-form" id="wfeb-settings-email">
				<?php wp_nonce_field( 'wfeb_save_settings', 'wfeb_settings_nonce' ); ?>
				<input type="hidden" name="wfeb_tab" value="email" />

				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h2><?php esc_html_e( 'Email Settings', 'wfeb' ); ?></h2>
					</div>
					<div class="wfeb-card-body">

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_email_from_name">
								<?php esc_html_e( 'From Name', 'wfeb' ); ?>
							</label>
							<input type="text" id="wfeb_email_from_name" name="wfeb_email_from_name"
								value="<?php echo esc_attr( $settings['email_from_name'] ); ?>"
								class="wfeb-form-input" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Name that appears in the "From" field of emails sent by WFEB.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_email_from_address">
								<?php esc_html_e( 'From Email', 'wfeb' ); ?>
							</label>
							<input type="email" id="wfeb_email_from_address" name="wfeb_email_from_address"
								value="<?php echo esc_attr( $settings['email_from_address'] ); ?>"
								class="wfeb-form-input" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Email address used as the sender for all WFEB emails.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb-test-email-address">
								<?php esc_html_e( 'Test Email Address', 'wfeb' ); ?>
							</label>
							<input type="email" id="wfeb-test-email-address" class="wfeb-form-input"
								placeholder="<?php esc_attr_e( 'Enter email address for test...', 'wfeb' ); ?>" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Enter an email address to send a test message to, then click Send Test Email.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label"><?php esc_html_e( 'Test Email', 'wfeb' ); ?></label>
							<button type="button" id="wfeb-test-email-btn" class="wfeb-btn wfeb-btn--sm wfeb-admin-send-test-email">
								<?php esc_html_e( 'Send Test Email', 'wfeb' ); ?>
							</button>
							<span class="wfeb-admin-test-email-status"></span>
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Sends a test email to the address above to verify email configuration.', 'wfeb' ); ?>
							</p>
						</div>

					</div>
				</div>

				<div class="wfeb-settings-save">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-admin-save-settings">
						<?php esc_html_e( 'Save Settings', 'wfeb' ); ?>
					</button>
				</div>
			</form>
			<?php endif; ?>

			<?php // ============================================================ ?>
			<?php // EXAM TAB ?>
			<?php // ============================================================ ?>
			<?php if ( 'exam' === $active_tab ) : ?>
			<form method="post" action="" class="wfeb-settings-form" id="wfeb-settings-exam">
				<?php wp_nonce_field( 'wfeb_save_settings', 'wfeb_settings_nonce' ); ?>
				<input type="hidden" name="wfeb_tab" value="exam" />

				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h2><?php esc_html_e( 'Achievement Thresholds', 'wfeb' ); ?></h2>
					</div>
					<div class="wfeb-card-body">

						<p class="wfeb-form-description">
							<?php esc_html_e( 'Define the minimum score required for each achievement level. The exam total is out of 80.', 'wfeb' ); ?>
						</p>

						<table class="widefat wfeb-admin-table wfeb-admin-threshold-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Achievement Level', 'wfeb' ); ?></th>
									<th><?php esc_html_e( 'Playing Level', 'wfeb' ); ?></th>
									<th><?php esc_html_e( 'Minimum Score', 'wfeb' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$default_thresholds = array(
									array( 'level' => 'MASTERY',  'playing_level' => 'World Class',       'min' => 80 ),
									array( 'level' => 'DIAMOND',  'playing_level' => 'Professional',      'min' => 70 ),
									array( 'level' => 'GOLD',     'playing_level' => 'Semi-Professional',  'min' => 60 ),
									array( 'level' => 'SILVER',   'playing_level' => 'Advanced Amateur',   'min' => 50 ),
									array( 'level' => 'BRONZE',   'playing_level' => 'Amateur',            'min' => 40 ),
									array( 'level' => 'MERIT+',   'playing_level' => 'Intermediate',       'min' => 30 ),
									array( 'level' => 'MERIT',    'playing_level' => 'Developing',         'min' => 20 ),
									array( 'level' => 'MERIT-',   'playing_level' => 'Foundation Plus',    'min' => 15 ),
									array( 'level' => 'PASS+',    'playing_level' => 'Foundation',         'min' => 10 ),
									array( 'level' => 'PASS',     'playing_level' => 'Beginner',           'min' => 5 ),
								);

								$thresholds = ! empty( $settings['achievement_thresholds'] )
									? $settings['achievement_thresholds']
									: $default_thresholds;

								foreach ( $thresholds as $index => $threshold ) :
									$level         = isset( $threshold['level'] ) ? $threshold['level'] : '';
									$playing_level = isset( $threshold['playing_level'] ) ? $threshold['playing_level'] : '';
									$min_score     = isset( $threshold['min'] ) ? $threshold['min'] : 0;
								?>
								<tr>
									<td>
										<span class="wfeb-admin-badge" style="background-color: <?php echo esc_attr( wfeb_get_level_color( $level ) ); ?>;">
											<?php echo esc_html( $level ); ?>
										</span>
										<input type="hidden" name="thresholds[<?php echo esc_attr( $index ); ?>][level]"
											value="<?php echo esc_attr( $level ); ?>" />
									</td>
									<td>
										<?php echo esc_html( $playing_level ); ?>
										<input type="hidden" name="thresholds[<?php echo esc_attr( $index ); ?>][playing_level]"
											value="<?php echo esc_attr( $playing_level ); ?>" />
									</td>
									<td>
										<input type="number" name="thresholds[<?php echo esc_attr( $index ); ?>][min]"
											value="<?php echo esc_attr( $min_score ); ?>"
											class="wfeb-form-input" min="0" max="80" />
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

					</div>
				</div>

				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h2><?php esc_html_e( 'Scoring Reference Tables', 'wfeb' ); ?></h2>
					</div>
					<div class="wfeb-card-body">

						<div class="wfeb-admin-detail-grid">
							<div class="wfeb-admin-card">
								<h3><?php esc_html_e( 'Sprint Scoring', 'wfeb' ); ?></h3>
								<table class="widefat wfeb-admin-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Time (seconds)', 'wfeb' ); ?></th>
											<th><?php esc_html_e( 'Score', 'wfeb' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr><td>&lt; 5.5</td><td>10</td></tr>
										<tr><td>5.5 - 5.9</td><td>9</td></tr>
										<tr><td>6.0 - 6.4</td><td>8</td></tr>
										<tr><td>6.5 - 6.9</td><td>7</td></tr>
										<tr><td>7.0 - 7.4</td><td>6</td></tr>
										<tr><td>7.5 - 7.9</td><td>5</td></tr>
										<tr><td>8.0 - 8.4</td><td>4</td></tr>
										<tr><td>8.5 - 8.9</td><td>3</td></tr>
										<tr><td>9.0 - 9.4</td><td>2</td></tr>
										<tr><td>9.5 - 9.9</td><td>1</td></tr>
										<tr><td>10.0+</td><td>0</td></tr>
									</tbody>
								</table>
							</div>
							<div class="wfeb-admin-card">
								<h3><?php esc_html_e( 'Dribble Scoring', 'wfeb' ); ?></h3>
								<table class="widefat wfeb-admin-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Time (seconds)', 'wfeb' ); ?></th>
											<th><?php esc_html_e( 'Score', 'wfeb' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr><td>&lt; 4.0</td><td>10</td></tr>
										<tr><td>4.0 - 4.4</td><td>9</td></tr>
										<tr><td>4.5 - 4.9</td><td>8</td></tr>
										<tr><td>5.0 - 5.4</td><td>7</td></tr>
										<tr><td>5.5 - 5.9</td><td>6</td></tr>
										<tr><td>6.0 - 6.4</td><td>5</td></tr>
										<tr><td>6.5 - 6.9</td><td>4</td></tr>
										<tr><td>7.0 - 7.4</td><td>3</td></tr>
										<tr><td>7.5 - 7.9</td><td>2</td></tr>
										<tr><td>8.0 - 8.4</td><td>1</td></tr>
										<tr><td>8.5+</td><td>0</td></tr>
									</tbody>
								</table>
							</div>
							<div class="wfeb-admin-card">
								<h3><?php esc_html_e( 'Kickups Scoring', 'wfeb' ); ?></h3>
								<table class="widefat wfeb-admin-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Count (best of 3)', 'wfeb' ); ?></th>
											<th><?php esc_html_e( 'Score', 'wfeb' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr><td>100+</td><td>10</td></tr>
										<tr><td>90 - 99</td><td>9</td></tr>
										<tr><td>75 - 89</td><td>8</td></tr>
										<tr><td>60 - 74</td><td>7</td></tr>
										<tr><td>45 - 59</td><td>6</td></tr>
										<tr><td>30 - 44</td><td>5</td></tr>
										<tr><td>15 - 29</td><td>4</td></tr>
										<tr><td>10 - 14</td><td>3</td></tr>
										<tr><td>5 - 9</td><td>2</td></tr>
										<tr><td>3 - 4</td><td>1</td></tr>
										<tr><td>&lt; 3</td><td>0</td></tr>
									</tbody>
								</table>
							</div>
						</div>

					</div>
				</div>

				<div class="wfeb-settings-save">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-admin-save-settings">
						<?php esc_html_e( 'Save Settings', 'wfeb' ); ?>
					</button>
				</div>
			</form>
			<?php endif; ?>

			<?php // ============================================================ ?>
			<?php // CERTIFICATE TAB ?>
			<?php // ============================================================ ?>
			<?php if ( 'certificate' === $active_tab ) : ?>
			<form method="post" action="" class="wfeb-settings-form" id="wfeb-settings-certificate">
				<?php wp_nonce_field( 'wfeb_save_settings', 'wfeb_settings_nonce' ); ?>
				<input type="hidden" name="wfeb_tab" value="certificate" />

				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h2><?php esc_html_e( 'Certificate Settings', 'wfeb' ); ?></h2>
					</div>
					<div class="wfeb-card-body">

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_cert_background">
								<?php esc_html_e( 'Certificate Background Image', 'wfeb' ); ?>
							</label>
							<input type="url" id="wfeb_cert_background" name="wfeb_cert_background"
								value="<?php echo esc_attr( $settings['cert_background'] ); ?>"
								class="wfeb-form-input" />
							<button type="button" class="wfeb-btn wfeb-btn--sm wfeb-admin-upload-btn" data-target="#wfeb_cert_background">
								<?php esc_html_e( 'Upload Image', 'wfeb' ); ?>
							</button>
							<?php if ( ! empty( $settings['cert_background'] ) ) : ?>
								<button type="button" class="wfeb-btn wfeb-btn--sm wfeb-btn--danger wfeb-admin-remove-cert-bg"
									data-tooltip="Remove custom background and use the default certificate design">
									<?php esc_html_e( 'Remove', 'wfeb' ); ?>
								</button>
								<div class="wfeb-cert-bg-preview">
									<img src="<?php echo esc_url( $settings['cert_background'] ); ?>"
										alt="<?php esc_attr_e( 'Certificate Background', 'wfeb' ); ?>" />
								</div>
							<?php endif; ?>
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Background image for the generated PDF certificate. Recommended size: 842 x 595 px (A4 landscape).', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label" for="wfeb_cert_authoriser_name" data-tooltip="Name printed on every certificate as the official signatory">
								<?php esc_html_e( 'Authoriser Name', 'wfeb' ); ?>
							</label>
							<input type="text" id="wfeb_cert_authoriser_name" name="wfeb_cert_authoriser_name"
								value="<?php echo esc_attr( $settings['cert_authoriser_name'] ); ?>"
								class="wfeb-form-input" />
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Name of the person authorising certificates. Appears on the printed certificate.', 'wfeb' ); ?>
							</p>
						</div>

						<div class="wfeb-form-row">
							<label class="wfeb-form-label"><?php esc_html_e( 'Preview', 'wfeb' ); ?></label>
							<button type="button" class="wfeb-btn wfeb-btn--sm wfeb-admin-preview-certificate">
								<?php esc_html_e( 'Preview Certificate', 'wfeb' ); ?>
							</button>
							<span class="wfeb-admin-preview-status"></span>
							<p class="wfeb-form-description">
								<?php esc_html_e( 'Generates a sample certificate preview with placeholder data.', 'wfeb' ); ?>
							</p>
						</div>

					</div>
				</div>

				<div class="wfeb-settings-save">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-admin-save-settings">
						<?php esc_html_e( 'Save Settings', 'wfeb' ); ?>
					</button>
				</div>
			</form>
			<?php endif; ?>

		</div><!-- .wfeb-settings-content -->

	</div><!-- .wfeb-settings-wrap -->

</div><!-- .wfeb-wrap -->
