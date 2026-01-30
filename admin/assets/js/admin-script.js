// Admin Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle Functionality
    const sidebar = document.getElementById('sidebar');
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const closeSidebar = document.getElementById('close-sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const body = document.body;

    function openSidebar() {
        sidebar.classList.add('active');
        mainContent.classList.add('sidebar-active');
        sidebarOverlay.classList.add('active');
        body.classList.add('sidebar-active');
    }

    function closeSidebarMenu() {
        sidebar.classList.remove('active');
        mainContent.classList.remove('sidebar-active');
        sidebarOverlay.classList.remove('active');
        body.classList.remove('sidebar-active');
    }

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openSidebar);
    }

    if (closeSidebar) {
        closeSidebar.addEventListener('click', closeSidebarMenu);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebarMenu);
    }

    // Handle submenu toggles
    const submenuToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const targetId = this.getAttribute('data-bs-target');
                const targetSubmenu = document.querySelector(targetId);
                if (targetSubmenu) {
                    targetSubmenu.classList.toggle('show');
                }
            }
            
            // Ensure sidebar scrolls to show expanded content
            setTimeout(() => {
                const sidebar = document.getElementById('sidebar');
                const targetId = this.getAttribute('data-bs-target');
                const targetSubmenu = document.querySelector(targetId);
                
                if (sidebar && targetSubmenu && targetSubmenu.classList.contains('show')) {
                    const submenuRect = targetSubmenu.getBoundingClientRect();
                    const sidebarRect = sidebar.getBoundingClientRect();
                    
                    // Check if submenu is fully visible
                    if (submenuRect.bottom > sidebarRect.bottom) {
                        // Scroll to make the submenu visible
                        const scrollAmount = submenuRect.bottom - sidebarRect.bottom + 20;
                        sidebar.scrollTop += scrollAmount;
                    }
                }
            }, 350); // Wait for collapse animation to complete
        });
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
            const isClickOnToggleBtn = mobileMenuToggle && mobileMenuToggle.contains(e.target);
            const isClickOnCloseBtn = closeSidebar && closeSidebar.contains(e.target);
            
            if (!isClickInsideSidebar && !isClickOnToggleBtn && !isClickOnCloseBtn && 
                sidebar && sidebar.classList.contains('active')) {
                closeSidebarMenu();
            }
        }
    });

    // Close sidebar if screen resized from mobile to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebarMenu();
            // Reset any open submenus
            document.querySelectorAll('.collapse').forEach(submenu => {
                submenu.classList.remove('show');
            });
        }
    });

    // Initialize Charts
    initializeCharts();
});

// Chart Initialization
function initializeCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Monthly Sales',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: '#7c43b9',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        display: window.innerWidth > 768
                    },
                    title: {
                        display: true,
                        text: 'Monthly Sales Overview',
                        font: {
                            size: window.innerWidth <= 768 ? 14 : 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: window.innerWidth <= 768 ? 10 : 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: window.innerWidth <= 768 ? 10 : 12
                            }
                        }
                    }
                }
            }
        });
    }

    // Revenue Distribution Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'doughnut',
            data: {
                labels: ['Products', 'Services', 'Subscriptions'],
                datasets: [{
                    data: [300, 150, 100],
                    backgroundColor: ['#7c43b9', '#9c6dd3', '#bca0dc'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth <= 768 ? 'bottom' : 'right',
                        labels: {
                            font: {
                                size: window.innerWidth <= 768 ? 10 : 12
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Revenue Distribution',
                        font: {
                            size: window.innerWidth <= 768 ? 14 : 16
                        }
                    }
                }
            }
        });
    }
}

// Handle Notifications
function markNotificationAsRead(notificationId) {
    // Add your notification handling logic here
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.classList.add('read');
    }
}

// Handle Profile Dropdown
document.addEventListener('click', function(e) {
    const profileDropdown = document.querySelector('.profile-dropdown');
    const profileToggle = document.querySelector('.profile-toggle');
    
    if (profileDropdown && profileToggle) {
        if (e.target === profileToggle || profileToggle.contains(e.target)) {
            profileDropdown.classList.toggle('show');
        } else if (!profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('show');
        }
    }
});