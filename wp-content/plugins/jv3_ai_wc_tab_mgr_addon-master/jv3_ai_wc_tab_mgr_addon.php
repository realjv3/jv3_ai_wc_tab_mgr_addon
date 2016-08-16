<?php
/**
 * Plugin Name: All Import to WooCommerce tab manager fix
 * Plugin URI: https://github.com/realjv3
 * Author: John Verity
 * Description: Importing products into WooCommerce via the WP All Import Pro and the WP All Import Pro WooCommerce Add-on. WooCommerce Products have a set of default tabs (Description and Attributes) which we are able to use the import tool to map most of the data to fields with no issue. However since some of these products require additional tabs of information we installed the WooCommerce Tabs Manager to accommodate the additional data. The issue is that the import tool does not recognize these fields and so we are unable to map the data to them.
 * Version: 1.0
 */

add_action('pmxi_update_post_meta', 'jv3_update_post_meta', 10, 3);

function jv3_update_post_meta($pid, $m_key, $m_value) {
    if ($m_key == '_features_content' || $m_key == '_floorplan_content') {
        global $wpdb;
        $content = '';
        switch ($m_key) {
            case '_features_content': {
                $tab_title = 'Features';
                $tab_name = 'features' . '-' .$pid;
                $content = $m_value;
                break;
            }
            case '_floorplan_content': {
                $tab_title = 'Floor Plan';
                $tab_name = 'floor-plan' . '-' .$pid;
                foreach($m_value as $img) {
                    if ($img != '')
                        $content .= "<img src='{$img}'>";
                }
                break;
            }
        }
        $tab = jv3_get_product_tab($pid, $tab_title);

        //delete/update existing tab, or insert new tab
        if ($content == '' || $content == null) { //since no imported value
            update_post_meta($pid, '_override_tab_layout', 'no');
            if (count($tab[0])) //delete existing product tab
            $wpdb->delete(
                    DB_NAME.'WP_POSTS', array(
                        'WP_POSTS.POST_PARENT' => $pid,
                        'WP_POSTS.POST_TYPE' => 'wp_product_tab',
                        'WP_POSTS.POST_TITLE' => $tab_title,
                    )
                );
        } else {
            if (count($tab[0])) { //update existing tab
                foreach($tab as $tab) {
                    if($tab['POST_TITLE'] == $tab_title) {
                        wp_update_post(array(
                            'ID' => $tab['ID'],
                            'post_author' => 2,
                            'post_content' => $content,
                            'post_title' => $tab_title,
                            'post_status' => 'publish',
                            'post_type' => 'wc_product_tab',
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_password' => 'fdbs_import',
                            'post_parent' => $pid,
                            'post_name' => $tab_name
                        ));
                        break;
                    }
                }
            } else {    //insert tab
                wp_insert_post(array(
                    'post_author' => 2,
                    'post_content' => $content,
                    'post_title' => $tab_title,
                    'post_status' => 'publish',
                    'post_type' => 'wc_product_tab',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_password' => 'fdbs_import',
                    'post_parent' => $pid,
                    'menu_order' => 0,
                    'post_name' => $tab_name,
                ));
            }
        }

        //update product tab meta data
        $tabs = jv3_get_product_tab($pid, '');
        $tabs_meta = array(
            'core_tab_description' => array(
                'position' => 0,
                'type' => 'core',
                'id' => 'description',
                'title' => 'Overview',
                'heading' => 'Vehicle Overview'
            ),
            'core_tab_additional_information' => array(
                'position' => 1,
                'type' => 'core',
                'id' => 'additional_information',
                'title' => 'Specifications',
                'heading' => 'Specifications'
            )
        );
        if(!count($tabs[0])) {  //if there aren't any product tabs, just set default tabs
            update_post_meta($pid, '_product_tabs', $tabs_meta);
            update_post_meta($pid, '_override_tab_layout', 'no');
        } else { //else set default tabs and product tabs
            $pos = 2;
            foreach($tabs as $tab) {
                $tabs_meta["product_tab_{$tab['ID']}"] = array('position' => $pos, 'type' => 'product', 'id' => "$tab[ID]", 'name' => ($tab['POST_TITLE'] == 'Features') ? 'features' . '-' .$pid : 'floor-plan' . '-' .$pid);
                $pos++;
            }
            update_post_meta($pid, '_product_tabs', $tabs_meta);
            update_post_meta($pid, '_override_tab_layout', 'yes');
        }
    }
}

/**
 * Get product tab posts for a product
 * @param string $pid
 * @param string $title - Use blank string to search for all tabs for a product
 * @return array|null|object
 */
function jv3_get_product_tab($pid, $title = '') {
    global $wpdb;
    return $wpdb->get_results("
                SELECT
                    WP_POSTS.ID,
                    WP_POSTS.POST_TITLE,
                    WP_POSTS.POST_CONTENT,
                    WP_POSTS.POST_TYPE
                FROM
                    ".DB_NAME.".WP_POSTS
                WHERE
                    WP_POSTS.POST_PARENT LIKE '{$pid}'
                    AND WP_POSTS.POST_TYPE LIKE 'wc_product_tab'
                    AND WP_POSTS.POST_TITLE LIKE '%{$title}%'
            ", ARRAY_A);
}