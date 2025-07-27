<?php

/**
 * imh-plugin-mgr, a Web Interface for cPanel/WHM and CWP
 *
 * Provides things,
 * and stuff.
 *
 * Compatible with:
 *   - cPanel/WHM: /usr/local/cpanel/whostmgr/docroot/cgi/imh-plugin-mgr/index.php
 *   - CWP:       /usr/local/cwpsrv/htdocs/resources/admin/modules/imh-plugin-mgr.php
 *
 * Author: 
 * Maintainer: InMotion Hosting
 * Version: 0.0.2
 */


// ==========================
// 1. Environment Detection
// 2. Session & Security
// 3. HTML Header & CSS
// 4. Main Interface
// 5. First Tab
// 6. Second Tab
// 7. HTML Footer
// ==========================





// ==========================
// 1. Environment Detection
// ==========================

declare(strict_types=1);

$script_name = "imh-plugin-mgr";

$isCPanelServer = (
    (is_dir('/usr/local/cpanel') || is_dir('/var/cpanel') || is_dir('/etc/cpanel')) && (is_file('/usr/local/cpanel/cpanel') || is_file('/usr/local/cpanel/version'))
);

$isCWPServer = (
    is_dir('/usr/local/cwp')
);

if ($isCPanelServer) {
    if (getenv('REMOTE_USER') !== 'root') exit('Access Denied');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else { // CWP
    if (!isset($_SESSION['logged']) || $_SESSION['logged'] != 1 || !isset($_SESSION['username']) || $_SESSION['username'] !== 'root') {
        exit('Access Denied');
    }
};










// ==========================
// 2. Session & Security
// ==========================

$CSRF_TOKEN = NULL;

if (!isset($_SESSION['csrf_token'])) {
    $CSRF_TOKEN = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $CSRF_TOKEN;
} else {
    $CSRF_TOKEN = $_SESSION['csrf_token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        exit("Invalid CSRF token");
    }
}

define('IMH_SAR_CACHE_DIR', '/root/tmp/' . $script_name . '');

if (!is_dir(IMH_SAR_CACHE_DIR)) {
    mkdir(IMH_SAR_CACHE_DIR, 0700, true);
}

// Clear old cache files

$cache_dir = IMH_SAR_CACHE_DIR;
$expire_seconds = 3600; // e.g. 1 hour

foreach (glob("$cache_dir/*.cache") as $file) {
    if (is_file($file) && (time() - filemtime($file) > $expire_seconds)) {
        unlink($file);
    }
}

function imh_safe_cache_filename($tag)
{
    return IMH_SAR_CACHE_DIR . '/sar_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $tag) . '.cache';
}


function imh_cached_shell_exec($tag, $command, $sar_interval)
{
    $cache_file = imh_safe_cache_filename($tag);



    if (file_exists($cache_file)) {
        if (fileowner($cache_file) !== 0) { // 0 = root
            unlink($cache_file);
            // treat as cache miss
        } else {
            $mtime = filemtime($cache_file);
            if (time() - $mtime < $sar_interval) {
                return file_get_contents($cache_file);
            }
        }
    }
    $out = shell_exec($command);
    if (strlen(trim($out))) {
        file_put_contents($cache_file, $out);
    }
    return $out;
}












// ==========================
// 3. HTML Header & CSS
// ==========================

if ($isCPanelServer) {
    require_once('/usr/local/cpanel/php/WHM.php');
    WHM::header($script_name . ' WHM Interface', 0, 0);

    // Find all key files in /var/cpanel/cluster/*/config/imh
    $key_files = [];
    $cluster_dir = '/var/cpanel/cluster';
    if (is_dir($cluster_dir)) {
        foreach (glob($cluster_dir . '/*/config/imh') as $file) {
            if (is_file($file)) {
                $key_files[] = [
                    'username' => basename(dirname(dirname($file))), // Extract username from path
                    'contents' => is_readable($file) ? file_get_contents($file) : null
                ];
            }
        }
    }
    $platform = 'cPanel';
} else {
    echo '<div class="panel-body">';
    $key_file = '/opt/imh-cwp-dns/config/key';
    $platform = 'CWP';
};








// Styles for the tabs and buttons

?>

<style>
    .imh-title {
        margin: 0.25em 0 1em 0;
    }

    .imh-title-img {
        margin-right: 0.5em;
    }

    .tabs-nav {
        display: flex;
        border-bottom: 1px solid #e3e3e3;
        margin-bottom: 2em;
    }

    .tabs-nav button {
        border: none;
        background: #f8f8f8;
        color: #333;
        padding: 12px 28px;
        cursor: pointer;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        font-size: 1em;
        margin-bottom: -1px;
        border-bottom: 2px solid transparent;
        transition: background 0.15s, border-color 0.15s;
    }

    .tabs-nav button.active {
        background: #fff;
        border-bottom: 2px solid rgb(175, 82, 32);
        color: rgb(175, 82, 32);
        font-weight: 600;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .imh-box {
        margin: 2em 0;
        padding: 1em;
        border: 1px solid #ccc;
        border-radius: 8px;
        display: block;
    }

    .imh-box.margin-bottom {
        margin-bottom: 1em;
    }

    .imh-larger-text {
        font-size: 1.5em;
    }

    .imh-spacer {
        margin-top: 2em;
    }

    .imh-footer-box {
        margin: 2em 0 2em 0;
        padding: 1em;
        border: 1px solid #ccc;
        border-radius: 8px;
        display: block;
    }

    .imh-footer-img {
        margin-bottom: 1em;
    }

    .imh-footer-box a {
        color: rgb(175, 82, 32);
    }

    .imh-footer-box a:hover,
    .imh-footer-box a:focus {
        color: rgb(97, 51, 27);
    }

    .imh-plugins-list {
        background: #fff;
        /* For light mode */
        /* For dark mode, you could use: background: #222; */
        color: #222;
        border-radius: 8px;
    }

    .imh-plugins-list th,
    .imh-plugins-list td {
        padding: 12px 10px;
        vertical-align: top;
        font-size: 1.06em;
    }

    .imh-plugins-list tr:nth-child(even) {
        background: #fafafa;
    }

    .imh-plugins-list tr:hover {
        background: #f4f8fc;
    }

    .imh-plugins-list a,
    .imh-plugins-list button {
        text-decoration: none;
        font-weight: 500;
    }

    .imh-plugins-list button:focus {
        outline: 1px dotted #555;
    }
</style>

<?php





// ==========================
// 4. Main Interface
// ==========================

$img_src = $isCWPServer ? 'design/img/' . $script_name . '.png' : $script_name . '.png';
echo '<h1 class="imh-title"><img src="' . htmlspecialchars($img_src) . '" alt="new-plugin" class="imh-title-img" />IMH Plugin Manager</h1>';



// This is the tab selector for the two main sections

echo '<div class="tabs-nav" id="imh-tabs-nav">
    <button type="button" class="active" data-tab="tab-one" aria-label="Plugin Manager">Plugin Manager</button>
    <button type="button" data-tab="tab-two" aria-label="IMH DNS API Key">IMH DNS API Key</button>
</div>';





// Tab selector script

?>

<script>
    // Tab navigation functionality

    document.querySelectorAll('#imh-tabs-nav button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Remove 'active' class from all buttons and tab contents
            document.querySelectorAll('#imh-tabs-nav button').forEach(btn2 => btn2.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            // Activate this button and the corresponding tab
            btn.classList.add('active');
            var tabId = btn.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
</script>
<?php






// ==========================
// 5. Plugin Manager Tab
// ==========================

echo '<div id="tab-one" class="tab-content active">';

// Fetch plugin list from remote (or local cache if you prefer)
$plugin_list_url = 'https://raw.githubusercontent.com/gemini2463/imh-plugin-mgr/master/plugin-list.jsonl';
$plugin_list = @file($plugin_list_url, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$plugins = [];
foreach ($plugin_list as $line) {
    $plugins[] = json_decode($line, true);
}

// Helper: Get plugin file path by plugin_name and environment
function imh_plugin_path($plugin_name, $isCPanelServer)
{
    if ($isCPanelServer) {
        // cPanel/WHM
        return "/usr/local/cpanel/whostmgr/docroot/cgi/{$plugin_name}/index.php";
    } else {
        // CWP
        return "/usr/local/cwpsrv/htdocs/resources/admin/modules/{$plugin_name}.php";
    }
}

// Placeholder: Is this plugin installed? (Just checks file exists)
function imh_plugin_installed($plugin_name, $isCPanelServer)
{
    $path = imh_plugin_path($plugin_name, $isCPanelServer);
    return file_exists($path);
}

// Placeholder: Get installed version of plugin (stub for now)
function imh_plugin_installed_version($plugin_name, $isCPanelServer)
{
    $path = imh_plugin_path($plugin_name, $isCPanelServer);
    if (!file_exists($path)) {
        return null;
    }
    $contents = file_get_contents($path);
    if (preg_match('/^\s*\*\s*Version:\s*([^\s*]+).*$/mi', $contents, $m)) {
        return $m[1];
    }
    return 'Unknown';
}

//  Uninstall plugin 
function imh_plugin_uninstall($plugin_name, $isCPanelServer)
{
    if ($plugin_name === 'imh-plugin-mgr') {
        // Don't allow uninstall of self
        return false;
    }

    if ($isCPanelServer) {
        // Remove cPanel files
        $cgi_dir = "/usr/local/cpanel/whostmgr/docroot/cgi/$plugin_name";
        $conf_file = "/var/cpanel/apps/{$plugin_name}.conf";
        $addon_png = "/usr/local/cpanel/whostmgr/docroot/addon_plugins/{$plugin_name}.png";
        $appconfig_path = "/usr/local/cpanel/whostmgr/docroot/cgi/{$plugin_name}/{$plugin_name}.conf";
        $register_appconfig = "/usr/local/cpanel/bin/register_appconfig";
        // Remove CGI directory
        if (is_dir($cgi_dir)) shell_exec("rm -rf " . escapeshellarg($cgi_dir));
        // Remove conf file
        if (file_exists($conf_file)) shell_exec("rm -f " . escapeshellarg($conf_file));
        // Remove addon png
        if (file_exists($addon_png)) shell_exec("rm -f " . escapeshellarg($addon_png));
        // Unregister appconfig
        if (file_exists($register_appconfig) && file_exists($appconfig_path)) {
            shell_exec(escapeshellarg($register_appconfig) . " --unregister " . escapeshellarg($appconfig_path));
            shell_exec("/usr/local/cpanel/bin/unregister_appconfig " . escapeshellarg($plugin_name));
        }
    } else {
        // CWP: Remove files
        $php_file = "/usr/local/cwpsrv/htdocs/resources/admin/modules/{$plugin_name}.php";
        $img_file = "/usr/local/cwpsrv/htdocs/admin/design/img/{$plugin_name}.png";
        $js_file  = "/usr/local/cwpsrv/htdocs/admin/design/js/{$plugin_name}.js";
        if (file_exists($php_file)) shell_exec("rm -f " . escapeshellarg($php_file));
        if (file_exists($img_file)) shell_exec("rm -f " . escapeshellarg($img_file));
        if (file_exists($js_file)) shell_exec("rm -f " . escapeshellarg($js_file));
    }

    // Also remove $HOME/root/PLUGIN_NAME for non-panel (if used)
    $plain_dir = "/root/$plugin_name";
    if (is_dir($plain_dir)) shell_exec("rm -rf " . escapeshellarg($plain_dir));
    return true;
}

// Update plugin
function imh_plugin_update($plugin_name, $isCPanelServer)
{
    // Get the plugin list (in memory or refetch)
    $plugin_list_url = 'https://raw.githubusercontent.com/gemini2463/imh-plugin-mgr/master/plugin-list.jsonl';
    $plugin_list = @file($plugin_list_url, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($plugin_list as $line) {
        $plugin = json_decode($line, true);
        if ($plugin['plugin_name'] === $plugin_name) {
            $cmd = $plugin['install_command'];
            shell_exec($cmd . " > /dev/null 2>&1"); // hide output if desired
            return true;
        }
    }
    return false;
}

// Handle action POSTs (install/uninstall/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plugin_action'], $_POST['plugin_name'])) {
    $plugin_action = $_POST['plugin_action'];
    $plugin_name   = $_POST['plugin_name'];

    if ($plugin_action === 'install') {
        // Find install_command from plugin list
        foreach ($plugins as $p) {
            if ($p['plugin_name'] === $plugin_name) {
                $cmd = $p['install_command'];
                shell_exec($cmd); // Uncomment if safe
                $msg = "Plugin $plugin_name installed";
                break;
            }
        }
    } elseif ($plugin_action === 'uninstall') {
        if ($plugin_name === 'imh-plugin-mgr') {
            $msg = "<span style='color:#b32d2e;'>Uninstall is not allowed for the IMH Plugin Manager!</span>";
        } else {
            if (imh_plugin_uninstall($plugin_name, $isCPanelServer)) {
                $msg = "Plugin <strong>" . htmlspecialchars($plugin_name) . "</strong> uninstalled.";
            } else {
                $msg = "Failed to uninstall <strong>" . htmlspecialchars($plugin_name) . "</strong>";
            }
        }
    } elseif ($plugin_action === 'update') {
        if (imh_plugin_update($plugin_name, $isCPanelServer)) {
            $msg = "Plugin <strong>" . htmlspecialchars($plugin_name) . "</strong> updated.";
        } else {
            $msg = "Failed to update <strong>" . htmlspecialchars($plugin_name) . "</strong>";
        }
    }
    if (isset($msg)) echo "<div class='imh-box' style='background:#e3fad9;border:1px solid #b5e6b7'>{$msg}</div>";
}

// Table header
echo '<table class="imh-plugins-list" style="width:100%; border-collapse:collapse; margin-bottom:2em;">';
echo '<tr>
    <th style="text-align:left; width: 25%;">Plugin</th>
    <th style="text-align:left;">Description</th>
    <th style="text-align:left; width: 9%;">Installed Version</th>
    <th style="text-align:left; width: 9%;">Available Version</th>
    <th style="text-align:left; width: 18%;">Actions</th>
</tr>';

foreach ($plugins as $plugin) {
    $plugin_name     = $plugin['plugin_name'];
    $is_manager      = ($plugin_name === 'imh-plugin-mgr');
    $is_installed    = imh_plugin_installed($plugin_name, $isCPanelServer);

    $installed_version = imh_plugin_installed_version($plugin_name, $isCPanelServer);
    $available_version = $plugin['version'];

    // Compare: treat null installed_version as not installed
    $can_update = false;
    if ($is_installed && $installed_version !== null && $available_version) {
        $can_update = version_compare($available_version, $installed_version, '>');
    }

    echo '<tr style="border-top:1px solid #e3e3e3;">';
    echo '<td><strong>' . htmlspecialchars($plugin['title']) . '</strong></td>';
    echo '<td>' . htmlspecialchars($plugin['description']) . '</td>';
    echo '<td>' . ($installed_version !== null ? htmlspecialchars($installed_version) : '<span style="color:#b0b0b0;">Not installed</span>') . '</td>';
    echo '<td>' . htmlspecialchars($available_version) . '</td>';
    echo '<td>';

    // Actions logic
    if ($is_installed) {
        if ($can_update) {
            echo '<form method="post" style="display:inline;margin:0;">
                    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($CSRF_TOKEN) . '">
                    <input type="hidden" name="plugin_action" value="update">
                    <input type="hidden" name="plugin_name" value="' . htmlspecialchars($plugin_name) . '">
                    <button type="submit" style="background:none;border:none;color:#f39c12;cursor:pointer;padding:0;margin-right:8px;">Update</button>
                  </form>';
        }
        // Only show uninstall if not IMH manager plugin
        if (!$is_manager) {
            echo '<form method="post" style="display:inline;margin:0;">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($CSRF_TOKEN) . '">
                <input type="hidden" name="plugin_action" value="uninstall">
                <input type="hidden" name="plugin_name" value="' . htmlspecialchars($plugin_name) . '">
                <button type="submit" style="background:none;border:none;color:#b32d2e;cursor:pointer;padding:0;">Uninstall</button>
              </form>';
        } //else {
        //echo '<span style="color:#888;">-</span>';
        //}
    } else {
        echo '<form method="post" style="display:inline;margin:0;">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($CSRF_TOKEN) . '">
                <input type="hidden" name="plugin_action" value="install">
                <input type="hidden" name="plugin_name" value="' . htmlspecialchars($plugin_name) . '">
                <button type="submit" style="background:none;border:none;color:#0073aa;cursor:pointer;padding:0;">Install</button>
              </form>';
    }

    echo '</td>';
    echo '</tr>';
}
echo '</table>';

echo "</div>";












// ==========================
// 6. Second Tab
// ==========================

echo '<div id="tab-two" class="tab-content">';

echo "<div class='imh-box imh-box.margin-bottom'><p class='imh-larger-text'>IMH DNS API Key</p>";

if ($key_file) {
    if (is_readable($key_file)) {
        $contents = file_get_contents($key_file);
        if (strlen(trim($contents))) {
            // Show file contents in <pre>
            echo "<strong>File:</strong> <code>" . htmlspecialchars($key_file) . "</code><br>";
            echo "<pre style='margin-top:1em;background:#fafafa;border:1px solid #eee;padding:.75em;'>" . htmlspecialchars($contents) . "</pre>";
        } else {
            echo "<span style='color:#b32d2e;'>The key file exists but is empty.</span>";
        }
    } else {
        if (file_exists($key_file)) {
            echo "<span style='color:#b32d2e;'>The key file exists but is not readable.<br>Check file permissions for <code>" . htmlspecialchars($key_file) . "</code>.</span>";
        } else {
            echo "<span style='color:#b32d2e;'>The key file <code>" . htmlspecialchars($key_file) . "</code> was not found on this $platform server.</span>";
        }
    }
}

if ($key_files) {
    echo "<div style='margin-top:1em;'>";
    echo "<strong>Cluster Key Files:</strong><br>";
    foreach ($key_files as $kf) {
        echo "<div style='margin-bottom:1em;'>";
        echo "<span style='font-weight:600;'>User:</span> " . htmlspecialchars($kf['username']) . "<br>";
        if ($kf['contents'] !== null && strlen(trim($kf['contents']))) {
            echo "<pre style='background:#fafafa;border:1px solid #eee;padding:.75em;'>" . htmlspecialchars($kf['contents']) . "</pre>";
        } else {
            echo "<span style='color:#b32d2e;'>Key file exists but is empty or unreadable.</span>";
        }
        echo "</div>";
    }
    echo "</div>";
}

echo '</div>';

echo "</div>";


// ==========================
// 7. HTML Footer
// ==========================

echo '<div class="imh-footer-box"><img src="' . htmlspecialchars($img_src) . '" alt="new-plugin" class="imh-footer-img" /><p>Plugins by <a href="https://inmotionhosting.com" target="_blank">InMotion Hosting</a>.</p></div>';




if ($isCPanelServer) {
    WHM::footer();
} else {
    echo '</div>';
};
