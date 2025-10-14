<?php
/**
 * Template Name: Top 10 Cryptos
 * Description: Shows a ranking of the top 10 cryptocurrencies using CoinGecko's public API (cached via transients).
 */

get_header();

// Try transient cache first
$cache_key = 'fp_top_10_coins_v1';
$coins = get_transient( $cache_key );
if ( false === $coins ) {
    $request = wp_remote_get( 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=10&page=1&sparkline=false&price_change_percentage=1h%2C24h%2C7d' );
    if ( is_wp_error( $request ) ) {
        $coins = array();
    } else {
        $body = wp_remote_retrieve_body( $request );
        $data = json_decode( $body, true );
        if ( is_array( $data ) ) {
            $coins = $data;
            // Cache for 10 minutes
            set_transient( $cache_key, $coins, 10 * MINUTE_IN_SECONDS );
        } else {
            $coins = array();
        }
    }
}

?>
<section class="card">
    <h2>Top 10 Cryptocurrencies</h2>
    <p class="muted">Data provided by CoinGecko. Updated every 10 minutes.</p>
    <?php if ( empty( $coins ) ) : ?>
        <div class="card">Unable to retrieve data at the moment.</div>
    <?php else : ?>
        <table class="asset-table top-coins-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>1h</th>
                    <th>24h</th>
                    <th>7d</th>
                    <th>Market Cap</th>
                    <th>Volume (24h)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $coins as $i => $c ) :
                $rank = $i + 1;
                $name = $c['name'] ?? '';
                $symbol = strtoupper( $c['symbol'] ?? '' );
                $price = isset( $c['current_price'] ) ? $c['current_price'] : 0;
                $pc1h = isset( $c['price_change_percentage_1h_in_currency'] ) ? $c['price_change_percentage_1h_in_currency'] : null;
                $pc24h = isset( $c['price_change_percentage_24h_in_currency'] ) ? $c['price_change_percentage_24h_in_currency'] : null;
                $pc7d = isset( $c['price_change_percentage_7d_in_currency'] ) ? $c['price_change_percentage_7d_in_currency'] : null;
                $mcap = isset( $c['market_cap'] ) ? $c['market_cap'] : 0;
                $vol = isset( $c['total_volume'] ) ? $c['total_volume'] : 0;
                $thumb = isset( $c['image'] ) ? $c['image'] : '';
            ?>
                <tr>
                    <td><?php echo esc_html( $rank ); ?></td>
                    <td style="display:flex;gap:10px;align-items:center">
                <?php $coin_page = get_page_by_path( 'coin' );
                    $base = $coin_page ? get_permalink( $coin_page ) : home_url( '/coin/' );
                    $coin_link = esc_url( add_query_arg( 'id', $c['id'], $base ) ); ?>
                        <?php if ( $thumb ) : ?><a href="<?php echo $coin_link; ?>"><img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $name ); ?>" style="width:22px;height:22px;border-radius:50%;"></a><?php endif; ?>
                        <div>
                            <div style="font-weight:700"><a href="<?php echo $coin_link; ?>" style="color:inherit;text-decoration:none"><?php echo esc_html( $name ); ?></a></div>
                            <div style="color:var(--muted);font-size:0.85rem"><?php echo esc_html( $symbol ); ?></div>
                        </div>
                    </td>
                    <td>$<?php echo number_format( $price, 2 ); ?></td>
                    <td><?php echo is_null( $pc1h ) ? '—' : esc_html( number_format( $pc1h, 2 ) . '%' ); ?></td>
                    <td><?php echo is_null( $pc24h ) ? '—' : esc_html( number_format( $pc24h, 2 ) . '%' ); ?></td>
                    <td><?php echo is_null( $pc7d ) ? '—' : esc_html( number_format( $pc7d, 2 ) . '%' ); ?></td>
                    <td>$<?php echo number_format( $mcap ); ?></td>
                    <td>$<?php echo number_format( $vol ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php get_footer();
