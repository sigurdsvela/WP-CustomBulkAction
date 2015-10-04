<?php
namespace CustomBulkAction;

class CustomBulkAction {
    /* #### Static interface #### */
    private static $customActions = array();
    private static $initRan = false;

    /**
     * Called from the bottom of this file
     * Adds actions and scripts required for this to work
     * NEVER CALL THIS FUNCTION
     *
     * @return NEVER YOU MIND, DONT CALL THIS FUNCION, but it's void.
     */
    public static function init() {
        if (self::$initRan) return;
        self::$initRan = true;
        self::injectJS();

        add_action('load-edit.php', function() {
            self::runCallbacks();
        });

        // TODO, admin notices
        // add_action('admin_notices', 'custom_bulk_admin_notices');
 
        // function custom_bulk_admin_notices() {
         
        //   global $post_type, $pagenow;
         
        //   if($pagenow == 'edit.php' && $post_type == 'post' &&
        //      isset($_REQUEST['exported']) && (int) $_REQUEST['exported']) {
        //     $message = sprintf( _n( 'Post exported.', '%s posts exported.', $_REQUEST['exported'] ), number_format_i18n( $_REQUEST['exported'] ) );
        //     echo "<div class="updated"><p>{$message}</p></div>";
        //   }
        // }
    }

    /**
     * Runs the appropriate callback handler
     *
     * @return void
     */
    private static function runCallbacks() {
        // 0. get the action
        $wp_list_table = _get_list_table('WP_Posts_List_Table');
        $action = $wp_list_table->current_action();
        
        // 1. Get the custom action object
        $bulkAction = self::getCustomBulkAction($action);

        if ($bulkAction === null) return;
        
        // 2. security check
        check_admin_referer('bulk-posts');

        // Get the post ids
        if(isset($_REQUEST['post'])) {
            $post_ids = array_map('intval', $_REQUEST['post']);
        }
        
        if(empty($post_ids)) return;

        // Run the callback
        $callback = ($bulkAction->getCallback());
        $callback($post_ids);

        // Build the redirect url. Add all id's to the query args
        $sendback = remove_query_arg( array('untrashed', 'deleted', 'ids'), wp_get_referer() );
        $sendback = add_query_arg( array('ids' => join(',', $post_ids) ), $sendback );

        // 4. Redirect client
        wp_redirect($sendback);
        exit();
    }

    /**
     * Injects the JS required to add the custom bulk action to the
     * select box
     *
     * @return void
     */
    private static function injectJS() {
        add_action('admin_footer-edit.php', function() {
            global $post_type;
            $customBulkActions = CustomBulkAction::getCustomBulkActionsForPostType($post_type);
            if(!empty($customBulkActions)) {
            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                        <?php foreach ($customBulkActions as $customBulkAction): ?>
                        <?php
                            $name = $customBulkAction->getName();
                            $lable = $customBulkAction->getLable();
                        ?>
                            jQuery('<option>').val('<?php echo $name ?>').text('<?php echo $lable ?>').appendTo("select[name='action']");
                            jQuery('<option>').val('<?php echo $name ?>').text('<?php echo $lable ?>').appendTo("select[name='action2']");
                        <?php endforeach; ?>
                    });
                </script>
            <?php
            }
        });
    }

    /**
     * Add a custom bulk action.
     * This function will have no effect after the
     * "admin_footer-edit.php" action runs
     *
     * @param string $name The name of the bulk action. Must be unique
     * @param string $lable The lable
     * @param string $post_type The post type this bulk is applicable to
     * @param callable $callback The callback to run for each post when this bulk action is called
     *
     * @return void
     */
    public static function addCustomBulkAction($name, $lable, $post_type, callable $callback) {
        self::$customActions[$name] = new self($name, $lable, $post_type, $callback);
    }

    /**
     * Get a spesific bulk action by the $name
     *
     * @return CustomBulkAction The custom bulk action
     */
    public static function getCustomBulkActionCallback($name) {
        return self::$customActions[$name]->getCallback();
    }

    /**
     * Return an assiative array with $name=>CustomBulkAction pairs
     *
     * @return CustomBulkAction[string] The custom bulk actions
     */
    public static function getCustomBulkActions() {
        return self::$customActions;
    }

    /**
     * Get a CustomBulkAction object from the name
     *
     * @return CustomBulkAction|null the custom bulk action
     */
    public static function getCustomBulkAction($name) {
        if (isset(self::$customActions[$name])) {
            return self::$customActions[$name];
        } else {
            return null;
        }
    }

    /**
     * Gets an array of CustomBulkAction objects for a spesific post type
     */
    public static function getCustomBulkActionsForPostType($postType) {
        $customBulkActionsForPostType = array();
        $customBulkActions = self::getCustomBulkActions();
        foreach ($customBulkActions as $customBulkAction) {
            if ($customBulkAction->getPostType() === $postType) {
                $customBulkActionsForPostType[] = $customBulkAction;
            }
        }
        return $customBulkActionsForPostType;
    }

    /* #### Instance interface #### */
    private $name;
    private $lable;
    private $callback;
    private $postType;

    /**
     * Create a new instance of CustomBulkAction
     *
     * @param string The name of the bulk action
     * @param string The lable for the bulk action
     * @param string The post type for the bulk action
     * @param callable The callable to call for each post
     */
    private function __construct($name, $lable, $postType, callable $callback) {
        $this->name = $name;
        $this->lable = $lable;
        $this->postType = $postType;
        $this->callback = $callback;
    }

    /**
     * Get the name
     *
     * @return string The name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get the lable
     *
     * @return string The lable
     */
    public function getLable() {
        return $this->lable;
    }

    /**
     * Get the post type
     *
     * @return string The post type
     */
    public function getPostType() {
        return $this->postType;
    }

    /**
     * Get the callback
     *
     * @return callable The callback
     */
    public function getCallback() {
        return $this->callback;
    }
}
CustomBulkAction::init();
