<?php
/**
 * AI Hairstyle – Admin Assets (JS + CSS) for Hairstyle & Staff CPTs
 */

class AI_Hairstyle_Admin_Assets {

    public static function enqueue( $hook ) {
        global $post;

        // Only load on our CPT edit screens
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->post_type, array( 'ai_hairstyle', 'ai_staff' ) ) ) {
            return;
        }

        // Media uploader
        wp_enqueue_media();

        // jQuery UI DateTimePicker (for blocked slots)
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.6.3', true );
        wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css' );

        // Timepicker for weekly hours (12-hour AM/PM)
        wp_enqueue_script( 'jquery-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.3.5/jquery.timepicker.min.js', array( 'jquery' ), '1.3.5', true );
        wp_enqueue_style( 'jquery-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.3.5/jquery.timepicker.min.css' );

        // Our dedicated admin CSS
        wp_enqueue_style( 'ai-hairstyle-admin', AI_HAIRSTYLE_PLUGIN_URL . 'assets/css/admin.css', array(), AI_HAIRSTYLE_VERSION );

        // Inline JS
        wp_add_inline_script( 'jquery', '
            jQuery(document).ready(function($) {

                // Weekly hours time picker (12-hour AM/PM)
                $(".ai-time-picker").timepicker({
                    timeFormat: "h:mm TT",
                    interval: 15,
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true
                });

                // Closed day disables inputs
                $(document).on("change", ".ai-day-closed", function(){
                    var checked = this.checked;
                    $(this).closest("tr").find(".ai-time-picker").prop("disabled", checked);
                });

                // Blocked slots datetime picker (24-hour)
                $(".ai-datetime-picker").datetimepicker({
                    format: "Y-m-d H:i",
                    step: 15
                });

                function openGalleryFrame( current_ids, callback ) {
                    var ids_array = current_ids ? current_ids.split(",").map(Number).filter(Boolean) : [];

                    var frame = wp.media({
                        frame:    "post",
                        state:    "gallery-library",
                        title:    "Edit Gallery",
                        editing:  true,
                        multiple: true,
                        selection: new wp.media.model.Selection( null, { multiple: true } )
                    });

                    if ( ids_array.length > 0 ) {
                        var selection = frame.state("gallery-library").get("selection");
                        wp.media.query({ post__in: ids_array, perPage: -1 })
                            .more()
                            .done(function() { selection.add( this.models ); });
                    }

                    frame.on("update", function( selection ) {
                        var ids = selection.models.map(function(m) { return m.id; }).join(",");
                        callback( ids, selection );
                        frame.close();
                    });

                    frame.open();
                }

                // Main gallery (Hairstyles)
                $(document).on("click", ".ai-edit-main-gallery", function(e) {
                    e.preventDefault();
                    var $input  = $("#main_gallery_ids");
                    var current = $input.val() || "";
                    openGalleryFrame( current, function( ids, selection ) {
                        $input.val( ids );
                        var $preview = $("#main-gallery-preview");
                        $preview.empty();
                        selection.models.forEach(function(m) {
                            var url = m.attributes.sizes.medium ? m.attributes.sizes.medium.url : m.attributes.url;
                            $preview.append("<img src=\"" + url + "\" style=\"width:175px;height:175px;object-fit:cover;border-radius:10px;margin:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);\" />");
                        });
                    });
                });

                // Variant galleries
                $(document).on("click", ".ai-upload-variant-images", function(e) {
                    e.preventDefault();
                    var $row    = $(this).closest("tr");
                    var $input  = $row.find(".gallery-ids");
                    var current = $input.val() || "";
                    openGalleryFrame( current, function( ids, selection ) {
                        $input.val( ids );
                        var $preview = $row.find(".variant-thumb-preview");
                        $preview.empty();
                        selection.models.forEach(function(m) {
                            var url = m.attributes.sizes.thumbnail ? m.attributes.sizes.thumbnail.url : m.attributes.url;
                            $preview.append("<img src=\"" + url + "\" style=\"width:100px;height:100px;object-fit:cover;border-radius:8px;margin:6px;box-shadow:0 2px 6px rgba(0,0,0,0.1);\" />");
                        });
                    });
                });

                // Staff photo uploader
                var staffPhotoFrame;
                $(document).on("click", ".ai-upload-staff-photo", function(e){
                    e.preventDefault();
                    if ( staffPhotoFrame ) {
                        staffPhotoFrame.open();
                        return;
                    }
                    staffPhotoFrame = wp.media({
                        title: "Select Staff Photo",
                        button: { text: "Use this photo" },
                        multiple: false,
                        library: { type: "image" }
                    });
                    staffPhotoFrame.on("select", function(){
                        var attachment = staffPhotoFrame.state().get("selection").first().toJSON();
                        $("#ai-staff-photo-preview").html("<img src=\"" + attachment.sizes.medium.url + "\" style=\"max-width:200px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);\">");
                        $("#ai_staff_photo_id").val(attachment.id);
                        $(".ai-remove-staff-photo").show();
                    });
                    staffPhotoFrame.open();
                });

                $(document).on("click", ".ai-remove-staff-photo", function(e){
                    e.preventDefault();
                    $("#ai-staff-photo-preview").empty();
                    $("#ai_staff_photo_id").val("");
                    $("#ai_staff_photo_alt").val("");
                    $(this).hide();
                });

                // Repeater logic (variants, products, blocked slots)
                $(document).on("click", ".ai-add-row, .ai-add-blocked", function(e) {
                    e.preventDefault();
                    var $tbody    = $(this).closest(".repeatable-wrapper").find("tbody.repeater-items");
                    var $template = $tbody.find("tr.template-row").first().clone();
                    $template.removeClass("template-row").addClass("new-manual");
                    $template.find("input, textarea, button").prop("disabled", false);
                    $template.find(".commit-buttons").show();
                    $template.find(".committed-buttons").hide();
                    $template.find(".variant-thumb-preview img, .upsell-image").remove();
                    $template.find(".gallery-ids, .image-id-field").val("");
                    $tbody.append($template);

                    // Re-init datetime pickers on new rows
                    $template.find(".ai-datetime-picker").removeClass("hasDatepicker").datetimepicker({
                        format: "Y-m-d H:i",
                        step: 15
                    });
                });

                $(document).on("click", ".ai-commit-row, .ai-commit-blocked", function(e) {
                    e.preventDefault();
                    var $row = $(this).closest("tr");
                    var nameInput = $row.find("input[name$=\"[name]\"]");
                    if (nameInput.length && nameInput.val().trim() === "") {
                        alert("Name is required.");
                        return;
                    }
                    // Blocked slots validation
                    if ($row.find(".ai-datetime-picker").length) {
                        var start = $row.find("input[name$=\"[start]\"]").val();
                        var end = $row.find("input[name$=\"[end]\"]").val();
                        if (!start || !end) {
                            alert("Both start and end date/time are required.");
                            return;
                        }
                        if (new Date(end) <= new Date(start)) {
                            alert("End date/time must be after start date/time.");
                            return;
                        }
                    }
                    $row.removeClass("new-manual").addClass("committed");
                    $row.find("input:not([type=hidden]), textarea, .ai-upload-variant-images, .ai-upload-single-image").prop("disabled", true);
                    $row.find(".commit-buttons").hide();
                    $row.find(".committed-buttons").show();
                });

                $(document).on("click", ".ai-cancel-row, .ai-cancel-blocked", function(e) {
                    e.preventDefault();
                    $(this).closest("tr").remove();
                });

                $(document).on("click", ".ai-edit-row, .ai-edit-blocked", function(e) {
                    e.preventDefault();
                    var $row = $(this).closest("tr");
                    $row.removeClass("committed");
                    $row.find("input:not([type=hidden]), textarea, .ai-upload-variant-images, .ai-upload-single-image").prop("disabled", false);
                    $row.find(".commit-buttons").show();
                    $row.find(".committed-buttons").hide();
                });

                $(document).on("click", ".ai-clone-row", function(e) {
                    e.preventDefault();
                    var $row   = $(this).closest("tr");
                    var $clone = $row.clone();
                    $clone.removeClass("committed").addClass("new-manual");
                    $clone.find("input:not([type=hidden]), textarea, button").prop("disabled", false);
                    $clone.find(".variant-thumb-preview img, .upsell-image").remove();
                    $clone.find(".gallery-ids, .image-id-field").val("");
                    $clone.find(".commit-buttons").show();
                    $clone.find(".committed-buttons").hide();
                    $row.after($clone);
                });

                $(document).on("click", ".ai-remove-row, .ai-remove-blocked", function(e) {
                    e.preventDefault();
                    if (!confirm("Are you sure you want to delete this item? This cannot be undone.")) return;
                    var $tbody = $(this).closest("tbody");
                    if ($tbody.find("tr").not(".template-row").length > 1) {
                        $(this).closest("tr").remove();
                    } else {
                        alert("Cannot delete the last item.");
                    }
                });

                // Stylist "All" master checkbox
                $(document).on("change", ".ai-assign-all", function(){
                    var checked = this.checked;
                    $(".ai-individual-staff").css({
                        "opacity": checked ? "0.5" : "1",
                        "pointer-events": checked ? "none" : "auto"
                    });
                    $(".ai-individual-staff input[type=\"checkbox\"]").prop("checked", checked).prop("disabled", checked);
                });
            });
        ' );
    }
}

// Enqueue on all relevant screens
add_action( 'admin_enqueue_scripts', array( 'AI_Hairstyle_Admin_Assets', 'enqueue' ) );