<?php global $theme; get_header(); ?>

    <div id="main">
    
        <?php $theme->hook('main_before'); ?>
        
        <div id="content">
            <?php $theme->hook('content_before'); ?>
            <div id="homepage-widgets" class="clearfix">
                
                <?php
                /**
                * Homepage  Widget Areas. Manage the widgets from: wp-admin -> Appearance -> Widgets 
                */
                ?>
                <div class="homepage-widget-box">
                    <?php
                        if(!dynamic_sidebar('homepage_1')) {
                            $theme->hook('homepage_1');
                        }
                    ?>
                </div>
                
                <div class="homepage-widget-box homepage-widget-box-last">
                    <?php
                        if(!dynamic_sidebar('homepage_2')) {
                            $theme->hook('homepage_2');
                        }
                    ?>
                </div>
            
            </div>
            <?php $theme->hook('content_after'); ?>
        </div><!-- #content -->
        
        <?php get_sidebars(); ?>  
        
        <?php $theme->hook('main_after'); ?>
        
    </div><!-- #main -->
    
<?php get_footer(); ?>