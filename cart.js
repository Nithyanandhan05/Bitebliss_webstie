document.addEventListener('DOMContentLoaded', () => {

    const cartContainer = document.querySelector('.cart-items-list');

    // If there's no cart on the page (e.g., it's empty), stop the script.
    if (!cartContainer) {
        return;
    }

    // A single event listener on the parent container is more efficient.
    cartContainer.addEventListener('click', (event) => {
        const button = event.target.closest('button'); // Find which button was actually clicked
        if (!button) return; // Exit if the click wasn't on a button

        const itemId = button.dataset.itemId;
        let action = null;

        if (button.classList.contains('increase-btn')) {
            action = 'increase';
        } else if (button.classList.contains('decrease-btn')) {
            action = 'decrease';
        } else if (button.classList.contains('remove-btn')) {
            action = 'remove';
        }

        // If a valid action button was clicked, call the function to update the cart
        if (action && itemId) {
            updateCart(action, itemId);
        }
    });

    /**
     * Sends the update request to the server and reloads the page to show changes.
     * @param {string} action - The action to perform ('increase', 'decrease', 'remove').
     * @param {string} itemId - The unique ID of the item in the cart session.
     */
    function updateCart(action, itemId) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('cart_item_id', itemId); // Use the correct unique ID

        fetch('update_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // The easiest and most reliable way to show all changes is to reload the page.
                // This ensures all totals, item counts, and quantities are perfectly in sync.
                window.location.reload();
            } else {
                alert('Failed to update cart. Please try again.');
            }
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
            alert('An error occurred. Please check your connection and try again.');
        });
    }
});