<script>
    (() => {
        let currentVersion = @js(\App\Support\SiteVersion::current());
        let checking = false;

        const checkForAdminChanges = async () => {
            if (checking) return;
            checking = true;

            try {
                const response = await fetch(@js(route('site.version')), {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) return;

                const { version } = await response.json();

                if (version && version !== currentVersion) {
                    currentVersion = version;

                    if (document.visibilityState === 'visible') {
                        window.location.reload();
                    } else {
                        document.addEventListener('visibilitychange', () => {
                            if (document.visibilityState === 'visible') {
                                window.location.reload();
                            }
                        }, { once: true });
                    }
                }
            } catch (_) {
                // Temporary network errors should not interrupt the current page.
            } finally {
                checking = false;
            }
        };

        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                checkForAdminChanges();
            }
        }, 30000);
    })();
</script>
