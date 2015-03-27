<?php
/**
 * All actions of AnsPress
 *
 * @package   AnsPress
 * @author    Rahul Aryan <admin@rahularyan.com>
 * @license   GPL-2.0+
 * @link      http://wp3.in
 * @copyright 2014 Rahul Aryan
 */

class AnsPress_BP
{
	/**
	 * Initialize the class
	 * @since 2.0.1
	 */
	public function __construct()
	{
		//add_action( 'ap_enqueue', 'bp_activity_mentions_script' );
		add_action( 'bp_setup_nav',  array( $this, 'content_setup_nav') );
		add_post_type_support( 'question', 'buddypress-activity' );
		add_post_type_support( 'answer', 'buddypress-activity' );
		add_action( 'init', array($this, 'question_answer_tracking') );
		add_action( 'bp_activity_entry_meta', array($this, 'activity_buttons') );
		add_filter( 'bp_activity_custom_post_type_post_action', array($this, 'activity_action'), 10, 2 );
		add_filter( 'bp_before_member_header_meta', array($this, 'bp_profile_header_meta'));
		add_filter( 'ap_the_question_content', array($this, 'ap_the_question_content'));
		add_filter( 'the_content', array($this, 'ap_the_answer_content'));
		
	}

	public function content_setup_nav()
	{
		global $bp;

		bp_core_new_nav_item( array(
		    'name'                  => __('Reputation', 'ap'),
		    'slug'                  => 'reputation',
		    'screen_function'       => array($this, 'reputation_screen_link'),
		    'position'              => 30,//weight on menu, change it to whatever you want
		    'default_subnav_slug' => 'my-posts-subnav'

		) );
		bp_core_new_nav_item( array(
		    'name'                  => sprintf(__('Questions %s', 'ap'), '<span class="count">'.count_user_posts( bp_displayed_user_id() , 'question' ).'</span>'),
		    'slug'                  => 'questions',
		    'screen_function'       => array($this, 'questions_screen_link'),
		    'position'              => 40,//weight on menu, change it to whatever you want
		    'default_subnav_slug' => 'my-posts-subnav'

		) );
		bp_core_new_nav_item( array(
		    'name'                  => sprintf(__('Answers %s', 'ap'), '<span class="count">'.count_user_posts( bp_displayed_user_id() , 'answer' ).'</span>'),
		    'slug'                  => 'answers',
		    'screen_function'       => array($this, 'answers_screen_link'),
		    'position'              => 40,//weight on menu, change it to whatever you want
		    'default_subnav_slug' => 'my-posts-subnav'

		) );
	}

	public function reputation_screen_link() {
	    add_action( 'bp_template_title', array($this, 'reputation_screen_title') );
	    add_action( 'bp_template_content', array($this, 'reputation_screen_content') );
	    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	public function reputation_screen_title() {
	    _e('Reputation', 'ap');
	}

	public function reputation_screen_content() {
		global $wpdb;
		$user_id = bp_displayed_user_id();
		// Preparing your query
      	$query = "SELECT v.* FROM ".$wpdb->prefix."ap_meta v WHERE v.apmeta_type='reputation' AND v.apmeta_userid = $user_id";
        
	
		//adjust the query to take pagination
		/*if(!empty($paged) && !empty($this->per_page)){
			$offset=($paged-1)*$this->per_page;
			$query.=' LIMIT '.(int)$offset.','.$this->per_page;
		}		
		*/
		$reputation = $wpdb->get_results($query);
    	echo '<div class="anspress-container">';
	    include ap_get_theme_location('user-reputation.php');
	    echo '</div>';
	}

	public function questions_screen_link() {
	    add_action( 'bp_template_title', array($this, 'questions_screen_title') );
	    add_action( 'bp_template_content', array($this, 'questions_screen_content') );
	    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	public function questions_screen_title() {
	    _e('Questions', 'ap');
	}

	public function questions_screen_content() {
		global $questions;

    	$questions 		 = new Question_Query(array('author' => bp_displayed_user_id()));
    	echo '<div class="anspress-container">';
	    include ap_get_theme_location('user-questions.php');
	    echo '</div>';
	    wp_reset_postdata();
	}

	public function answers_screen_link() {
	    add_action( 'bp_template_title', array($this, 'answers_screen_title') );
	    add_action( 'bp_template_content', array($this, 'answers_screen_content') );
	    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	public function answers_screen_title() {
	    _e('Answers', 'ap');
	}

	public function answers_screen_content() {
		global $answers;

    	$answers 		 = new Answers_Query(array('author' => bp_displayed_user_id()));
    	echo '<div class="anspress-container">';
	    include ap_get_theme_location('user-answers.php');
	    echo '</div>';
	    wp_reset_postdata();
	}

	public function question_answer_tracking(){
		// Check if the Activity component is active before using it.
	    if ( !function_exists('bp_is_active') || ! bp_is_active( 'activity' ) ) {
	        return;
	    }
	 
	    bp_activity_set_post_type_tracking_args( 'question', array(
	        'component_id'             => 'activity',
	        'action_id'                => 'new_question',
	        'contexts'                 => array( 'activity', 'member' ),
	        'bp_activity_admin_filter' => __( 'Question', 'ap' ),
            'bp_activity_front_filter' => __( 'Question', 'ap' ),
            'bp_activity_new_post'     => __( '%1$s asked a new <a href="AP_CPT_LINK">question</a>', 'ap' ),
            'bp_activity_new_post_ms'  => __( '%1$s asked a new <a href="AP_CPT_LINK">question</a>, on the site %3$s', 'ap' ),
	    ) );

	    bp_activity_set_post_type_tracking_args( 'answer', array(
	        'component_id'             => 'activity',
	        'action_id'                => 'new_answer',
	        'contexts'                 => array( 'activity', 'member' ),
	        'bp_activity_admin_filter' => __( 'Answer', 'ap' ),
            'bp_activity_front_filter' => __( 'Answer', 'ap' ),
            'bp_activity_new_post'     => __( '%1$s <a href="AP_CPT_LINK">answered</a> a question', 'ap' ),
            'bp_activity_new_post_ms'  => __( '%1$s <a href="AP_CPT_LINK">answered</a> a question, on the site %3$s', 'ap' ),
	    ) );
	}

	public function activity_buttons()
	{
		if('new_question' == bp_get_activity_type())
			echo '<a class="button answer bp-secondary-action" title="'.__('Answer this question', 'ap').'" href="'.ap_answers_link(bp_get_activity_secondary_item_id()).'">'.__('Answer', 'ap').'</a>';
	}

	public function activity_action($action, $activity)
	{	
		if($activity->type == 'new_question' || $activity->type == 'new_answer')
			return str_replace('AP_CPT_LINK', get_permalink( $activity->secondary_item_id ), $action);

		return $action;
	}

	public function bp_profile_header_meta(){
		echo '<span class="ap-user-meta ap-user-meta-reputation">'. sprintf(__('%d Reputation', 'ap'), ap_get_reputation( bp_displayed_user_id(), true)) .'</span>';

		//echo '<span class="ap-user-meta ap-user-meta-share">'.sprintf(__('%d&percent; of reputation on this site', 'ap'), ap_get_user_reputation_share(bp_displayed_user_id())).'</span>';
	}

	/**
	 * Filter question content and link metions
	 * @return string
	 */
	public function ap_the_question_content($content){
		return bp_activity_at_name_filter($content);
	}

	public function ap_the_answer_content($content){
		global $post;
		
		if($post->post_type == 'answer')
			return bp_activity_at_name_filter($content);

		return $content;
	}
}