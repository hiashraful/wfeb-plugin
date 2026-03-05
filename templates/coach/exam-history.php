<?php
/**
 * Template: Coach Dashboard - Exam History Section
 *
 * Displays the coach's exam history with filters, search,
 * status tabs, and pagination.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Coach data.
$coach    = WFEB()->coach_dashboard->get_coach_data();
$coach_id = wfeb_get_coach_id();

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();

// Query params (no nonce needed for GET-based filtering of own data).
$current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search_term    = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$date_from      = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$date_to        = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page       = 20;
$offset         = ( $current_page - 1 ) * $per_page;

// Fetch exams.
$exams = WFEB()->exam->get_by_coach(
	$coach_id,
	array(
		'search'    => $search_term,
		'date_from' => $date_from,
		'date_to'   => $date_to,
		'status'    => $current_status,
		'limit'     => $per_page,
		'offset'    => $offset,
	)
);

// Total count for pagination.
$total_all       = WFEB()->exam->get_count( $coach_id );
$total_completed = WFEB()->exam->get_count( $coach_id, 'completed' );
$total_draft     = WFEB()->exam->get_count( $coach_id, 'draft' );

$total_filtered = $total_all;
if ( 'completed' === $current_status ) {
	$total_filtered = $total_completed;
} elseif ( 'draft' === $current_status ) {
	$total_filtered = $total_draft;
}

$total_pages = ceil( $total_filtered / $per_page );

// Build filter URLs.
$section_url = add_query_arg( 'section', 'exam-history', $base_url );
?>

<!-- Filter Bar -->
<div class="wfeb-card">
	<div class="wfeb-card-body">
		<form class="wfeb-filter-bar" method="get" action="<?php echo esc_url( $base_url ); ?>">
			<input type="hidden" name="section" value="exam-history">
			<?php if ( $current_status ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>">
			<?php endif; ?>

			<div class="wfeb-filter-row">
				<div class="wfeb-search-bar wfeb-filter-search">
					<svg class="wfeb-search-bar-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					<input
						type="text"
						name="search"
						class="wfeb-search-input"
						placeholder="<?php echo esc_attr__( 'Search by player name...', 'wfeb' ); ?>"
						value="<?php echo esc_attr( $search_term ); ?>"
					>
				</div>

				<div class="wfeb-filter-dates">
					<input
						type="date"
						name="date_from"
						class="wfeb-form-input wfeb-filter-date"
						value="<?php echo esc_attr( $date_from ); ?>"
						placeholder="<?php echo esc_attr__( 'From', 'wfeb' ); ?>"
					>
					<input
						type="date"
						name="date_to"
						class="wfeb-form-input wfeb-filter-date"
						value="<?php echo esc_attr( $date_to ); ?>"
						placeholder="<?php echo esc_attr__( 'To', 'wfeb' ); ?>"
					>
				</div>

				<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm">
					<span class="dashicons dashicons-search"></span>
					<?php echo esc_html__( 'Filter', 'wfeb' ); ?>
				</button>

				<?php if ( $search_term || $date_from || $date_to ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'section', 'exam-history', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--secondary wfeb-btn--sm">
						<?php echo esc_html__( 'Clear', 'wfeb' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- Exams Table -->
<div class="wfeb-card">
	<!-- Status Tabs -->
	<div class="wfeb-tabs">
		<a
			class="wfeb-tab<?php echo '' === $current_status ? ' wfeb-tab--active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-history' ), $base_url ) ); ?>"
		>
			<?php
			printf(
				/* translators: %d: total exam count */
				esc_html__( 'All (%d)', 'wfeb' ),
				$total_all
			);
			?>
		</a>
		<a
			class="wfeb-tab<?php echo 'completed' === $current_status ? ' wfeb-tab--active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-history', 'status' => 'completed' ), $base_url ) ); ?>"
		>
			<?php
			printf(
				/* translators: %d: completed exam count */
				esc_html__( 'Completed (%d)', 'wfeb' ),
				$total_completed
			);
			?>
		</a>
		<a
			class="wfeb-tab<?php echo 'draft' === $current_status ? ' wfeb-tab--active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-history', 'status' => 'draft' ), $base_url ) ); ?>"
		>
			<?php
			printf(
				/* translators: %d: draft exam count */
				esc_html__( 'Draft (%d)', 'wfeb' ),
				$total_draft
			);
			?>
		</a>
	</div>
	<div class="wfeb-card-body wfeb-p-0">
		<?php if ( ! empty( $exams ) ) : ?>
			<div class="wfeb-table-responsive">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th class="wfeb-sortable" data-sort="date"><?php echo esc_html__( 'Date', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="player"><?php echo esc_html__( 'Player', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="score"><?php echo esc_html__( 'Score', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="level"><?php echo esc_html__( 'Level', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="status"><?php echo esc_html__( 'Status', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th><?php echo esc_html__( 'Actions', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $exams as $exam ) : ?>
							<tr>
								<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
								<td><?php echo esc_html( $exam->player_name ); ?></td>
								<td><?php echo esc_html( $exam->total_score . '/80' ); ?></td>
								<td>
									<span
										class="wfeb-badge wfeb-badge--level"
										style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>;"
									>
										<?php echo esc_html( $exam->achievement_level ); ?>
									</span>
								</td>
								<td>
									<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
										<?php echo esc_html( ucfirst( $exam->status ) ); ?>
									</span>
								</td>
								<td>
									<div class="wfeb-actions">
										<a
											href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-details', 'exam_id' => $exam->id ), $base_url ) ); ?>"
											class="wfeb-action-btn wfeb-action-btn--view"
											title="<?php echo esc_attr__( 'View Details', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-visibility"></span>
										</a>
										<a
											href="<?php echo esc_url( add_query_arg( array( 'section' => 'conduct-exam', 'exam_id' => $exam->id ), $base_url ) ); ?>"
											class="wfeb-action-btn wfeb-action-btn--edit"
											title="<?php echo esc_attr__( 'Edit Exam', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-edit"></span>
										</a>
										<button
											type="button"
											class="wfeb-action-btn wfeb-action-btn--delete wfeb-delete-exam"
											data-id="<?php echo esc_attr( $exam->id ); ?>"
											title="<?php echo esc_attr__( 'Delete Exam', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<!-- Pagination -->
				<div class="wfeb-pagination">
					<?php if ( $current_page > 1 ) : ?>
						<a
							href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-history', 'paged' => $current_page - 1, 'status' => $current_status, 'search' => $search_term ), $base_url ) ); ?>"
							class="wfeb-pagination-link"
						>
							&laquo; <?php echo esc_html__( 'Previous', 'wfeb' ); ?>
						</a>
					<?php endif; ?>

					<span class="wfeb-pagination-info">
						<?php
						printf(
							/* translators: 1: current page, 2: total pages */
							esc_html__( 'Page %1$d of %2$d', 'wfeb' ),
							$current_page,
							$total_pages
						);
						?>
					</span>

					<?php if ( $current_page < $total_pages ) : ?>
						<a
							href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-history', 'paged' => $current_page + 1, 'status' => $current_status, 'search' => $search_term ), $base_url ) ); ?>"
							class="wfeb-pagination-link"
						>
							<?php echo esc_html__( 'Next', 'wfeb' ); ?> &raquo;
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<div class="wfeb-empty-state">
				<span class="dashicons dashicons-list-view"></span>
				<p>
					<?php
					if ( $search_term || $date_from || $date_to || $current_status ) {
						echo esc_html__( 'No exams match your filters.', 'wfeb' );
					} else {
						echo esc_html__( 'No exams yet. Conduct your first exam to get started.', 'wfeb' );
					}
					?>
				</p>
				<?php if ( ! $search_term && ! $date_from && ! $date_to && ! $current_status ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'section', 'conduct-exam', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
						<?php echo esc_html__( 'Conduct Exam', 'wfeb' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
