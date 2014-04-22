<?php
 /*
 Plugin Name: Excerpt Tools 
 Description: Customize your excerpts. Allows you to limit the length of excerpts with a jQuery character counter, display a custom title and description for the excerpt box and show the excerpt box on pages.
 Author: Zack Kakia; fork by Marcus Downing
 Version: 0.3.1
 */

$jscounter = plugins_url( 'js/jquery.charcounter.js', __FILE__ );

add_action('admin_init', 'e_tools_box_init');

// Checks options to see if custom excerpts are turned on. if so, adds them
function e_tools_box_init() {
  $options = get_option('e_tools');
  do_action('log', 'Excerpt tools: init', $options);
  $title = isset($options['excerpt_title']) ? trim((string) $options['excerpt_title']) : '';
  if (empty($title) || !is_string($title)) $title = __('Excerpt', 'excerpt-tools');
  if (empty($title) || !is_string($title)) $title = 'Excerpt';
  do_action('log', 'Excerpt tools: Title: "%s"', $title);

  foreach (get_post_types(array(), 'objects') as $post_type) {
    if ($options['enable_'.$post_type->name] == 1) {
      do_action('log', 'Excerpt tools: Add meta box', $post_type->name, $title);
      remove_meta_box('postexcerpt', $post_type->name, 'core');
      add_meta_box('e_tools_excerpt', $title, 'e_tools_meta_box', $post_type->name, 'normal', 'high');
    }
  }
}
 
add_action('admin_init', 'e_tools_init');
add_action('admin_menu', 'e_tools_add_page');

// Init plugin options to white list our options
function e_tools_init() {
   register_setting('e_tools_options', 'e_tools');
}

// Add menu page
function e_tools_add_page() {
   add_options_page('Excerpt Options', 'Excerpt Tools', 'manage_options', 'e_tools_handler', 'e_tools_page');
}

// Draw the menu page itself
function e_tools_page() { 
  global $jscounter;
  $options = get_option('e_tools');

?>
<div class="wrap">
  <h2><?php _e('Excerpt Tools', 'excerpt-tools'); ?></h2>
  <form method="post" action="options.php">
    <?php settings_fields('e_tools_options'); ?>
  <div class='metabox-holder'>
  <div class='postbox'>

  <h3><?php _e('Options', 'excerpt-tools'); ?></h3>
  <div class='inside'>
   <table class="form-table">
      <?php

        $post_types = array();
        $post_type_icons = array(
          'post' => 'dashicons-admin-post',
          'page' => 'dashicons-admin-page',
          'attachment' => 'dashicons-admin-media',
          );

        foreach (get_post_types(array(), 'objects') as $post_type) {
          // if ($post_type->exclude_from_search) continue;
          if (in_array($post_type->name, array('revision', 'nav_menu_item'))) continue;

          $post_types[$post_type->name] = $post_type->labels->name;
          if (preg_match('/^dashicons-/', $post_type->menu_icon))
            $post_type_icons[$post_type->name] = $post_type->menu_icon;
        }
        do_action('log', 'Excerpt tools: Post types', $post_types);

        $i = 0;
        foreach ($post_types as $key => $name) {
          echo "<tr>";
          if ($i == 0) {
            echo "<th scope='row' rowspan='".count($post_types)."'>Post types</th>";
          }
          $i++;
          echo "<td><label for='e_tools_enable_$key'>";
          echo "<input type='checkbox' name='e_tools[enable_$key]' id='e_tools_enable_$key' value='1' "; checked('1', $options["enable_$key"]); echo ">";
          if (isset($post_type_icons[$key]))
            echo " &nbsp;<i class='dashicons ${post_type_icons[$key]}'></i>&nbsp; ";
          echo $name;
          echo "</td></tr>";
        }

        $length = '';
        if (!empty($options['excerpt_length'])) {
          $len = intval($options['excerpt_length']);
          if ($len > 0)
            $length = $len;
        }
      ?>
      
      <tr valign="top">
        <th scope="row"><?php _e('Excerpt Length', 'excerpt-tools'); ?></th>
        <td><input type="text" name="e_tools[excerpt_length]" id='excerpt_length' value="<?php echo $length; ?>" placeholder='150' style='text-align: right; width: 4em;' /> &nbsp;characters</td>
      </tr>
      
      <tr valign="top">
        <th scope="row"><?php _e('Excerpt title', 'excerpt-tools'); ?></th>
        <td><input type="text" name="e_tools[excerpt_title]" id='excerpt_title' value="<?php echo $options['excerpt_title']; ?>" placeholder='Excerpt' style='width: 24em;' /></td>
      </tr>
      
      <tr valign="top">
        <th scope="row"><?php _e('Excerpt description', 'excerpt-tools'); ?></th>
        <td>
          <textarea rows="2" cols="60" name="e_tools[excerpt_text]"  id="excerpt_text"><?php echo $options['excerpt_text']; ?></textarea>
        </td>
      </tr>
    </table>
    
  </div>
</div>

<p class="submit">
  <input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
</p>
    
</div>
  </form>
</div>

<?php
}


function e_tools_meta_box($post) {
  wp_enqueue_script('jquery');
  $options = get_option('e_tools');
  do_action('log', 'Excerpt tools: Meta box', $options);
  $title = $options['excerpt_title'];
  if (empty($title))
    $title = __('Excerpt');

  $length = intval($options['excerpt_length']);
  if (empty($length) || $length <= 0)
    $length = 150;

  ?>
  <div style='margin: -6px -12px 0 -12px;'>
    <textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" style='width: 100%; min-width: 100%; max-width: 100%; border-width: 0 0 1px 0; resize: vertical;'><?php 
      echo $post->post_excerpt; 
    ?></textarea>
  </div>
  <?php
  if (!empty($options['excerpt_text']))
    echo "<p>".$options['excerpt_text']."</p>";
  ?>

  <script type="text/javascript"  src="<?php echo plugins_url( 'js/jquery.charcounter.js', __FILE__ ); ?>"> </script>
  <script>

    jQuery(function($) {
      $("#excerpt").charCounter( <?php  echo $length; ?>, {
        container: "<div id='counter' class='counter' style='padding-top:5px; padding-left: 8px;'></div>",
        classname: "counter",
        format: "%1 characters remaining"
      });
    });

  </script>
  <?php
}