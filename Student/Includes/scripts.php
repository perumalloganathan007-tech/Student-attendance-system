<!-- Bootstrap core JavaScript-->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Page level plugins -->
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Custom scripts -->
<script>
$(document).ready(function() {
    // Sidebar toggle
    $('#sidebarToggle').on('click', function() {
        $('body').toggleClass('sidebar-toggled');
        $('.sidebar').toggleClass('toggled');
        
        // Close dropdowns when sidebar is toggled on mobile
        if ($(window).width() < 768) {
            $('.sidebar .collapse').collapse('hide');
        }
    });

    // Initialize DataTables
    if($.fn.DataTable) {
        $('.dataTable').DataTable();
    }

    // Automatically collapse sidebar on mobile
    function checkWidth() {
        if ($(window).width() < 768) {
            $('body').addClass('sidebar-toggled');
            $('.sidebar').addClass('toggled');
            $('.sidebar .collapse').collapse('hide');
        } else {
            $('body').removeClass('sidebar-toggled');
            $('.sidebar').removeClass('toggled');
        }
    }

    // Check width on load
    checkWidth();

    // Check width on resize
    $(window).resize(checkWidth);

    // Handle sidebar collapse state
    $('.nav-link[data-toggle="collapse"]').on('click', function() {
        if ($(window).width() < 768) {
            $('body').addClass('sidebar-toggled');
            $('.sidebar').addClass('toggled');
        }
    });

    // Keep sidebar active state
    var path = window.location.pathname;
    var page = path.split("/").pop();
    
    $('.sidebar .nav-item .nav-link').each(function() {
        var $this = $(this);
        if ($this.attr('href') === page) {
            $this.addClass('active');
            if ($this.closest('.collapse').length) {
                $this.closest('.collapse').addClass('show');
                $this.closest('.nav-item').addClass('active');
            }
        }
    });

    // Scroll to top functionality
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('.scroll-to-top').fadeIn();
        } else {
            $('.scroll-to-top').fadeOut();
        }
    });

    $('.scroll-to-top').click(function(e) {
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, 800);
    });
});
</script>
