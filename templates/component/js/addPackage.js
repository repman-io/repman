(function() {
    $('.addPackageType').on('change', function(e) {
        const type = e.target.value;
        const baseUrl = $('.addPackageFormUrl').val();

        window.location.href = `${baseUrl}/${type}`;
    });
})();
