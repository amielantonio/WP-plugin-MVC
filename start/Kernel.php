<?php

class Kernel {

    /**
     * Run the kernel class
     */
    public static function run()
    {
        static::runRouters();
        static::runAPI();
        static::runShortcodes();
    }

    protected static function runRouters()
    {
        require_once S_BASEPATH . "/start/web.php";
        Router::run();
    }

    protected static function runScripts()
    {

    }

    protected static function runAPI()
    {
        require_once S_BASEPATH . "/start/api.php";
        Web::run();
    }

    protected static function runShortcodes()
    {
        require_once S_BASEPATH . "/start/shortcodes.php";
        Shortcode::run();
    }

}
