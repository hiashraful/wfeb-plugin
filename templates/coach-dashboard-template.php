<?php
/**
 * Template: Coach Dashboard (Master Layout)
 *
 * Standalone full-page template that bypasses the WordPress theme.
 * Outputs its own DOCTYPE, html, head, and body tags.
 * Loads sidebar navigation, header, and section content.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get coach data and dashboard instance.
$coach           = WFEB()->coach_dashboard->get_coach_data();
$coach_id        = wfeb_get_coach_id();
$current_section = WFEB()->coach_dashboard->get_current_section();
$sidebar_items   = WFEB()->coach_dashboard->get_sidebar_items();
$page_title      = WFEB()->coach_dashboard->get_page_title();
$section_template = WFEB()->coach_dashboard->get_section_template();

// Coach display data.
$coach_name    = $coach ? esc_html( $coach->full_name ) : esc_html__( 'Coach', 'wfeb' );
$coach_credits = $coach ? absint( $coach->credits_balance ) : 0;

// Logo URL.
$logo_url = WFEB_PLUGIN_URL . 'assets/images/LOGO RED TRANSPARENT.png';

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
	<script>(function(){var t;try{t=localStorage.getItem('wfeb_theme')}catch(e){}if(!t){var m=document.cookie.match(/(?:^|; )wfeb_theme=([^;]*)/);if(m)t=m[1];}document.documentElement.setAttribute('data-theme',t||'light');})();</script>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html__( 'Coach Dashboard - WFEB', 'wfeb' ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<?php wp_head(); ?>
</head>
<body class="wfeb-dashboard-page">
<script>(function(){var t=document.documentElement.getAttribute('data-theme')||'light';document.body.setAttribute('data-theme',t);})();</script>

	<div class="wfeb-dashboard">

		<!-- Sidebar -->
		<aside class="wfeb-sidebar" id="wfeb-sidebar">

			<div class="wfeb-sidebar-brand">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'WFEB', 'wfeb' ); ?>">
				<span class="wfeb-sidebar-brand-name"><?php echo esc_html__( 'WFEB', 'wfeb' ); ?></span>
			</div>

			<nav class="wfeb-sidebar-nav">
				<?php foreach ( $sidebar_items as $item ) : ?>
					<a
						class="wfeb-sidebar-link<?php echo ( $current_section === $item['slug'] ) ? ' active' : ''; ?>"
						href="<?php echo esc_url( $item['url'] ); ?>"
					>
						<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php
			// Sidebar avatar footer.
			$logout_login_page_id = get_option( 'wfeb_coach_login_page_id' );
			$logout_redirect_url  = $logout_login_page_id ? get_permalink( $logout_login_page_id ) : home_url( '/coach-login/' );

			$avatar_url = '';
			if ( $coach && ! empty( $coach->profile_picture ) ) {
				$avatar_url = wp_get_attachment_image_url( absint( $coach->profile_picture ), 'thumbnail' );
			}

			// Build initials fallback.
			$initials = '';
			if ( $coach && ! empty( $coach->full_name ) ) {
				$name_parts = explode( ' ', trim( $coach->full_name ) );
				$initials   = strtoupper( substr( $name_parts[0], 0, 1 ) );
				if ( count( $name_parts ) > 1 ) {
					$initials .= strtoupper( substr( end( $name_parts ), 0, 1 ) );
				}
			}
			?>
			<div class="wfeb-sidebar-coach">
				<div class="wfeb-sidebar-avatar-area">
					<?php if ( $avatar_url ) : ?>
						<img class="wfeb-sidebar-avatar" id="wfeb-sidebar-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $coach_name ); ?>">
					<?php else : ?>
						<div class="wfeb-sidebar-avatar wfeb-sidebar-avatar--initials" id="wfeb-sidebar-avatar-img"><?php echo esc_html( $initials ); ?></div>
					<?php endif; ?>
					<span class="wfeb-sidebar-coach-name"><?php echo esc_html( $coach_name ); ?></span>

					<!-- Hover popup -->
					<div class="wfeb-sidebar-popup">
						<a class="wfeb-sidebar-popup-link" href="<?php echo esc_url( add_query_arg( 'section', 'documentation', get_permalink( get_option( 'wfeb_coach_dashboard_page_id' ) ) ) ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
							<?php echo esc_html__( 'Documentation', 'wfeb' ); ?>
						</a>
						<div class="wfeb-sidebar-popup-divider"></div>
						<a class="wfeb-sidebar-popup-link wfeb-sidebar-popup-link--danger" href="<?php echo esc_url( wp_logout_url( $logout_redirect_url ) ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
							<?php echo esc_html__( 'Log Out', 'wfeb' ); ?>
						</a>
					</div>
				</div>
			</div>

		</aside>

		<!-- Main Content -->
		<main class="wfeb-main">

			<!-- Header -->
			<header class="wfeb-header">
				<button class="wfeb-hamburger" id="wfeb-hamburger" aria-label="<?php echo esc_attr__( 'Toggle sidebar', 'wfeb' ); ?>">
					<span class="dashicons dashicons-menu"></span>
				</button>

				<div class="wfeb-header-search">
					<span class="wfeb-header-search-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					</span>
					<input type="text" placeholder="<?php echo esc_attr__( 'Search', 'wfeb' ); ?>" id="wfeb-global-search" autocomplete="off">
				</div>

				<div class="wfeb-header-actions">
					<a href="<?php echo esc_url( add_query_arg( 'section', 'credits', get_permalink( get_option( 'wfeb_coach_dashboard_page_id' ) ) ) ); ?>" class="wfeb-credits-badge">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
						<?php echo esc_html( $coach_credits ); ?>
					</a>
					<button class="wfeb-header-icon-btn" id="wfeb-theme-toggle" aria-label="<?php echo esc_attr__( 'Toggle theme', 'wfeb' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
					</button>
					<button class="wfeb-header-icon-btn" aria-label="<?php echo esc_attr__( 'Settings', 'wfeb' ); ?>" onclick="window.location.href='<?php echo esc_url( add_query_arg( 'section', 'settings', get_permalink( get_option( 'wfeb_coach_dashboard_page_id' ) ) ) ); ?>'">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
					</button>
				</div>
			</header>

			<!-- Section Content -->
			<div class="wfeb-content" style="opacity: 0;">
				<?php
				if ( file_exists( $section_template ) ) {
					include $section_template;
				} else {
					echo '<div class="wfeb-card"><div class="wfeb-card-body"><p>' . esc_html__( 'Section not found.', 'wfeb' ) . '</p></div></div>';
				}
				?>
			</div>

		</main>

	</div>

	<!-- Mobile sidebar overlay -->
	<div class="wfeb-sidebar-overlay" id="wfeb-sidebar-overlay"></div>

	<?php wp_footer(); ?>
</body>
</html>
