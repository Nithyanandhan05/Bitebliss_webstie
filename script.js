// =================================================================================
// BITE BLISS - COMPLETE & UNIFIED JAVASCRIPT FILE (ALL FUNCTIONS INCLUDED)
// =================================================================================

// --- 1. ADDED: GLOBAL HELPER FUNCTIONS ---
// These functions are defined here to be available everywhere in the script.

/**
 * Updates the cart count number in the main site header.
 * @param {number | string} count The new total number of items in the cart.
 */
function updateCartCountInHeader(count) {
    // This assumes your header's cart count element has a class like 'cart-count-badge'
    const headerBadge = document.querySelector('.cart-icon span, .cart-count-badge');
    if (headerBadge) {
        const countAsInt = parseInt(count, 10);
        // UPDATED: Simplified display logic
        headerBadge.style.display = countAsInt > 0 ? 'flex' : 'none'; // Or 'block'
        headerBadge.textContent = countAsInt;
    }
}

/**
 * UPDATED: Now robustly updates the floating cart for both mobile and desktop views.
 * It targets both the number badge (for mobile) and the descriptive text (for desktop).
/**
 * Updates the visibility and count of the floating cart bar.
 * @param {number | string} count The new total number of items in the cart.
 */
function updateFloatingCart(count) {
    const cartBar = document.querySelector('.floating-cart-bar');
    if (!cartBar) return;

    const itemCountBadgeEl = cartBar.querySelector('.cart-badge'); 
    const countAsInt = parseInt(count, 10);

    if (countAsInt > 0) {
        // Update the notification badge number, if it exists
        if (itemCountBadgeEl) {
            itemCountBadgeEl.textContent = countAsInt;
        }
        // Show the entire bar
        cartBar.classList.add('visible');
    } else {
        // Hide the bar if the cart is empty
        cartBar.classList.remove('visible');
    }
}

