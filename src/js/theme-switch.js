var toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
var currentTheme = localStorage.getItem('theme');
var mainHeader = document.querySelector('.main-header');
var themeIcon = document.querySelector('#theme-icon');

if (toggleSwitch) {

    if (currentTheme === 'dark') {
        if (!document.body.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode');
        }
        if (mainHeader && mainHeader.classList.contains('navbar-light')) {
            mainHeader.classList.add('navbar-dark');
            mainHeader.classList.remove('navbar-light');
        }
        toggleSwitch.checked = true;
        
        if (themeIcon) {
            themeIcon.className = 'fas fa-moon';
        }
    }

    function switchTheme(e) {
        if (e.target.checked) {
            if (!document.body.classList.contains('dark-mode')) {
                document.body.classList.add('dark-mode');
            }
            if (mainHeader && mainHeader.classList.contains('navbar-light')) {
                mainHeader.classList.add('navbar-dark');
                mainHeader.classList.remove('navbar-light');
            }
            localStorage.setItem('theme', 'dark');
            
            if (themeIcon) {
                themeIcon.className = 'fas fa-moon';
            }
        } else {
            if (document.body.classList.contains('dark-mode')) {
                document.body.classList.remove('dark-mode');
            }
            if (mainHeader && mainHeader.classList.contains('navbar-dark')) {
                mainHeader.classList.add('navbar-light');
                mainHeader.classList.remove('navbar-dark');
            }
            localStorage.setItem('theme', 'light');
            
            if (themeIcon) {
                themeIcon.className = 'fas fa-sun';
            }
        }    
    }

    toggleSwitch.addEventListener('change', switchTheme, false);
}