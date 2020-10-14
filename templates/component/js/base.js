(function() {
    $('[data-target="confirmation"]').on('click', function() {
        const confirmationModalId = '#confirmation-modal';
        const $source = $(this);
        const $modal = $(`${confirmationModalId}`);
        const $modalForm = $(`${confirmationModalId} form`);
        const $modalTitle = $(`${confirmationModalId} .modal-title`);

        $modalForm.prop('action', $source.data('action'));
        $modalForm.children('[name=_method]').val($source.data('method'));
        $modalTitle.html($source.data('title') || 'Confirm action');

        $modal.modal('show');
    });

    $('.copy-to-clipboard').click(function() {
        $($(this).data('clipboard-target')).select();
        document.execCommand('copy');
    });

    $('.show-token').click(function() {
        $(this).parent().find('[data-type="token"]').removeClass('d-none');
        $(this).remove();
    });

    $('[data-toggle="popover"]').popover({
        trigger: 'hover'
    });

    $('[data-toggle="tooltip"]').tooltip();

    $('.number-format').each(function() {
        $(this).text(parseInt($(this).text()).toLocaleString());
    });
})();
