

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
        <div class="card-title">Vlera totale e portofolit</div>
        <div class="card-value">$<?php echo number_format( $total_value, 2 ); ?></div>
    </div>
    <div class="card gradient-card">
        <div class="card-title">Asetet e gjurmuara</div>
        <div class="card-value"><?php echo count( $assets ); ?></div>
    </div>
    <div class="card gradient-card">
        <div class="card-title">Përditëso Çmimet</div>
        <button class="button" id="update-prices">Përditëso Çmimet</button>
    </div>
</section>

<!-- Add Asset modal is now triggered from the Asset List header -->
<section class="chart-wrap">
    <canvas id="portfolio-chart" width="400" height="180"></canvas>
    <script>window.portfolioChartData = {labels:<?php echo json_encode($labels); ?>,data:<?php echo json_encode($data); ?>};</script>
</section>
<section>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
        <h2 style="margin:0">Lista e aseteve</h2>
        <div>
            <button class="button" id="open-add-asset">Shto asset</button>
        </div>
    </div>
    <?php if ( $sell_error ) : ?>
        <div style="color:#ff006e; margin-bottom:10px; font-weight:bold;"> <?php echo esc_html($sell_error); ?> </div>
    <?php endif; ?>
    <table class="asset-table">
        <thead>
            <tr><th>Emri</th><th>Bilanci</th><th>Çmimi (USD)</th><th>Vlera (USD)</th><th>Alokimi</th><th>Veprimi</th></tr>
        </thead>
        <tbody>
        <?php foreach ( $assets as $asset ) :
            $symbol = get_post_meta( $asset->ID, '_asset_symbol', true );
            $balance = (float) get_post_meta( $asset->ID, '_asset_balance', true );
            $price = (float) get_post_meta( $asset->ID, '_asset_price', true );
            $value = $balance * $price;
            $percent = $total_value > 0 ? round( ( $value / $total_value ) * 100, 2 ) : 0;
        ?>
            <tr>
                <td>
                    <div class="asset-row">
                  <?php $coin_page = get_page_by_path( 'coin' );
                      $base = $coin_page ? get_permalink( $coin_page ) : home_url( '/coin/' );
                      $coin_link = esc_url( add_query_arg( 'id', strtolower( $symbol ), $base ) ); ?>
                        <div class="asset-badge"><a href="<?php echo $coin_link; ?>" style="color:inherit;text-decoration:none"><?php echo esc_html( strtoupper(substr($symbol,0,2)) ); ?></a></div>
                            <div class="asset-meta">
                                <a href="<?php echo $coin_link; ?>" class="asset-name"><?php echo esc_html( get_the_title( $asset ) ); ?></a>
                            <div class="asset-symbol"><?php echo esc_html( strtoupper( $symbol ) ); ?></div>
                        </div>
                    </div>
                </td>
                <td class="asset-balance"><?php echo $balance; ?></td>
                <td class="asset-price">$<?php echo number_format( $price, 2 ); ?></td>
                <td class="asset-value">$<?php echo number_format( $value, 2 ); ?></td>
                <td>
                    <div class="allocation">
                        <div class="allocation-bar"><div class="allocation-fill" style="width:<?php echo esc_attr( $percent ); ?>%"></div></div>
                        <div class="allocation-percent"><?php echo esc_html( $percent ); ?>%</div>
                    </div>
                </td>
                <td>
                    <div class="asset-actions">
                    <form method="post" style="display:inline;" data-asset-name="<?php echo esc_attr( get_the_title( $asset ) ); ?>">
                        <input type="hidden" name="sell_asset_nonce" value="<?php echo esc_attr( wp_create_nonce('sell_asset') ); ?>" />
                        <input type="hidden" name="sell_asset_id" value="<?php echo esc_attr( $asset->ID ); ?>" />
                        <input type="number" name="sell_amount" min="0.00000001" max="<?php echo $balance; ?>" step="any" placeholder="Amount" style="width:90px;" required />
                        <button class="button button-sell" type="submit">Shit</button>
                    </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<!-- Sell confirmation modal -->
<div class="modal-backdrop" id="sell-modal" aria-hidden="true">
<div class="modal" role="dialog" aria-modal="true" aria-labelledby="sell-modal-title">
<h3 id="sell-modal-title">Konfirmo Shitjen</h3>
<p>Ju jeni gati të shisni <strong id="sell-modal-amount">0</strong> nga <span id="sell-modal-name"></span>. Ky veprim do të përditësojë portofolin tuaj.</p>
<div class="modal-actions">
<button class="btn btn-cancel" id="sell-modal-cancel">Anulo</button>
<button class="btn btn-confirm" id="sell-modal-confirm">Konfirmo Shitjen</button>
        </div>
    </div>
</div>
<!-- Add Asset modal -->
<div class="modal-backdrop" id="add-asset-modal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="add-asset-title">
        <h3 id="add-asset-title">Add Crypto Asset</h3>
        <?php if ( $add_error ) : ?>
            <div style="color:#ff006e; margin-bottom:10px; font-weight:bold;"> <?php echo esc_html($add_error); ?> </div>
        <?php endif; ?>
        <form method="post" id="add-asset-form" style="display:grid;gap:10px">
            <input type="hidden" name="add_asset_nonce" value="<?php echo esc_attr( wp_create_nonce('add_asset') ); ?>" />
            <label>Emri: <input type="text" name="asset_name" required /></label>
            <label>Simbol: <input type="text" name="asset_symbol" required maxlength="10" style="text-transform:uppercase;" /></label>
            <label>Bilanci: <input type="number" name="asset_balance" step="any" min="0.00000001" required /></label>
            <label>Çmimi (USD): <input type="number" name="asset_price" step="any" min="0.0001" required /></label>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:6px">
                <button class="btn btn-cancel" type="button" id="add-asset-cancel">Anulo</button>
                <button class="btn btn-confirm" type="submit">Shto asset</button>
            </div>
        </form>
    </div>
</div>
<?php get_footer(); ?>
