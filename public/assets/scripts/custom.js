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

// State wise District
$(document).ready(function () {
    const stateSelect    = $('#state_id');
    const districtSelect = $('#district_id');
    const selectedState    = stateSelect.val();
    const selectedDistrict = "{{ old('district_id', $shop->district_id ?? '') }}";

    districtSelect.prop('disabled', true); 
    stateSelect.select2({
        placeholder: 'Select State',
        // allowClear: true,
        width: '100%'
    });
    districtSelect.select2({
        placeholder: 'Select District',
        // allowClear: true,
        width: '100%'
    });

    function loadDistricts(stateId, selectedDistrict = null) {
        districtSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>').trigger('change');
        if (!stateId) {
            districtSelect.html('<option value="">-- Select District --</option>').prop('disabled', true).trigger('change');
            return;
        }
        $.ajax({
            url: `/api/get-districts/${stateId}`,
            type: 'GET',
            success: function (data) {
                districtSelect.empty().append('<option value="">-- Select District --</option>');
                $.each(data, function (i, district) {
                    let selected = selectedDistrict == district.id ? 'selected' : '';
                    districtSelect.append(`<option value="${district.id}" ${selected}>${district.name}</option>`);
                });
                districtSelect.prop('disabled', false).trigger('change');
            },
            error: function () {
                districtSelect.html('<option value="">Error loading districts</option>').prop('disabled', true).trigger('change');
            }
        });
    } 
    if (selectedState) {
        loadDistricts(selectedState, selectedDistrict);
    } 
    stateSelect.on('change', function () {
        loadDistricts(this.value);
    });
});

// Select All States
document.getElementById('selectAllStates').addEventListener('change', function () {
    document.querySelectorAll('input[name="selected_state_ids[]"]').forEach(cb => {
        cb.checked = this.checked;
    });
});
