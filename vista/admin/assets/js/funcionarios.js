document.addEventListener('DOMContentLoaded', function() {
    // Modal Editar Funcionario
    const editarFuncionarioModal = document.getElementById('editarFuncionarioModal');
    if (editarFuncionarioModal) {
        editarFuncionarioModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            fetch(`index.php?page=admin_get_funcionario&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.funcionario.id;
                        document.getElementById('edit_nombre').value = data.funcionario.nombre;
                        document.getElementById('edit_apellidos').value = data.funcionario.apellidos;
                        document.getElementById('edit_email').value = data.funcionario.email;
                        document.getElementById('edit_telefono').value = data.funcionario.telefono;
                        document.getElementById('edit_direccion').value = data.funcionario.direccion;
                        document.getElementById('edit_activo').value = data.funcionario.activo;
                    } else {
                        alert('No se pudieron cargar los datos del funcionario');
                    }
                })
                .catch(() => alert('Error al cargar los datos del funcionario'));
        });
    }

    // Modal Cambiar Estado
    const cambiarEstadoModal = document.getElementById('cambiarEstadoModal');
    if (cambiarEstadoModal) {
        cambiarEstadoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const estadoActual = button.getAttribute('data-estado');
            const nuevoEstado = estadoActual == '1' ? '0' : '1';
            document.getElementById('cambiar_nombre_funcionario').textContent = nombre;
            document.getElementById('cambiar_id').value = id;
            document.getElementById('cambiar_estado').value = nuevoEstado;
            const btn = document.getElementById('btnConfirmarCambio');
            btn.textContent = estadoActual == '1' ? 'Desactivar' : 'Activar';
            btn.className = estadoActual == '1' ? 'btn btn-danger' : 'btn btn-success';
        });
    }

    // Exportar PDF
    const exportarPDF = document.getElementById('exportarPDF');
    if (exportarPDF && window.ExportUtils) {
        exportarPDF.addEventListener('click', function() {
            ExportUtils.exportToPDF('funcionarios-table', 'funcionarios');
        });
    }

    // Exportar Excel
    const exportarExcel = document.getElementById('exportarExcel');
    if (exportarExcel && window.ExportUtils) {
        exportarExcel.addEventListener('click', function() {
            ExportUtils.exportToExcel('tabla-funcionarios', 'funcionarios');
        });
    }
});