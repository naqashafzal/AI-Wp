<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the custom sidebar for the AI Chatbox.
 * This creates the widget area in the WordPress admin panel.
 */
add_action( 'widgets_init', 'aicb_widgets_init' );
function aicb_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'AI Chatbox Sidebar', 'aicb' ),
        'id'            => 'aicb-chat-sidebar',
        'description'   => __( 'Widgets in this area will be shown in the sidebar of the full-page AI Chatbox.', 'aicb' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s aicb-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_widget( 'AI_Chatbox_Top_Questions_Widget' );
}

/**
 * Top Questions Widget Class with advanced settings.
 */
class AI_Chatbox_Top_Questions_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'aicb_top_questions_widget',
            __('AI Chatbox: Top Questions', 'aicb'),
            array( 'description' => __( 'Displays a list of the most frequently asked questions.', 'aicb' ), )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        // Get settings with defaults
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $show_count = ! empty( $instance['show_count'] ) ? $instance['show_count'] : false;

        global $wpdb;
        $activity_table = $wpdb->prefix . 'aicb_activity_log';
        
        // Use prepare to safely insert variables into the SQL query
        $top_searches = $wpdb->get_results( $wpdb->prepare(
            "SELECT content, COUNT(*) as count FROM {$activity_table} WHERE event_type = 'search' GROUP BY content ORDER BY count DESC LIMIT %d",
            $number
        ) );

        if ($top_searches) {
            echo '<ul class="aicb-top-questions-list">';
            foreach ($top_searches as $search) {
                echo '<li><a href="#" class="aicb-top-question-link"><span>' . esc_html($search->content) . '</span>';
                if ($show_count) {
                    echo '<span class="aicb-question-count">' . esc_html($search->count) . '</span>';
                }
                echo '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No questions have been asked yet.</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Top Questions', 'aicb' );
        $number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $show_count = isset( $instance['show_count'] ) ? (bool) $instance['show_count'] : false;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label> 
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php _e( 'Number of questions to show:' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" max="20" value="<?php echo esc_attr( $number ); ?>" size="3">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>">
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php _e( 'Display search count?' ); ?></label>
        </p>
        <?php 
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['number'] = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;
        $instance['show_count'] = isset( $new_instance['show_count'] ) ? (bool) $new_instance['show_count'] : false;
        return $instance;
    }
}
