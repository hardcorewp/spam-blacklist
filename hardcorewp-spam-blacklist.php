<?php
/**
 * Plugin Name: @HardcoreWP Spam Backlist
 * Description: Provides an aggressive blacklist and whitelist for comment spam. Upon activation it creates a Spam Control Lists post type ('hcwp_spam_control_list') to contains the various blacklist and whitelists. The various control lists are author, IP, email and URL for each of the two list types and upon activation it scans all the comment spam and for each type author, IP, email and URL occurs more than 3 times in spam it loads the the associated blacklist. Then when a comment is submitted containing any blacklisted value it displays a page explain to the user they have been blacklisted and asks them to email the site admin with the details shown in an HTML &lt;textarea&gt; by the plugin so they can be whitelisted, and it presents their comment in another &lt;textarea&gt; that they can copy and save it locally to post later. In the next version it will initilize the whitelists with all approved posters.
 * Plugin URI: http://github.com/hardcorewp/spam-blacklist
 * Author: HardcoreWP (Mike Schinkel)
 * Author Email: mike@newclarity.net
 * Author URI: http://hardcorewp.com
 * Version: 0.1beta
 */

/*
 * We activate the plugin using the 'plugins_loaded' so you can chain it or remove it if you need to.
 */
add_action( 'plugins_loaded', array( 'HardcoreWP_Spam_Blacklist', '_plugins_loaded_9' ), 9 );

/**
 * Class HardcoreWP_Spam_Blacklist
 */
class HardcoreWP_Spam_Blacklist {
  const POST_TYPE = 'hcwp_spam_control_lists';

  /**
   * @var HardcoreWP_Spam_Blacklist
   */
  private static $_instance;

  /**
   * @var array
   */
  private static $_control_lists;

  static function on_load() {
    register_activation_hook( __FILE__, array( __CLASS__, '_activate' ) );
  }

  /**
   *
   */
  static function _plugins_loaded_9() {
    self::enable();
  }

  /**
   *
   */
  static function enable() {
    self::$_instance = new HardcoreWP_Spam_Blacklist();
  }

  /**
   *
   */
  private function __construct() {
    add_action( 'init', array( $this, '_init_0' ), 0 );
    add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
    add_action( 'pre_comment_on_post', array( $this, '_pre_comment_on_post' ) );
  }
  /**
   *
   */
  static function _pre_comment_on_post( $post_id ) {
    $comment_info = array(
      'author'          => self::_get_POST( 'author' ),
      'author-email'    => self::_get_POST( 'email' ),
      'author-url'      => urldecode( self::_get_POST( 'url' ) ),
      'author-ip'       => $_SERVER['REMOTE_ADDR'],
      'comment'         => self::_get_POST( 'comment' ),
    );
    if ( ! self::_in_control_list( 'white', $comment_info ) ) {
      if ( self::_in_control_list( 'black', $comment_info ) ) {
        self::_is_probably_spam( $comment_info );
      }
    }
  }

