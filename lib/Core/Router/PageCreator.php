<?php

class PageCreator {

    protected $capabilityDefault = "manage_options";

    /**
     * Controller Namespace
     *
     * @var string
     */
    protected $controllerURL = "\App\Http\Controller\\";

    /**
     * @var RouteModel
     */
    protected $routeModel;


    public function __construct( RouteModel $routeModel = null )
    {
        $this->routeModel = ( is_null($routeModel) ) ? (new RouteModel) : $routeModel;
    }

    /**
     * Entrance to create the pages
     *
     * @param Router $page
     * @return bool
     */
    public function create( Router $page )
    {
        foreach( $page::$pages as $page ) {
            $this->{'create_'.$page['location']}( $page );
        }

        return true;
    }

    /**
     * Create a menu page
     *
     * @param $page
     * @return string
     */
    protected function create_menu( $page )
    {
        $capability = isset($page['capability']) ? $page['capability'] : $this->capabilityDefault;

        if( current_user_can( $capability ) ){
            return add_menu_page(
                __( $page['title'], 'textdomain' ),
                __( $page['title'], 'textdomain' ),
                isset($page['capability']) ? $page['capability'] : $this->capabilityDefault,
                isset( $page['menu_slug'] ) ? $page['menu_slug'] : $this->toSlug( $page['title'] ),
                $this->setMethod( $page['function'] ),
                isset( $page['icon_url'] ) ? $page['icon_url'] : "",
                5
            );
        }

        return "";
    }

    /**
     * Create a Submenu page
     *
     * @param $page
     * @return false|string
     */
    protected function create_submenu( $page )
    {
        $capability = isset($page['capability']) ? $page['capability'] : $this->capabilityDefault;
        $parent_slug = $this->toSlug( $page['parent_name'] );

        if( current_user_can( $capability ) ){
            return add_submenu_page(
                $parent_slug,
                __($page['title'], 'textdomain' ),
                __($page['title'], 'textdomain' ),
                isset($page['capability']) ? $page['capability'] : $this->capabilityDefault,
                isset( $page['menu_slug'] ) ? $page['menu_slug'] : $this->toSlug( $page['title'] ),
                $this->setMethod( $page['function'] )
            );
        }

        return "";
    }

    /**
     * Creates the a woocommerce tab and panel that will be shown inside the product data
     *
     * @param $page
     */
    protected function create_woocommerce_tabs($page)
    {
        $class = [];

        if (isset($page['product_type'])) {
            if (is_array($page['product_type'])) {
                foreach ($page['product_type'] as $productType) {
                    $class[] = 'show_if_' . $productType;
                }
            } else {
                $class = 'show_if_' . $page['product_type'];
            }
        }

        $controllerClass = "\\App\Http\Controller\\" . $page['function']['controller'];

        $controller = new $controllerClass;

        $saveMethod = isset($page['save']) ? $page['save'] : "save";
        $panelMethod = isset($page['panel']) ? $page['panel'] : "panel";

        $slug = $this->toSlug($page['title']);
        $tab = $slug . "_tab";

        add_filter('woocommerce_product_data_tabs', function ($tabs) use ($page, $class, $tab) {
            $tabs[$tab] = [
                'label' => __($page['title'], 'textdomain'),
                'target' => $tab,
                'class' => $class,
            ];

            return $tabs;
        });

        add_action('woocommerce_product_data_panels', function () use ($controller, $panelMethod, $tab) {
            echo "<div id='{$tab}' class='panel wc-metaboxes-wrapper'>";
                if (method_exists($controller, $panelMethod)) {
                    $controller->$panelMethod();
                } else {
                    echo "Panel method does not exists";
                };
            echo  "</div><!-- END TAB -->";

        });

        add_action('woocommerce_process_product_meta', function () use ($controller, $saveMethod) {
            if (method_exists($controller, 'beforeSave')) {
                $controller->beforeSave();
            }

            if (method_exists($controller, $saveMethod)) {

                $controller->$saveMethod();

            } else {
                throw new Exception('no save method indicated');
            }

            if (method_exists($controller, 'afterSave')) {
                $controller->afterSave();
            }

        });
    }


    protected function create_woocommerce_settings($page)
    {

        $controllerClass = "\\App\Http\Controller\\" . $page['function']['controller'];

        $settingsMethod = isset($page['settings']) ? $page['settings'] : "settings";

        add_filter('woocommerce_settings_tabs_array', function ($settings_tabs) use( $page ) {
            $settings_tabs['settings_tab_demo'] = __( addslashes($page['title']), 'woocommerce-settings-tab-demo' );
            return $settings_tabs;
        }, 50, 1);


        add_filter('woocommerce_settings_tabs_settings_tab_demo', function () use($controllerClass, $settingsMethod) {
            woocommerce_admin_fields($controllerClass::$settingsMethod());
        });

        add_filter('woocommerce_update_options_settings_tab_demo', function () use($controllerClass, $settingsMethod) {
            woocommerce_update_options($controllerClass::$settingsMethod());
        });
    }


    protected function create_woocommerce_accounts_tab( $page )
    {
        $controllerClass = "\\App\Http\Controller\\" . $page['function']['controller'];
        $controller = new $controllerClass;

        $account_title = addslashes($page['title']);
        $account_slug = sanitize_title($account_title);
        $contentMethod = isset($page['function']['method']) ? $page['function']['method'] : "content";

        //Endpoint
        add_action('init', function() use($account_slug){
            add_rewrite_endpoint($account_slug, EP_ROOT | EP_PAGES);
        });

        //Query Vars
        add_filter("woocommerce_get_query_vars", function($vars) use($account_slug){
            $vars[] = $account_slug;
            return $vars;
        }, 0);

        //Menu items
        add_filter('woocommerce_account_menu_items', function($items) use ($account_title, $account_slug){
            var_dump($items);
            $items[$account_slug] = $account_title;
            return $items;
        });

        add_action("woocommerce_account_{$account_slug}_title", function() use($account_title){
            $title = __($account_title);
            return $title;
        });

        add_action("woocomerce_account_{$account_slug}_endpoint", [$controller, $contentMethod]);

    }

    /**
     * Sets the method to be used for the menu
     *
     * @param $method
     * @return array | Closure
     */
    protected function setMethod( $method )
    {
        //Check whether the url specifies a channel for the route,
        //If it has then return the associated controller and method
        //for the route channel.
        if( Router::isBeingListened() ) {

            $controller = "\\App\Http\Controller\\".Router::getController();

            $method = Router::getMethod();

            return function() use ($controller, $method){
                return RouteModel::bind( $controller, $method );
            };
        }

        //Return the closure immediately
        if( $method instanceof Closure) {
            return $method;
        }

        //If we don't have any route channel specified or the method being passed
        //is not an instance of a Closure, then return the class and method that
        //the user defined on its routes
        $setClass =  $this->controllerURL.$method['controller'];

        $class = new $setClass;

        return [ $class, $method['method'] ];
    }

    /**
     * To slug, {Should transfer to helper functions instead}
     *
     * @param $string
     * @return null|string|string[]
     */
    protected function toSlug( $string )
    {
        // replace non letter or digits by -
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);

        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);

        // remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', $string);

        // trim
        $string = trim($string, '-');

        // remove duplicate -
        $string = preg_replace('~-+~', '-', $string);

        // lowercase
        $string = strtolower($string);

        if (empty($string)) {
            return 'n-a';
        }

        return $string;
    }
}
