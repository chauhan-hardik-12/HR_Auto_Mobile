    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            <b>HR Auto Mobile</b> Management Suite v2.0
        </div>
        <strong>Copyright &copy; <?= date('Y') ?> <a href="../index.php" target="_blank">HR Auto Mobile</a>.</strong> All rights reserved.
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Plugins -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<!-- SweetAlert2 -->
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr -->
<script src="plugins/toastr/toastr.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>

<script>
$(function () {
    // Initialize default standard DataTables
    $('.datatable-init').DataTable({
        "responsive": true,
        "autoWidth": false,
        "pageLength": 25,
        "order": []
    });

    // Initialize DataTables with Export Buttons
    $('.datatable-buttons').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 25,
        "order": [],
        "buttons": ["copy", "csv", "excel", "pdf", "print"]
    }).buttons().container().appendTo('#' + $('.datatable-buttons').attr('id') + '_wrapper .col-md-6:eq(0)');

    // Display Flash Messages if set
    <?php if (!empty($flash)): ?>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000"
        };
        <?php if ($flash['type'] === 'success'): ?>
            toastr.success(<?= json_encode($flash['message']) ?>);
        <?php elseif ($flash['type'] === 'error'): ?>
            toastr.error(<?= json_encode($flash['message']) ?>);
        <?php elseif ($flash['type'] === 'warning'): ?>
            toastr.warning(<?= json_encode($flash['message']) ?>);
        <?php else: ?>
            toastr.info(<?= json_encode($flash['message']) ?>);
        <?php endif; ?>
    <?php endif; ?>
});

// Confirmation dialog helper
function confirmAction(e, message) {
    if (!confirm(message || 'Are you sure you want to proceed with this action?')) {
        e.preventDefault();
        return false;
    }
    return true;
}

// Force reload from server if restored from browser back-forward cache after logout
window.addEventListener('pageshow', function (event) {
    if (event.persisted || (window.performance && (window.performance.navigation && window.performance.navigation.type === 2))) {
        window.location.reload();
    }
});
</script>
</body>
</html>
