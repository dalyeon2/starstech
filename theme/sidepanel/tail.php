        <footer class="theme-footer">
            &copy; Genebiotehch. All rights reserved.
        </footer>
        </main>
    </div> <!-- /.panel-layout -->
</div> <!-- /#wrap -->

<?php
$standalone = !defined('_GNUBOARD_') || (defined('G5_IS_ADMIN') && G5_IS_ADMIN);

if ($standalone) {
    ?>
</body>
</html>
<?php
} else {
    if (defined('G5_THEME_PATH') && file_exists(G5_THEME_PATH . '/tail.sub.php')) {
        include_once(G5_THEME_PATH . '/tail.sub.php');
    }
}
