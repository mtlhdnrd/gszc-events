$(document).ready(function() {

    //Navigation Handling
    $('.nav-link').click(handleNavigation);
    function handleNavigation(e) {
        e.preventDefault();
        let target = $(this).data('target');
        $('.content-section').hide();
        $('#' + target).show();
        $('.nav-item').removeClass('active');
        $(this).parent().addClass('active');
    }
    
});