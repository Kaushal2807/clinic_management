    </div>
    <!-- End Main Content Wrapper -->

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($clinic['clinic_name']) ?>. All rights reserved.
                </p>
                <p class="text-sm text-gray-500">
                    Powered by <span class="font-semibold text-indigo-600"><?= APP_NAME ?></span> v<?= APP_VERSION ?>
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
