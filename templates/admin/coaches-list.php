<?php
/**
 * WFEB Admin Coaches List Template
 *
 * Displays the coaches list with status tabs, search, table, and pagination.
 *
 * Variables available from WFEB_Admin_Coaches::render_list():
 * - $coaches, $total_items, $total_pages, $paged, $per_page
 * - $status, $search
 * - $count_all, $count_pending, $count_approved, $count_rejected, $count_suspended
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url = admin_url( 'admin.php?page=wfeb-coaches' );
?>
<div class="wfeb-wrap">

	<!-- Page Header -->
	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'Coaches', 'wfeb' ); ?></h1>
	</div>

	<!-- Stats Row -->
	<div class="wfeb-stats-grid">
		<div class="wfeb-stat-card">
			<span class="wfeb-stat-label"><?php esc_html_e( 'Total', 'wfeb' ); ?></span>
			<span class="wfeb-stat-number"><?php echo esc_html( $count_all ); ?></span>
		</div>
		<div class="wfeb-stat-card">
			<span class="wfeb-stat-label"><?php esc_html_e( 'Approved', 'wfeb' ); ?></span>
			<span class="wfeb-stat-number"><?php echo esc_html( $count_approved ); ?></span>
		</div>
		<div class="wfeb-stat-card">
			<span class="wfeb-stat-label"><?php esc_html_e( 'Pending', 'wfeb' ); ?></span>
			<span class="wfeb-stat-number"><?php echo esc_html( $count_pending ); ?></span>
		</div>
		<div class="wfeb-stat-card">
			<span class="wfeb-stat-label"><?php esc_html_e( 'Rejected', 'wfeb' ); ?></span>
			<span class="wfeb-stat-number"><?php echo esc_html( $count_rejected ); ?></span>
		</div>
		<div class="wfeb-stat-card">
			<span class="wfeb-stat-label"><?php esc_html_e( 'Suspended', 'wfeb' ); ?></span>
			<span class="wfeb-stat-number"><?php echo esc_html( $count_suspended ); ?></span>
		</div>
	</div>

	<!-- Table Card -->
	<div class="wfeb-table-card">

		<!-- Status Tabs -->
		<ul class="wfeb-status-tabs">
			<li>
				<a href="<?php echo esc_url( $base_url ); ?>"
					class="<?php echo empty( $status ) ? 'current' : ''; ?>">
					<?php esc_html_e( 'All', 'wfeb' ); ?>
					<span class="wfeb-tab-count"><?php echo esc_html( $count_all ); ?></span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'pending', $base_url ) ); ?>"
					class="<?php echo 'pending' === $status ? 'current' : ''; ?>">
					<?php esc_html_e( 'Pending', 'wfeb' ); ?>
					<span class="wfeb-tab-count"><?php echo esc_html( $count_pending ); ?></span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'approved', $base_url ) ); ?>"
					class="<?php echo 'approved' === $status ? 'current' : ''; ?>">
					<?php esc_html_e( 'Approved', 'wfeb' ); ?>
					<span class="wfeb-tab-count"><?php echo esc_html( $count_approved ); ?></span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'rejected', $base_url ) ); ?>"
					class="<?php echo 'rejected' === $status ? 'current' : ''; ?>">
					<?php esc_html_e( 'Rejected', 'wfeb' ); ?>
					<span class="wfeb-tab-count"><?php echo esc_html( $count_rejected ); ?></span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'suspended', $base_url ) ); ?>"
					class="<?php echo 'suspended' === $status ? 'current' : ''; ?>">
					<?php esc_html_e( 'Suspended', 'wfeb' ); ?>
					<span class="wfeb-tab-count"><?php echo esc_html( $count_suspended ); ?></span>
				</a>
			</li>
		</ul>

		<!-- Filter / Search Bar -->
		<form method="get" class="wfeb-filter-bar">
			<input type="hidden" name="page" value="wfeb-coaches" />
			<?php if ( ! empty( $status ) ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>" />
			<?php endif; ?>

			<div class="wfeb-filter-group">
				<label for="wfeb-coach-search" class="screen-reader-text">
					<?php esc_html_e( 'Search Coaches', 'wfeb' ); ?>
				</label>
				<div class="wfeb-search-box">
					<input
						type="search"
						id="wfeb-coach-search"
						name="s"
						class="wfeb-search-input"
						value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php esc_attr_e( 'Search by name, email, or NGB#...', 'wfeb' ); ?>"
					/>
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-search-btn">
						<?php esc_html_e( 'Search', 'wfeb' ); ?>
					</button>
				</div>
			</div>
		</form>

		<!-- Table Header -->
		<div class="wfeb-table-header">
			<span class="wfeb-table-title">
				<?php
				if ( ! empty( $search ) ) {
					/* translators: %s: search term */
					printf( esc_html__( 'Results for &ldquo;%s&rdquo;', 'wfeb' ), esc_html( $search ) );
				} elseif ( ! empty( $status ) ) {
					echo esc_html( ucfirst( $status ) ) . ' ' . esc_html__( 'Coaches', 'wfeb' );
				} else {
					esc_html_e( 'All Coaches', 'wfeb' );
				}
				?>
			</span>
			<?php if ( $total_pages > 1 ) : ?>
				<span class="wfeb-pagination-info">
					<?php
					/* translators: %s: total number of items */
					printf( esc_html__( '%s coaches', 'wfeb' ), esc_html( $total_items ) );
					?>
				</span>
			<?php endif; ?>
		</div>

		<!-- Coaches Table -->
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Email', 'wfeb' ); ?></th>
					<th data-tooltip="National Governing Body registration number"><?php esc_html_e( 'NGB #', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Country', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wfeb' ); ?></th>
					<th data-tooltip="Exam credits available. Each exam submission costs 1 credit"><?php esc_html_e( 'Credits', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'wfeb' ); ?></th>
					<th class="col-actions"><?php esc_html_e( 'Actions', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $coaches ) ) : ?>
				<tr>
					<td colspan="8" class="wfeb-table-empty">
						<?php esc_html_e( 'No coaches found.', 'wfeb' ); ?>
					</td>
				</tr>
				<?php else : ?>
					<?php foreach ( $coaches as $coach ) : ?>
					<tr data-coach-row="<?php echo absint( $coach->id ); ?>">
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $coach->id ) ) ); ?>">
								<strong><?php echo esc_html( $coach->full_name ); ?></strong>
							</a>
						</td>
						<td><?php echo esc_html( $coach->email ); ?></td>
						<td><?php echo esc_html( $coach->ngb_number ); ?></td>
						<td>
							<?php echo isset( $coach->country ) ? esc_html( $coach->country ) : '&mdash;'; ?>
						</td>
						<td>
							<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $coach->status ); ?>">
								<?php echo esc_html( ucfirst( $coach->status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $coach->credits_balance ); ?></td>
						<td><?php echo esc_html( wfeb_format_date( $coach->created_at ) ); ?></td>
						<td class="col-actions">
							<div class="wfeb-row-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $coach->id ) ) ); ?>"
									class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
									<?php esc_html_e( 'View', 'wfeb' ); ?>
								</a>

								<?php if ( 'pending' === $coach->status ) : ?>
									<button type="button"
										class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-approve-coach"
										data-coach-id="<?php echo absint( $coach->id ); ?>"
										data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
										data-tooltip="Grant this coach access to submit player exams">
										<?php esc_html_e( 'Approve', 'wfeb' ); ?>
									</button>
									<button type="button"
										class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm wfeb-reject-coach"
										data-coach-id="<?php echo absint( $coach->id ); ?>"
										data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
										data-tooltip="Deny this coach's registration">
										<?php esc_html_e( 'Reject', 'wfeb' ); ?>
									</button>
								<?php endif; ?>

								<?php if ( 'approved' === $coach->status ) : ?>
									<button type="button"
										class="wfeb-btn wfeb-btn--warning wfeb-btn--sm wfeb-suspend-coach"
										data-coach-id="<?php echo absint( $coach->id ); ?>"
										data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
										data-tooltip="Temporarily disable this coach's access. Data is preserved">
										<?php esc_html_e( 'Suspend', 'wfeb' ); ?>
									</button>
								<?php endif; ?>

								<?php if ( 'suspended' === $coach->status || 'rejected' === $coach->status ) : ?>
									<button type="button"
										class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-approve-coach"
										data-coach-id="<?php echo absint( $coach->id ); ?>"
										data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
										data-tooltip="Restore this coach's access to submit exams">
										<?php esc_html_e( 'Re-approve', 'wfeb' ); ?>
									</button>
								<?php endif; ?>

								<button type="button"
									class="wfeb-btn wfeb-btn--danger wfeb-btn--sm wfeb-remove-coach"
									data-coach-id="<?php echo absint( $coach->id ); ?>"
									data-coach-name="<?php echo esc_attr( $coach->full_name ); ?>"
									data-tooltip="Permanently delete this coach and all their data"
									data-tooltip-pos="right">
									<?php esc_html_e( 'Remove', 'wfeb' ); ?>
								</button>
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
				<a class="wfeb-page-btn"
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
					<a class="wfeb-page-btn"
						href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>">
						<?php echo esc_html( $i ); ?>
					</a>
				<?php endif; ?>
			<?php endfor; ?>

			<?php if ( $paged < $total_pages ) : ?>
				<a class="wfeb-page-btn"
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
