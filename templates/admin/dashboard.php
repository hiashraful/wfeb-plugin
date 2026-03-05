<?php
/**
 * WFEB Admin Dashboard Template
 *
 * Displays the main WFEB admin dashboard with stats, pending coaches,
 * and recent exam activity.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$coach_model = WFEB()->coach;
$player_model = WFEB()->player;
$exam_model   = WFEB()->exam;
$cert_model   = WFEB()->certificate;

// Stats.
$total_coaches      = $coach_model->get_count();
$total_players      = $player_model->get_count();
$total_exams        = $exam_model->get_count();
$total_certificates = $cert_model->get_count();
$pending_count      = $coach_model->get_count( 'pending' );

// Credits sold (sum of purchase transactions).
global $wpdb;
$transactions_table = $wpdb->prefix . 'wfeb_credit_transactions';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$credits_sold = absint( $wpdb->get_var(
	"SELECT COALESCE(SUM(amount), 0) FROM {$transactions_table} WHERE type = 'purchase'"
) );

// Pending coaches.
$pending_coaches = $coach_model->get_all( array(
	'status' => 'pending',
	'limit'  => 20,
	'offset' => 0,
) );

// Recent exams (last 10).
$exams_table   = $wpdb->prefix . 'wfeb_exams';
$players_table = $wpdb->prefix . 'wfeb_players';
$coaches_table = $wpdb->prefix . 'wfeb_coaches';

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$recent_exams = $wpdb->get_results(
	"SELECT e.*, p.full_name AS player_name, c.full_name AS coach_name
	FROM {$exams_table} AS e
	LEFT JOIN {$players_table} AS p ON e.player_id = p.id
	LEFT JOIN {$coaches_table} AS c ON e.coach_id = c.id
	ORDER BY e.created_at DESC
	LIMIT 10"
);

if ( ! $recent_exams ) {
	$recent_exams = array();
}
?>
<div class="wfeb-wrap">

	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'WFEB Dashboard', 'wfeb' ); ?></h1>
	</div>

	<!-- Stats Grid -->
	<div class="wfeb-stats-grid">

		<div class="wfeb-stat-card">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Total Coaches', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $total_coaches ); ?></div>
		</div>

		<div class="wfeb-stat-card">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Total Players', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $total_players ); ?></div>
		</div>

		<div class="wfeb-stat-card">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Total Exams', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $total_exams ); ?></div>
		</div>

		<div class="wfeb-stat-card wfeb-stat-card--info">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Total Certificates', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $total_certificates ); ?></div>
		</div>

		<div class="wfeb-stat-card wfeb-stat-card--pending" data-tooltip="Coaches who registered but haven't been approved or rejected yet">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Pending Approvals', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $pending_count ); ?></div>
		</div>

		<div class="wfeb-stat-card wfeb-stat-card--accent" data-tooltip="Total exam credits purchased by all coaches. Each credit allows one exam submission">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Credits Sold', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $credits_sold ); ?></div>
		</div>

	</div>

	<?php if ( ! empty( $pending_coaches ) ) : ?>
	<!-- Pending Coach Approvals -->
	<div class="wfeb-table-card" id="wfeb-pending-coaches-section">
		<div class="wfeb-table-header">
			<h2><?php esc_html_e( 'Pending Coach Approvals', 'wfeb' ); ?></h2>
		</div>
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Email', 'wfeb' ); ?></th>
					<th data-tooltip="National Governing Body registration number"><?php esc_html_e( 'NGB #', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Date Registered', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pending_coaches as $pc ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $pc->id ) ) ); ?>">
							<?php echo esc_html( $pc->full_name ); ?>
						</a>
					</td>
					<td><?php echo esc_html( $pc->email ); ?></td>
					<td><?php echo esc_html( $pc->ngb_number ); ?></td>
					<td><?php echo esc_html( wfeb_format_date( $pc->created_at ) ); ?></td>
					<td>
						<button type="button"
							class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-approve-coach"
							data-tooltip="Grant this coach access to submit player exams"
							data-coach-id="<?php echo absint( $pc->id ); ?>">
							<?php esc_html_e( 'Approve', 'wfeb' ); ?>
						</button>
						<button type="button"
							class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm wfeb-reject-coach"
							data-tooltip="Deny this coach's registration"
							data-coach-id="<?php echo absint( $pc->id ); ?>">
							<?php esc_html_e( 'Reject', 'wfeb' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<!-- Recent Exam Activity -->
	<div class="wfeb-table-card">
		<div class="wfeb-table-header">
			<h2><?php esc_html_e( 'Recent Exam Activity', 'wfeb' ); ?></h2>
		</div>
		<?php if ( empty( $recent_exams ) ) : ?>
			<p class="wfeb-empty-state"><?php esc_html_e( 'No exams recorded yet.', 'wfeb' ); ?></p>
		<?php else : ?>
		<table class="wfeb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Player', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Coach', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Score', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Level', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent_exams as $exam ) : ?>
				<tr>
					<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
					<td><?php echo esc_html( $exam->player_name ); ?></td>
					<td><?php echo esc_html( $exam->coach_name ); ?></td>
					<td><?php echo esc_html( $exam->total_score ); ?>/80</td>
					<td>
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( strtolower( $exam->achievement_level ) ); ?>">
							<?php echo esc_html( $exam->achievement_level ); ?>
						</span>
					</td>
					<td>
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
							<?php echo esc_html( ucfirst( $exam->status ) ); ?>
						</span>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

</div>