  static function _is_probably_spam( $comment_info ) {
    $admin_email = esc_attr( get_option( 'admin_email' ) );
    $comment = esc_html( $comment_info['comment'] );
    unset( $comment_info['comment'] );
    foreach( $comment_info as $name => $value ) {
      unset( $comment_info[$name] );
      $comment_info[] = "{$name}: {$value}";
    }
    $comment_info = esc_html( implode( "\n", $comment_info ) );
    $html =<<<HTML
<div style="margin:50px auto;width:600px;">
  <h2>OOPS! Might be Spam?</h2>
  <p>Sorry for the inconvenience but we are using a <strong>Spam Blacklist</strong> based on this site's previous spam and your
  comment got flagged as spam. Well <strong>that sucks!</strong></p>
  <p>Tell you what. We can <strong>add you to our spam whitelist</strong> and let you know with an email reply.</p>
  <h3>Email us <em>This</em>:</h3>
  <p>Email us the following information so we can be able to fully add you to our spam whitelist.</p>
  <textarea rows="6" cols="60">{$comment_info}</textarea>
  <p>After you've copied the above information to your clipboard <a href="mailto:{$admin_email}?subject=Comment marked as spam"><strong>click here</strong></a> to open your email program with our email address and subject line ready to go.</p>
  <h3>Save <em>This</em> for Later:</h3>
  <p>Save your comment to somewhere on your computer <em>(a text file on your computer's desktop, maybe?)</em> so that after we reply telling you we've added you to the whitelist you can return and post the comment later without have to compose your comment again.</p>
  <textarea rows="15" cols="80">{$comment}</textarea>
</div>
HTML;
    echo $html;
    exit;
  }

  /**
   *
   */
  static function disable() {
    remove_action( 'init', array( self::$_instance, '_init' ) );
    remove_action( 'edit_form_after_title', array( self::$_instance, 'edit_form_after_title' ) );

    // Cannot easily unregister post type
   	remove_action( 'activate_' . plugin_basename( __FILE__  ), array( __CLASS__, '_activate' ) );
    self::$_instance = null;
  }

  function edit_form_after_title() {
    global $post;
    if ( ! $post->post_parent ) {
      echo '<h2>Nothing to see here</h2>';
      echo '<p>No data entry needed here. See the <a href="';
      echo admin_url( 'edit.php?post_type=' . self::POST_TYPE );
      echo '">specific (child) Spam Control Lists</a> instead.</p>';
      remove_post_type_support( self::POST_TYPE, 'excerpt' );
    } else {
      echo '<h2>' . get_the_title() . ' ' . get_the_title( $post->post_parent )  . '</h2>';
      if ( is_numeric( strpos( $post->post_name, 'whitelist' ) ) ) {
        $list_type = __( 'whitelist', 'hardcorewp' );
        $action = __( 'approved', 'hardcorewp' );
      } else {
        $list_type = __( 'blacklist', 'hardcorewp' );
        $action = __( 'denied', 'hardcorewp' );
      }
      $message = __( '<p>Enter <strong>%ss</strong> for the %s, one per line.<p></p>Any %ss attempting to comment will be automatically %s.</p>', 'hardcorewp' );
      $action = is_numeric( strpos( $post->post_type, 'whitelist' ) ) ? __( 'whitelisted', 'hardcorewp' ) : __( 'blacklisted', 'hardcorewp' );
      printf( $message, strtolower( $post->post_title ), $list_type, strtolower( $post->post_title ), $action );
      echo '<br/><textarea rows="25" cols="60" name="content" style="margin-left:3em;">';
      echo $post->post_content;
      echo '</textarea>';
    }
  }

  /**
   *
   */
  static function get_instance() {
    return self::$_instance;
  }

  /**
   *
   */
  static function _activate() {
    $args = array(
      'post_type' => self::POST_TYPE,
    );
    $blacklist_parent = self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Spam Blacklists', 'hardcorewp' ),
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author', 'hardcorewp' ),
      'post_parent' => $blacklist_parent,
      'post_content' => self::get_initial_blacklist_by( 'author' ),
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author Email', 'hardcorewp' ),
      'post_parent' => $blacklist_parent,
      'post_content' => self::get_initial_blacklist_by( 'author_email' ),
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author IP', 'hardcorewp' ),
      'post_parent' => $blacklist_parent,
      'post_content' => self::get_initial_blacklist_by( 'author_IP' ),
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author URL', 'hardcorewp' ),
      'post_parent' => $blacklist_parent,
      'post_content' => self::get_initial_blacklist_by( 'author_url' ),
    )));

    $whitelist_parent = self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Spam Whitelists', 'hardcorewp' ),
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author', 'hardcorewp' ),
      'post_parent' => $whitelist_parent,
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author Email', 'hardcorewp' ),
      'post_parent' => $whitelist_parent,
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author IP', 'hardcorewp' ),
      'post_parent' => $whitelist_parent,
    )));
    self::_maybe_update_post( wp_parse_args( $args, array(
      'post_title' => __( 'Author URL', 'hardcorewp' ),
      'post_parent' => $whitelist_parent,
    )));

  }

  /**
   *
   */
  function _init_0() {
    register_post_type( HardcoreWP_Spam_Blacklist::POST_TYPE, array(
      'label'               => __( 'Spam Blacklists', 'hardcorewp' ),
      'description'         => __( 'Spam Blacklists and Whitelists for Comments', 'hardcorewp' ),
      'labels'              => array(
        'name'                => _x( 'Spam Control Lists', 'Post Type General Name', 'hardcorewp' ),
        'singular_name'       => _x( 'Spam Control List', 'Post Type Singular Name', 'hardcorewp' ),
        'menu_name'           => __( 'Spam Blacklist', 'hardcorewp' ),
        'parent_item_colon'   => __( 'n/a', 'hardcorewp' ),
        'all_items'           => __( 'Spam Control Lists', 'hardcorewp' ),
        'view_item'           => __( 'View Spam Control List', 'hardcorewp' ),
        'add_new_item'        => __( 'n/a', 'hardcorewp' ),
        'add_new'             => __( 'n/a', 'hardcorewp' ),
        'edit_item'           => __( 'Edit Spam Control List', 'hardcorewp' ),
        'update_item'         => __( 'Update Spam Control List', 'hardcorewp' ),
        'search_items'        => __( 'Search Spam Control Lists', 'hardcorewp' ),
        'not_found'           => __( 'No spam control lists found', 'hardcorewp' ),
        'not_found_in_trash'  => __( 'No spam control lists found in Trash', 'hardcorewp' ),
      ),
      'supports'            => array( null ),
      'taxonomies'          => array( 'hcwp_spam_marker_type' ),
      'hierarchical'        => true,
      'public'              => false,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_nav_menus'   => false,
      'show_in_admin_bar'   => true,
      'menu_position'       => 80,
      'menu_icon'           => '',
      'can_export'          => true,
      'has_archive'         => false,
      'exclude_from_search' => true,
      'publicly_queryable'  => false,
      'rewrite'             => false,
      'capability_type'     => 'page',
    ));
  }

  /**
   */
  private static function _initialize() {
    if ( ! isset( self::$_instance ) ) {
      self::enable();
      self::get_instance()->_init_0();  // Need to register the Spam Blacklist post type
    }
  }

  /**
   * Add or Update a post based on prior existence.
   *
   * @param array $post_array
   *
   * @return int
   */
  private static function _maybe_update_post( $post_array ) {
    self::_initialize();
    $post_array = wp_parse_args( $post_array, array(
      'post_parent' => 0,
      'post_status' => 'publish',
      'post_name'   => false,
    ));
    $slug = $post_array['post_name'] ? $post_array['post_name'] : sanitize_title_with_dashes( $post_array['post_title'] );
    $post_path = self::_get_parent_path( $post_array['post_parent'], $slug );
    $post = get_page_by_path( $post_path, OBJECT, $post_array['post_type'] );
    if ( $post instanceof WP_Post ) {
      $post_id = $post->ID;
    } else {
      $post_id = wp_insert_post( $post_array );
      $post = get_post( $post_id );

      /**
       * Now assign the taxonomies to the posts.
       */
      if ( isset( $post_array['taxonomies'] ) && is_array( $post_array['taxonomies'] ) ) {
        foreach( $post_array['taxonomies'] as $taxonomy => $terms ) {
          wp_set_object_terms( $post_id, $terms, $taxonomy );
        }
      }
      unset( $post_array['taxonomies'] );
      unset( $post_array['child_posts'] );

      /**
       * Everything else is assumed to be a post meta key
       */
      foreach( $post_array as $meta_key => $meta_value ) {
        /**
         * If not already prefixed with underscore, add it.
         */
        if ( isset( $meta_key[0] ) && '_' != $meta_key[0] )
          $meta_key = "_{$meta_key}";
        update_post_meta( $post->ID, $meta_key, $meta_value );
      }
    }

    return $post_id;
  }

  /**
   * @param int $post_id
   * @param bool|string $child_path
   *
   * @return string
   */
  private static function _get_parent_path( $post_id, $child_path = false ) {
    $path = false;
    if ( ! $post_id ) {
      $path = $child_path;
    } else {
      $post = get_post( $post_id );
      if ( $post ) {
        $path = $post->post_name;
        if ( $post->post_parent ) {
          $path = self::_get_parent_path( $post->post_parent, $path );
        }
      }
      if ( $child_path ) {
        $path = $path ? "{$path}/{$child_path}" : $child_path;
      }
    }
    return $path;
  }

  /**
   *
   */
  private static function _get_POST( $field_name ) {
    return isset( $_POST[$field_name] ) ? $_POST[$field_name] : null;
  }

  /**
   * @param string $lists_type 'black' or 'white'
   * @param array $comment_info
   *
   * @return bool|mixed
   */
  private static function _in_control_list( $lists_type, $comment_info ) {
    $in_control_list = 'white' == $lists_type ? is_user_logged_in() : ! is_user_logged_in();
    if ( ! $in_control_list ) {
      $control_lists = self::_get_spam_control_lists( $lists_type );
      foreach( $control_lists as $list_name => $control_list ) {
        if ( $value = strtolower( trim( $comment_info[$list_name] ) ) ) {
          if ( ! empty( $control_list->post_content ) ) {
            $control_list = explode( "\n", strtolower( trim( $control_list->post_content ) ) );
            $in_control_list = is_numeric( array_search( $value, $control_list ) );
            break;
          }
        }
      }
    }
    return $in_control_list;
  }
  /**
   * @param string $lists_type 'black' or 'white'
   *
   * @return mixed
   */
  private static function _get_spam_control_lists( $lists_type ) {
    if ( ! isset( self::$_control_lists )  ) {
      /**
       * @var wpdb $wpdb
       */
      global $wpdb;
      $sql = <<<SQL
SELECT
  ID,
	post_parent,
	post_name,
	post_content
FROM
	{$wpdb->posts}
WHERE
	post_type='%s'
ORDER BY
	post_parent ASC
SQL;
      $control_lists = array();
      $control_index = array();
      if ( $rows = $wpdb->get_results( $wpdb->prepare( $sql, self::POST_TYPE ) ) ) {
        foreach( $rows as $index => $row ) {
          if ( 0 == $row->post_parent ) {
            $row->lists = array();
            $list_type = preg_replace( '#^spam-(.*?)$#', '$1', $row->post_name );
            $control_lists[$list_type] = $row;
            $control_index[$row->ID] = $list_type;
          } else {
            $control_lists[$control_index[$row->post_parent]]->lists[$row->post_name] = $row;
          }
        }
      }
      self::$_control_lists = $control_lists;
    }
    return self::$_control_lists["{$lists_type}lists"]->lists;
  }

  /**
   * @param $by
   *
   * @return string
   */
  static function get_initial_blacklist_by( $by ) {
    /**
     * @var wpdb $wpdb
     */
    global $wpdb;
    $by = esc_sql( $by );
    $sql =<<<SQL
SELECT
  comment_{$by}
FROM
  {$wpdb->comments}
WHERE
  comment_approved='spam'
GROUP BY
  comment_{$by}
HAVING
  COUNT(*) >= 3
ORDER BY
  COUNT(*) DESC
SQL;
    $values = $wpdb->get_col( $sql );
    return implode( "\n", $values ) . "\n";
  }


}
HardcoreWP_Spam_Blacklist::on_load();
