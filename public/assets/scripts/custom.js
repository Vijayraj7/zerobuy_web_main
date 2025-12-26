// Tabs navigation
$(document).ready(function() {
    $('.next-btn').click(function() { // Next Button
        let nextStep = $(this).data('next');
        $('.step-content').hide();
        $('.step-' + nextStep).show();
        $('#productTabs .nav-link').removeClass('active');
        $('#productTabs .nav-link[data-step="' + nextStep + '"]').addClass('active');
    });
    $('.prev-btn').click(function() { // Previous Button
        let prevStep = $(this).data('prev');
        $('.step-content').hide();
        $('.step-' + prevStep).show();
        $('#productTabs .nav-link').removeClass('active');
        $('#productTabs .nav-link[data-step="' + prevStep + '"]').addClass('active');
    });
    $('#productTabs .nav-link').click(function(e) { //Tab Click Navigation
        e.preventDefault();
        let step = $(this).data('step');
        $('.step-content').hide();
        $('.step-' + step).show();
        $('#productTabs .nav-link').removeClass('active');
        $(this).addClass('active');
    });
});

// Flash Message
function showFlash(message) {
    // remove existing flash if any
    const existingFlash = document.querySelector('.bulk-flash-message');
    if (existingFlash) {
        existingFlash.remove();
    }

    const flash = document.createElement('div');
    flash.className = 'alert alert-danger bulk-flash-message';
    flash.innerHTML = `
        ${message}
        <button class="btn-close btn-sm float-end" onclick="this.parentElement.remove()"></button>
    `;
    flash.style.cssText = 'position:fixed; top:10px; right:10px; z-index:9999;';

    document.body.appendChild(flash);

    setTimeout(() => {
        if (flash) flash.remove();
    }, 5000);
}