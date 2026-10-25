// nav.js — injects a hamburger into .navbar and toggles menu for mobile
(function(){
    document.addEventListener('DOMContentLoaded', function(){
        let nav = document.querySelector('.navbar');
        if (!nav) {
            // fallback to first <nav> element when .navbar is not present
            nav = document.querySelector('nav');
        }
        if (!nav) return;

        // don't inject twice
        if (nav.querySelector('.hamburger')) return;

        const ham = document.createElement('button');
        ham.className = 'hamburger';
        ham.setAttribute('aria-label','Toggle menu');
        ham.innerHTML = '<span class="bar" aria-hidden="true"></span>';

        ham.addEventListener('click', () => {
            nav.classList.toggle('menu-open');
            const expanded = nav.classList.contains('menu-open');
            ham.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });

        // Insert hamburger at start of navbar
        nav.insertBefore(ham, nav.firstChild);

        // Close menu when clicking outside (mobile)
        document.addEventListener('click', (e) => {
            if (!nav.classList.contains('menu-open')) return;
            if (nav.contains(e.target)) return; // click inside
            nav.classList.remove('menu-open');
        });
    });
})();
