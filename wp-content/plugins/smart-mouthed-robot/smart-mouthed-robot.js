(function($) {
    $(document).ready(function() {
        // Handle the generate button click event
        $('#smart_mouthed_robot_generate_button').on('click', function(e) {
            e.preventDefault();

            // Get the selected text
            var selectedText = getSelectedText();

            // Make sure there is selected text
            if (selectedText) {
                // Make API request to ChatGPT and replace the selected text with the generated content
                generate_chatgpt_content(selectedText, function(generatedContent) {
                    // Replace the selected text with the generated content
                    replaceSelectedText(selectedText, generatedContent);
                });
            } else {
                alert("Please select the text you want to replace.");
            }
        });

        // Function to retrieve the selected text
        function getSelectedText() {
            var selection = window.getSelection();
            if (selection && selection.toString()) {
                return selection;
            }
            return null;
        }

        // Function to replace the selected text with the generated content
        function replaceSelectedText(selection, content) {
			let current_content = wp.data.select("core/editor").getEditedPostContent();
			let updatedContent = current_content.replace(selection.toString(), content);
			wp.data.dispatch("core/editor").editPost({ "content": updatedContent });
            let focusNode = selection.focusNode;
            let start = Math.min(selection.anchorOffset, selection.focusOffset);
            let end = Math.max(selection.anchorOffset, selection.focusOffset);
            focusNode.data = focusNode.data.substr(0,start) + content + focusNode.data.substr(end,focusNode.data.length-1);
        }

        // Function to generate content using ChatGPT
        function generate_chatgpt_content(text, callback) {
            // Make API request to WordPress backend to proxy the ChatGPT request
            $.ajax({
                url: ajaxurl,
                method: "POST",
                data: {
                    action: "chatgpt_proxy_request",
                    text: text.toString()
                },
                success: function(response) {
                    callback(response.generatedContent);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error:", errorThrown);
                }
            });
        }
    });
})(jQuery);
