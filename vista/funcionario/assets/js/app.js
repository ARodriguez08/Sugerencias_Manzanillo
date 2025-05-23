document.addEventListener('DOMContentLoaded', function() {
    // Modal Ver Sugerencia
    const verSugerenciaModal = document.getElementById('verSugerenciaModal');
    if (verSugerenciaModal) {
        verSugerenciaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('ver_id').textContent = button.getAttribute('data-id');
            document.getElementById('ver_titulo').textContent = button.getAttribute('data-titulo');
            document.getElementById('ver_categoria').textContent = button.getAttribute('data-categoria');
            document.getElementById('ver_estado').textContent = button.getAttribute('data-estado');
            document.getElementById('ver_usuario').textContent = button.getAttribute('data-usuario');
            document.getElementById('ver_fecha').textContent = button.getAttribute('data-fecha');
            document.getElementById('ver_descripcion').textContent = button.getAttribute('data-descripcion');
        });
    }

    // Modal Responder Sugerencia
    const responderSugerenciaModal = document.getElementById('responderSugerenciaModal');
    if (responderSugerenciaModal) {
        responderSugerenciaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('sugerencia_id').value = button.getAttribute('data-id');
            document.getElementById('titulo_sugerencia').value = button.getAttribute('data-titulo');
        });
    }

    // Validación del formulario
    const responderSugerenciaForm = document.getElementById('responderSugerenciaForm');
    if (responderSugerenciaForm) {
        responderSugerenciaForm.addEventListener('submit', function(event) {
            const estadoId = document.getElementById('estado_id').value;
            const respuesta = document.getElementById('respuesta').value;

            if (!estadoId) {
                event.preventDefault();
                alert('Por favor, seleccione una decisión.');
                return false;
            }

            if (respuesta.length < 10) {
                event.preventDefault();
                alert('La respuesta debe tener al menos 10 caracteres.');
                return false;
            }

            return true;
        });
    }
});