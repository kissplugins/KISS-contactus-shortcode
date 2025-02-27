document.addEventListener('DOMContentLoaded', function () {
    const copyButton = document.getElementById('hypercart-copy-shortcode');
    const shortcodeInput = document.getElementById('hypercart-contactus-shortcode');

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
});