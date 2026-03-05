<?php
/**
 * WFEB Admin Players List Template
 *
 * Displays the players list with search and pagination.
 *
 * Variables available from WFEB_Admin_Players::render_list():
 * - $players, $total_items, $total_pages, $paged, $per_page, $search
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url = admin_url( 'admin.php?page=wfeb-players' );
?>
<div class="wfeb-wrap">

	<!-- Page Header -->
	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'Players', 'wfeb' ); ?></h1>
	</div>

	<!-- Table Card -->
	<div class="wfeb-table-card">

		<!-- Filter / Search Bar -->
		<form method="get" class="wfeb-filter-bar">
			<input type="hidden" name="page" value="wfeb-players" />

			<div class="wfeb-filter-group">
				<label for="wfeb-player-search" class="screen-reader-text">
					<?php esc_html_e( 'Search Players', 'wfeb' ); ?>
				</label>
				<div class="wfeb-search-box">
					<input
						type="search"
						id="wfeb-player-search"
						name="s"
						class="wfeb-search-input"
						value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php esc_attr_e( 'Search by name or email...', 'wfeb' ); ?>"
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
				} else {
					esc_html_e( 'All Players', 'wfeb' );
				}
				?>
			</span>
			<?php if ( $total_items > 0 ) : ?>
				<span class="wfeb-pagination-info">
					<?php
					/* translators: %s: total number of items */
					printf( esc_html__( '%s players', 'wfeb' ), esc_html( $total_items ) );
					?>
				</span>
			<?php endif; ?>
		</div>

		<!-- Players Table -->
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Email', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Date of Birth', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Coach', 'wfeb' ); ?></th>
					<th data-tooltip="Total exams taken by this player"><?php esc_html_e( 'Exams', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Best Score', 'wfeb' ); ?></th>
					<th data-tooltip="Highest achievement level attained across all exams"><?php esc_html_e( 'Best Level', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Date Added', 'wfeb' ); ?></th>
					<th class="col-actions"><?php esc_html_e( 'Actions', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $players ) ) : ?>
				<tr>
					<td colspan="9" class="wfeb-table-empty">
						<?php esc_html_e( 'No players found.', 'wfeb' ); ?>
					</td>
				</tr>
				<?php else : ?>
					<?php foreach ( $players as $player ) : ?>
					<tr data-player-row="<?php echo absint( $player->id ); ?>">
						<td><strong><?php echo esc_html( $player->full_name ); ?></strong></td>
						<td><?php echo esc_html( $player->email ); ?></td>
						<td><?php echo esc_html( wfeb_format_date( $player->dob ) ); ?></td>
						<td>
							<?php if ( ! empty( $player->coach_name ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $player->coach_id ) ) ); ?>">
									<?php echo esc_html( $player->coach_name ); ?>
								</a>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $player->exam_count ); ?></td>
						<td>
							<?php if ( ! empty( $player->best_score ) ) : ?>
								<?php echo esc_html( $player->best_score ); ?>/80
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $player->best_level ) ) : ?>
								<span class="wfeb-badge wfeb-badge--level">
									<?php echo esc_html( $player->best_level ); ?>
								</span>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( wfeb_format_date( $player->created_at ) ); ?></td>
						<td class="col-actions">
							<div class="wfeb-row-actions">
								<button
									type="button"
									class="wfeb-btn wfeb-btn--danger wfeb-btn--sm wfeb-delete-player"
									data-player-id="<?php echo absint( $player->id ); ?>"
									data-player-name="<?php echo esc_attr( $player->full_name ); ?>"
									data-tooltip="Permanently delete this player and all their exam records"
									data-tooltip-pos="right">
									<?php esc_html_e( 'Delete', 'wfeb' ); ?>
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
