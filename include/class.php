<?php

function editorial_control_defaults() {
    add_option('supercontributor','yes');
    update_option('supercontributor','yes');
    allow_contributor_uploads();

    // add_action('admin_init', 'allow_contributor_uploads');
    // if ( current_user_can('contributor') && !current_user_can('upload_files') ) {
    // }
    add_option('notificationemails','editor@pokerstars.com');
    add_option('approvednotification','yes');
    add_option('declinednotification','yes');
    $from_email = parse_url(get_option('siteurl'), PHP_URL_HOST);
    $from_email = ltrim($from_email,'www.');
    $from_email = "admin@". $from_email;
    add_option('fromemail',$from_email);
}


function editorial_control_deactivate() {
    $contributor = get_role('contributor');
    $contributor->remove_cap('upload_files');
    update_option('supercontributor','');
}

function editorial_control_uninstall() {
    $contributor = get_role('contributor');
    $contributor->remove_cap('upload_files');
    delete_option( 'supercontributor' );

    delete_option( 'notificationemails' );
    delete_option( 'approvednotification' );
    delete_option( 'declinednotification' );
    delete_option( 'fromemail' );
}

// Action function for menu hook. Add a new submenu under options
function ec_add_option_page() {
    add_options_page('Editorial Control', 'Editorial Control', 'edit_themes', 'editorial_control', 'ec_options_page');
}

function allow_contributor_uploads() {
    $contributor = get_role('contributor');
    $contributor->add_cap('upload_files');
}
function disallow_contributor_uploads() {
    $contributor = get_role('contributor');
    $contributor->remove_cap('upload_files');
}


function presstrends_plugin() {
    // PressTrends Account API Key
    $api_key = '35dwpeap865ii8z3pz8k8xhqoyo9zl953v94';
    $auth    = '5a8dzobwfj0k4ty0jkheyb2lajjftijcv';
    // Start of Metrics
    global $wpdb;
    $data = get_transient( 'presstrends_cache_data' );
    if ( !$data || $data == '' ) {
        $api_base = 'http://api.presstrends.io/index.php/api/pluginsites/update?auth=';
        $url      = $api_base . $auth . '&api=' . $api_key . '';
        $count_posts    = wp_count_posts();
        $count_pages    = wp_count_posts( 'page' );
        $comments_count = wp_count_comments();
        if ( function_exists( 'wp_get_theme' ) ) {
            $theme_data = wp_get_theme();
            $theme_name = urlencode( $theme_data->Name );
        } else {
            $theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
            $theme_name = $theme_data['Name'];
        }
        $plugin_name = '&';
        foreach ( get_plugins() as $plugin_info ) {
            $plugin_name .= $plugin_info['Name'] . '&';
        }
        // CHANGE __FILE__ PATH IF LOCATED OUTSIDE MAIN PLUGIN FILE
        $plugin_data         = get_plugin_data( __FILE__ );
        $posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
        $data                = array(
            'url'             => base64_encode(site_url()),
            'posts'           => $count_posts->publish,
            'pages'           => $count_pages->publish,
            'comments'        => $comments_count->total_comments,
            'approved'        => $comments_count->approved,
            'spam'            => $comments_count->spam,
            'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
            'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
            'theme_version'   => $plugin_data['Version'],
            'theme_name'      => $theme_name,
            'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
            'plugins'         => count( get_option( 'active_plugins' ) ),
            'plugin'          => urlencode( $plugin_name ),
            'wpversion'       => get_bloginfo( 'version' ),
        );
        foreach ( $data as $k => $v ) {
            $url .= '&' . $k . '=' . $v . '';
        }
        wp_remote_get( $url );
        set_transient( 'presstrends_cache_data', $data, 60 * 60 * 24 );
    }
}


