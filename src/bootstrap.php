<?php
/**
 * Boostrapping and common utility definition.
 */

//加载配置文件
Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.default.php');
if (file_exists(XHGUI_ROOT_DIR . '/config/config.php')) {
    Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
}
