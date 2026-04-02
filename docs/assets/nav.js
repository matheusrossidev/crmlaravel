/* Syncro Documentation — Navigation & Layout */
(function() {
    var pages = [
        { id: 'index', title: 'Overview', icon: '&#9776;', href: 'index.html' },
        { section: 'Arquitetura' },
        { id: 'architecture', title: 'Arquitetura', icon: '&#9881;', href: 'architecture.html' },
        { id: 'database', title: 'Banco de Dados', icon: '&#9707;', href: 'database.html' },
        { id: 'deployment', title: 'Deploy', icon: '&#9729;', href: 'deployment.html' },
        { section: 'Backend' },
        { id: 'models', title: 'Models', icon: '&#9830;', href: 'models.html' },
        { id: 'controllers', title: 'Controllers', icon: '&#9654;', href: 'controllers.html' },
        { id: 'services', title: 'Services & Jobs', icon: '&#9881;', href: 'services.html' },
        { id: 'routes', title: 'Rotas', icon: '&#8644;', href: 'routes.html' },
        { section: 'Frontend' },
        { id: 'frontend', title: 'Frontend', icon: '&#9635;', href: 'frontend.html' },
        { section: 'Integrações' },
        { id: 'integrations', title: 'Integrações', icon: '&#9889;', href: 'integrations.html' },
        { section: 'Referência' },
        { id: 'llm-reference', title: 'LLM Reference', icon: '&#9733;', href: 'llm-reference.html' }
    ];

    // Detect current page
    var path = window.location.pathname;
    var currentFile = path.substring(path.lastIndexOf('/') + 1) || 'index.html';
    var currentId = currentFile.replace('.html', '');

    // Build sidebar HTML
    function buildSidebar() {
        var html = '';
        html += '<div class="sidebar-header">';
        html += '  <img src="assets/logo.svg" alt="Syncro">';
        html += '  <div><h1>Syncro</h1><span>Documentação</span></div>';
        html += '</div>';
        html += '<div class="search-box">';
        html += '  <span class="search-icon">&#128269;</span>';
        html += '  <input type="text" id="navSearch" placeholder="Buscar...">';
        html += '</div>';
        html += '<nav class="sidebar-nav" id="sidebarNav">';

        for (var i = 0; i < pages.length; i++) {
            var p = pages[i];
            if (p.section) {
                html += '<div class="sidebar-section">' + p.section + '</div>';
            } else {
                var active = p.id === currentId ? ' class="active"' : '';
                html += '<a href="' + p.href + '"' + active + '>';
                html += '<span class="nav-icon">' + p.icon + '</span>';
                html += p.title;
                html += '</a>';
            }
        }

        html += '</nav>';
        html += '<div class="sidebar-footer">Syncro &copy; 2026 &mdash; Docs v1.0</div>';
        return html;
    }

    // Inject sidebar
    var sidebar = document.createElement('div');
    sidebar.className = 'sidebar';
    sidebar.id = 'sidebar';
    sidebar.innerHTML = buildSidebar();
    document.body.insertBefore(sidebar, document.body.firstChild);

    // Mobile toggle
    var toggle = document.createElement('button');
    toggle.className = 'sidebar-toggle';
    toggle.innerHTML = '&#9776;';
    toggle.onclick = function() { sidebar.classList.toggle('open'); };
    document.body.insertBefore(toggle, document.body.firstChild);

    // Close sidebar on click outside (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && e.target !== toggle) {
            sidebar.classList.remove('open');
        }
    });

    // Search filter
    var searchInput = document.getElementById('navSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            var links = document.querySelectorAll('#sidebarNav a');
            var sections = document.querySelectorAll('#sidebarNav .sidebar-section');
            for (var i = 0; i < links.length; i++) {
                var match = links[i].textContent.toLowerCase().indexOf(q) !== -1;
                links[i].style.display = match || !q ? '' : 'none';
            }
            // Show sections only if they have visible links after them
            for (var i = 0; i < sections.length; i++) {
                var next = sections[i].nextElementSibling;
                var hasVisible = false;
                while (next && !next.classList.contains('sidebar-section')) {
                    if (next.style.display !== 'none') hasVisible = true;
                    next = next.nextElementSibling;
                }
                sections[i].style.display = hasVisible || !q ? '' : 'none';
            }
        });
    }

    // Table filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        var filters = document.querySelectorAll('.table-filter input');
        for (var i = 0; i < filters.length; i++) {
            (function(input) {
                var tableId = input.getAttribute('data-table');
                var table = document.getElementById(tableId);
                if (!table) return;
                input.addEventListener('input', function() {
                    var q = this.value.toLowerCase();
                    var rows = table.querySelectorAll('tbody tr');
                    for (var j = 0; j < rows.length; j++) {
                        var text = rows[j].textContent.toLowerCase();
                        rows[j].style.display = text.indexOf(q) !== -1 ? '' : 'none';
                    }
                });
            })(filters[i]);
        }
    });

    // Set page title
    document.title = 'Syncro Docs — ' + (document.querySelector('.page-title') || document.querySelector('h1') || {}).textContent || 'Documentation';
})();
