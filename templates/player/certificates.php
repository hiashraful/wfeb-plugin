<?php
/**
 * Template: Player Dashboard - My Certificates Section
 *
 * Displays the player's certificates in grid or list view with
 * toggle buttons, achievement level badges, and download actions.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Player data.
$player    = WFEB()->player_dashboard->get_player_data();
$player_id = wfeb_get_player_id();

// Fetch certificates.
$certificates = WFEB()->certificate->get_by_player( $player_id, array( 'limit' => 100 ) );

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
?>

<?php if ( ! empty( $certificates ) ) : ?>

	<!-- View Toggle -->
	<div class="wfeb-flex-between wfeb-mb-24">
		<div class="wfeb-text-muted" style="font-size: 14px;">
			<?php
			printf(
				/* translators: %d: number of certificates */
				esc_html( _n( '%d certificate', '%d certificates', count( $certificates ), 'wfeb' ) ),
				count( $certificates )
			);
			?>
		</div>
		<div class="wfeb-flex wfeb-gap-8">
			<button type="button" class="wfeb-btn wfeb-btn--sm wfeb-btn--primary" id="wfeb-view-grid" aria-label="<?php echo esc_attr__( 'Grid view', 'wfeb' ); ?>">
				<span class="dashicons dashicons-grid-view"></span>
				<?php echo esc_html__( 'Grid', 'wfeb' ); ?>
			</button>
			<button type="button" class="wfeb-btn wfeb-btn--sm wfeb-btn--secondary" id="wfeb-view-list" aria-label="<?php echo esc_attr__( 'List view', 'wfeb' ); ?>">
				<span class="dashicons dashicons-list-view"></span>
				<?php echo esc_html__( 'List', 'wfeb' ); ?>
			</button>
		</div>
	</div>

	<!-- Grid View -->
	<div class="wfeb-cert-grid" id="wfeb-cert-grid">
		<?php foreach ( $certificates as $cert ) :
			$level_lower = sanitize_title( $cert->achievement_level );
			$detail_url  = add_query_arg(
				array(
					'section' => 'certificate-detail',
					'cert_id' => absint( $cert->id ),
				),
				$base_url
			);
		?>
			<div class="wfeb-cert-card wfeb-cert-card--<?php echo esc_attr( $level_lower ); ?>">
				<div class="wfeb-cert-card-level">
					<span
						class="wfeb-badge wfeb-badge--lg"
						style="background-color: <?php echo esc_attr( wfeb_get_level_color( $cert->achievement_level ) ); ?>; color: #fff;"
					>
						<?php echo esc_html( $cert->achievement_level ); ?>
					</span>
				</div>

				<div class="wfeb-cert-card-score">
					<span class="wfeb-cert-card-score-value"><?php echo esc_html( $cert->total_score ); ?></span>
					<span class="wfeb-cert-card-score-max">/80</span>
				</div>

				<div class="wfeb-cert-card-info">
					<div class="wfeb-cert-card-info-row">
						<span class="wfeb-cert-card-info-label"><?php echo esc_html__( 'Certificate', 'wfeb' ); ?></span>
						<span class="wfeb-cert-card-info-value"><?php echo esc_html( $cert->certificate_number ); ?></span>
					</div>
					<div class="wfeb-cert-card-info-row">
						<span class="wfeb-cert-card-info-label"><?php echo esc_html__( 'Date', 'wfeb' ); ?></span>
						<span class="wfeb-cert-card-info-value"><?php echo esc_html( wfeb_format_date( $cert->issued_at ) ); ?></span>
					</div>
					<div class="wfeb-cert-card-info-row">
						<span class="wfeb-cert-card-info-label"><?php echo esc_html__( 'Examiner', 'wfeb' ); ?></span>
						<span class="wfeb-cert-card-info-value"><?php echo esc_html( $cert->coach_name ); ?></span>
					</div>
				</div>

				<div class="wfeb-cert-card-actions">
					<?php if ( ! empty( $cert->pdf_url ) ) : ?>
						<a href="<?php echo esc_url( $cert->pdf_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--primary" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-download"></span>
							<?php echo esc_html__( 'Download', 'wfeb' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $detail_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--secondary">
						<?php echo esc_html__( 'Details', 'wfeb' ); ?>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- List View (hidden by default) -->
	<div class="wfeb-card" id="wfeb-cert-list" style="display: none;">
		<div class="wfeb-card-body" style="padding: 0;">
			<div class="wfeb-table-wrap">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Cert #', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Date', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Score', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Level', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Examiner', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Action', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $certificates as $cert ) :
							$detail_url = add_query_arg(
								array(
									'section' => 'certificate-detail',
									'cert_id' => absint( $cert->id ),
								),
								$base_url
							);
						?>
							<tr style="cursor: pointer;" onclick="window.location.href='<?php echo esc_url( $detail_url ); ?>'">
								<td><strong><?php echo esc_html( $cert->certificate_number ); ?></strong></td>
								<td><?php echo esc_html( wfeb_format_date( $cert->issued_at ) ); ?></td>
								<td><?php echo esc_html( $cert->total_score . '/80' ); ?></td>
								<td>
									<span
										class="wfeb-badge"
										style="background-color: <?php echo esc_attr( wfeb_get_level_color( $cert->achievement_level ) ); ?>; color: #fff;"
									>
										<?php echo esc_html( $cert->achievement_level ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $cert->coach_name ); ?></td>
								<td>
									<?php if ( ! empty( $cert->pdf_url ) ) : ?>
										<a href="<?php echo esc_url( $cert->pdf_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--primary" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">
											<span class="dashicons dashicons-download"></span>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

<?php else : ?>

	<!-- Empty State -->
	<div class="wfeb-card">
		<div class="wfeb-card-body">
			<div class="wfeb-empty-state">
				<div class="wfeb-empty-state-icon">
					<span class="dashicons dashicons-awards"></span>
				</div>
				<p class="wfeb-empty-state-title"><?php echo esc_html__( 'No Certificates Yet', 'wfeb' ); ?></p>
				<p class="wfeb-empty-state-text"><?php echo esc_html__( 'Your certificates will appear here after your coach conducts an exam and generates a certificate for you.', 'wfeb' ); ?></p>
			</div>
		</div>
	</div>

<?php endif; ?>
