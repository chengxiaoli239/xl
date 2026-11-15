(function () {
    function initThemeToggle() {
        var button = document.getElementById('xl-theme-toggle');
        if (!button) return;

        var icon = button.querySelector('i');
        var label = button.querySelector('span');
        var root = document.documentElement;

        function render() {
            var dark = root.getAttribute('data-xl-theme') === 'dark';
            var action = dark ? '切换到浅色主题' : '切换到暗色主题';
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            button.setAttribute('aria-label', action);
            button.title = action;
            icon.className = dark ? 'fa fa-sun-o' : 'fa fa-moon-o';
            label.textContent = dark ? '浅色' : '暗色';
        }

        button.addEventListener('click', function () {
            var dark = root.getAttribute('data-xl-theme') === 'dark';
            if (dark) {
                root.removeAttribute('data-xl-theme');
            } else {
                root.setAttribute('data-xl-theme', 'dark');
            }
            try {
                localStorage.setItem('xl-admin-theme', dark ? 'light' : 'dark');
            } catch (error) {}
            render();
        });

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle);
    } else {
        initThemeToggle();
    }
})();
