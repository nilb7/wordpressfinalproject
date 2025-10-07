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
            <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Crypto Portfolio</a></h1>
            <nav class="site-nav" role="navigation">
                <?php if ( has_nav_menu( 'primary' ) ) wp_nav_menu( array( 'theme_location' => 'primary', 'container' => '', 'menu_class' => 'menu' ) ); ?>
            </nav>
        </div>
    </header>
    <main class="container">
