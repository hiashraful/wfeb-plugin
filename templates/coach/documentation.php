<?php
/**
 * Template: Coach Dashboard - Documentation Section
 *
 * Displays the WFEB system documentation for coaches.
 *
 * @package WFEB
 * @since   1.8.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wfeb-section-header">
	<h2 class="wfeb-section-title"><?php echo esc_html__( 'Documentation', 'wfeb' ); ?></h2>
</div>

<div class="wfeb-docs">

	<!-- Introduction -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'What is WFEB?', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body">
			<p class="wfeb-docs-text">
				<?php echo esc_html__( 'WFEB (World Football Examination Board) is a football skills certification platform. As a coach, you can register players, conduct standardised 7-category skills exams, and generate official certificates for your players.', 'wfeb' ); ?>
			</p>
		</div>
	</div>

	<!-- The Flow -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'How It Works', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-docs-steps">
				<div class="wfeb-docs-step">
					<div class="wfeb-docs-step-number">1</div>
					<div class="wfeb-docs-step-content">
						<strong><?php echo esc_html__( 'Get Approved', 'wfeb' ); ?></strong>
						<p><?php echo esc_html__( 'Register as a coach and wait for admin approval. Once approved, you can access the full dashboard.', 'wfeb' ); ?></p>
					</div>
				</div>
				<div class="wfeb-docs-step">
					<div class="wfeb-docs-step-number">2</div>
					<div class="wfeb-docs-step-content">
						<strong><?php echo esc_html__( 'Buy Credits', 'wfeb' ); ?></strong>
						<p><?php echo esc_html__( 'Purchase certificate credits via the Credits page. Each completed exam costs 1 credit. Drafts are free.', 'wfeb' ); ?></p>
					</div>
				</div>
				<div class="wfeb-docs-step">
					<div class="wfeb-docs-step-number">3</div>
					<div class="wfeb-docs-step-content">
						<strong><?php echo esc_html__( 'Add Players', 'wfeb' ); ?></strong>
						<p><?php echo esc_html__( 'Add players to your roster with their details (name, date of birth, email, etc.).', 'wfeb' ); ?></p>
					</div>
				</div>
				<div class="wfeb-docs-step">
					<div class="wfeb-docs-step-number">4</div>
					<div class="wfeb-docs-step-content">
						<strong><?php echo esc_html__( 'Conduct Exams', 'wfeb' ); ?></strong>
						<p><?php echo esc_html__( 'Run the 7-category skills exam on a player. You can save as a draft (free) or complete the exam (costs 1 credit).', 'wfeb' ); ?></p>
					</div>
				</div>
				<div class="wfeb-docs-step">
					<div class="wfeb-docs-step-number">5</div>
					<div class="wfeb-docs-step-content">
						<strong><?php echo esc_html__( 'Certificates Generated', 'wfeb' ); ?></strong>
						<p><?php echo esc_html__( 'On completion, a certificate is automatically generated with a unique number, PDF created, and emailed to the player.', 'wfeb' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Exam Categories -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'The Exam (7 Categories, /80 Total)', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body wfeb-p-0">
			<div class="wfeb-table-responsive">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th>#</th>
							<th><?php echo esc_html__( 'Category', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Inputs', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Max Score', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>1</td>
							<td><strong><?php echo esc_html__( 'Short Passing', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Left foot + Right foot (0-5 each)', 'wfeb' ); ?></td>
							<td>/10</td>
						</tr>
						<tr>
							<td>2</td>
							<td><strong><?php echo esc_html__( 'Long Passing', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Left foot + Right foot (0-5 each)', 'wfeb' ); ?></td>
							<td>/10</td>
						</tr>
						<tr>
							<td>3</td>
							<td><strong><?php echo esc_html__( 'Shooting', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( '4 goal corners: TL, TR, BL, BR (0-5 each)', 'wfeb' ); ?></td>
							<td>/20</td>
						</tr>
						<tr>
							<td>4</td>
							<td><strong><?php echo esc_html__( 'Sprint', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Time in seconds - converted to score', 'wfeb' ); ?></td>
							<td>/10</td>
						</tr>
						<tr>
							<td>5</td>
							<td><strong><?php echo esc_html__( 'Dribble', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Time in seconds - converted to score', 'wfeb' ); ?></td>
							<td>/10</td>
						</tr>
						<tr>
							<td>6</td>
							<td><strong><?php echo esc_html__( 'Kickups', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( '3 attempts, best counts - converted to score', 'wfeb' ); ?></td>
							<td>/10</td>
						</tr>
						<tr>
							<td>7</td>
							<td><strong><?php echo esc_html__( 'Volley', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Left foot + Right foot (0-5 each)', 'wfeb' ); ?></td>
							<td>/10</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Score Conversion Tables -->
	<div class="wfeb-docs-grid">

		<!-- Sprint -->
		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h3 class="wfeb-card-title"><?php echo esc_html__( 'Sprint - Time to Score', 'wfeb' ); ?></h3>
			</div>
			<div class="wfeb-card-body wfeb-p-0">
				<div class="wfeb-table-responsive">
					<table class="wfeb-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Time (seconds)', 'wfeb' ); ?></th>
								<th><?php echo esc_html__( 'Score', 'wfeb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr><td>&lt; 5.5</td><td>10</td></tr>
							<tr><td>&lt; 6.0</td><td>9</td></tr>
							<tr><td>&lt; 6.5</td><td>8</td></tr>
							<tr><td>&lt; 7.0</td><td>7</td></tr>
							<tr><td>&lt; 7.5</td><td>6</td></tr>
							<tr><td>&lt; 8.0</td><td>5</td></tr>
							<tr><td>&lt; 8.5</td><td>4</td></tr>
							<tr><td>&lt; 9.0</td><td>3</td></tr>
							<tr><td>&lt; 9.5</td><td>2</td></tr>
							<tr><td>&lt; 10.0</td><td>1</td></tr>
							<tr><td>&ge; 10.0</td><td>0</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Dribble -->
		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h3 class="wfeb-card-title"><?php echo esc_html__( 'Dribble - Time to Score', 'wfeb' ); ?></h3>
			</div>
			<div class="wfeb-card-body wfeb-p-0">
				<div class="wfeb-table-responsive">
					<table class="wfeb-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Time (seconds)', 'wfeb' ); ?></th>
								<th><?php echo esc_html__( 'Score', 'wfeb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr><td>&lt; 4.0</td><td>10</td></tr>
							<tr><td>&lt; 4.5</td><td>9</td></tr>
							<tr><td>&lt; 5.0</td><td>8</td></tr>
							<tr><td>&lt; 5.5</td><td>7</td></tr>
							<tr><td>&lt; 6.0</td><td>6</td></tr>
							<tr><td>&lt; 6.5</td><td>5</td></tr>
							<tr><td>&lt; 7.0</td><td>4</td></tr>
							<tr><td>&lt; 7.5</td><td>3</td></tr>
							<tr><td>&lt; 8.0</td><td>2</td></tr>
							<tr><td>&lt; 8.5</td><td>1</td></tr>
							<tr><td>&ge; 8.5</td><td>0</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Kickups -->
		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h3 class="wfeb-card-title"><?php echo esc_html__( 'Kickups - Count to Score', 'wfeb' ); ?></h3>
			</div>
			<div class="wfeb-card-body wfeb-p-0">
				<div class="wfeb-table-responsive">
					<table class="wfeb-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Count', 'wfeb' ); ?></th>
								<th><?php echo esc_html__( 'Score', 'wfeb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr><td>&ge; 100</td><td>10</td></tr>
							<tr><td>&ge; 90</td><td>9</td></tr>
							<tr><td>&ge; 75</td><td>8</td></tr>
							<tr><td>&ge; 60</td><td>7</td></tr>
							<tr><td>&ge; 45</td><td>6</td></tr>
							<tr><td>&ge; 30</td><td>5</td></tr>
							<tr><td>&ge; 15</td><td>4</td></tr>
							<tr><td>&ge; 10</td><td>3</td></tr>
							<tr><td>&ge; 5</td><td>2</td></tr>
							<tr><td>&ge; 3</td><td>1</td></tr>
							<tr><td>&lt; 3</td><td>0</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

	</div>

	<!-- Achievement Levels -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'Achievement Levels', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body wfeb-p-0">
			<div class="wfeb-table-responsive">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Score', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Level', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Playing Level', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$levels = array(
							array( '80', 'MASTERY', __( 'World Class', 'wfeb' ) ),
							array( '70-79', 'DIAMOND', __( 'Professional', 'wfeb' ) ),
							array( '60-69', 'GOLD', __( 'Semi-Professional', 'wfeb' ) ),
							array( '50-59', 'SILVER', __( 'Advanced Amateur', 'wfeb' ) ),
							array( '40-49', 'BRONZE', __( 'Amateur', 'wfeb' ) ),
							array( '30-39', 'MERIT+', __( 'Intermediate', 'wfeb' ) ),
							array( '20-29', 'MERIT', __( 'Developing', 'wfeb' ) ),
							array( '15-19', 'MERIT-', __( 'Foundation Plus', 'wfeb' ) ),
							array( '10-14', 'PASS+', __( 'Foundation', 'wfeb' ) ),
							array( '5-9', 'PASS', __( 'Beginner', 'wfeb' ) ),
							array( '< 5', 'UNGRADED', __( 'Ungraded', 'wfeb' ) ),
						);
						foreach ( $levels as $level ) :
						?>
							<tr>
								<td><?php echo esc_html( $level[0] ); ?></td>
								<td>
									<span
										class="wfeb-badge wfeb-badge--level"
										style="background-color: <?php echo esc_attr( wfeb_get_level_color( $level[1] ) ); ?>;"
									>
										<?php echo esc_html( $level[1] ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $level[2] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Drafts vs Completed -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'Draft vs Completed Exams', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body wfeb-p-0">
			<div class="wfeb-table-responsive">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Aspect', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Draft', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Completed', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php echo esc_html__( 'Editable', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'Yes', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'No', 'wfeb' ); ?></td>
						</tr>
						<tr>
							<td><?php echo esc_html__( 'Deletable', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'Yes', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'No', 'wfeb' ); ?></td>
						</tr>
						<tr>
							<td><?php echo esc_html__( 'Credit Cost', 'wfeb' ); ?></td>
							<td>0</td>
							<td>1</td>
						</tr>
						<tr>
							<td><?php echo esc_html__( 'Certificate Generated', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'No', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'Yes', 'wfeb' ); ?></td>
						</tr>
						<tr>
							<td><?php echo esc_html__( 'Player Account Created', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'No', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'Yes (on first exam)', 'wfeb' ); ?></td>
						</tr>
						<tr>
							<td><?php echo esc_html__( 'Permanent', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'No', 'wfeb' ); ?></td>
							<td><?php echo esc_html__( 'Yes', 'wfeb' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Credits & Certificates -->
	<div class="wfeb-docs-grid">
		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h3 class="wfeb-card-title"><?php echo esc_html__( 'Credits System', 'wfeb' ); ?></h3>
			</div>
			<div class="wfeb-card-body">
				<ul class="wfeb-docs-list">
					<li><?php echo esc_html__( 'Coaches start with 0 credits', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'Credits are purchased via the Credits page', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'Each completed exam costs 1 credit', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'Drafts are free (no credit used)', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'A full transaction ledger is kept for audit', 'wfeb' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h3 class="wfeb-card-title"><?php echo esc_html__( 'Certificates', 'wfeb' ); ?></h3>
			</div>
			<div class="wfeb-card-body">
				<ul class="wfeb-docs-list">
					<li><?php echo esc_html__( 'One certificate per completed exam (1:1)', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'Each certificate has a unique sequential number', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'PDF is generated and emailed to the player', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'Players can be examined multiple times', 'wfeb' ); ?></li>
					<li><?php echo esc_html__( 'Anyone can verify certificates publicly using the player name, certificate number, and date of birth', 'wfeb' ); ?></li>
				</ul>
			</div>
		</div>
	</div>

</div>
