jQuery(function() {
    jQuery('span.stars').stars();
});
jQuery.fn.stars = function() {
    return jQuery(this).each(function() {
        // Get the value
        var val = parseFloat(jQuery(this).html());
        // Make sure that the value is in 0 - 5 range, multiply to get width
        var size = Math.max(0, (Math.min(5, val))) * 16;
        // Create stars holder
        var jQueryspan = jQuery('<span />').width(size);
        // Replace the numerical value with stars
        jQuery(this).html(jQueryspan);
    });
}