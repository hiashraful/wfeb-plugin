<?php
/**
 * WFEB Admin Coach Detail Template
 *
 * Displays a single coach's detailed information, action buttons,
 * players list, and recent exams.
 *
 * Variables available from WFEB_Admin_Coaches::render_detail():
 * - $coach, $players, $recent_exams, $transactions
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wfeb-wrap">

	<!-- Back Link -->
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches' ) ); ?>"
		class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm wfeb-back-link">
		&larr; <?php esc_html_e( 'Back to Coaches', 'wfeb' ); ?>
	</a>

	<!-- Page Header -->
	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title">
			<?php echo esc_html( $coach->full_name ); ?>
			<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $coach->status ); ?>">
				<?php echo esc_html( ucfirst( $coach->status ) ); ?>
			</span>
		</h1>
		<div class="wfeb-page-actions">
			<?php if ( 'pending' === $coach->status ) : ?>
				<button type="button"
					class="wfeb-btn wfeb-btn--primary wfeb-approve-coach"
					data-coach-id="<?php echo absint( $coach->id ); ?>"
					data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
					data-tooltip="Grant this coach access to submit player exams">
					<?php esc_html_e( 'Approve Coach', 'wfeb' ); ?>
				</button>
				<button type="button"
					class="wfeb-btn wfeb-btn--ghost wfeb-reject-coach"
					data-coach-id="<?php echo absint( $coach->id ); ?>"
					data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
					data-tooltip="Deny this coach's registration">
					<?php esc_html_e( 'Reject Coach', 'wfeb' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( 'approved' === $coach->status ) : ?>
				<button type="button"
					class="wfeb-btn wfeb-btn--warning wfeb-suspend-coach"
					data-coach-id="<?php echo absint( $coach->id ); ?>"
					data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
					data-tooltip="Temporarily disable this coach's access. Data is preserved">
					<?php esc_html_e( 'Suspend Coach', 'wfeb' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( 'suspended' === $coach->status || 'rejected' === $coach->status ) : ?>
				<button type="button"
					class="wfeb-btn wfeb-btn--primary wfeb-approve-coach"
					data-coach-id="<?php echo absint( $coach->id ); ?>"
					data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
					data-tooltip="Grant this coach access to submit player exams">
					<?php esc_html_e( 'Re-approve Coach', 'wfeb' ); ?>
				</button>
			<?php endif; ?>

			<button type="button"
				class="wfeb-btn wfeb-btn--danger wfeb-remove-coach"
				data-coach-id="<?php echo absint( $coach->id ); ?>"
				data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
				data-tooltip="Permanently delete this coach and all their data">
				<?php esc_html_e( 'Remove Coach', 'wfeb' ); ?>
			</button>
		</div>
	</div>

	<!-- Two-column detail layout: info card (left) + sidebar cards (right) -->
	<div class="wfeb-detail-grid">

		<!-- Left column: Coach Information -->
		<div>
			<div class="wfeb-card">
				<div class="wfeb-card-header">
					<h2 class="wfeb-card-title"><?php esc_html_e( 'Coach Information', 'wfeb' ); ?></h2>
				</div>

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Full Name', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $coach->full_name ); ?></span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Email', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $coach->email ); ?></span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Phone', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $coach->phone ); ?></span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Address', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $coach->address ); ?></span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label" data-tooltip="National Governing Body registration number"><?php esc_html_e( 'NGB Number', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $coach->ngb_number ); ?></span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Country', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<?php echo isset( $coach->country ) ? esc_html( $coach->country ) : '&mdash;'; ?>
					</span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Date of Birth', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $coach->dob ) ); ?></span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Status', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $coach->status ); ?>">
							<?php echo esc_html( ucfirst( $coach->status ) ); ?>
						</span>
					</span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label" data-tooltip="Exam credits available. Each exam costs 1 credit"><?php esc_html_e( 'Credits Balance', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<strong><?php echo esc_html( $coach->credits_balance ); ?></strong>
					</span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Date Registered', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<?php echo esc_html( wfeb_format_date( $coach->created_at, 'j M Y H:i' ) ); ?>
					</span>
				</div>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label" data-tooltip="Official coaching certification uploaded during registration"><?php esc_html_e( 'Coaching Certificate', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<?php if ( ! empty( $coach->coaching_certificate ) ) : ?>
							<a href="<?php echo esc_url( $coach->coaching_certificate ); ?>"
								target="_blank"
								rel="noopener noreferrer">
								<?php esc_html_e( 'View Certificate File', 'wfeb' ); ?>
							</a>
						<?php else : ?>
							<span class="wfeb-text-muted"><?php esc_html_e( 'Not uploaded', 'wfeb' ); ?></span>
						<?php endif; ?>
					</span>
				</div>

				<?php if ( 'rejected' === $coach->status && ! empty( $coach->rejection_reason ) ) : ?>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Rejection Reason', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value wfeb-text-danger">
						<?php echo esc_html( $coach->rejection_reason ); ?>
					</span>
				</div>
				<?php endif; ?>

			</div><!-- /.wfeb-card (info) -->
		</div><!-- /left column -->

		<!-- Right column: Actions + Credit Adjustment -->
		<div>

			<!-- Actions Card -->
			<div class="wfeb-card">
				<div class="wfeb-card-header">
					<h2 class="wfeb-card-title"><?php esc_html_e( 'Actions', 'wfeb' ); ?></h2>
				</div>

				<div class="wfeb-action-panel">
					<p class="wfeb-action-panel-title"><?php esc_html_e( 'Coach Status', 'wfeb' ); ?></p>
					<div class="wfeb-action-buttons">
						<?php if ( 'pending' === $coach->status ) : ?>
							<button type="button"
								class="wfeb-btn wfeb-btn--primary wfeb-approve-coach"
								data-coach-id="<?php echo absint( $coach->id ); ?>"
								data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
								data-tooltip="Grant this coach access to submit player exams">
								<?php esc_html_e( 'Approve Coach', 'wfeb' ); ?>
							</button>
							<button type="button"
								class="wfeb-btn wfeb-btn--ghost wfeb-reject-coach"
								data-coach-id="<?php echo absint( $coach->id ); ?>"
								data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
								data-tooltip="Deny this coach's registration">
								<?php esc_html_e( 'Reject Coach', 'wfeb' ); ?>
							</button>
						<?php endif; ?>

						<?php if ( 'approved' === $coach->status ) : ?>
							<button type="button"
								class="wfeb-btn wfeb-btn--warning wfeb-suspend-coach"
								data-coach-id="<?php echo absint( $coach->id ); ?>"
								data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
								data-tooltip="Temporarily disable this coach's access. Data is preserved">
								<?php esc_html_e( 'Suspend Coach', 'wfeb' ); ?>
							</button>
						<?php endif; ?>

						<?php if ( 'suspended' === $coach->status || 'rejected' === $coach->status ) : ?>
							<button type="button"
								class="wfeb-btn wfeb-btn--primary wfeb-approve-coach"
								data-coach-id="<?php echo absint( $coach->id ); ?>"
								data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
								data-tooltip="Grant this coach access to submit player exams">
								<?php esc_html_e( 'Re-approve Coach', 'wfeb' ); ?>
							</button>
						<?php endif; ?>

						<button type="button"
							class="wfeb-btn wfeb-btn--danger wfeb-remove-coach"
							data-coach-id="<?php echo absint( $coach->id ); ?>"
							data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
							data-tooltip="Permanently delete this coach and all their data">
							<?php esc_html_e( 'Remove Coach', 'wfeb' ); ?>
						</button>
					</div>
				</div>
			</div><!-- /.wfeb-card (actions) -->

			<!-- Credit Adjustment Card -->
			<div class="wfeb-card">
				<div class="wfeb-card-header">
					<h2 class="wfeb-card-title"><?php esc_html_e( 'Adjust Credits', 'wfeb' ); ?></h2>
				</div>

				<div class="wfeb-credits-bar">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Current Balance', 'wfeb' ); ?></span>
					<strong class="wfeb-stat-number">
						<?php echo esc_html( $coach->credits_balance ); ?>
					</strong>
				</div>

				<form class="wfeb-admin-credit-form" method="post">
					<?php wp_nonce_field( 'wfeb_admin_adjust_credits', 'wfeb_credit_nonce' ); ?>
					<input type="hidden" name="coach_id" value="<?php echo absint( $coach->id ); ?>" />

					<div class="wfeb-credit-adjust-row">
						<input
							type="number"
							id="wfeb-credit-amount"
							name="credit_amount"
							value="0"
							min="-999"
							max="999"
							aria-label="<?php esc_attr_e( 'Credit adjustment amount', 'wfeb' ); ?>"
						/>
						<input
							type="text"
							id="wfeb-credit-reason"
							name="credit_reason"
							value=""
							placeholder="<?php esc_attr_e( 'Reason for adjustment...', 'wfeb' ); ?>"
						/>
						<button
							type="submit"
							class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-admin-adjust-credits-btn"
							data-coach-id="<?php echo absint( $coach->id ); ?>"
							data-tooltip="Apply this credit change to the coach's balance immediately">
							<?php esc_html_e( 'Adjust', 'wfeb' ); ?>
						</button>
					</div>
					<p class="wfeb-text-muted wfeb-text-hint">
						<?php esc_html_e( 'Enter a positive number to add credits, or negative to deduct.', 'wfeb' ); ?>
					</p>
				</form>
			</div><!-- /.wfeb-card (credits) -->

		</div><!-- /right column -->

	</div><!-- /.wfeb-detail-grid -->


	<!-- Players Section -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h2 class="wfeb-card-title">
				<?php esc_html_e( 'Players', 'wfeb' ); ?>
				<span class="wfeb-tab-count"><?php echo esc_html( count( $players ) ); ?></span>
			</h2>
		</div>

		<?php if ( empty( $players ) ) : ?>
			<p class="wfeb-text-muted"><?php esc_html_e( 'No players registered by this coach.', 'wfeb' ); ?></p>
		<?php else : ?>
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Date of Birth', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Email', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $players as $player ) : ?>
				<tr>
					<td><?php echo esc_html( $player->full_name ); ?></td>
					<td><?php echo esc_html( wfeb_format_date( $player->dob ) ); ?></td>
					<td><?php echo esc_html( $player->email ); ?></td>
					<td><?php echo esc_html( $player->phone ); ?></td>
					<td><?php echo esc_html( wfeb_format_date( $player->created_at ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div><!-- /.wfeb-card (players) -->


	<!-- Recent Exams Section -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h2 class="wfeb-card-title"><?php esc_html_e( 'Recent Exams', 'wfeb' ); ?></h2>
		</div>

		<?php if ( empty( $recent_exams ) ) : ?>
			<p class="wfeb-text-muted"><?php esc_html_e( 'No exams conducted by this coach yet.', 'wfeb' ); ?></p>
		<?php else : ?>
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Player', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Score', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Level', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wfeb' ); ?></th>
					<th class="col-actions"><?php esc_html_e( 'Actions', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent_exams as $exam ) : ?>
				<tr>
					<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
					<td><?php echo esc_html( $exam->player_name ); ?></td>
					<td><?php echo esc_html( $exam->total_score ); ?>/80</td>
					<td>
						<span class="wfeb-badge" style="background-color:<?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>;color:#fff;">
							<?php echo esc_html( $exam->achievement_level ); ?>
						</span>
					</td>
					<td>
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
							<?php echo esc_html( ucfirst( $exam->status ) ); ?>
						</span>
					</td>
					<td class="col-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-exams&exam_id=' . absint( $exam->id ) ) ); ?>"
							class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
							<?php esc_html_e( 'View', 'wfeb' ); ?>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div><!-- /.wfeb-card (exams) -->

</div><!-- /.wfeb-wrap -->
