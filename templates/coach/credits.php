<?php
/**
 * Template: Coach Dashboard - Credits Section
 *
 * Displays the coach's credit balance, buy more button,
 * and transaction history.
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

// Credit balance.
$credits = $coach ? absint( $coach->credits_balance ) : 0;

// Transaction history.
$transactions = WFEB()->coach->get_transactions(
	$coach_id,
	array(
		'limit'  => 50,
		'offset' => 0,
	)
);

// Transaction type badges mapping.
$type_badges = array(
	'purchase'   => 'completed',
	'usage'      => 'draft',
	'refund'     => 'info',
	'adjustment' => 'neutral',
);

$type_labels = array(
	'purchase'   => __( 'Purchase', 'wfeb' ),
	'usage'      => __( 'Usage', 'wfeb' ),
	'refund'     => __( 'Refund', 'wfeb' ),
	'adjustment' => __( 'Admin Adjust', 'wfeb' ),
);
?>

<!-- Credit Balance Card -->
<div class="wfeb-card wfeb-credit-balance-card">
	<div class="wfeb-card-body">
		<div class="wfeb-credit-balance">
			<div class="wfeb-credit-balance-icon">
				<span class="dashicons dashicons-awards"></span>
			</div>
			<div class="wfeb-credit-balance-info">
				<span class="wfeb-credit-balance-value"><?php echo esc_html( $credits ); ?></span>
				<span class="wfeb-credit-balance-label"><?php echo esc_html__( 'Certificate Credits Available', 'wfeb' ); ?></span>
			</div>
			<div class="wfeb-credit-balance-action">
				<?php
				$buy_section_url = add_query_arg( 'section', 'buy-credits', $base_url );
				?>
				<a href="<?php echo esc_url( $buy_section_url ); ?>"
				   class="wfeb-btn wfeb-btn--primary wfeb-btn--lg">
					<span class="dashicons dashicons-cart"></span>
					<?php echo esc_html__( 'Buy More Credits', 'wfeb' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<?php if ( $credits < 5 ) : ?>
	<div class="wfeb-alert wfeb-alert--warning">
		<span class="dashicons dashicons-warning"></span>
		<div>
			<strong><?php echo esc_html__( 'Low Credit Balance', 'wfeb' ); ?></strong>
			<p><?php echo esc_html__( 'Your credit balance is running low. Purchase more credits to continue conducting exams and generating certificates.', 'wfeb' ); ?></p>
		</div>
	</div>
<?php endif; ?>

<!-- Transaction History -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Transaction History', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">
		<?php if ( ! empty( $transactions ) ) : ?>
			<div class="wfeb-table-responsive">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th class="wfeb-sortable" data-sort="date"><?php echo esc_html__( 'Date', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="type"><?php echo esc_html__( 'Type', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="amount"><?php echo esc_html__( 'Amount', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="balance"><?php echo esc_html__( 'Balance After', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th><?php echo esc_html__( 'Description', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $transactions as $txn ) : ?>
							<?php
							$txn_type  = sanitize_key( $txn->type );
							$badge_cls = isset( $type_badges[ $txn_type ] ) ? $type_badges[ $txn_type ] : 'neutral';
							$type_text = isset( $type_labels[ $txn_type ] ) ? $type_labels[ $txn_type ] : ucfirst( $txn_type );
							$amount    = intval( $txn->amount );
							?>
							<tr>
								<td><?php echo esc_html( wfeb_format_date( $txn->created_at, 'j M Y, g:i A' ) ); ?></td>
								<td>
									<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $badge_cls ); ?>">
										<?php echo esc_html( $type_text ); ?>
									</span>
								</td>
								<td>
									<span class="wfeb-credit-amount wfeb-credit-amount--<?php echo $amount >= 0 ? 'positive' : 'negative'; ?>">
										<?php echo $amount >= 0 ? '+' . esc_html( $amount ) : esc_html( $amount ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $txn->balance ); ?></td>
								<td><?php echo esc_html( $txn->description ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="wfeb-empty-state">
				<span class="dashicons dashicons-awards"></span>
				<p><?php echo esc_html__( 'No transactions yet. Your credit history will appear here after your first purchase.', 'wfeb' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