function notify_status($new_status, $old_status, $post) {
    global $current_user;
    $contributor = get_userdata($post->post_author);

    $boundary = uniqid();

    if ($old_status != 'pending' && $new_status == 'pending') {
        $emails=get_option('notificationemails');
        if(strlen($emails)) {

            $post_title = get_the_title($post->ID);
            $from_email   = get_option('fromemail');
            $headers = 'From : Review Post <'. $from_email .'>' . "\r\n".
                       "MIME-Version: 1.0\r\n".
                       "Content-Type: multipart/alternative;boundary=" . $boundary . "\r\n";

            $subject='['.get_option('blogname').'] "'.$post_title.'" is pending review';

            $message = "This is a MIME encoded message.";
            $message .= "\r\n\r\n--" . $boundary . "\r\n";
            $message .= "Content-type: text/plain;charset=utf-8\r\n\r\n";

            $message.="
                A new post on [".get_option('blogname')."] by {$contributor->display_name} is pending review. \n
                URL : ". get_option('siteurl').". \n
                Author : {$contributor->user_login} <{$contributor->user_email}> (IP:{$_SERVER['REMOTE_ADDR']}). \n
                Title : $post_title. \n
                Powered by: WP Editorial Control
            ";

            $message .= "\r\n\r\n--" . $boundary . "\r\n";
            $message .= "Content-type: text/html;charset=utf-8\r\n\r\n";
            $message.="
                <strong>A new post on [".get_option('blogname')."] by {$contributor->display_name} is pending review.</strong>
                <br/><br/>
                URL : ". get_option('siteurl')."
                <br/>
                Author : {$contributor->user_login} <{$contributor->user_email}> (IP:{$_SERVER['REMOTE_ADDR']})
                <br/>
                Title : $post_title
            ";

            $category = get_the_category($post->ID);
            if(isset($category[0])) {
                $message.= " (Category : {$category[0]->name})<br/><br/>";
            }

            $message.="
                Preview it: ".get_option('siteurl')."/?p={$post->ID}&preview=true
                <br/>
                Edit/Publish it: ".get_option('siteurl')."/wp-admin/post.php?action=edit&post={$post->ID}
                <br/>
            ";

            $message.="<br/>###";
            $post = get_post($post->ID);
            $message.= apply_filters('the_content', $post->post_content);
            $message.="###<br/><br/>";

            $message.= "Edit/Publish it: ".get_option('siteurl')."/wp-admin/post.php?action=edit&post={$post->ID}<br/><br/><br/>";

            $message.="Powered by: WP Editorial Control";

            $message .= "\r\n\r\n--" . $boundary . "--";

            wp_mail( $emails, $subject, $message, $headers);
        }
    } elseif ($old_status == 'pending' && $new_status == 'publish' && $current_user->ID!=$contributor->ID) {
        if(get_option('approvednotification')=='yes') {
            $from_email   = get_option('fromemail');
            $headers[] = 'From: Review Post <'. $from_email .'>';
            $headers[] = "Content-Type: multipart/alternative;boundary=" . $boundary . "\r\n";
            $subject='['.get_option('blogname').'] "'.$post->post_title.'" approved';
            $message="{$contributor->display_name},\n\nCongratulations, your post has been approved and published at ".get_permalink($post->ID)." .\n\n";
            $message.="By {$current_user->display_name} <{$current_user->user_email}>\n\n\n";
            $message.="Powered by: WP Editorial Control";
            wp_mail( $contributor->user_email, $subject, $message, $headers);
        }
    } elseif ($old_status == 'pending' && $new_status == 'draft' && $current_user->ID!=$contributor->ID) {
        if(get_option('declinednotification')=='yes') {
            $from_email   = get_option('fromemail');
            $headers[] = 'From: Review Post <'. $from_email .'>';
            $headers[] = "Content-Type: multipart/alternative;boundary=" . $boundary . "\r\n";
            $subject='['.get_option('blogname').'] "'.$post->post_title.'" declined';
            $message="{$contributor->display_name},\n\nSorry ,our post has not been approved. You can edit the post at ".get_option('siteurl')."/wp-admin/post.php?action=edit&post={$post->ID} .\n\n";
            $message.="By {$current_user->display_name} <{$current_user->user_email}>\n\n\n";
            $message.="Powered by: Editorial Control";
            wp_mail( $contributor->user_email, $subject, $message, $headers);
        }
    }
}

function ec_options_page() {
	if(isset($_POST['save'])) {
		update_option('supercontributor',$_POST['supercontributor']);
		
		if(isset($_POST['supercontributor'])) {
			allow_contributor_uploads(); 
		} else {
			disallow_contributor_uploads();
		}

        update_option('notificationemails',$_POST['notificationemails']);
        update_option('approvednotification',$_POST['approvednotification']);
        update_option('declinednotification',$_POST['declinednotification']);

		echo "<div id='message' class='updated fade'><p>Editorial control settings have now been saved.</p></div>";
    }
?>
	<div class="wrap"><h2>Editorial Control Status</h2>
        <form name="site" action="" method="post" id="notifier">
            <div id="review">
                <fieldset id="pendingdiv">
                    <legend><b><?php _e('Editorial Control settings are set to:') ?></b></legend>
                </fieldset>
                <br />
                <fieldset id="reviewdiv">
                    <div>
                        <input type="text" size="50" name="notificationemails" tabindex="1" id="notificationemails" value="<?php echo attribute_escape(get_option('notificationemails')); ?>"><br />
                        Enter the email address of who should be notified when a post is in a pending review status(comma separated).
                    </div>
                    <br/>
                    <div>
                        <label for="supercontributor" class="selectit">
                            <input type="checkbox" tabindex="2" id="supercontributor" name="supercontributor" value="yes" <?php if(get_option('supercontributor')=='yes') echo 'checked="checked"'; ?> />
                            Allow the contributor role to upload images.
                        </label>
                        <br/>
                        <label for="approvednotification" class="selectit">
                            <input type="checkbox" tabindex="2" id="approvednotification" name="approvednotification" value="yes" <?php if(get_option('approvednotification')=='yes') echo 'checked="checked"'; ?> />
                            Notify the contributor when their post is approved.
                        </label>
                        <br />
                        <label for="declinednotification" class="selectit">
                            <input type="checkbox" tabindex="3" id="declinednotification" name="declinednotification" value="yes" <?php if(get_option('declinednotification')=='yes') echo 'checked="checked"'; ?> />
                            Notify contributor when their post is declined (set back to draft).
                        </label>
                    </div>
                </fieldset>
                <br />
                <p class="submit">
                    <input name="save" type="submit" id="savenotifier" tabindex="6" style="font-weight: bold;" value="Save Settings" />
                </p>
            </div>
        </form>
        <small>Powered by WP Editorial Control</small>
	</div>
<?php
}
?>