// --- 2. CONSOLIDATED: MAIN EVENT LISTENER ---
// All your code is now safely inside this single DOMContentLoaded listener.
document.addEventListener("DOMContentLoaded", () => {

    // --- Hero Slider ---
    if (typeof Swiper !== 'undefined' && document.querySelector('.hero-slider')) {
        new Swiper('.hero-slider', {
            loop: true,
            effect: 'fade',
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
        });
    }
    // In script.js, find the line:
// document.addEventListener("DOMContentLoaded", () => {
// ... and add this code inside it ...

    // --- 3D Review Carousel ---
    if (typeof Swiper !== 'undefined' && document.querySelector('.reviews-carousel')) {
        new Swiper('.reviews-carousel', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            coverflowEffect: {
                rotate: 50,       // Slide rotation in degrees
                stretch: 0,       // Stretch space between slides (in px)
                depth: 100,       // Depth offset in px (slides translate in Z axis)
                modifier: 1,      // Effect multiplier
                slideShadows: true, // Enables slides shadows
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    }

// ...
// });
    // --- Scroll Animation (Intersection Observer) ---
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            } else {
                entry.target.classList.remove('show');
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.hidden').forEach((el) => observer.observe(el));

    // --- Standard "Add to Cart" Button ---
    document.querySelectorAll('.btn-add-to-cart').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const btn = e.currentTarget;
            if (btn.classList.contains('adding') || btn.classList.contains('added')) {
                return;
            }
            const productId = btn.dataset.productId;
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            fetch('cart_logic.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCountInHeader(data.cart_count);
                        updateFloatingCart(data.cart_count);
                        btn.classList.add('adding');
                        setTimeout(() => {
                            btn.classList.remove('adding');
                        }, 2000);
                    } else {
                        alert(data.message || 'Could not add to cart.');
                    }
                })
                .catch(error => console.error('Fetch Error:', error));
        });
    });

    // --- Brownie Customization Side Panel Logic ---
    const panel = document.getElementById('customizationPanel');
    const panelOverlay = document.getElementById('panelOverlay');
    if (panel && panelOverlay) {
        const panelCloseBtn = document.getElementById('panelCloseBtn');
        const customizeButtons = document.querySelectorAll('.btn-customize');
        const form = document.getElementById('customizationForm');
        const productIdInput = document.getElementById('panelProductId');
        const productNameInput = document.getElementById('panelProductNameInput');
        const basePriceInput = document.getElementById('panelBasePrice');
        const egglessIncreaseInput = document.getElementById('panelEgglessIncrease');
        const productImage = document.getElementById('panelProductImage');
        const productNameDisplay = document.getElementById('panelProductName');
        const totalPriceDisplay = document.getElementById('panelTotalPrice');

        const openPanel = (e) => {
            const button = e.currentTarget;
            productIdInput.value = button.dataset.productId;
            productNameInput.value = button.dataset.productName;
            basePriceInput.value = button.dataset.basePrice;
            egglessIncreaseInput.value = button.dataset.egglessIncrease;
            productImage.src = button.dataset.productImage;
            productNameDisplay.textContent = button.dataset.productName;
            document.body.classList.add('panel-open');
            panel.classList.add('is-open');
            panelOverlay.classList.add('is-open');
            updateTotalPrice();
        };

        const closePanel = () => {
            document.body.classList.remove('panel-open');
            panel.classList.remove('is-open');
            panelOverlay.classList.remove('is-open');
            setTimeout(() => {
                if (form) {
                    form.reset();
                    document.getElementById('quantity').value = 1;
                }
            }, 500);
        };

        const updateTotalPrice = () => {
            const basePrice = parseFloat(basePriceInput.value) || 0;
            const egglessIncrease = parseFloat(egglessIncreaseInput.value) || 0;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const piecesSelect = document.getElementById('pieces');
            const selectedOption = piecesSelect.options[piecesSelect.selectedIndex];
            const pieceMultiplier = parseFloat(selectedOption.dataset.multiplier) || 1;
            let priceWithPieces = basePrice * pieceMultiplier;
            let currentPricePerUnit = priceWithPieces;

            // UPDATED: Added a null check to prevent errors if no radio button is selected.
            const eggPreference = document.querySelector('input[name="egg_preference"]:checked');
            if (eggPreference && eggPreference.value === 'Eggless') {
                currentPricePerUnit += egglessIncrease;
            }
            
            const total = currentPricePerUnit * quantity;
            totalPriceDisplay.textContent = `₹${total.toFixed(2)}`;
        };

        customizeButtons.forEach(btn => btn.addEventListener('click', openPanel));
        if (panelCloseBtn) panelCloseBtn.addEventListener('click', closePanel);
        panelOverlay.addEventListener('click', closePanel);

        if (form) {
            form.addEventListener('change', updateTotalPrice);
            form.addEventListener('input', updateTotalPrice);
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = form.querySelector('.btn-panel-add');
                submitBtn.classList.add('loading');
                const formData = new FormData(form);
                fetch('add_to_cart.php', { method: 'POST', body: formData })
                    .then(response => {
                        if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                        return response.json();
                    })
                    .then(data => {
                        submitBtn.classList.remove('loading');
                        if (data && data.success) {
                            updateCartCountInHeader(data.cart_count);
                            updateFloatingCart(data.cart_count);
                            closePanel();
                        } else {
                            alert(data.message || 'Could not add item to cart.');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        submitBtn.classList.remove('loading');
                        alert('An error occurred while adding to the cart. Please try again.');
                    });
            });
        }
    }

    // --- Glassmorphism Hover Effect ---
    const aboutBox = document.querySelector('.about-box');
    if (aboutBox) {
        aboutBox.addEventListener('mousemove', (e) => {
            const rect = aboutBox.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            aboutBox.style.setProperty('--mouse-x', `${x}px`);
            aboutBox.style.setProperty('--mouse-y', `${y}px`);
        });
    }

    // --- REMOVED: Floating Cart Bar Animation on Page Load ---
    // This block was removed because it conflicted with the primary CSS animation
    // triggered by adding the 'visible' class. The default scale animation
    // from your CSS is now the only one used, preventing conflicts.

    // --- Smooth Scrolling ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.length > 1) {
                e.preventDefault();
                const targetElement = document.querySelector(href);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

    // --- Header Scroll Effect ---
    const body = document.body;
    window.addEventListener('scroll', () => {
        body.classList.toggle('scrolled', window.scrollY > 50);
    });

    // --- Menu Text Animation ---
    const menuHeading = document.querySelector('#menu h2');
    if (menuHeading) {
        const text = menuHeading.textContent;
        menuHeading.innerHTML = '';
        text.split('').forEach((letter, index) => {
            const span = document.createElement('span');
            span.innerHTML = letter === ' ' ? '&nbsp;' : letter;
            span.style.transitionDelay = `${index * 0.05}s`;
            menuHeading.appendChild(span);
        });
    }

    // --- Login/Sign Up Side Panel ---
    const profileBtn = document.getElementById('profileBtn');
    const loginSidePanel = document.getElementById('sidePanel');
    const loginOverlay = document.getElementById('loginOverlay');
    const loginPanelCloseBtn = document.getElementById('closeBtn');
    const tabLinks = document.querySelectorAll('.tab-link');
    const formContainers = document.querySelectorAll('.form-container');

    function closeLoginPanel() {
        if (loginSidePanel) loginSidePanel.classList.remove('is-open');
        if (loginOverlay) loginOverlay.classList.remove('is-open');
    }

    if (profileBtn) {
        profileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if(loginSidePanel && loginOverlay) {
                loginSidePanel.classList.add('is-open');
                loginOverlay.classList.add('is-open');
            }
        });
    }
    if (loginPanelCloseBtn) loginPanelCloseBtn.addEventListener('click', closeLoginPanel);
    if (loginOverlay) loginOverlay.addEventListener('click', closeLoginPanel);

    if (tabLinks.length > 0 && formContainers.length > 0) {
        tabLinks.forEach(tab => {
            tab.addEventListener('click', () => {
                const formId = tab.dataset.form;
                tabLinks.forEach(link => link.classList.remove('active'));
                tab.classList.add('active');
                formContainers.forEach(form => {
                    form.classList.toggle('active', form.id === `${formId}Form`);
                });
            });
        });
    }

    // --- 3D Card Tilt Effect (For Desktop Only) ---
    const isTouchDevice = () => 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (!isTouchDevice()) {
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
                card.style.transition = 'transform 0.1s ease';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
                card.style.transition = 'transform 0.5s ease';
            });
        });
    }

    // --- Cart Page Interactivity (Commented out as per original) ---
    document.querySelectorAll('.cart-item').forEach(item => {
        // ... Original commented-out logic is preserved here if you need it later
    });

    // --- Login/Sign Up Form Submission ---
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const messageEl = this.querySelector('.form-message');
            fetch('login_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    messageEl.textContent = data.message;
                    messageEl.style.color = data.success ? 'lightgreen' : 'salmon';
                    if (data.success) {
                        setTimeout(() => {
                            window.location.href = data.user_type === 'admin' ? 'admin/index.php' : window.location.pathname;
                        }, 1000);
                    }
                });
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const messageEl = this.querySelector('.form-message');
            fetch('register_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    messageEl.textContent = data.message;
                    messageEl.style.color = data.success ? 'lightgreen' : 'salmon';
                    if (data.success) this.reset();
                });
        });
    }
    
});