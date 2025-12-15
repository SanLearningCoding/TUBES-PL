            </div>
        </div>
    </div>
</div>
</div>
<!-- View\template\assets\auth_footer.php -->

<!-- Footer -->
<footer class="footer-simple mt-auto">
    <p>&copy; 2024 <strong>Sistem Manajemen Stok Darah PMI</strong>
</footer>

<style>
.footer-simple {
    background: transparent;
    color: #333333;
    padding: 1.5rem 1rem;
    width: 100%;
    text-align: center;
    font-size: 0.85rem;
    border-top: 1px solid #e0e0e0;
}

.footer-simple p {
    margin: 0;
}

.footer-simple strong {
    color: #000000;
    font-weight: 700;
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="View/template/assets/js/ui.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="View/template/assets/js/ui.js"></script>
<script>
    // Mirror the small sidebar toggle behavior for auth pages (safe no-op)
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('sidebarToggle');
        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                document.body.classList.toggle('show-sidebar');
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (document.body.classList.contains('show-sidebar')) document.body.classList.remove('show-sidebar');
        var main = document.querySelector('.main-content'); if (main) main.style.visibility = 'visible';
    });
</script>
</body>
</html>