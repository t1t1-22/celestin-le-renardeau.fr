jQuery(function ($) {
    const $id = $('#clst-file-id');
    const $label = $('#clst-media-label');
    let frame;
    $('.clst-media-pick').on('click', function (event) {
        event.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({
            title: 'Choisir un PDF ou une image',
            button: { text: 'Utiliser ce fichier' },
            multiple: false
        });
        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            if (!['application/pdf', 'image/png', 'image/jpeg', 'image/webp'].includes(attachment.mime)) {
                window.alert('Choisis un PDF ou une image PNG, JPEG ou WebP.');
                return;
            }
            $id.val(attachment.id);
            $label.text(attachment.filename || attachment.title || 'Fichier sélectionné');
        });
        frame.open();
    });
    $('.clst-media-remove').on('click', function (event) {
        event.preventDefault();
        $id.val('');
        $label.text('Aucun fichier sélectionné');
    });
});
