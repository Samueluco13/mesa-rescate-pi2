(function ($) {
  'use strict';
  $(function () {
    let picker;
    $('#ra_choose_logo').on('click', function (event) {
      event.preventDefault();
      if (picker) { picker.open(); return; }
      picker = wp.media({title: 'Elige el logotipo', button: {text: 'Usar esta imagen'}, library: {type: 'image'}, multiple: false});
      picker.on('select', function () {
        const attachment = picker.state().get('selection').first().toJSON();
        $('#ra_logo_id').val(attachment.id);
        $('#ra_logo_preview').empty().append($('<img>', {src: attachment.sizes?.thumbnail?.url || attachment.url, alt: 'Logotipo seleccionado'}));
      });
      picker.open();
    });
    $('#ra_remove_logo').on('click', function () { $('#ra_logo_id').val('0'); $('#ra_logo_preview').empty(); });
  });
})(jQuery);
