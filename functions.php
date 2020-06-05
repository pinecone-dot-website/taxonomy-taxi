<?php

namespace Taxonomy_Taxi;

/**
 * Render a page into wherever (admin)
 * 
 * @param $_template string
 * @param $vars      object|array
 * 
 * @return string html
 */
function render($_template, $vars = [])
{
    if (file_exists(__DIR__ . '/views/' . $_template . '.php')) {
        $_template_file = __DIR__ . '/views/' . $_template . '.php';
    } else {
        return "<div>template missing: $_template</div>";
    }

    extract((array) $vars, EXTR_SKIP);

    ob_start();
    require $_template_file;
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

/**
 * Gets the version of the plugin
 * 
 * @return string
 */
function version()
{
    $data = get_plugin_data(__DIR__ . '/_plugin.php');
    return $data['Version'];
}
