jQuery(document).ready(function($) {
    // Edit form submission via Ajax
    $('#edit-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            url: devhivContactForm.ajax_url,
            type: 'POST',
            data: {
                action: 'devhiv_contactform_edit_response',
                nonce: devhivContactForm.nonce,
                data: formData
            },
            success: function(response) {
                if (response.success) {
                    alert('Response updated successfully.');
                    location.reload(); // Reload the page to update the table
                } else {
                    alert('Failed to update response.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax request failed');
            }
        });
    });

    // Delete action via Ajax
    $('.devhiv-delete-link').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }

        var deleteLink = $(this);
        var itemId = deleteLink.data('item-id');
        var nonce = deleteLink.data('nonce');

        $.ajax({
            url: devhivContactForm.ajax_url,
            type: 'POST',
            data: {
                action: 'devhiv_contactform_delete_response',
                nonce: nonce,
                id: itemId
            },
            success: function(response) {
                if (response.success) {
                    alert('Response deleted successfully.');
                    window.location.reload(); // Reload the page to update the table
                } else {
                    alert('Failed to delete response.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax request failed');
            }
        });
    });
});
