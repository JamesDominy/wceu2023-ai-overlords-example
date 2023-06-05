<?php
/**
 * Plugin Name: Smart Mouthed Robot
 * Plugin URI: https://your-plugin-website.com/
 * Description: Updates selected text in the post editor with ChatGPT generated content while editing.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://your-website.com/
 */

// Enqueue required scripts and styles
function smart_mouthed_robot_enqueue_scripts() {
    wp_enqueue_script('chatgpt-script', 'https://cdn.openai.com/chat-widget/beta/chatgpt.js', array(), '1.0', true);
    wp_enqueue_script('smart-mouthed-robot', plugin_dir_url(__FILE__) . 'smart-mouthed-robot.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'smart_mouthed_robot_enqueue_scripts');

// Add meta box to post editor
function smart_mouthed_robot_add_meta_box() {
    add_meta_box('smart_mouthed_robot_meta_box', 'Smart Mouthed Robot', 'smart_mouthed_robot_meta_box_callback', 'post', 'normal', 'high');
}
add_action('add_meta_boxes', 'smart_mouthed_robot_add_meta_box');

// Meta box callback function
function smart_mouthed_robot_meta_box_callback($post) {
    wp_nonce_field(basename(__FILE__), 'smart_mouthed_robot_nonce');
    $content = get_post_meta($post->ID, '_smart_mouthed_robot_content', true);
    ?>
    <div>
        <button id="smart_mouthed_robot_generate_button" class="button">Generate New Content</button>
        <div id="smart_mouthed_robot_generated_content"><?php echo $content; ?></div>
        <input type="hidden" id="smart_mouthed_robot_generated_content_input" name="_smart_mouthed_robot_content" value="<?php echo esc_attr($content); ?>">
    </div>
    <?php
}

// Save meta box data
function smart_mouthed_robot_save_meta_box_data($post_id) {
    if (!isset($_POST['smart_mouthed_robot_nonce']) || !wp_verify_nonce($_POST['smart_mouthed_robot_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['_smart_mouthed_robot_content'])) {
        update_post_meta($post_id, '_smart_mouthed_robot_content', sanitize_text_field($_POST['_smart_mouthed_robot_content']));
    }
}
add_action('save_post', 'smart_mouthed_robot_save_meta_box_data');

// Handle the proxy request to ChatGPT API
function chatgpt_proxy_request_handler() {
    $selectedText = sanitize_text_field($_POST['text']);

    // Make API request to ChatGPT from the server-side
    $response = wp_remote_post('https://api.openai.com/v1/engines/davinci-codex/completions', array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer YOUR_API_KEY',
        ),
        'body' => json_encode(array(
            'prompt' => $selectedText,
            'max_tokens' => 100,
        )),
    ));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // Retrieve the generated content from the API response
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        $generatedContent = $response_data['choices'][0]['text'];

        // Return the generated content as the response
        wp_send_json_success(array('generatedContent' => $generatedContent));
    } else {
        // Return an error response
        wp_send_json_error();
    }
}
add_action('wp_ajax_chatgpt_proxy_request', 'chatgpt_proxy_request_handler');
add_action('wp_ajax_nopriv_chatgpt_proxy_request', 'chatgpt_proxy_request_handler');
