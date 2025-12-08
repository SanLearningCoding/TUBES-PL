            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-simple mt-auto">
        <p>&copy; 2024 <strong>Sistem Manajemen Stok Darah PMI</strong></p>
    </footer>

    <style>
    /* Make footer part of normal document flow so it scrolls away with page.
       Remove page-level flex layout and internal scrolling so footer isn't
       pinned to the viewport. */

    html, body {
        height: auto;
    }

    body {
        /* Use default flow so footer sits after content */
        min-height: auto;
    }

    .app-wrapper {
        display: block;
        min-height: auto;
    }

    nav {
        /* keep default behavior */
    }

    .main-content {
        /* Allow page to scroll naturally rather than an inner scroll area */
        overflow: visible;
    }

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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .footer-simple {
            padding: 1rem 0.75rem;
            font-size: 0.75rem;
        }
    }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- UI behavior -->
    <script src="View/template/assets/js/ui.js"></script>
    <!-- Footer scripts -->
</body>
</html>