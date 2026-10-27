(function ($) {
    'use strict';
    $(document).off('change.ubicacion', '.ubicacion-provincia').on('change.ubicacion', '.ubicacion-provincia', function () {
        var wrapper = $(this).closest('.ubicacion-selector');
        var provincia = String(this.value || '');
        // A duplicate change/focus cycle must not clear an existing locality.
        if (wrapper.attr('data-provincia-actual') === provincia) { return; }
        wrapper.attr('data-provincia-actual', provincia);
        var localidad = wrapper.find('.ubicacion-localidad');
        var estado = wrapper.find('.ubicacion-estado');
        var previous = wrapper.data('request');
        // Invalidate callbacks before aborting the preceding request.
        var revision = (wrapper.data('revision') || 0) + 1;
        wrapper.data('revision', revision);
        if (previous) { previous.abort(); }
        localidad.empty().append($('<option>').val('').text('')).val('').trigger('change.select2');
        estado.removeClass('text-danger').text('');
        wrapper.data('blocked', false);
        localidad.prop('disabled', false);
        if (!provincia) { return; }
        wrapper.data('blocked', true);
        localidad.prop('disabled', true);
        estado.text('Cargando localidades…');
        var request = $.getJSON(wrapper.attr('data-localidades-url'), {provincia: provincia});
        wrapper.data('request', request);
        request.done(function (data) {
            if (wrapper.data('revision') !== revision) { return; }
            if (!data || !Array.isArray(data.items)) { failed(); return; }
            data.items.forEach(function (item) {
                localidad.append($('<option>').val(item.id).text(item.descr));
            });
            // Loading options never chooses the first locality on the user's behalf.
            localidad.val('').trigger('change.select2');
            wrapper.data('blocked', false);
            estado.text('');
        }).fail(function (_, status) {
            if (status !== 'abort') { failed(); }
        }).always(function () {
            if (wrapper.data('revision') === revision) { localidad.prop('disabled', false); }
        });
        function failed() {
            if (wrapper.data('revision') !== revision) { return; }
            wrapper.removeAttr('data-provincia-actual');
            estado.addClass('text-danger').text('No se pudieron cargar las localidades. Volvé a seleccionar la provincia para reintentar.');
        }
    });
    $(document).off('submit.ubicacion', 'form').on('submit.ubicacion', 'form', function (event) {
        var blocked = false;
        $(this).find('.ubicacion-selector').each(function () { if ($(this).data('blocked')) { blocked = true; } });
        if (blocked) { event.preventDefault(); event.stopImmediatePropagation(); }
    });
})(jQuery);
