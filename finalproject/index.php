

<?php
// Handle frontend asset add form BEFORE any output
$add_error = '';
if ( isset($_POST['add_asset_nonce']) && wp_verify_nonce($_POST['add_asset_nonce'], 'add_asset') ) {
    $name = sanitize_text_field($_POST['asset_name'] ?? '');
    $symbol = sanitize_text_field($_POST['asset_symbol'] ?? '');
    $balance = floatval($_POST['asset_balance'] ?? 0);
    $price = floatval($_POST['asset_price'] ?? 0);
    if ($name && $symbol && $balance > 0 && $price > 0) {
        $asset_id = wp_insert_post(array(
            'post_type' => 'asset',
            'post_title' => $name,
            'post_status' => 'publish',
        ));
        if ($asset_id) {
            update_post_meta($asset_id, '_asset_symbol', $symbol);
            update_post_meta($asset_id, '_asset_balance', $balance);
            update_post_meta($asset_id, '_asset_price', $price);
            // Redirect to avoid resubmission
            wp_redirect( home_url() );
            exit;
        } else {
            $add_error = 'Failed to add asset.';
        }
    } else {
        $add_error = 'Please fill in all fields with valid values.';
    }
}

// Handle sell asset form
$sell_error = '';
if ( isset($_POST['sell_asset_nonce']) && wp_verify_nonce($_POST['sell_asset_nonce'], 'sell_asset') ) {
    $sell_id = intval($_POST['sell_asset_id'] ?? 0);
    $sell_amount = floatval($_POST['sell_amount'] ?? 0);
    if ($sell_id && $sell_amount > 0) {
        $current_balance = (float) get_post_meta($sell_id, '_asset_balance', true);
        if ($sell_amount <= $current_balance) {
            $new_balance = $current_balance - $sell_amount;
            if ($new_balance > 0) {
                update_post_meta($sell_id, '_asset_balance', $new_balance);
            } else {
                // Remove asset if balance is zero
                wp_delete_post($sell_id, true);
            }
            wp_redirect( home_url() );
            exit;
        } else {
            $sell_error = 'Cannot sell more than you own.';
        }
    } else {
        $sell_error = 'Invalid sell request.';
    }
}

get_header();

// Get all assets
$assets = get_posts( array( 'post_type' => 'asset', 'numberposts' => -1 ) );
$total_value = 0;
$labels = [];
$data = [];
foreach ( $assets as $asset ) {
    $balance = (float) get_post_meta( $asset->ID, '_asset_balance', true );
    $price = (float) get_post_meta( $asset->ID, '_asset_price', true );
    $value = $balance * $price;
    $total_value += $value;
    $labels[] = get_the_title( $asset );
    $data[] = $value;
}
?>
<section class="dashboard-cards">
    <div class="card gradient-card">
        <div class="card-title">Total Portfolio Value</div>
        <div class="card-value">$<?php echo number_format( $total_value, 2 ); ?></div>
    </div>
    <div class="card gradient-card">
        <div class="card-title">Assets Tracked</div>
        <div class="card-value"><?php echo count( $assets ); ?></div>
    </div>
    <div class="card gradient-card">
        <div class="card-title">Update Prices</div>
        <button class="button" id="update-prices">Update Prices</button>
    </div>
</section>

<section class="card" style="max-width:500px;margin:0 auto 32px;">
    <h2 style="margin-top:0;">Add Crypto Asset</h2>
    <?php if ( $add_error ) : ?>
        <div style="color:#ff006e; margin-bottom:10px; font-weight:bold;"> <?php echo esc_html($add_error); ?> </div>
    <?php endif; ?>
    <form method="post" style="display:grid;gap:12px;">
        <input type="hidden" name="add_asset_nonce" value="<?php echo esc_attr( wp_create_nonce('add_asset') ); ?>" />
        <label>Name: <input type="text" name="asset_name" required /></label>
        <label>Symbol: <input type="text" name="asset_symbol" required maxlength="10" style="text-transform:uppercase;" /></label>
        <label>Balance: <input type="number" name="asset_balance" step="any" min="0.00000001" required /></label>
        <label>Price (USD): <input type="number" name="asset_price" step="any" min="0.0001" required /></label>
        <button class="button" type="submit">Add Asset</button>
    </form>
</section>
<section class="chart-wrap">
    <canvas id="portfolio-chart" width="400" height="180"></canvas>
    <script>window.portfolioChartData = {labels:<?php echo json_encode($labels); ?>,data:<?php echo json_encode($data); ?>};</script>
</section>
<section>
    <h2>Asset List</h2>
    <?php if ( $sell_error ) : ?>
        <div style="color:#ff006e; margin-bottom:10px; font-weight:bold;"> <?php echo esc_html($sell_error); ?> </div>
    <?php endif; ?>
    <table class="asset-table">
        <thead>
            <tr><th>Name</th><th>Symbol</th><th>Balance</th><th>Price (USD)</th><th>Value (USD)</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ( $assets as $asset ) :
            $symbol = get_post_meta( $asset->ID, '_asset_symbol', true );
            $balance = (float) get_post_meta( $asset->ID, '_asset_balance', true );
            $price = (float) get_post_meta( $asset->ID, '_asset_price', true );
            $value = $balance * $price;
        ?>
            <tr>
                <td><a href="<?php echo get_permalink( $asset ); ?>"><?php echo esc_html( get_the_title( $asset ) ); ?></a></td>
                <td><?php echo esc_html( strtoupper( $symbol ) ); ?></td>
                <td><?php echo $balance; ?></td>
                <td>$<?php echo number_format( $price, 2 ); ?></td>
                <td>$<?php echo number_format( $value, 2 ); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="sell_asset_nonce" value="<?php echo esc_attr( wp_create_nonce('sell_asset') ); ?>" />
                        <input type="hidden" name="sell_asset_id" value="<?php echo esc_attr( $asset->ID ); ?>" />
                        <input type="number" name="sell_amount" min="0.00000001" max="<?php echo $balance; ?>" step="any" placeholder="Amount" style="width:90px;" required />
                        <button class="button button-sell" type="submit">Sell</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php get_footer(); ?>
