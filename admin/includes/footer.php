        </main>

        <footer class="admin-footer">

            <p>
                © <?= date('Y') ?> Veloura.
                All rights reserved.
            </p>

            <span>
                Made to Make You Shine
            </span>

        </footer>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const toggle =
        document.getElementById('adminMobileToggle');

    const sidebar =
        document.getElementById('adminSidebar');

    const overlay =
        document.getElementById('adminSidebarOverlay');

    if (toggle && sidebar && overlay) {

        toggle.addEventListener('click', function () {

            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');

        });

        overlay.addEventListener('click', function () {

            sidebar.classList.remove('active');
            overlay.classList.remove('active');

        });

    }

});

</script>

</body>

</html>