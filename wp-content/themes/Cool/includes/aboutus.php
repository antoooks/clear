<?php

    new Themater_AboutUs();
    
    class Themater_AboutUs
    {
        var $theme;
        
        var $defaults = array(
            'enabled' => 'true',
            'hook' => 'main_before',
            'title' => 'Welcome to our website',
            'image' => 'about-image.jpg',
            'content' => 'Lorem ipsum eu usu assum liberavisse, ut munere praesent complectitur mea. Sit an option maiorum principes. Ne per probo magna idque, est veniam exerci appareat no. Sit at amet propriae intellegebat, natum iusto forensibus duo ut.'
        );
        
        function __construct()
        {
            global $theme;
            $this->theme = $theme;
            
            if( array_key_exists('aboutus', $this->theme->options['plugins_options']) ) {
                $this->defaults = array_merge($this->defaults, $this->theme->options['plugins_options']['aboutus']);
            }
            
            $this->theme->add_hook($this->defaults['hook'], array($this, 'display_aboutus'));
            $this->aboutus_options();
            
        }

        
        function display_aboutus()
        {
            if(is_home() && $this->theme->display("aboutus_enabled")) {
                ?><div class="span-24 aboutusbox">
                
                <?php 
                
                if($this->theme->display('aboutus_title')) {
                    echo '<h2 class="aboutusbox-title">' . $this->theme->get_option('aboutus_title') . '</h2>';
                }
                
                if($this->theme->display('aboutus_image')) {
                    echo '<img class="aboutusbox-image" src="' . $this->theme->get_option('aboutus_image') . '" />';
                }
                
                if($this->theme->display('aboutus_content')) {
                    echo '<div class="aboutusbox-content">' . $this->theme->get_option('aboutus_content') . '</div>';
                }
                ?></div><?php
            }
        }
        
        function aboutus_options()
        {
            $this->theme->admin_option(array('About Us', 14), 
                '"About Us" section enabled?', 'aboutus_enabled', 
                'checkbox', $this->defaults['enabled'], 
                array('display'=>'inline')
            );
            
            $this->theme->admin_option('About Us', 
                'Title', 'aboutus_title', 
                'text', $this->defaults['title']
            );
       
            $this->theme->admin_option('About Us', 
                'Image', 'aboutus_image', 
                'imageupload', '', 
                array('help' => "Enter the full url. Leave it blank if you don't want to use an image.")
            );
            
            $this->theme->admin_option('About Us', 
                'Content', 'aboutus_content', 
                'textarea', $this->defaults['content'],
                array('style'=>'height: 250px;')
            );
        }
    }
?>