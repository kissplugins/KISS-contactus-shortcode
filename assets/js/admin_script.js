document.addEventListener('DOMContentLoaded', function () {
    // Function to handle copy-to-clipboard for a given input and button
    function setupCopyToClipboard(inputId, buttonId) {
        const copyButton = document.getElementById(buttonId);
        const shortcodeInput = document.getElementById(inputId);

        if (copyButton && shortcodeInput) {
            copyButton.addEventListener('click', function () {
                // Select the text in the input field
                shortcodeInput.select();
                shortcodeInput.setSelectionRange(0, 99999); // For mobile devices

                try {
                    // Copy the selected text to the clipboard
                    navigator.clipboard.writeText(shortcodeInput.value).then(
                        function () {
                            // Success: Provide feedback to the user
                            copyButton.textContent = 'Copied!';
                            setTimeout(function () {
                                copyButton.textContent = 'Copy shortcode';
                            }, 2000); // Reset button text after 2 seconds
                        },
                        function (err) {
                            // Error: Fallback to older method if navigator.clipboard fails
                            console.error('Failed to copy: ', err);
                            try {
                                document.execCommand('copy');
                                copyButton.textContent = 'Copied!';
                                setTimeout(function () {
                                    copyButton.textContent = 'Copy shortcode';
                                }, 2000);
                            } catch (err) {
                                alert('Failed to copy the shortcode. Please copy it manually.');
                            }
                        }
                    );
                } catch (err) {
                    alert('Failed to copy the shortcode. Please copy it manually.');
                }
            });
        }
    }

    // Setup copy-to-clipboard for both shortcodes
    setupCopyToClipboard('hypercart-contactus-shortcode', 'hypercart-copy-shortcode-contactus');
    setupCopyToClipboard('hypercart-contact-us-shortcode', 'hypercart-copy-shortcode-contact-us');
});