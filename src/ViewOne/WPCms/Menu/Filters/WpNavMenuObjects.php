<?php

namespace WPCms\Menu\Filters;

class WpNavMenuObjects {
    
    public function getNavMenuChildrenFilter($items, $args){
        
        if( !empty($args->child_of) ) {

            $items = $this->getNavMenuItemChildrensByMenuItem( $items, $args );
        }
        
        if( $args->breadcrumb ) {
            
            $current_menu_item_id = $this->getCurrentNavMenuItemId($items, $args);
            $items = $this->getNavMenuItemsToBreadcrumb( $items, $args, $current_menu_item_id );
            
            
            // If there is no items in menu create one from current page
            if(empty($items)){
                
                $postarr = array(
                    'post_title' => get_the_title(get_the_ID()),
                    'current' => true
                );

                $defaults = array('post_status' => 'draft', 'post_type' => 'post', 'post_author' => 0,
                    'ping_status' => get_option('default_ping_status'), 'post_parent' => 0,
                    'menu_order' => 0, 'to_ping' =>  '', 'pinged' => '', 'post_password' => '',
                    'guid' => '', 'post_content_filtered' => '', 'post_excerpt' => '', 'import_id' => 0,
                    'post_content' => '', 'post_title' => '');

                $postarr = wp_parse_args($postarr, $defaults);

                unset( $postarr[ 'filter' ] );

                $post = (object) sanitize_post($postarr, 'db');
                $item = wp_setup_nav_menu_item($post);
                
                $item->object_id = get_queried_object_id();
                
                $items[] = $item;
                
                _wp_menu_item_classes_by_context($items);
            }
        }

        return $items;
    }
    
    
    public function getNavMenuItemChildrensByMenuItem($items, $args){
            
        if (is_int($args->child_of)){
            $items = $this->getNavMenuItemsChildrensByMenuItem($items, $args, $args->child_of);
        }
        
        if(is_string($args->child_of)){
            
            if ($args->child_of == 'current_root'){
                $items = $this->getNavMenuItemChildrensByCurrentRootMenuItem($items, $args);
            }
            
            if ($args->child_of == 'current'){
                $items = $this->getNavMenuItemChildrensByCurrentMenuItem($items, $args);
            }
        }
        
        return $items;
        
    }
    
    public function getNavMenuItemChildrensByCurrentRootMenuItem($items, $args){
        
        $current_menu_item_id = $this->getCurrentNavMenuItemId($items, $args);
        $root_menu_item_id = $this->getRootParrentNavMenuItemId($items, $args, $current_menu_item_id);
        
        return $this->getNavMenuItemsChildrensByMenuItem($items, $args, $root_menu_item_id);
        
    }
    
    public function getCurrentNavMenuItemId($items, $args){
        
        foreach ($items as $item) {
            if($item->current && !$item->current_item_ancestor && !$item->current_item_parent){
                return $item->ID;
            }
        }
    }
    
    public function getRootParrentNavMenuItemId($items, $args, $menu_item_id) {
        
        foreach ($items as $item) {
            if($item->ID == $menu_item_id){
                if ($item->menu_item_parent == 0){
                    return $menu_item_id;
                }else {
                    return $this->getRootParrentNavMenuItemId($items, $args, $item->menu_item_parent);
                }
            }
        }
    }
    
    
    public function getNavMenuItemChildrensByCurrentMenuItem($items, $args){
        
        $current_menu_item_id = $this->getCurrentNavMenuItemId($items, $args);
        return $this->getNavMenuItemsChildrensByMenuItem($items, $args, $current_menu_item_id);
        
    }
    
    public function getNavMenuItemsChildrensByMenuItem($items, $args, $menu_item_id){
        
        $item_list = array();
        
        foreach ( $items as $item ) {

            if ( isset($item->menu_item_parent) && $item->menu_item_parent == $menu_item_id ) { 
                $item_list[] = $item;
                
                $children = $this->getNavMenuItemsChildrensByMenuItem($items, $args, $item->db_id);

                if ( $children ) {
                    if(!$args->expand_current){
                        $item_list = array_merge($item_list, $children); 
                    }

                    if($args->expand_current && ($item->current == 1 || $item->current_item_ancestor == 1 || $item->current_item_parent == 1 )){
                        $item_list = array_merge($item_list, $children);
                    }
                }
            }
        }

        return $item_list;
    }
    
    public function getNavMenuChildrenByPost($items, $args){
        
        $child_of_page = $args->child_of_page;
        
        if(is_string($child_of_page)){
            
            if ($child_of_page == 'current_root_post'){
                $items = $this->getNavMenuItemByCurrentRootPost($items);
            }
            
            if ($child_of_page == 'current_post'){
                $items = $this->getNavMenuItemByCurrentPost($items);
            }
        }
        
        return $items;
    }
    
    public function getNavMenuItemByCurrentPost($items){
        
        global $post;
        $post_id = $post->ID;
        
        return $this->getNavMenuItemByPost($items, $post_id);
        
    }
    
    public function getNavMenuItemByCurrentRootPost($items){
                
        global $post;
        $root_post_id = 0;

        if ($post->post_parent)	{
            $ancestors = get_post_ancestors($post->ID);
            $root = count($ancestors) - 1;
            $root_parent_id = $ancestors[$root];
        } else {
            $root_post_id = $post->ID;
        }
        
        return $this->getNavMenuItemByPost($items, $root_post_id);
        
    }
    
    public function getNavMenuItemByPost($items, $post_id) {
        
        $returned_menu_items = array();

        foreach ($items as $menu_item) {
            
            $menu_item_meta = get_post_meta($menu_item->ID);

            if(array_search($post_id, $menu_item_meta['_menu_item_object_id']) !== false){
                $returned_menu_items[] = $menu_item;
            }
        }

        return $returned_menu_items;
    }
    
    public function getNavMenuItemsToBreadcrumb( $items, $args, $current_menu_item_id ){
        
        $returned_menu_items = array();
        
        foreach ($items as $item) {
            if($item->ID == $current_menu_item_id){
                $returned_menu_items[] = $item;
                
                if( $item->menu_item_parent != 0 ) {
                    $current_menu_item_id = $item->menu_item_parent;
                    $returned_menu_items = array_merge($returned_menu_items, $this->getNavMenuItemsToBreadcrumb( $items, $args, $current_menu_item_id ));
                }
            }
        }
        
        return $returned_menu_items;
    }
}