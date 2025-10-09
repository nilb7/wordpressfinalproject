<?php
/**
 * Crypto Portfolio Theme Functions
 */

function cryptoportfolio_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array( 'primary' => 'Primary Menu' ) );
}
add_action( 'after_setup_theme', 'cryptoportfolio_setup' );

function cryptoportfolio_scripts() {
    // Google Fonts (kept lightweight)
    wp_enqueue_style( 'cryptoportfolio-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap', array(), null );
    wp_enqueue_style( 'cryptoportfolio-style', get_stylesheet_uri(), array('cryptoportfolio-fonts'), filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );
    wp_enqueue_script( 'cryptoportfolio-main', get_template_directory_uri() . '/assets/js/portfolio.js', array( 'chartjs', 'jquery' ), filemtime( get_stylesheet_directory() . '/assets/js/portfolio.js' ), true );
    wp_localize_script( 'cryptoportfolio-main', 'CryptoPortfolio', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'cryptoportfolio_scripts' );

// Register Asset CPT
function cryptoportfolio_register_asset_cpt() {
    $labels = array(
        'name' => 'Assets',
        'singular_name' => 'Asset',
        'menu_name' => 'Portfolio',
        'add_new' => 'Add Asset',
        'add_new_item' => 'Add New Asset',
        'edit_item' => 'Edit Asset',
        'new_item' => 'New Asset',
        'view_item' => 'View Asset',
        'all_items' => 'All Assets',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array( 'title', 'editor', 'thumbnail' ),
        'menu_icon' => 'dashicons-chart-pie',
    );
    register_post_type( 'asset', $args );
}
add_action( 'init', 'cryptoportfolio_register_asset_cpt' );

// Add custom fields for Asset: symbol, balance, price
function cryptoportfolio_asset_meta_boxes() {
    add_meta_box( 'asset_details', 'Asset Details', 'cryptoportfolio_asset_details_cb', 'asset', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'cryptoportfolio_asset_meta_boxes' );

function cryptoportfolio_asset_details_cb( $post ) {
    $symbol = get_post_meta( $post->ID, '_asset_symbol', true );
    $balance = get_post_meta( $post->ID, '_asset_balance', true );
    $price = get_post_meta( $post->ID, '_asset_price', true );
    echo '<p><label>Symbol: <input type="text" name="asset_symbol" value="' . esc_attr( $symbol ) . '" /></label></p>';
    echo '<p><label>Balance: <input type="number" step="any" name="asset_balance" value="' . esc_attr( $balance ) . '" /></label></p>';
    echo '<p><label>Price (USD): <input type="number" step="any" name="asset_price" value="' . esc_attr( $price ) . '" /></label></p>';
    echo '<p><em>Price can be updated automatically via the dashboard.</em></p>';
}

function cryptoportfolio_save_asset_meta( $post_id ) {
    if ( isset( $_POST['asset_symbol'] ) ) update_post_meta( $post_id, '_asset_symbol', sanitize_text_field( $_POST['asset_symbol'] ) );
    if ( isset( $_POST['asset_balance'] ) ) update_post_meta( $post_id, '_asset_balance', floatval( $_POST['asset_balance'] ) );
    if ( isset( $_POST['asset_price'] ) ) update_post_meta( $post_id, '_asset_price', floatval( $_POST['asset_price'] ) );
}
add_action( 'save_post_asset', 'cryptoportfolio_save_asset_meta' );

// AJAX: Update prices (stub, extend for real API)
add_action( 'wp_ajax_cryptoportfolio_update_prices', 'cryptoportfolio_update_prices' );
function cryptoportfolio_update_prices() {
    // In production, fetch from CoinGecko or similar
    $assets = get_posts( array( 'post_type' => 'asset', 'numberposts' => -1 ) );
    foreach ( $assets as $asset ) {
        $symbol = get_post_meta( $asset->ID, '_asset_symbol', true );
        // Demo: random price
        $price = rand( 10, 50000 ) + ( rand( 0, 99 ) / 100 );
        update_post_meta( $asset->ID, '_asset_price', $price );
    }
    wp_send_json_success( array( 'message' => 'Prices updated (demo only)' ) );
}

/**
 * Front-end login handler (admin-post)
 */
function fp_frontend_login_handler() {
    if ( ! isset( $_POST['fp_login_nonce'] ) || ! wp_verify_nonce( $_POST['fp_login_nonce'], 'fp_frontend_login' ) ) {
        wp_redirect( wp_get_referer() ?: home_url() );
        exit;
    }
    $creds = array();
    $creds['user_login'] = sanitize_text_field( $_POST['fp_user'] ?? '' );
    $creds['user_password'] = $_POST['fp_pass'] ?? '';
    $creds['remember'] = ! empty( $_POST['fp_remember'] ) ? true : false;
    $user = wp_signon( $creds, false );
    if ( is_wp_error( $user ) ) {
        // redirect back with error
        $redirect = add_query_arg( 'fp_login', 'error', wp_get_referer() ?: home_url() );
        wp_redirect( $redirect );
        exit;
    }
    wp_redirect( wp_get_referer() ?: home_url() );
    exit;
}
add_action( 'admin_post_nopriv_fp_frontend_login', 'fp_frontend_login_handler' );

/**
 * Front-end registration handler (admin-post)
 */
function fp_frontend_register_handler() {
    if ( ! isset( $_POST['fp_register_nonce'] ) || ! wp_verify_nonce( $_POST['fp_register_nonce'], 'fp_frontend_register' ) ) {
        wp_redirect( wp_get_referer() ?: home_url() );
        exit;
    }
    // check if registrations allowed
    if ( ! get_option( 'users_can_register' ) ) {
        $redirect = add_query_arg( 'fp_register', 'disabled', wp_get_referer() ?: home_url() );
        wp_redirect( $redirect );
        exit;
    }
    $email = sanitize_email( $_POST['fp_email'] ?? '' );
    $password = $_POST['fp_password'] ?? '';
    $username = sanitize_user( $_POST['fp_username'] ?? '' );
    if ( ! is_email( $email ) ) {
        $redirect = add_query_arg( 'fp_register', 'invalid_email', wp_get_referer() ?: home_url() );
        wp_redirect( $redirect );
        exit;
    }
    if ( email_exists( $email ) ) {
        $redirect = add_query_arg( 'fp_register', 'email_exists', wp_get_referer() ?: home_url() );
        wp_redirect( $redirect );
        exit;
    }
    if ( empty( $username ) ) {
        // derive from email
        $username = sanitize_user( current( explode( '@', $email ) ) );
    }
    if ( username_exists( $username ) ) {
        // append random suffix
        $username = $username . rand(100,999);
    }
    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        $redirect = add_query_arg( 'fp_register', 'error', wp_get_referer() ?: home_url() );
        wp_redirect( $redirect );
        exit;
    }
    // Auto-login newly registered user
    $creds = array( 'user_login' => $username, 'user_password' => $password, 'remember' => false );
    wp_signon( $creds, false );
    wp_redirect( wp_get_referer() ?: home_url() );
    exit;
}
add_action( 'admin_post_nopriv_fp_frontend_register', 'fp_frontend_register_handler' );

// Create Top 10 page if missing (safe on init)
function fp_ensure_top10_page(){
    $slug = 'top-cryptos';
    $existing = get_page_by_path( $slug );
    if ( ! $existing ) {
        $page_id = wp_insert_post( array(
            'post_title' => 'Top 10 Cryptos',
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ) );
        if ( $page_id && ! is_wp_error( $page_id ) ) {
            // assign template if possible
            update_post_meta( $page_id, '_wp_page_template', 'template-top-cryptos.php' );
        }
    }
}
add_action( 'init', 'fp_ensure_top10_page' );

function fp_ensure_coin_page(){
    $slug = 'coin';
    $existing = get_page_by_path( $slug );
    if ( ! $existing ) {
        $page_id = wp_insert_post( array(
            'post_title' => 'Coin',
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ) );
        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_post_meta( $page_id, '_wp_page_template', 'template-coin-detail.php' );
        }
    }
}
add_action( 'init', 'fp_ensure_coin_page' );
