/**
 * AI Hairstyle Try-On Pro – Frontend Wizard JavaScript
 * Handles multi-step wizard, photo uploads, hairstyle selection via AJAX, and placeholder display
 * All logic is modular, heavily commented, and uses vanilla jQuery for compatibility
 */

jQuery(document).ready(function($) {
    // Cache frequently used DOM elements
    const wizardContainer = $('#ai-hairstyle-tryon-pro');
    const step1 = $('#step-1');
    const uploadedGrid = $('#uploaded-images');
    const hairstyleGrid = $('#hairstyle-grid');
    const mainUploadBtn = $('#main-upload-btn');
    const secondaryUploadBtn = $('#secondary-upload-btn');
    const cameraBtn = $('#camera-btn');
    const bookNowBtn = $('#book-now-btn');
    const selectedHairstyleInput = $('#selected_hairstyle_id');
    const genderTabs = $('.gender-tab');

    // State variables
    let uploadedFiles = [];
    const maxUploads = 4;
    let selectedHairstyleId = null;

    // Helper: Clear generated placeholder images
    function clearGeneratedImages() {
        uploadedGrid.find('.generated-main, .generated-thumbs').remove();
    }

    // Helper: Display 4 placeholder images (front + 3 thumbs)
    function showStylePlaceholders() {
        clearGeneratedImages();

        // Main front view
        const mainImg = $('<img>', {
            src: 'https://via.placeholder.com/600x800/0073aa/fff?text=Front+View+with+New+Style',
            alt: 'Front view with selected hairstyle',
            class: 'generated-main'
        });
        uploadedGrid.append(mainImg);

        // Thumbnail container
        const thumbContainer = $('<div>', { class: 'generated-thumbs' });
        const thumbs = [
            { src: 'https://via.placeholder.com/200x300/0073aa/fff?text=Left+Side',   alt: 'Left side view' },
            { src: 'https://via.placeholder.com/200x300/0073aa/fff?text=Right+Side',  alt: 'Right side view' },
            { src: 'https://via.placeholder.com/200x300/0073aa/fff?text=Back+View',   alt: 'Back view' }
        ];

        thumbs.forEach(thumb => {
            thumbContainer.append($('<img>', {
                src: thumb.src,
                alt: thumb.alt,
                class: 'generated-thumb'
            }));
        });

        uploadedGrid.append(thumbContainer);
    }

    // Step 1: Load hairstyles via AJAX
    function loadHairstyles(gender = 'women') {
        hairstyleGrid.html('<div class="loading">Loading styles...</div>');

        $.ajax({
            url: aiHairstyleData.ajax_url,
            type: 'POST',
            data: {
                action: 'load_hairstyles',
                gender: gender,
                nonce: aiHairstyleData.nonce
            },
            success: function(response) {
                if (response.success && response.data.hairstyles.length > 0) {
                    hairstyleGrid.empty();
                    response.data.hairstyles.forEach(style => {
                        const btn = $('<button>', {
                            class: 'hairstyle-btn',
                            'data-id': style.id,
                            text: style.title  // Just text – no image
                        });
                        hairstyleGrid.append(btn);
                    });
                } else {
                    hairstyleGrid.html('<p>No styles available yet. Add some in the admin panel.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading hairstyles:', error);
                hairstyleGrid.html('<p>Error loading styles. Please refresh the page.</p>');
            }
        });
    }

    // Initial load (default to women if both genders enabled)
    loadHairstyles();

    // Gender tab switching (if enabled)
    genderTabs.on('click', function() {
        genderTabs.removeClass('active');
        $(this).addClass('active');
        const gender = $(this).data('gender'); // 'men' or 'women'
        loadHairstyles(gender);
    });

    // Upload photos (1–4)
    $(document).on('click', '#main-upload-btn, #secondary-upload-btn', function(e) {
        e.preventDefault();
        const input = $('<input>', {
            type: 'file',
            accept: 'image/*',
            multiple: true,
            class: 'hidden'
        }).on('change', function() {
            const files = Array.from(this.files);
            files.forEach(file => {
                if (uploadedFiles.length < maxUploads) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = $('<img>', {
                            src: e.target.result,
                            alt: 'Uploaded photo',
                            class: 'uploaded-preview'
                        });
                        const wrapper = $('<div>', { class: 'upload-item' })
                            .append(img)
                            .append($('<button>', {
                                type: 'button',
                                class: 'remove-upload',
                                text: '×'
                            }));
                        uploadedGrid.append(wrapper);
                        uploadedFiles.push(file);
                        if (uploadedFiles.length >= maxUploads) {
                            mainUploadBtn.addClass('hidden');
                            secondaryUploadBtn.addClass('hidden');
                            cameraBtn.addClass('hidden');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
            this.value = ''; // Reset input
        }).click();
    });

    // Remove uploaded photo
    $(document).on('click', '.remove-upload', function() {
        const wrapper = $(this).closest('.upload-item');
        const index = wrapper.index();
        uploadedFiles.splice(index, 1);
        wrapper.remove();

        // Re-show upload buttons if under max
        if (uploadedFiles.length < maxUploads) {
            mainUploadBtn.removeClass('hidden');
            secondaryUploadBtn.removeClass('hidden');
            cameraBtn.removeClass('hidden');
        }
    });

    // Hairstyle selection
    $(document).on('click', '.hairstyle-btn', function() {
        $('.hairstyle-btn').removeClass('active');
        $(this).addClass('active');

        const styleId = $(this).data('id');
        selectedHairstyleInput.val(styleId);
        selectedHairstyleId = styleId;

        // Clear uploads and show placeholders
        uploadedGrid.empty();
        uploadedFiles = [];
        mainUploadBtn.addClass('hidden');
        secondaryUploadBtn.addClass('hidden');
        cameraBtn.addClass('hidden');

        showStylePlaceholders();

        bookNowBtn.prop('disabled', false);

        // Scroll to generated images
        $('html, body').animate({ scrollTop: uploadedGrid.offset().top - 50 }, 500);
    });

    // Reset wizard
    $('#reset-wizard').on('click', function() {
        uploadedGrid.empty();
        uploadedFiles = [];
        $('.hairstyle-btn').removeClass('active');
        selectedHairstyleInput.val('');
        mainUploadBtn.removeClass('hidden');
        secondaryUploadBtn.removeClass('hidden');
        cameraBtn.removeClass('hidden');
        bookNowBtn.prop('disabled', true);
    });
});