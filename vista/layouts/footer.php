</main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="material-icons me-2">location_city</i>Sugerencias-Manzanillo</h5>
                    <p>Plataforma de comunicación y gestión de solicitudes ciudadanas para mejorar la calidad de vida en Manzanillo.</p>
                    <div class="d-flex social-links">
                        <a href="https://facebook.com" class="text-white me-3" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://twitter.com" class="text-white me-3" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://instagram.com" class="text-white me-3" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://youtube.com" class="text-white" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>

                </div>
                <div class="col-md-4">
                    <h5>Enlaces Útiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white"><i class="material-icons me-2">chevron_right</i>Inicio</a></li>
                        <li><a href="index.php?page=faq" class="text-white"><i class="material-icons me-2">chevron_right</i>Preguntas Frecuentes</a></li>
                        <li><a href="index.php?page=contacto" class="text-white"><i class="material-icons me-2">chevron_right</i>Contacto</a></li>
                        <li><a href="index.php?page=terminos" class="text-white"><i class="material-icons me-2">chevron_right</i>Términos y Condiciones</a></li>
                        <li><a href="index.php?page=privacidad" class="text-white"><i class="material-icons me-2">chevron_right</i>Política de Privacidad</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contacto</h5>
                    <address>
                        <p><i class="material-icons me-2">place</i> Av. Juárez #123, Col. Centro, Manzanillo, Colima</p>
                        <p><i class="material-icons me-2">phone</i> (314) 123-4567</p>
                        <p><i class="material-icons me-2">email</i> contacto@sugerencias-manzanillo.mx</p>
                    </address>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Sugerencias-Manzanillo. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Desarrollado con <i class="material-icons text-danger">favorite</i> para los ciudadanos de Manzanillo</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/scripts.js"></script>
    
    <script>
    // Inicializar componentes una vez que el DOM esté cargado
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Inicializar datepickers
        if (document.querySelector('.datepicker')) {
            flatpickr('.datepicker', {
                locale: 'es',
                dateFormat: "Y-m-d",
                allowInput: true
            });
        }
        
        // Inicializar DataTables
        if (document.querySelector('.datatable')) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                },
                responsive: true
            });
        }
        
        // Inicializar Select2
        if (document.querySelector('.select2')) {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }
    });
    </script>
</body>
</html>
