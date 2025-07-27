<script type="text/javascript">
    $(document).ready(function() {
        var newButtons = '' +
            ' <li>' +
            ' <a href="#" class="hasUl"><span aria-hidden="true" class="icon16 icomoon-icon-hammer"></span>IMH Plugins<span class="hasDrop icon16 icomoon-icon-arrow-down-2"></span></a>' +
            '      <ul class="sub">'
        <?php
        $plugins = [
            ['system' => 'imh-plugin-mgr', 'title' => 'IMH Plugin Manager'],
            ['system' => 'imh-new-plugin', 'title' => 'New Plugin Template'],
            ['system' => 'imh-php-extension', 'title' => 'PHP Extensions'],
            ['system' => 'imh-sys-snap', 'title' => 'System Snapshot']
        ];
        foreach ($plugins as $plugin) {
            $file = "/usr/local/cwpsrv/htdocs/resources/admin/modules/{$plugin['system']}.php";
            if (file_exists($file)) {
                echo "+'                <li><a href=\"index.php?module={$plugin['system']}\"><span class=\"icon16 icomoon-icon-arrow-right-3\"></span>{$plugin['title']}</a></li>'\n";
            }
        }

        ?>
            +
            '      </ul>' +
            '</li>';
        $(".mainnav > ul").append(newButtons);
    });
</script>