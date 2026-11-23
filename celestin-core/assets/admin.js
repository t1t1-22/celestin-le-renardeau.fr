jQuery(function($){
  $('.cel-media-choose').on('click', function(e) {
    e.preventDefault();
    var row = $(this).closest('.cel-media-setting');
    var picker = wp.media({ title: 'Choisir une illustration de Célestin', button: { text: 'Utiliser cette image' }, multiple: false });
    picker.on('select', function() {
      var item = picker.state().get('selection').first().toJSON();
      row.find('.cel-media-id').val(item.id);
      row.find('.cel-media-preview').attr('src', item.url).show();
    });
    picker.open();
  });
  $('.cel-media-remove').on('click', function(e) {
    e.preventDefault();
    var row = $(this).closest('.cel-media-setting');
    row.find('.cel-media-id').val('');
    row.find('.cel-media-preview').hide().attr('src', '');
  });

  var tabs = $('.cel-design-tab');
  var panels = $('.cel-design-panel');
  function openTab(key, focus) {
    var selected = tabs.filter('[data-cel-tab="' + key + '"]');
    var panel = $('#cel-panel-' + key);
    if (!selected.length || !panel.length) return;
    panels.prop('hidden', true);
    panel.prop('hidden', false);
    tabs.removeClass('is-active').attr({'aria-selected':'false', 'tabindex':'-1'});
    selected.addClass('is-active').attr({'aria-selected':'true', 'tabindex':'0'});
    $('#cel-active-tab').val(key);
    if (focus) selected.trigger('focus');
  }
  tabs.on('click', function() { openTab($(this).data('cel-tab'), false); });
  tabs.on('keydown', function(e) {
    if (!['ArrowLeft','ArrowRight','Home','End'].includes(e.key)) return;
    e.preventDefault();
    var index = tabs.index(this);
    var next = e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length-1 :
      (index + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    openTab(tabs.eq(next).data('cel-tab'), true);
  });
});
