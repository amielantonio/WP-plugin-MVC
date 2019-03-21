<?php
namespace App\Core\Pages;

use Closure;
use App\Core\Pages\PageCreator;

class Page {

    /**
     * Storage for page information
     *
     *
     * @var array
     */
    public static $pages = [];

    /**
     * Page Instance
     *
     * @var Page
     */
    protected static $instance;

    /**
     * @var \App\Core\Pages\PageCreator
     */
    protected $creator;

    /**
     * Page constructor.
     */
    public function __construct()
    {
        $this->creator = new PageCreator();
    }

    /**
     * Create a page?
     *
     * @param string $location
     * @param string $title
     * @param Closure|string $controller
     * @param array $settings
     * @return Page
     */
    public static function add( $location, $title, $controller, $settings = [] )
    {
        //Check for Instance
        if( self::$instance === null ) {
            self::$instance = new self;
        }

        $function = ( $controller instanceof Closure )
                        ? $controller
                        : self::$instance->setController( $controller );


        self::$pages[] = array_merge( compact( 'location', 'title', 'function' ), $settings );

        return self::$instance;

    }


    /**
     * Binds the built array to the admin_menu action of Wordpress
     */
    public static function create()
    {
        add_action( 'admin_menu', array( self::$instance, 'create_pages' ) );
    }

    /**
     * Creates the pages that will be binded to Wordpress menu
     *
     * @return bool
     */
    public function create_pages()
    {
         return self::$instance->creator->create( self::$instance ) ;
    }

    /**
     * Create a primary menu on the sidebar of Wordpress Admin
     *
     * @param $name
     * @param $controller
     * @param array $settings
     * @return Page
     */
    public static function addMenu( $name, $controller, $settings = [] )
    {
        self::add( 'menu', $name, $controller, $settings );

        return self::$instance;
    }

    /**
     * Create a submenu under the parent name that was passed
     *
     * @param $parent_name
     * @param $name
     * @param $controller
     * @param array $settings
     * @return Page
     */
    public static function addSubMenu( $parent_name, $name, $controller, $settings = [] )
    {
        self::add( 'submenu',
            $name,
            $controller,
            array_merge(['parent_name' => $parent_name], $settings));

        return self::$instance;
    }

    /**
     * Create a submenu under the Dashboard tab
     *
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addDashboard( $name, $controller, $settings = [] )
    {
        self::add( 'dashboard', $name, $controller, $settings );
    }

    /**
     * Create a submenu under the Post tab
     *
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addPosts( $name, $controller, $settings = [] )
    {
        self::add( 'posts', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addUtility( $name, $controller, $settings = [] )
    {
        self::add( 'utility', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addMedia( $name, $controller, $settings = [] )
    {
        self::add( 'media', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addComments( $name, $controller, $settings = [] )
    {
        self::add( 'comments', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addPages( $name, $controller, $settings = [] )
    {
        self::add( 'pages', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addUsers( $name, $controller, $settings = [] )
    {
        self::add( 'users', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addTheme( $name, $controller, $settings = [] )
    {
        self::add( 'theme', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addPlugins( $name, $controller, $settings = [] )
    {
        self::add( 'plugins', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addManagement( $name, $controller, $settings = [] )
    {
        self::add( 'management', $name, $controller, $settings );
    }

    /**
     * @param $name
     * @param $controller
     * @param array $settings
     */
    public function addOptions( $name, $controller, $settings = [] )
    {
        self::add( 'options', $name, $controller, $settings );
    }

    public function name( $name )
    {

    }

    /**
     * Set Controller and method to array
     *
     * @param $controller
     * @return array
     */
    protected function setController( $controller )
    {
        $method = explode( '@', $controller );

        return [
            'controller' => $method[0],
            'method' => $method[1]
        ];
    }

    protected function instance()
    {
        return $this;
    }

    protected function bindAction( $location, $action, $settings )
    {

    }

    protected function getBinding( $key )
    {

    }


}
