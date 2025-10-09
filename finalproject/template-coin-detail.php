<?php
/**
 * Template Name: Coin Detail
 * Description: Shows details for a single coin using ?id=coin-id (CoinGecko). Includes a 30-day price chart.
 */
get_header();
$coin_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : null;
if (! $coin_id) {
    echo '<section class="card"><p>No coin specified.</p></section>';
    get_footer();
    exit;
}

$cache_key = 'fp_coin_' . $coin_id;
$coin = get_transient( $cache_key );
if ( false === $coin ) {
    // try direct fetch first
    $req = wp_remote_get( 'https://api.coingecko.com/api/v3/coins/' . rawurlencode($coin_id) . '?localization=false&tickers=false&market_data=true&sparkline=false' );
    if ( is_wp_error( $req ) ) {
        $coin = null;
    } else {
        $body = wp_remote_retrieve_body( $req );
        $coin = json_decode( $body, true );
        if ( ! is_array($coin) || empty($coin['id']) ) {
            // direct fetch didn't work: try search endpoint to map symbol/name -> id
            $search_q = rawurlencode( $coin_id );
            $sreq = wp_remote_get( 'https://api.coingecko.com/api/v3/search?query=' . $search_q );
            if ( ! is_wp_error( $sreq ) ) {
                $sbody = wp_remote_retrieve_body( $sreq );
                $sdata = json_decode( $sbody, true );
                if ( is_array( $sdata ) && ! empty( $sdata['coins'] ) ) {
                    // prefer exact symbol match
                    $found = null;
                    $lower = strtolower( $coin_id );
                    foreach ( $sdata['coins'] as $sc ) {
                        if ( isset( $sc['symbol'] ) && strtolower( $sc['symbol'] ) === $lower ) { $found = $sc['id']; break; }
                    }
                    // fallback: first result
                    if ( ! $found ) {
                        $found = $sdata['coins'][0]['id'];
                    }
                    if ( $found ) {
                        // fetch using the found id
                        $req2 = wp_remote_get( 'https://api.coingecko.com/api/v3/coins/' . rawurlencode($found) . '?localization=false&tickers=false&market_data=true&sparkline=false' );
                        if ( ! is_wp_error( $req2 ) ) {
                            $body2 = wp_remote_retrieve_body( $req2 );
                            $coin = json_decode( $body2, true );
                        }
                    }
                }
            }
        }
        if ( is_array($coin) && ! empty( $coin['id'] ) ) {
            set_transient( $cache_key, $coin, 10 * MINUTE_IN_SECONDS );
            // also store mapping of symbol -> id for faster lookup
            if ( ! empty( $coin['symbol'] ) ) {
                set_transient( 'fp_symbol_map_' . strtolower( $coin['symbol'] ), $coin['id'], 12 * HOUR_IN_SECONDS );
            }
        } else {
            $coin = null;
        }
    }
}

// Chart data (30 days)
$chart_key = 'fp_coin_chart_' . $coin_id;
$chart = get_transient($chart_key);
if ( false === $chart ) {
    // use resolved coin id if available
    $chart_id = $coin['id'] ?? $coin_id;
    $req = wp_remote_get( 'https://api.coingecko.com/api/v3/coins/' . rawurlencode($chart_id) . '/market_chart?vs_currency=usd&days=30' );
    if ( ! is_wp_error( $req ) ) {
        $body = wp_remote_retrieve_body( $req );
        $chart = json_decode( $body, true );
        if ( is_array($chart) ) set_transient( $chart_key, $chart, 10 * MINUTE_IN_SECONDS );
    }
}

?>
<section class="card">
    <?php if ( ! $coin ) : ?>
        <p>Unable to load coin data.</p>
    <?php else :
        $name = $coin['name'];
        $symbol = strtoupper($coin['symbol']);
        $img = $coin['image']['large'] ?? '';
        $price = $coin['market_data']['current_price']['usd'] ?? 0;
        $mcap = $coin['market_data']['market_cap']['usd'] ?? 0;
        $vol = $coin['market_data']['total_volume']['usd'] ?? 0;
    ?>
    <div style="display:flex;gap:16px;align-items:center;margin-bottom:12px">
        <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" style="width:56px;height:56px;border-radius:12px" alt="<?php echo esc_attr($name); ?>" /><?php endif; ?>
        <div>
            <h2 style="margin:0"><?php echo esc_html($name); ?> <span style="color:var(--muted);font-size:0.9rem"><?php echo esc_html($symbol); ?></span></h2>
            <div style="font-weight:800;font-size:1.2rem">$<?php echo number_format($price,2); ?></div>
        </div>
    </div>

    <div style="margin-bottom:16px">
        <canvas id="coin-chart" width="800" height="300"></canvas>
    </div>

    <div style="display:flex;gap:18px;flex-wrap:wrap">
        <div class="card" style="min-width:160px;padding:12px">Market Cap<br><strong>$<?php echo number_format($mcap); ?></strong></div>
        <div class="card" style="min-width:160px;padding:12px">24h Volume<br><strong>$<?php echo number_format($vol); ?></strong></div>
        <div class="card" style="min-width:160px;padding:12px">Homepage<br><a href="<?php echo esc_url($coin['links']['homepage'][0] ?? '#'); ?>" target="_blank">Visit</a></div>
    </div>

    <script>
    (function(){
        var chartData = <?php echo json_encode( $chart['prices'] ?? array() ); ?>;
        var labels = chartData.map(function(p){ var d = new Date(p[0]); return d.toLocaleDateString(); });
        var data = chartData.map(function(p){ return p[1]; });

        function initChart(){
            var canvas = document.getElementById('coin-chart');
            if (!canvas) return;
            if (typeof Chart === 'undefined') return false;
            if (!chartData || !chartData.length) return true; // nothing to draw
            try{
                new Chart(canvas.getContext('2d'),{
                    type:'line',
                    data:{labels:labels,datasets:[{label:'Price (USD)',data:data,borderColor:'#3a86ff',backgroundColor:'rgba(58,134,255,0.08)',fill:true}]},
                    options:{scales:{x:{ticks:{color:'#cdd6e0'}},y:{ticks:{color:'#cdd6e0'}}},plugins:{legend:{display:false}}}
                );
            } catch(e){
                console.error('Chart init error', e);
            }
            return true;
        }

        // Try to init immediately, otherwise retry until Chart is available
        if (!initChart()){
            var attempts = 0;
            var maxAttempts = 30;
            var tid = setInterval(function(){
                attempts++;
                if (initChart() || attempts >= maxAttempts){
                    clearInterval(tid);
                }
            }, 200);
        }
    })();
    </script>

    <?php endif; ?>
</section>

<?php get_footer();
