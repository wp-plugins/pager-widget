<?php

/*
Plugin Name: Pager Widget
Plugin URI: http://dcdcgroup.org
Description: Widget that provides "Parent | Previous | Next" buttons to navigate between pages at the same hierarchy level (and up to the parent page). You can modify the settings to choose which words you want to use. To enable, first activate the plugin, then add the widget to a sidebar in the Widgets settings page.
Author: Paul Aumer-Ryan, Programmer, Distance Course Design & Consulting (DCDC), College of Education, University of Hawai'i at Manoa
Version: 1.5
Author URI: http://combinelabs.com/paul
*/

/**
 * PagerWidget Class
 */
class PagerWidget extends WP_Widget {
  // constructor
  function PagerWidget() {
    parent::WP_Widget(
      'wp-pager', 
      $name='Pager Widget', 
      $widget_options = array(
        'description' => 'Print "Parent | Previous | Next" links to navigate between pages at the same level in the page hierarchy (and up to the parent page).'
      )
    );
  }
  
  // @see WP_Widget::widget ... (function to display widget)
  function widget($args, $instance) {
    extract($args);
    $labelParent = esc_attr($instance['labelParent']);
    $labelPrev = esc_attr($instance['labelPrev']);
    $labelNext = esc_attr($instance['labelNext']);
    $pageDepth = intval($instance['pageDepth']);
    
    echo $before_widget;
    echo $before_title;
    echo $after_title;
    
    // Get page object (since we're outside of the loop)
    global $wp_query;
    $post = $wp_query->get_queried_object();
    // Make sure we're on a level $pageDepth page in the hierarchy that has siblings, 
    // or on a level $pageDepth-1 page that has children
    $hierarchyDepth = 0;
    $page = $post;
    while ($page->post_parent) {
      $page = get_post($page->post_parent);
      $hierarchyDepth++;
    }
    $children = wp_list_pages("child_of={$post->ID}&echo=0");
    $siblings = wp_list_pages("title_li=&child_of={$post->post_parent}&echo=0&depth=1");

    if (!(($hierarchyDepth==$pageDepth && $siblings) || ($hierarchyDepth==($pageDepth-1) && $children))) {
      echo $after_widget;
      return;
    }
  
    // Print links to parent, previous, and next page
    echo "<div id='linksPrevNext'>";
    if ($hierarchyDepth==($pageDepth-1) && $children) { // we're on a level $pageDepth-1 page that has children
      // Get links to children pages
      $numberOfMatches = preg_match_all("/<a href=[\"|\'](.*?)[\"|\']/i",$children,$matches,PREG_PATTERN_ORDER);
      $parentURI = get_permalink($post->ID);
      $nextURI = "";
      if (count($matches[1]) > 0 ) $nextURI = $matches[1][0];
      if (strlen($nextURI)>0) {
        echo "<a href='$nextURI'>$labelNext</a>";
      }
    } else if ($hierarchyDepth==$pageDepth && $siblings) { // level $pageDepth page that has siblings
      // Get links to sibling pages
      $numberOfMatches = preg_match_all("/<a href=[\"|\'](.*?)[\"|\']/i",$siblings,$matches,PREG_PATTERN_ORDER);
      // Get links to parent, previous, and next page
      $currentSlug = get_permalink($post->ID); //$post->post_name;
      $parentURI = get_permalink($post->post_parent);
      $prevURI = get_permalink($post->post_parent);
      $nextURI = "";
      for ($i=0; $i<count($matches[1]); $i++) {
        if (strpos($matches[1][$i],$currentSlug) !== FALSE) { // we found the current page
          if ($i < count($matches[1])-1) 
            $nextURI = $matches[1][$i+1];
          break;
        }
        $prevURI = $matches[1][$i];
      }
      echo "  <a href='$parentURI'>$labelParent</a>";
      echo "  &nbsp;&nbsp; | &nbsp;&nbsp;";
      if (strlen($prevURI)>0 && $prevURI!==$parentURI)
        echo "<a href='$prevURI'>$labelPrev</a>";
      if (strlen($prevURI)>0 && $prevURI!==$parentURI && strlen($nextURI)>0)
        echo "&nbsp;&nbsp; | &nbsp;&nbsp;";
      if (strlen($nextURI)>0)
        echo "<a href='$nextURI'>$labelNext</a>";
    }
    echo "</div>";

    echo $after_widget;
  }
  
  // @see WP_Widget::update ... (function to save posted form data from widget admin panel)
  function update($new_instance, $old_instance) {
    if (!isset($new_instance['submit']))
      return false;
    $instance = $old_instance;
    $instance['labelParent'] = strip_tags($new_instance['labelParent']);
    $instance['labelPrev'] = strip_tags($new_instance['labelPrev']);
    $instance['labelNext'] = strip_tags($new_instance['labelNext']);
    $instance['pageDepth'] = intval($new_instance['pageDepth']);
    return $instance;
  }
  
  // @see WP_Widget::form ... (function to display options when widget added to sidebar)
  function form($instance) {
    $instance = wp_parse_args((array)$instance, array(
      'labelParent' => 'Back to Overview', 
      'labelPrev'   => '&laquo; Previous Page', 
      'labelNext'   => 'Next Page &raquo;',
      'pageDepth'   => 1
    ));
    
    $valueParent = esc_attr($instance['labelParent']);
    $idParent = $this->get_field_id('labelParent');
    $nameParent = $this->get_field_name('labelParent');
    echo "<label for='$idParent'>Label for Parent link: ";
    echo "<input type='text' class='widefat' id='$idParent' name='$nameParent' value='$valueParent' />";
    echo "</label><br/><br/>";

    $valuePrev = esc_attr($instance['labelPrev']);
    $idPrev = $this->get_field_id('labelPrev');
    $namePrev = $this->get_field_name('labelPrev');
    echo "<label for='$idPrev'>Label for Previous link: ";
    echo "<input type='text' class='widefat' id='$idPrev' name='$namePrev' value='$valuePrev' />";
    echo "</label><br/><br/>";

    $valueNext = esc_attr($instance['labelNext']);
    $idNext = $this->get_field_id('labelNext');
    $nameNext = $this->get_field_name('labelNext');
    echo "<label for='$idNext'>Label for Next link: ";
    echo "<input type='text' class='widefat' id='$idNext' name='$nameNext' value='$valueNext' />";
    echo "</label><br/><br/>";
    
    $valueDepth = intval($instance['pageDepth']);
    $idDepth = $this->get_field_id('pageDepth');
    $nameDepth = $this->get_field_name('pageDepth');
    echo "<label for='$idDepth'>Show pager on this level of the hierarchy (0 is top level): ";
    echo "<input type='text' class='widefat' id='$idDepth' name='$nameDepth' value='$valueDepth' />";
    echo "</label><br/><br/>";
    
    echo "<p>Note: you can apply CSS styles to #linksPrevNext</p><br/><br/>";

    $idSubmit = $this->get_field_id('submit');
    $nameSubmit = $this->get_field_name('submit');
    echo "<input type='hidden' id='$idSubmit' name='$nameSubmit' value='1' />";
  }
}

add_action('widgets_init', create_function('', 'return register_widget("PagerWidget");'));

?>