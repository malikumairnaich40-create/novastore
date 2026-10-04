        </main>
    </div>

    <!-- Frontend Core Scripts for AJAX and Toasts -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script>
        // Mobile admin menu toggle
        const toggleBtn = document.getElementById('admin-mobile-toggle');
        const sidebar = document.querySelector('aside');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-y-0');
                sidebar.classList.toggle('left-0');
                sidebar.classList.toggle('z-50');
            });
        }
    </script>
</body>
</html>
