    <hr>
    <footer>
        <p><small>&copy; <?= date('Y') ?> Lab Activity 7 - Relational Databases (RDBMS) &amp; PDO Core</small></p>
    </footer>

    <script>
    (function() {
        // Detect browser default timezone and persist in cookie for server-side awareness
        try {
            const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (browserTz) {
                const existingCookie = document.cookie.split('; ').find(row => row.startsWith('client_tz='));
                const currentVal = existingCookie ? decodeURIComponent(existingCookie.split('=')[1]) : '';
                if (currentVal !== browserTz) {
                    document.cookie = "client_tz=" + encodeURIComponent(browserTz) + ";path=/;max-age=31536000;SameSite=Lax";
                }
            }
        } catch(e) {}

        // Format all <time datetime="..."> elements according to the browser's local timezone and clock
        function formatBrowserTime() {
            document.querySelectorAll('time[datetime]').forEach(function(el) {
                const isoStr = el.getAttribute('datetime');
                if (!isoStr) return;
                const date = new Date(isoStr);
                if (isNaN(date.getTime())) return;

                const now = new Date();
                const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000);

                // Preserve (edited) label if element is an edit marker
                if (el.hasAttribute('data-edited-marker')) {
                    const localTime = date.toLocaleString();
                    el.title = "Edited on " + localTime;
                    return;
                }

                let text = '';
                if (diffSec < 60) {
                    text = 'Just now';
                } else if (diffSec < 3600) {
                    const mins = Math.floor(diffSec / 60);
                    text = mins === 1 ? '1 minute ago' : mins + ' minutes ago';
                } else if (diffSec < 86400) {
                    const hours = Math.floor(diffSec / 3600);
                    text = hours === 1 ? '1 hour ago' : hours + ' hours ago';
                } else if (diffSec < 172800) {
                    text = 'Yesterday at ' + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                } else if (diffSec < 604800) {
                    const days = Math.floor(diffSec / 86400);
                    text = days + ' days ago';
                } else {
                    text = date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                }

                el.textContent = text;
                el.title = date.toLocaleString();
            });
        }

        formatBrowserTime();
    })();
    </script>
</body>
</html>
