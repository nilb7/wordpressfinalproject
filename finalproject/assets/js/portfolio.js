jQuery(function($){
    // Update prices button
    $(document).on('click', '#update-prices', function(e){
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Updating...');
        $.post(CryptoPortfolio.ajax_url, { action: 'cryptoportfolio_update_prices' }, function(resp){
            if(resp.success){
                location.reload();
            } else {
                alert('Failed to update prices.');
                $btn.prop('disabled', false).text('Update Prices');
            }
        }, 'json');
    });

    // Chart rendering
    if($('#portfolio-chart').length && typeof Chart !== 'undefined'){
        var ctx = document.getElementById('portfolio-chart').getContext('2d');
        var chartData = window.portfolioChartData || {labels:[],data:[]};
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: ['#eebc1d','#232946','#1a1e2d','#3a86ff','#ff006e','#8338ec','#fb5607']
                }]
            },
            options: { plugins: { legend: { labels: { color: '#fff' } } } }
        });
    }
});
