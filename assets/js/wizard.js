jQuery(document).ready(function($) {
    const wizard = $('#ai-hairstyle-wizard');
    if (!wizard.length) return;

    let images = []; // Array of { id: unique, src: dataURL }
    let mainImageId = null;
    const maxImages = 4;

    // Hidden file inputs
    const cameraInput = $('<input type="file" accept="image/*" capture="environment" style="display:none;">');
    const galleryInput = $('<input type="file" accept="image/*" style="display:none;">');
    wizard.append(cameraInput, galleryInput);

    // Bind buttons
    $('.ai-btn-camera, .ai-btn-camera-small').on('click', () => cameraInput.trigger('click'));
    $('.ai-btn-gallery, .ai-btn-gallery-small').on('click', () => galleryInput.trigger('click'));

    // File change handler
    cameraInput.add(galleryInput).on('change', function(e) {
        if (e.target.files.length && images.length < maxImages) {
            handleFile(e.target.files[0]);
        }
    });

    function handleFile(file) {
        if (!file.type.match('image.*')) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const id = Date.now() + Math.random();
            images.push({ id, src: e.target.result });

            if (!mainImageId) {
                mainImageId = id;
            }

            renderImages();
            updateButtons();
            checkNextButton();
        };
        reader.readAsDataURL(file);
    }

    function renderImages() {
        const mainBox = $('.ai-upload-main-box');
        const mainPreview = $('.ai-main-preview');

        // Main preview
        const mainImg = images.find(img => img.id === mainImageId);
        if (mainImg) {
            mainPreview.html(`<img src="${mainImg.src}" alt="Main">`);
            $('.ai-upload-placeholder').hide();
            mainBox.addClass('has-image');
        } else {
            mainPreview.empty();
            $('.ai-upload-placeholder').show();
            mainBox.removeClass('has-image');
        }

        // Thumbnails
        const thumbsHtml = images.filter(img => img.id !== mainImageId)
            .map(img => `
                <div class="ai-thumbnail" data-id="${img.id}">
                    <img src="${img.src}" alt="Thumbnail">
                    <button class="ai-delete-thumb" data-id="${img.id}">×</button>
                </div>
            `).join('');
        $('.ai-thumbnails-row').html(thumbsHtml || '<div></div>');

        // Bind thumbnail clicks (swap with main)
        $('.ai-thumbnail').off('click').on('click', function() {
            const thumbId = $(this).data('id');
            const oldMainId = mainImageId;
            mainImageId = thumbId;
            renderImages();
            bindDeleteButtons();
        });

        bindDeleteButtons();
    }

    function bindDeleteButtons() {
        $('.ai-delete-main').off('click').on('click', deleteMain);
        $('.ai-delete-thumb').off('click').on('click', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            deleteImage(id);
        });
    }

    function deleteMain() {
        if (!mainImageId) return;
        deleteImage(mainImageId);
    }

    function deleteImage(id) {
        images = images.filter(img => img.id !== id);
        if (mainImageId === id) {
            mainImageId = images[0] ? images[0].id : null;
        }
        renderImages();
        updateButtons();
        checkNextButton();

        // Reset file inputs to allow re-uploading the same image
        cameraInput.val('');
        galleryInput.val('');
    }

    function updateButtons() {
        const atMax = images.length >= maxImages;
        $('.ai-btn-camera, .ai-btn-gallery, .ai-btn-camera-small, .ai-btn-gallery-small')
            .prop('disabled', atMax);
    }

    function checkNextButton() {
        $('.ai-btn-next').prop('disabled', images.length === 0);
    }

    // Next button step navigation
    $('.ai-btn-next').on('click', () => {
        if (images.length === 0) return;
        const current = $('.ai-wizard-step.ai-step-active');
        const next = current.next('.ai-wizard-step');
        if (next.length) {
            current.removeClass('ai-step-active');
            next.addClass('ai-step-active');
            $('.ai-progress-fill').css('width', (parseInt(next.data('step')) / 5 * 100) + '%');
        }
    });

    // Initial state
    renderImages();
    updateButtons();
    checkNextButton();
});