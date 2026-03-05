<?php
/**
 * WFEB Admin Exams List Template
 *
 * Displays the exams list with filters, search, and pagination.
 *
 * Variables available from WFEB_Admin_Exams::render_list():
 * - $exams, $total_items, $total_pages, $paged, $per_page
 * - $search, $status, $date_from, $date_to
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wfeb-wrap">

	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'Exams', 'wfeb' ); ?></h1>
	</div>

	<!-- Table Card -->
	<div class="wfeb-table-card">

		<!-- Filter Bar -->
		<form method="get">
			<input type="hidden" name="page" value="wfeb-exams" />
			<div class="wfeb-filter-bar">
				<div class="wfeb-filter-group">
					<label for="wfeb-exam-search"><?php esc_html_e( 'Search', 'wfeb' ); ?></label>
					<input type="search" id="wfeb-exam-search" name="s" class="wfeb-search-input"
						value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php esc_attr_e( 'Player or coach name...', 'wfeb' ); ?>" />
				</div>
				<div class="wfeb-filter-group">
					<label for="wfeb-exam-status"><?php esc_html_e( 'Status', 'wfeb' ); ?></label>
					<select id="wfeb-exam-status" name="status">
						<option value=""><?php esc_html_e( 'All', 'wfeb' ); ?></option>
						<option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'wfeb' ); ?></option>
						<option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'wfeb' ); ?></option>
					</select>
				</div>
				<div class="wfeb-filter-group">
					<label for="wfeb-exam-date-from"><?php esc_html_e( 'From', 'wfeb' ); ?></label>
					<input type="date" id="wfeb-exam-date-from" name="date_from"
						value="<?php echo esc_attr( $date_from ); ?>" />
				</div>
				<div class="wfeb-filter-group">
					<label for="wfeb-exam-date-to"><?php esc_html_e( 'To', 'wfeb' ); ?></label>
					<input type="date" id="wfeb-exam-date-to" name="date_to"
						value="<?php echo esc_attr( $date_to ); ?>" />
				</div>
				<div class="wfeb-filter-actions">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm">
						<?php esc_html_e( 'Filter', 'wfeb' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-exams' ) ); ?>" class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
						<?php esc_html_e( 'Reset', 'wfeb' ); ?>
					</a>
				</div>
			</div>
		</form>

		<!-- Exams Table -->
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Player', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Coach', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Score', 'wfeb' ); ?></th>
					<th data-tooltip="Achievement level based on total score out of 80"><?php esc_html_e( 'Level', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wfeb' ); ?></th>
					<th data-tooltip="Certificate number issued for this exam. Dash means no certificate yet"><?php esc_html_e( 'Cert #', 'wfeb' ); ?></th>
					<th class="col-actions"><?php esc_html_e( 'Actions', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $exams ) ) : ?>
				<tr>
					<td colspan="8" class="wfeb-table-empty"><?php esc_html_e( 'No exams found.', 'wfeb' ); ?></td>
				</tr>
				<?php else : ?>
					<?php foreach ( $exams as $exam ) : ?>
					<?php
					$level_slug = sanitize_title( $exam->achievement_level );
					?>
					<tr>
						<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
						<td><?php echo esc_html( $exam->player_name ); ?></td>
						<td>
							<?php if ( ! empty( $exam->coach_name ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $exam->coach_id ) ) ); ?>">
									<?php echo esc_html( $exam->coach_name ); ?>
								</a>
							<?php else : ?>
								<?php esc_html_e( 'N/A', 'wfeb' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $exam->total_score ); ?>/80</td>
						<td>
							<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $level_slug ); ?>">
								<?php echo esc_html( $exam->achievement_level ); ?>
							</span>
						</td>
						<td>
							<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
								<?php echo esc_html( ucfirst( $exam->status ) ); ?>
							</span>
						</td>
						<td>
							<?php if ( ! empty( $exam->certificate_number ) ) : ?>
								<?php echo esc_html( $exam->certificate_number ); ?>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td class="col-actions">
							<div class="wfeb-row-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-exams&exam_id=' . absint( $exam->id ) ) ); ?>"
									class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
									<?php esc_html_e( 'View', 'wfeb' ); ?>
								</a>
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
				/* translators: %s: total number of items */
				printf( esc_html__( '%s items', 'wfeb' ), esc_html( $total_items ) );
				?>
			</span>
			<?php if ( $paged > 1 ) : ?>
				<a class="wfeb-page-btn" href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>">
					&lsaquo;
				</a>
			<?php else : ?>
				<span class="wfeb-page-btn disabled">&lsaquo;</span>
			<?php endif; ?>
			<span class="wfeb-page-btn active">
				<?php echo esc_html( $paged ); ?>
			</span>
			<span class="wfeb-pagination-info">
				<?php
				/* translators: %s: total number of pages */
				printf( esc_html__( 'of %s', 'wfeb' ), esc_html( $total_pages ) );
				?>
			</span>
			<?php if ( $paged < $total_pages ) : ?>
				<a class="wfeb-page-btn" href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>">
					&rsaquo;
				</a>
			<?php else : ?>
				<span class="wfeb-page-btn disabled">&rsaquo;</span>
			<?php endif; ?>
		</div>
		<?php endif; ?>

	</div><!-- .wfeb-table-card -->

</div><!-- .wfeb-wrap -->
