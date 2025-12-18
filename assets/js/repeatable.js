jQuery(document).ready(function($) {
    // Add variant row
    $('.ai-add-variant').on('click', function() {
        var container = $(this).closest('.ai-variant-repeater');
        var field = container.data('field');
        var index = container.find('.ai-variant-items tr').length;
        var row = '<tr>' +
            '<td><input type="text" name="' + field + '[' + index + '][name]" class="regular-text" /></td>' +
            '<td><textarea name="' + field + '[' + index + '][seo]" rows="2"></textarea></td>' +
            '<td class="ai-images-cell"><button type="button" class="button ai-upload-images" data-field="' + field + '" data-index="' + index + '">Add Images</button></td>' +
            '<td><button type="button" class="button ai-remove-variant">Delete</button></td>' +
            '</tr>';
        container.find('.ai-variant-items').append(row);
    });

    // Remove variant
    $(document).on('click', '.ai-remove-variant', function() {
        $(this).closest('tr').remove();
    });

    // Add product row
    $('.ai-add-product').on('click', function() {
        var container = $(this).closest('.ai-product-repeater');
        var field = container.data('field');
        var index = container.find('.ai-product-items tr').length;
        var row = '<tr>' +
            '<td><input type="text" name="' + field + '[' + index + '][name]" class="regular-text" /></td>' +
            '<td><input type="number" name="' + field + '[' + index + '][price]" step="0.01" /></td>' +
            '<td class="ai-images-cell"><button type="button" class="button ai-upload-image" data-field="' + field + '" data-index="' + index + '" data-sub="image">Upload Image</button></td>' +
            '<td><input type="url" name="' + field + '[' + index + '][link]" class="regular-text" /></td>' +
            '<td><textarea name="' + field + '[' + index + '][desc]" rows="3"></textarea></td>' +
            '<td><button type="button" class="button ai-remove-product">Delete</button></td>' +
            '</tr>';
        container.find('.ai-product-items').append(row);
    });

    // Remove product
    $(document).on('click', '.ai-remove-product', function() {
        $(this).closest('tr').remove();
    });

    // Media uploader for variants (multiple)
    $(document).on('click', '.ai-upload-images', function(e) {
        e.preventDefault();
        var button = $(this);
        var field = button.data('field');
        var index = button.data('index');
        var frame = wp.media({
            title: 'Select Images',
            multiple: true,
            library: { type: 'image' },
            button: { text: 'Use Images' }
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').map(function(a) { return a.toJSON().url; });
            var cell = button.closest('.ai-images-cell');
            cell.find('img').remove();
            cell.find('input[type="hidden"]').remove();
            attachments.forEach(function(url) {
                cell.append('<img src="' + url + '" style="max-width:100px;margin:5px;" />');
                cell.append('<input type="hidden" name="' + field + '[' + index + '][gallery][]" value="' + url + '" />');
            });
        });

        frame.open();
    });

    // Media uploader for product image (single)
    $(document).on('click', '.ai-upload-image', function(e) {
        e.preventDefault();
        var button = $(this);
        var field = button.data('field');
        var index = button.data('index');
        var sub = button.data('sub');
        var frame = wp.media({
            title: 'Select Image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use Image' }
        });

        frame.on('select', function() {
            var url = frame.state().get('selection').first().toJSON().url;
            var cell = button.closest('.ai-images-cell');
            cell.find('img').remove();
            cell.append('<img src="' + url + '" style="max-width:100px;margin:5px;" />');
            cell.find('input[type="hidden"]').remove();
            cell.append('<input type="hidden" name="' + field + '[' + index + '][' + sub + ']" value="' + url + '" />');
        });

        frame.open();
    });
});