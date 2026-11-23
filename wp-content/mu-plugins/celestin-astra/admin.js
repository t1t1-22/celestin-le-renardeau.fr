jQuery(function ($) {
    const labels = {
        clst_astra_hero_image: 'Choisir l’illustration principale de Célestin',
        clst_astra_village_image: 'Choisir l’illustration du village de Fleurville'
    };
    $('.clst-astra-image-pick').on('click', function (event) {
        event.preventDefault();
        const field = String($(this).data('field') || '');
        if (!Object.prototype.hasOwnProperty.call(labels, field)) return;
        const frame = wp.media({
            title: labels[field],
            button: { text: 'Utiliser cette illustration' },
            library: { type: 'image' },
            multiple: false
        });
        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            if (!attachment || !attachment.id || !attachment.url) return;
            $('#' + field).val(String(attachment.id));
            $('#' + field + '-preview').attr('src', attachment.url);
        });
        frame.open();
    });
    $('.clst-astra-image-clear').on('click', function (event) {
        event.preventDefault();
        const field = String($(this).data('field') || '');
        if (!Object.prototype.hasOwnProperty.call(labels, field)) return;
        $('#' + field).val('0');
        $('#' + field + '-preview').hide();
    });
});
