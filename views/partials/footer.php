            </main><!-- End main-content -->
        </div><!-- End page-container -->
    </div><!-- End wrapper -->

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            const body    = document.body;
            const sidebar = document.querySelector('.sidebar');
            const main    = document.querySelector('.main-content');

            if (sidebar.style.marginLeft === '-250px' || getComputedStyle(sidebar).marginLeft === '-250px') {
                sidebar.style.marginLeft = '0';
                main.style.marginLeft    = '250px';
                main.style.width         = 'calc(100% - 250px)';
            } else {
                sidebar.style.marginLeft = '-250px';
                main.style.marginLeft    = '0';
                main.style.width         = '100%';
            }
        }

        function toggleMenu(el) {
            const parent = el.parentElement;
            const tree   = parent.querySelector('.nav-treeview');
            const icon   = el.querySelector('.right-icon');

            if (parent.classList.contains('menu-open')) {
                parent.classList.remove('menu-open');
                icon.classList.remove('fa-angle-down');
                icon.classList.add('fa-angle-left');
                tree.style.display = 'none';
            } else {
                parent.classList.add('menu-open');
                icon.classList.remove('fa-angle-left');
                icon.classList.add('fa-angle-down');
                tree.style.display = 'block';
            }
        }

        function filterSidebar(query) {
            const items = document.querySelectorAll('#sidebarMenu a');
            const q = query.toLowerCase();
            items.forEach(a => {
                const text = a.textContent.toLowerCase();
                const li   = a.closest('li');
                if (li) li.style.display = text.includes(q) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
