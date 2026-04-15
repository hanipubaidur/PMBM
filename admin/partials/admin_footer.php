</div> </div> </div> <footer class="bg-white border-top mt-auto py-3 shadow-sm">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
            <div class="mb-2 mb-md-0">
                &copy; <?= date('Y') ?> <span class="fw-bold text-primary">PMBM MAN 1 MUSI RAWAS</span>. All rights reserved.
            </div>
            <div class="fw-medium">
                Versi 1.0 <i class="bi bi-rocket-takeoff-fill text-success ms-1"></i>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Toggle sidebar on mobile
    document.addEventListener('DOMContentLoaded', function() {
        const toggler = document.querySelector('.navbar-toggler');
        const sidebar = document.querySelector('.sidebar');
        
        if (toggler && sidebar) {
            toggler.addEventListener('click', () => {
                sidebar.classList.toggle('d-none');
            });
        }
    });
</script>
</body>
</html>