// Mobile Responsive Menu Handler
(function() {
    // Hamburger menü butonu oluştur
    function createMobileMenuButton() {
        if (window.innerWidth <= 768) {
            // Buton zaten varsa oluşturma
            if (document.querySelector('.mobile-menu-toggle')) return;
            
            const button = document.createElement('button');
            button.className = 'mobile-menu-toggle';
            button.innerHTML = '<i class="mdi mdi-menu" style="font-size: 20px;"></i>';
            button.style.display = 'none';
            
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            
            document.body.appendChild(button);
            document.body.appendChild(overlay);
            
            // Menü toggle
            button.addEventListener('click', function() {
                const sidebar = document.querySelector('.leftside-menu');
                const overlay = document.querySelector('.sidebar-overlay');
                
                if (sidebar && overlay) {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                }
            });
            
            // Overlay'e tıklanınca menüyü kapat
            overlay.addEventListener('click', function() {
                const sidebar = document.querySelector('.leftside-menu');
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
            
            // Menü linklerine tıklanınca menüyü kapat
            const menuLinks = document.querySelectorAll('.leftside-menu a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        setTimeout(() => {
                            const sidebar = document.querySelector('.leftside-menu');
                            const overlay = document.querySelector('.sidebar-overlay');
                            if (sidebar) sidebar.classList.remove('show');
                            if (overlay) overlay.classList.remove('show');
                        }, 300);
                    }
                });
            });
        }
    }
    
    // Sayfa yüklendiğinde
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createMobileMenuButton);
    } else {
        createMobileMenuButton();
    }
    
    // Ekran boyutu değiştiğinde
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            createMobileMenuButton();
            
            // Desktop'a geçildiğinde overlay'i kaldır
            if (window.innerWidth > 768) {
                const sidebar = document.querySelector('.leftside-menu');
                const overlay = document.querySelector('.sidebar-overlay');
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            }
        }, 250);
    });
})();
