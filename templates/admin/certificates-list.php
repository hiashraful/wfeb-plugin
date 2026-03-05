<?php
/**
 * WFEB Admin Certificates List Template
 *
 * Displays the certificates list with filters, search, and pagination.
 *
 * Variables available from WFEB_Admin_Certificates::render_list():
 * - $certificates, $total_items, $total_pages, $paged, $per_page
 * - $search, $status, $date_from, $date_to
 * - $count_all, $count_active, $count_revoked
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url = admin_url( 'admin.php?page=wfeb-certificates' );
?>
<div class="wfeb-wrap">

	<!-- Page Header -->
	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'Certificates', 'wfeb' ); ?></h1>
	</div>

	<!-- Table Card -->
	<div class="wfeb-table-card">

		<!-- Filter / Search Bar -->
		<form method="get" class="wfeb-filter-bar">
			<input type="hidden" name="page" value="wfeb-certificates" />
			<?php if ( ! empty( $status ) ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>" />
			<?php endif; ?>

			<div class="wfeb-filter-group">
				<label for="wfeb-cert-search" class="screen-reader-text">
					<?php esc_html_e( 'Search Certificates', 'wfeb' ); ?>
				</label>
				<div class="wfeb-search-box">
					<input
						type="search"
						id="wfeb-cert-search"
						name="s"
						class="wfeb-search-input"
						value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php esc_attr_e( 'Player, coach, or cert #...', 'wfeb' ); ?>"
					/>
				</div>
			</div>

			<div class="wfeb-filter-group">
				<label for="wfeb-cert-date-from" class="screen-reader-text">
					<?php esc_html_e( 'From Date', 'wfeb' ); ?>
				</label>
				<input
					type="date"
					id="wfeb-cert-date-from"
					name="date_from"
					class="wfeb-search-input"
					value="<?php echo esc_attr( $date_from ); ?>"
					placeholder="<?php esc_attr_e( 'From', 'wfeb' ); ?>"
				/>
			</div>

			<div class="wfeb-filter-group">
				<label for="wfeb-cert-date-to" class="screen-reader-text">
					<?php esc_html_e( 'To Date', 'wfeb' ); ?>
				</label>
				<input
					type="date"
					id="wfeb-cert-date-to"
					name="date_to"
					class="wfeb-search-input"
					value="<?php echo esc_attr( $date_to ); ?>"
					placeholder="<?php esc_attr_e( 'To', 'wfeb' ); ?>"
				/>
			</div>

			<div class="wfeb-filter-group">
				<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm">
					<?php esc_html_e( 'Filter', 'wfeb' ); ?>
				</button>
				<a href="<?php echo esc_url( $base_url ); ?>" class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
					<?php esc_html_e( 'Reset', 'wfeb' ); ?>
				</a>
			</div>
		</form>

		<!-- Table Header -->
		<div class="wfeb-table-header">
			<span class="wfeb-table-title">
				<?php
				if ( 'active' === $status ) {
					/* translators: %s: count */
					printf( esc_html__( 'Active Certificates (%s)', 'wfeb' ), esc_html( $count_active ) );
				} elseif ( 'revoked' === $status ) {
					/* translators: %s: count */
					printf( esc_html__( 'Revoked Certificates (%s)', 'wfeb' ), esc_html( $count_revoked ) );
				} else {
					/* translators: %s: count */
					printf( esc_html__( 'All Certificates (%s)', 'wfeb' ), esc_html( $count_all ) );
				}
				?>
			</span>

			<div class="wfeb-filter-tabs">
				<a
					href="<?php echo esc_url( $base_url ); ?>"
					class="wfeb-btn wfeb-btn--sm <?php echo empty( $status ) ? 'wfeb-btn--primary' : 'wfeb-btn--ghost'; ?>">
					<?php
					/* translators: %s: count */
					printf( esc_html__( 'All (%s)', 'wfeb' ), esc_html( $count_all ) );
					?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'status', 'active', $base_url ) ); ?>"
					class="wfeb-btn wfeb-btn--sm <?php echo 'active' === $status ? 'wfeb-btn--primary' : 'wfeb-btn--ghost'; ?>">
					<?php
					/* translators: %s: count */
					printf( esc_html__( 'Active (%s)', 'wfeb' ), esc_html( $count_active ) );
					?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'status', 'revoked', $base_url ) ); ?>"
					class="wfeb-btn wfeb-btn--sm <?php echo 'revoked' === $status ? 'wfeb-btn--primary' : 'wfeb-btn--ghost'; ?>">
					<?php
					/* translators: %s: count */
					printf( esc_html__( 'Revoked (%s)', 'wfeb' ), esc_html( $count_revoked ) );
					?>
				</a>
			</div>
		</div>

		<!-- Certificates Table -->
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Certificate #', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Player', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Coach', 'wfeb' ); ?></th>
					<th data-tooltip="Achievement level of the exam for this certificate"><?php esc_html_e( 'Level', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Issue Date', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wfeb' ); ?></th>
					<th class="col-actions"><?php esc_html_e( 'Actions', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $certificates ) ) : ?>
				<tr>
					<td colspan="7" class="wfeb-table-empty">
						<?php esc_html_e( 'No certificates found.', 'wfeb' ); ?>
					</td>
				</tr>
				<?php else : ?>
					<?php foreach ( $certificates as $cert ) : ?>
					<tr data-cert-row="<?php echo absint( $cert->id ); ?>">
						<td><strong><?php echo esc_html( $cert->certificate_number ); ?></strong></td>
						<td><?php echo esc_html( $cert->player_name ); ?></td>
						<td>
							<?php if ( ! empty( $cert->coach_name ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $cert->coach_id ) ) ); ?>">
									<?php echo esc_html( $cert->coach_name ); ?>
								</a>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $cert->achievement_level ) ) : ?>
								<span class="wfeb-badge wfeb-badge--level">
									<?php echo esc_html( $cert->achievement_level ); ?>
								</span>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( wfeb_format_date( $cert->issued_at ) ); ?></td>
						<td>
							<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $cert->status ); ?>">
								<?php echo esc_html( ucfirst( $cert->status ) ); ?>
							</span>
						</td>
						<td class="col-actions">
							<div class="wfeb-row-actions">
								<?php if ( ! empty( $cert->pdf_url ) ) : ?>
									<a
										href="<?php echo esc_url( $cert->pdf_url ); ?>"
										target="_blank"
										rel="noopener noreferrer"
										class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
										<?php esc_html_e( 'View', 'wfeb' ); ?>
									</a>
								<?php endif; ?>

								<?php if ( ! empty( $cert->exam_id ) ) : ?>
									<a
										href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-exams&exam_id=' . absint( $cert->exam_id ) ) ); ?>"
										data-tooltip="View the full exam record for this certificate"
										class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
										<?php esc_html_e( 'Exam', 'wfeb' ); ?>
									</a>
								<?php endif; ?>

								<?php if ( 'active' === $cert->status ) : ?>
									<button
										type="button"
										class="wfeb-btn wfeb-btn--danger wfeb-btn--sm wfeb-revoke-certificate"
										data-cert-id="<?php echo absint( $cert->id ); ?>"
										data-cert-number="<?php echo esc_attr( $cert->certificate_number ); ?>"
										data-tooltip="Permanently invalidate this certificate. Cannot be undone"
										data-tooltip-pos="right">
										<?php esc_html_e( 'Revoke', 'wfeb' ); ?>
									</button>
								<?php endif; ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
		<div class="wfeb-pagination">
			<span class="wfeb-pagination-info">
				<?php
				/* translators: 1: current page, 2: total pages */
				printf( esc_html__( 'Page %1$s of %2$s', 'wfeb' ), esc_html( $paged ), esc_html( $total_pages ) );
				?>
			</span>

			<?php if ( $paged > 1 ) : ?>
				<a
					class="wfeb-page-btn"
					href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>"
					aria-label="<?php esc_attr_e( 'Previous page', 'wfeb' ); ?>">
					&lsaquo;
				</a>
			<?php else : ?>
				<span class="wfeb-page-btn disabled" aria-disabled="true">&lsaquo;</span>
			<?php endif; ?>

			<?php
			$start_page = max( 1, $paged - 2 );
			$end_page   = min( $total_pages, $paged + 2 );
			for ( $i = $start_page; $i <= $end_page; $i++ ) :
			?>
				<?php if ( $i === $paged ) : ?>
					<span class="wfeb-page-btn active" aria-current="page"><?php echo esc_html( $i ); ?></span>
				<?php else : ?>
					<a
						class="wfeb-page-btn"
						href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>">
						<?php echo esc_html( $i ); ?>
					</a>
				<?php endif; ?>
			<?php endfor; ?>

			<?php if ( $paged < $total_pages ) : ?>
				<a
					class="wfeb-page-btn"
					href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>"
					aria-label="<?php esc_attr_e( 'Next page', 'wfeb' ); ?>">
					&rsaquo;
				</a>
			<?php else : ?>
				<span class="wfeb-page-btn disabled" aria-disabled="true">&rsaquo;</span>
			<?php endif; ?>
		</div>
		<?php endif; ?>

	</div><!-- /.wfeb-table-card -->

</div><!-- /.wfeb-wrap -->
