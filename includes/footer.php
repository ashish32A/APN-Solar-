            </main> <!-- End main-content -->
        </div> <!-- End page-container -->
    </div> <!-- End wrapper -->

    <!-- Scripts -->
    <script>
        // Simple sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const main = document.querySelector('.main-content');
            if(sidebar.style.display === 'none') {
                sidebar.style.display = 'block';
                main.style.marginLeft = '250px';
                main.style.width = 'calc(100% - 249px)';
            } else {
                sidebar.style.display = 'none';
                main.style.marginLeft = '0';
                main.style.width = '100%';
            }
        }

        function toggleMenu(el) {
            const parent = el.parentElement;
            const tree = parent.querySelector('.nav-treeview');
            const icon = el.querySelector('.right-icon');
            
            if(parent.classList.contains('menu-open')) {
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
    </script>
</body>
</html>
