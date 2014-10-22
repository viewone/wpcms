<?php

namespace WPCms\Menu\Filters;

class WpNavMenuArgs {
    
    /* Set default values for additional arguments. 
     * 
     * @since 1.0.0 
     * 
     * @param array $args Arguments
     * @return array $args Arguments
     */ 
    public function setDefaultArgsFilter($args){
        
        $defaults = array(
            'child_of' => '',
            'child_of_post' => '',
            'expand_current' => false,
            'breadcrumb' => false,
        );

        $args = wp_parse_args( $args, $defaults );
        
        return $args;
    }
}