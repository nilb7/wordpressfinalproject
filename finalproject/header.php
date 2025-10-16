<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <header class="site-header">
        <div class="container">
            <div style="display:flex;align-items:center;gap:12px;width:100%">
                <?php if ( is_user_logged_in() ) : $user = wp_get_current_user(); ?>
                    <div style="margin-right:12px;display:flex;align-items:center;gap:8px">
                        <span style="color:var(--muted);font-weight:700">Hi, <?php echo esc_html( $user->display_name ?: $user->user_login ); ?></span>
                        <a class="button" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">Profile</a>
                        <a class="button" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">Logout</a>
                    </div>
                <?php else : ?>
                    <div style="margin-right:12px;display:flex;align-items:center;gap:8px">
                        <a class="button" href="#" data-open="fp-login">Login</a>
                        <a class="button" href="#" data-open="fp-register">Register</a>
                    </div>
                <?php endif; ?>

                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Crypto Portfolio</a></h1>
            <?php $top_page = get_page_by_path( 'top-cryptos' );
                $top_url = $top_page ? get_permalink( $top_page ) : esc_url( home_url( '/top-cryptos/' ) ); ?>
            <a href="<?php echo $top_url; ?>" class="menu-link" style="margin-left:12px;color:var(--muted);text-decoration:none">Top 10</a>
                <button class="mobile-toggle" aria-expanded="false" aria-controls="primary-menu" id="mobile-toggle">Menu</button>
                <nav id="primary-menu" class="site-nav" role="navigation">
                    <?php if ( has_nav_menu( 'primary' ) ) {
                        wp_nav_menu( array( 'theme_location' => 'primary', 'container' => '', 'menu_class' => 'menu' ) );
                    } else {
                        echo '<ul class="menu"><li><a href="' . esc_url( home_url('/') ) . '">Home</a></li></ul>';
                    } ?>
                </nav>
                
            </div>
        </div>
    </header>

    <main class="container">
        <section class="hero" aria-label="Intro">
            <div class="left">
                <h2>Gjurmoni zotërimet tuaja të kriptovalutave, thjesht.</h2>
                <p>Shtoni shpejt asete, përditësoni çmimet dhe vizualizoni shpërndarjen e portofolit tuaj.</p>
            </div>
            <div class="cta">
                <button class="button" id="update-prices-hero">Përditëso Çmimet</button>
            </div>
        </section>
