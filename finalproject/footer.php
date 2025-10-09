    </main>
    <footer class="site-footer" style="margin-top:40px;">
        <div class="container footer-grid" style="padding:28px 0;color:var(--muted)">
            <div class="footer-col footer-about">
                <h4>About</h4>
                <p style="margin:6px 0 0">Track your crypto holdings, visualise allocations and manage trades in one simple dashboard.</p>
            </div>
            <div class="footer-col footer-links">
                <h4>Quick Links</h4>
                <?php if ( has_nav_menu( 'primary' ) ) { wp_nav_menu( array( 'theme_location' => 'primary', 'container' => '', 'menu_class' => 'footer-menu' ) ); } else { ?>
                    <ul class="footer-menu"><li><a href="<?php echo esc_url( home_url('/') ); ?>">Home</a></li></ul>
                <?php } ?>
            </div>
            <div class="footer-col footer-resources">
                <h4>Resources</h4>
                <ul class="footer-menu">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Docs</a></li>
                    <li><a href="#">Privacy</a></li>
                </ul>
            </div>
            <div class="footer-col footer-account">
                <h4>Account</h4>
                <div class="account-area">
                    <?php if ( is_user_logged_in() ) :
                        $user = wp_get_current_user(); ?>
                        <div class="account-info">Hello, <?php echo esc_html( $user->display_name ?: $user->user_login ); ?></div>
                        <a class="button" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">Logout</a>
                        <a class="button" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">Profile</a>
                    <?php else : ?>
                        <a class="button" href="#" data-open="fp-login">Login</a>
                        <a class="button" href="#" data-open="fp-register">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="container" style="border-top:1px solid rgba(255,255,255,0.03);padding:12px 0;margin-top:8px;color:var(--muted);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap">
            <div>&copy; <?php echo date('Y'); ?> Crypto Portfolio. All rights reserved.</div>
            <div style="display:flex;gap:12px;align-items:center">
                <a href="#" aria-label="Twitter">Twitter</a>
                <a href="#" aria-label="GitHub">GitHub</a>
                <a href="#" aria-label="Docs">Docs</a>
            </div>
        </div>
    </footer>
    <!-- Login Modal -->
    <div class="modal-backdrop" id="fp-login-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="fp-login-title">
            <h3 id="fp-login-title">Login</h3>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <input type="hidden" name="action" value="fp_frontend_login" />
                <input type="hidden" name="fp_login_nonce" value="<?php echo esc_attr( wp_create_nonce('fp_frontend_login') ); ?>" />
                <label>Username or Email: <input type="text" name="fp_user" required /></label>
                <label>Password: <input type="password" name="fp_pass" required /></label>
                <label><input type="checkbox" name="fp_remember" /> Remember me</label>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                    <button type="button" class="btn btn-cancel" id="fp-login-cancel">Cancel</button>
                    <button type="submit" class="btn btn-confirm">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal-backdrop" id="fp-register-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="fp-register-title">
            <h3 id="fp-register-title">Register</h3>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <input type="hidden" name="action" value="fp_frontend_register" />
                <input type="hidden" name="fp_register_nonce" value="<?php echo esc_attr( wp_create_nonce('fp_frontend_register') ); ?>" />
                <label>Username: <input type="text" name="fp_username" /></label>
                <label>Email: <input type="email" name="fp_email" required /></label>
                <label>Password: <input type="password" name="fp_password" required /></label>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                    <button type="button" class="btn btn-cancel" id="fp-register-cancel">Cancel</button>
                    <button type="submit" class="btn btn-confirm">Register</button>
                </div>
            </form>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
