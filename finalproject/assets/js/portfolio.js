jQuery(function($){
    // Update prices button
    $(document).on('click', '#update-prices', function(e){
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Updating...');
        $.post(CryptoPortfolio.ajax_url, { action: 'cryptoportfolio_update_prices' }, function(resp){
            if(resp.success){
                showToast('Prices updated');
                setTimeout(function(){ location.reload(); }, 700);
            } else {
                alert('Failed to update prices.');
                $btn.prop('disabled', false).text('Update Prices');
            }
        }, 'json');
    });

    // Hero update button
    $(document).on('click', '#update-prices-hero', function(e){
        e.preventDefault();
        $('#update-prices').trigger('click');
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

    // Intercept sell form submissions to show a confirm modal
    $(document).on('submit', 'form[data-asset-name]', function(e){
        e.preventDefault();
        var $form = $(this);
        var assetName = $form.data('asset-name') || 'asset';
        var amount = $form.find('input[name="sell_amount"]').val() || '0';
        $('#sell-modal-amount').text(amount);
        $('#sell-modal-name').text(assetName);
        $('#sell-modal').fadeIn(180).attr('aria-hidden','false');
        // store the form to submit later
        $('#sell-modal').data('pendingForm', $form);
    });

    // Modal buttons
    $(document).on('click', '#sell-modal-cancel', function(e){
        e.preventDefault();
        $('#sell-modal').fadeOut(150).attr('aria-hidden','true').removeData('pendingForm');
    });
    $(document).on('click', '#sell-modal-confirm', function(e){
        e.preventDefault();
        var $form = $('#sell-modal').data('pendingForm');
        if($form && $form.length){
            // hide modal then submit using native submit to avoid jQuery prevention
            $('#sell-modal').fadeOut(120).attr('aria-hidden','true').removeData('pendingForm');
            var formEl = $form.get(0);
            if (formEl && typeof formEl.submit === 'function') {
                formEl.submit();
            } else {
                // fallback to jQuery submit
                $form.off('submit');
                $form.submit();
            }
        }
    });

    // Add Asset modal open/close
    $(document).on('click', '#open-add-asset', function(e){
        e.preventDefault();
        $('#add-asset-modal').fadeIn(180).attr('aria-hidden','false');
    });
    $(document).on('click', '#add-asset-cancel', function(e){
        e.preventDefault();
        $('#add-asset-modal').fadeOut(150).attr('aria-hidden','true');
    });

    // Login/Register modal handlers
    $(document).on('click', '#fp-login-cancel', function(e){ e.preventDefault(); $('#fp-login-modal').fadeOut(150).attr('aria-hidden','true'); });
    $(document).on('click', '#fp-register-cancel', function(e){ e.preventDefault(); $('#fp-register-modal').fadeOut(150).attr('aria-hidden','true'); });

    // Expose quick open for login/register (useful for header links)
    $(document).on('click', '[data-open="fp-login"]', function(e){ e.preventDefault(); $('#fp-login-modal').fadeIn(180).attr('aria-hidden','false'); });
    $(document).on('click', '[data-open="fp-register"]', function(e){ e.preventDefault(); $('#fp-register-modal').fadeIn(180).attr('aria-hidden','false'); });

    // Auto-open if query params indicate a login/register action result
    (function(){
        var qs = (function(a){if(a=='')return{};var b={};for(var i=0;i<a.length;i++){var p=a[i].split('=');if(p.length==1)continue;b[decodeURIComponent(p[0])] = decodeURIComponent((p[1]||'').replace(/\+/g,' '));}return b;})(window.location.search.replace('?','').split('&'));
        if(qs.fp_login === 'error') $('#fp-login-modal').fadeIn(200).attr('aria-hidden','false');
        if(qs.fp_register === 'disabled' || qs.fp_register === 'email_exists' || qs.fp_register === 'invalid_email' || qs.fp_register === 'error') $('#fp-register-modal').fadeIn(200).attr('aria-hidden','false');
    })();
});

// Small utilities outside document ready
function showToast(message){
    var $t = jQuery('.toast');
    if(!$t.length){
        jQuery('body').append('<div class="toast">'+message+'</div>');
        $t = jQuery('.toast');
    }
    $t.text(message).fadeIn(200).css('display','block');
    setTimeout(function(){ $t.fadeOut(400); }, 2500);
}
