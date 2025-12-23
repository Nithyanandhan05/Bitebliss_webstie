// Enhanced Checkout Page JavaScript

document.addEventListener('DOMContentLoaded', function() {

    // Initialize all components

    initAddressSelector();

    initDeliveryTimeSelector();

    initFormValidation();

    initButtonAnimation();

    initScrollAnimations();

    initTooltips();

    initAddressAutocomplete();

    

    // Address selector functionality

    function initAddressSelector() {

        const addressRadios = document.querySelectorAll('input[name="address_type"]');

        const addressTextarea = document.getElementById('delivery_address');

        const newAddressRadio = document.getElementById('new_address');

        

        addressRadios.forEach(radio => {

            radio.addEventListener('change', function() {

                if (this.value === 'saved') {

                    // Show selected saved address

                    const selectedAddress = this.getAttribute('data-address');

                    if (selectedAddress) {

                        addressTextarea.value = selectedAddress;

                        addressTextarea.style.display = 'none';

                        addressTextarea.removeAttribute('required');

                        

                        // Add animation to address cards

                        const addressCard = this.closest('.address-card');

                        addressCard.style.transform = 'scale(1.02)';

                        setTimeout(() => {

                            addressCard.style.transform = 'scale(1)';

                        }, 200);

                    }

                } else if (this.value === 'new') {

                    // Show textarea for new address

                    addressTextarea.style.display = 'block';

                    addressTextarea.setAttribute('required', 'required');

                    addressTextarea.value = '';

                    addressTextarea.focus();

                    

                    // Smooth reveal animation

                    addressTextarea.style.opacity = '0';

                    addressTextarea.style.transform = 'translateY(-20px)';

                    setTimeout(() => {

                        addressTextarea.style.transition = 'all 0.5s ease';

                        addressTextarea.style.opacity = '1';

                        addressTextarea.style.transform = 'translateY(0)';

                    }, 50);

                }

            });

        });

        

        // Add hover effects to address cards

        const addressCards = document.querySelectorAll('.address-card');

        addressCards.forEach(card => {

            card.addEventListener('mouseenter', function() {

                this.style.transform = 'translateY(-2px) scale(1.01)';

            });

            

            card.addEventListener('mouseleave', function() {

                if (!this.querySelector('input').checked) {

                    this.style.transform = 'translateY(0) scale(1)';

                }

            });

        });

    }

    

    // Delivery time selector functionality

    function initDeliveryTimeSelector() {

        const deliveryRadios = document.querySelectorAll('input[name="delivery_time"]');

        const schedulePicker = document.getElementById('schedulePicker');

        const dateInput = schedulePicker?.querySelector('input[type="date"]');

        const timeInput = schedulePicker?.querySelector('input[type="time"]');

        

        deliveryRadios.forEach(radio => {

            radio.addEventListener('change', function() {

                if (this.value === 'scheduled') {

                    schedulePicker.style.display = 'block';

                    schedulePicker.style.animation = 'slideDown 0.5s ease-out';

                    

                    // Set minimum date and time

                    if (dateInput) {

                        const today = new Date();

                        const tomorrow = new Date(today);

                        tomorrow.setDate(tomorrow.getDate() + 1);

                        dateInput.min = tomorrow.toISOString().split('T')[0];

                        dateInput.setAttribute('required', 'required');

                    }

                    

                    if (timeInput) {

                        timeInput.setAttribute('required', 'required');

                    }

                } else {

                    schedulePicker.style.animation = 'slideUp 0.3s ease-out';

                    setTimeout(() => {

                        schedulePicker.style.display = 'none';

                    }, 300);

                    

                    // Remove required attributes

                    if (dateInput) dateInput.removeAttribute('required');

                    if (timeInput) timeInput.removeAttribute('required');

                }

                

                // Add selection animation

                const label = this.nextElementSibling;

                label.style.transform = 'scale(0.95)';

                setTimeout(() => {

                    label.style.transform = 'scale(1)';

                }, 150);

            });

        });

    }

    

    // Form validation with real-time feedback

    function initFormValidation() {

        const form = document.getElementById('checkoutForm');

        const inputs = form.querySelectorAll('input, textarea');

        

        inputs.forEach(input => {

            input.addEventListener('blur', validateField);

            input.addEventListener('input', clearErrors);

        });

        

        form.addEventListener('submit', function(e) {

            e.preventDefault();

            

            if (validateForm()) {

                submitForm();

            }

        });

        

        function validateField(e) {

            const field = e.target;

            const value = field.value.trim();

            

            // Remove existing error styling

            field.classList.remove('error');

            removeErrorMessage(field);

            

            // Validate based on field type

            let isValid = true;

            let errorMessage = '';

            

            if (field.hasAttribute('required') && !value) {

                isValid = false;

                errorMessage = 'This field is required';

            } else if (field.type === 'tel' && value) {

                const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;

                if (!phoneRegex.test(value)) {

                    isValid = false;

                    errorMessage = 'Please enter a valid phone number';

                }

            } else if (field.type === 'email' && value) {

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!emailRegex.test(value)) {

                    isValid = false;

                    errorMessage = 'Please enter a valid email address';

                }

            }

            

            if (!isValid) {

                showFieldError(field, errorMessage);

            } else {

                showFieldSuccess(field);

            }

            

            return isValid;

        }

        

        function clearErrors(e) {

            const field = e.target;

            field.classList.remove('error');

            removeErrorMessage(field);

        }

        

        function validateForm() {

            let isValid = true;

            inputs.forEach(input => {

                if (!validateField({ target: input })) {

                    isValid = false;

                }

            });

            return isValid;

        }

        

        function showFieldError(field, message) {

            field.classList.add('error');

            

            const errorDiv = document.createElement('div');

            errorDiv.className = 'error-message';

            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;

            

            field.parentNode.appendChild(errorDiv);

            

            // Animate error message

            errorDiv.style.opacity = '0';

            errorDiv.style.transform = 'translateY(-10px)';

            setTimeout(() => {

                errorDiv.style.transition = 'all 0.3s ease';

                errorDiv.style.opacity = '1';

                errorDiv.style.transform = 'translateY(0)';

            }, 50);

        }

        

        function showFieldSuccess(field) {

            field.classList.add('success');

            setTimeout(() => {

                field.classList.remove('success');

            }, 2000);

        }

        

        function removeErrorMessage(field) {

            const existingError = field.parentNode.querySelector('.error-message');

            if (existingError) {

                existingError.style.opacity = '0';

                existingError.style.transform = 'translateY(-10px)';

                setTimeout(() => {

                    existingError.remove();

                }, 300);

            }

        }

    }

    

    // Button animation and loading states

    function initButtonAnimation() {

        const proceedBtn = document.querySelector('.proceed-btn');

        

        if (proceedBtn) {

            proceedBtn.addEventListener('click', function(e) {

                if (!this.classList.contains('loading')) {

                    // Add ripple effect

                    createRippleEffect(e, this);

                }

            });

        }

        

        function createRippleEffect(event, button) {

            const ripple = document.createElement('span');

            const rect = button.getBoundingClientRect();

            const size = Math.max(rect.width, rect.height);

            const x = event.clientX - rect.left - size / 2;

            const y = event.clientY - rect.top - size / 2;

            

            ripple.className = 'ripple';

            ripple.style.cssText = `

                position: absolute;

                width: ${size}px;

                height: ${size}px;

                left: ${x}px;

                top: ${y}px;

                background: rgba(255, 255, 255, 0.3);

                border-radius: 50%;

                transform: scale(0);

                animation: ripple 0.6s linear;

                pointer-events: none;

            `;

            

            button.appendChild(ripple);

            

            setTimeout(() => {

                ripple.remove();

            }, 600);

        }

    }

    

    // Scroll-triggered animations

    function initScrollAnimations() {

        const observerOptions = {

            threshold: 0.1,

            rootMargin: '0px 0px -50px 0px'

        };

        

        const observer = new IntersectionObserver((entries) => {

            entries.forEach(entry => {

                if (entry.isIntersecting) {

                    entry.target.classList.add('animate-in');

                }

            });

        }, observerOptions);

        

        // Observe elements for scroll animations

        const animatedElements = document.querySelectorAll('.order-item, .bill-row, .payment-option');

        animatedElements.forEach(el => observer.observe(el));

    }

    

    // Tooltip functionality

    function initTooltips() {

        const tooltipElements = document.querySelectorAll('[data-tooltip]');

        

        tooltipElements.forEach(element => {

            element.addEventListener('mouseenter', showTooltip);

            element.addEventListener('mouseleave', hideTooltip);

        });

        

        function showTooltip(e) {

            const text = e.target.getAttribute('data-tooltip');

            if (!text) return;

            

            const tooltip = document.createElement('div');

            tooltip.className = 'tooltip';

            tooltip.textContent = text;

            document.body.appendChild(tooltip);

            

            const rect = e.target.getBoundingClientRect();

            tooltip.style.cssText = `

                position: fixed;

                top: ${rect.top - tooltip.offsetHeight - 10}px;

                left: ${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px;

                background: var(--secondary-brown);

                color: white;

                padding: 0.5rem 1rem;

                border-radius: 8px;

                font-size: 0.9rem;

                z-index: 1000;

                opacity: 0;

                transform: translateY(10px);

                transition: all 0.3s ease;

                pointer-events: none;

            `;

            

            setTimeout(() => {

                tooltip.style.opacity = '1';

                tooltip.style.transform = 'translateY(0)';

            }, 50);

            

            e.target._tooltip = tooltip;

        }

        

        function hideTooltip(e) {

            const tooltip = e.target._tooltip;

            if (tooltip) {

                tooltip.style.opacity = '0';

                tooltip.style.transform = 'translateY(10px)';

                setTimeout(() => {

                    tooltip.remove();

                }, 300);

            }

        }

    }

    

    // Address autocomplete (basic implementation)

    function initAddressAutocomplete() {

        const addressTextarea = document.getElementById('delivery_address');

        const suggestionsContainer = document.getElementById('addressSuggestions');

        

        if (!addressTextarea || !suggestionsContainer) return;

        

        let debounceTimer;

        

        addressTextarea.addEventListener('input', function() {

            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {

                const query = this.value.trim();

                if (query.length > 3) {

                    // Mock address suggestions (replace with actual API)

                    showAddressSuggestions([

                        `${query}, Pune, Maharashtra`,

                        `${query}, Mumbai, Maharashtra`,

                        `${query}, Bangalore, Karnataka`

                    ]);

                } else {

                    hideSuggestions();

                }

            }, 300);

        });

        

        function showAddressSuggestions(suggestions) {

            suggestionsContainer.innerHTML = '';

            

            suggestions.forEach(suggestion => {

                const suggestionItem = document.createElement('div');

                suggestionItem.className = 'suggestion-item';

                suggestionItem.innerHTML = `

                    <i class="fas fa-map-marker-alt"></i>

                    <span>${suggestion}</span>

                `;

                

                suggestionItem.addEventListener('click', () => {

                    addressTextarea.value = suggestion;

                    hideSuggestions();

                    addressTextarea.focus();

                });

                

                suggestionsContainer.appendChild(suggestionItem);

            });

            

            suggestionsContainer.style.display = 'block';

        }

        

        function hideSuggestions() {

            suggestionsContainer.style.display = 'none';

        }

        

        // Hide suggestions when clicking outside

        document.addEventListener('click', function(e) {

            if (!addressTextarea.contains(e.target) && !suggestionsContainer.contains(e.target)) {

                hideSuggestions();

            }

        });

    }

    

    // Form submission with loading state

    function submitForm() {

        const proceedBtn = document.querySelector('.proceed-btn');

        const form = document.getElementById('checkoutForm');

        

        // Show loading state

        proceedBtn.classList.add('loading');

        proceedBtn.disabled = true;

        

        // Simulate form processing

        setTimeout(() => {

            // In real application, this would be the actual form submission

            form.submit();

        }, 2000);

    }

    

    // Add CSS for error states and animations

    const style = document.createElement('style');

    style.textContent = `

        .form-input.error {

            border-color: var(--danger-red) !important;

            background: rgba(220, 53, 69, 0.05) !important;

            animation: shake 0.5s ease-in-out;

        }

        

        .form-input.success {

            border-color: var(--success-green) !important;

            background: rgba(40, 167, 69, 0.05) !important;

        }

        

        .error-message {

            display: flex;

            align-items: center;

            gap: 0.5rem;

            margin-top: 0.5rem;

            color: var(--danger-red);

            font-size: 0.9rem;

            font-weight: 500;

        }

        

        .error-message i {

            font-size: 0.8rem;

        }

        

        @keyframes shake {

            0%, 100% { transform: translateX(0); }

            25% { transform: translateX(-5px); }

            75% { transform: translateX(5px); }

        }

        

        @keyframes ripple {

            to {

                transform: scale(4);

                opacity: 0;

            }

        }

        

        @keyframes slideUp {

            from {

                opacity: 1;

                transform: translateY(0);

                max-height: 200px;

            }

            to {

                opacity: 0;

                transform: translateY(-20px);

                max-height: 0;

            }

        }

        

        .animate-in {

            animation: fadeInUp 0.6s ease-out both;

        }

        

        @keyframes fadeInUp {

            from {

                opacity: 0;

                transform: translateY(30px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

        

        .suggestion-item {

            display: flex;

            align-items: center;

            gap: 0.8rem;

            padding: 0.8rem;

            background: white;

            border: 1px solid var(--medium-gray);

            border-top: none;

            cursor: pointer;

            transition: all 0.3s ease;

        }

        

        .suggestion-item:hover {

            background: rgba(151, 90, 183, 0.05);

            transform: translateX(5px);

        }

        

        .suggestion-item:first-child {

            border-top: 1px solid var(--medium-gray);

            border-top-left-radius: var(--border-radius-small);

            border-top-right-radius: var(--border-radius-small);

        }

        

        .suggestion-item:last-child {

            border-bottom-left-radius: var(--border-radius-small);

            border-bottom-right-radius: var(--border-radius-small);

        }

        

        .suggestion-item i {

            color: var(--primary-purple);

        }

        

        #addressSuggestions {

            position: absolute;

            top: 100%;

            left: 0;

            right: 0;

            z-index: 10;

            box-shadow: var(--shadow-medium);

            border-radius: var(--border-radius-small);

            overflow: hidden;

            display: none;

        }

    `;

    

    document.head.appendChild(style);

    

    // Add smooth transitions to all interactive elements

    const interactiveElements = document.querySelectorAll('button, input, textarea, .address-card, .time-option, .order-item');

    interactiveElements.forEach(element => {

        element.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

    });

    

    // Add focus management for accessibility

    const focusableElements = document.querySelectorAll('input, textarea, button, select, [tabindex]:not([tabindex="-1"])');

    let currentFocusIndex = 0;

    

    document.addEventListener('keydown', function(e) {

        if (e.key === 'Tab') {

            // Custom tab navigation could be implemented here

        }

        

        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {

            // Handle enter key for better UX

            const nextInput = getNextFocusableElement(e.target);

            if (nextInput) {

                nextInput.focus();

            }

        }

    });

    

    function getNextFocusableElement(currentElement) {

        const focusableElements = Array.from(document.querySelectorAll('input:not([disabled]), textarea:not([disabled]), button:not([disabled])'));

        const currentIndex = focusableElements.indexOf(currentElement);

        return focusableElements[currentIndex + 1] || null;

    }

    

    // Performance optimization: lazy load non-critical animations

    const lazyAnimations = () => {

        // Add particle effects to buttons on hover

        const buttons = document.querySelectorAll('button, .address-card, .time-option');

        buttons.forEach(button => {

            button.addEventListener('mouseenter', function() {

                if (!this.querySelector('.hover-particles')) {

                    createHoverParticles(this);

                }

            });

        });

    };

    

    function createHoverParticles(element) {

        const particles = document.createElement('div');

        particles.className = 'hover-particles';

        particles.style.cssText = `

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            pointer-events: none;

            overflow: hidden;

        `;

        

        for (let i = 0; i < 3; i++) {

            const particle = document.createElement('div');

            particle.style.cssText = `

                position: absolute;

                width: 4px;

                height: 4px;

                background: rgba(151, 90, 183, 0.6);

                border-radius: 50%;

                animation: particleFloat 2s ease-out infinite;

                animation-delay: ${i * 0.2}s;

                top: ${Math.random() * 100}%;

                left: ${Math.random() * 100}%;

            `;

            particles.appendChild(particle);

        }

        

        element.appendChild(particles);

        

        setTimeout(() => {

            particles.remove();

        }, 2000);

    }

    

    // Initialize lazy animations after a delay

    setTimeout(lazyAnimations, 1000);

    

    // Add final style for particle animation

    const particleStyle = document.createElement('style');

    particleStyle.textContent = `

        @keyframes particleFloat {

            0% {

                opacity: 0;

                transform: translateY(20px) scale(0);

            }

            50% {

                opacity: 1;

                transform: translateY(-10px) scale(1);

            }

            100% {

                opacity: 0;

                transform: translateY(-30px) scale(0);

            }

        }

    `;

    document.head.appendChild(particleStyle);

});