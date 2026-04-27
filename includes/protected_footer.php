<footer class="app-footer">
    <div class="footer-inner">

        <div class="footer-left">
            <h3><?= htmlspecialchars($settings['system_name'] ?? 'System'); ?></h3>
            <p><?= htmlspecialchars($settings['footer_text'] ?? ''); ?></p>
        </div>

        <div class="footer-center">
            <a href="https://www.cut.ac.cy" target="_blank">CUT Website</a>
            <a href="#">Instagram</a>
            <a href="#">LinkedIn</a>
        </div>

        <div class="footer-right">
            <img
                    src="https://www.cut.ac.cy/digitalAssets/441/441550_1cut_logo_rgb_english_transparent_large.png"
                    alt="CUT Logo"
                    class="footer-logo"
            >
            <span><?= htmlspecialchars($settings['contact_email'] ?? ''); ?></span>
        </div>

    </div>
    <script src="../utils/chart_utils.js"></script>

</footer>