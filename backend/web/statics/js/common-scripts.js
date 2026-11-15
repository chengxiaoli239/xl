/*---LEFT BAR ACCORDION----*/
var xlMobileMenuScrollTop = 0;
function setXlMobileMenuScrollLock(locked) {
    var $html = $('html');
    var $body = $('body');
    if (locked) {
        xlMobileMenuScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
        document.body.style.setProperty('--xl-menu-scroll-top', '-' + xlMobileMenuScrollTop + 'px');
        $html.addClass('xl-mobile-menu-lock');
        $body.addClass('xl-mobile-menu-lock');
        return;
    }
    $html.removeClass('xl-mobile-menu-lock');
    var wasLocked = $body.hasClass('xl-mobile-menu-lock');
    $body.removeClass('xl-mobile-menu-lock');
    document.body.style.removeProperty('--xl-menu-scroll-top');
    if (wasLocked) {
        window.scrollTo(0, xlMobileMenuScrollTop);
    }
}

$(function() {
    $('#nav-accordion').dcAccordion({
        eventType: 'click',
        autoClose: true,
        saveState: false,
        disableLink: true,
        speed: 'slow',
        showCount: false,
        autoExpand: true,
        classExpand: 'dcjq-current-parent'
    });
});

// right slidebar
$(function(){
 $.slidebars();
});

var Script = function () {

//    sidebar dropdown menu auto scrolling

    jQuery('#sidebar .sub-menu > a').click(function () {
        var o = ($(this).offset());
        diff = 250 - o.top;
        if(diff>0)
            $("#sidebar").scrollTo("-="+Math.abs(diff),500);
        else
            $("#sidebar").scrollTo("+="+Math.abs(diff),500);
    });

//    sidebar toggle

    $(function() {
        function responsiveView() {
            var wSize = $(window).width();
            if (wSize <= 768) {
                if ($('#container').hasClass('xl-mobile-menu-open')) {
                    setXlMobileMenuScrollLock(false);
                }
                $('#container').addClass('sidebar-close');
                $('#container').removeClass('xl-mobile-menu-open');
                $('#sidebar > ul').hide();
                $('#sidebar').hide();
                $('.xl-nav-toggle').attr({'aria-expanded': 'false', 'aria-label': '打开菜单'});
            }

            if (wSize > 768) {
                setXlMobileMenuScrollLock(false);
                $('#container').removeClass('sidebar-close');
                $('#sidebar > ul').show();
                $('#sidebar').show();
                $('#container').removeClass('xl-mobile-menu-open');
                $('.xl-nav-toggle').attr({'aria-expanded': 'true', 'aria-label': '关闭菜单'});
            }
        }
        $(window).on('load', responsiveView);
        $(window).on('resize', responsiveView);
    });

    $('.xl-nav-toggle').click(function () {
        var v = $('#sidebar > ul').is(':visible');
        if ( v === true) {
            $('#main-content').css({
                'margin-left': '0px'
            });
            $('#sidebar').css({
                'margin-left': '-210px'
            });
            //$('#sidebar > ul').hide();
            $('#sidebar').css('display','none');
            $('#nav-accordion').css('display','none');
            $('#container').addClass('sidebar-closed');
            if ($('#container').hasClass('xl-mobile-menu-open')) {
                setXlMobileMenuScrollLock(false);
            }
            $('#container').removeClass('xl-mobile-menu-open');
            $(this).attr({'aria-expanded': 'false', 'aria-label': '打开菜单'});
        } else {
            $('#main-content').css({
                'margin-left': '210px'
            });
            //$('#sidebar > ul').show();
            $('#sidebar').css('display','block');
            $('#nav-accordion').css('display','block');
            $('#sidebar').css({
                'margin-left': '0'
            });
            $('#container').removeClass('sidebar-closed');
            if ($(window).width() <= 768) {
                $('#container').addClass('xl-mobile-menu-open');
                setXlMobileMenuScrollLock(true);
            }
            $(this).attr({'aria-expanded': 'true', 'aria-label': '关闭菜单'});
        }
    });
    $('.xl-nav-backdrop').on('click', function () {
        $('.xl-nav-toggle').trigger('click');
    });
    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $('#container').hasClass('xl-mobile-menu-open')) {
            $('.xl-nav-toggle').trigger('click');
        }
    });

// custom scrollbar
    // 检测是否为触屏设备（移动端），触屏设备使用原生滚动以获得流畅体验
    var isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);

    $('#sidebar').niceScroll({styler:'fb',cursorcolor:'#24766a', cursorwidth: '3', cursorborderradius: '10px', background: '#e1e8e3', spacebarenabled:false, cursorborder: ''});

    if (!isTouchDevice) {
        // 桌面端：使用niceScroll自定义滚动条
        $('html').niceScroll({styler:'fb',cursorcolor:'#24766a', cursorwidth: '6', cursorborderradius: '10px', background: '#e1e8e3', spacebarenabled:false,  cursorborder: '', zindex: '1000'});
    } else {
        // 移动端：使用原生滚动，CSS已设置-webkit-overflow-scrolling: touch确保流畅
        $('html').addClass('touch-scroll');
    }

// widget tools

    jQuery('.panel .tools .fa-chevron-down').click(function () {
        var el = jQuery(this).parents('.panel').children('.panel-body');
        if (jQuery(this).hasClass('fa-chevron-down')) {
            jQuery(this).removeClass('fa-chevron-down').addClass('fa-chevron-up');
            el.slideUp(200);
        } else {
            jQuery(this).removeClass('fa-chevron-up').addClass('fa-chevron-down');
            el.slideDown(200);
        }
    });

// by default collapse widget

//    $('.panel .tools .fa').click(function () {
//        var el = $(this).parents(".panel").children(".panel-body");
//        if ($(this).hasClass("fa-chevron-down")) {
//            $(this).removeClass("fa-chevron-down").addClass("fa-chevron-up");
//            el.slideUp(200);
//        } else {
//            $(this).removeClass("fa-chevron-up").addClass("fa-chevron-down");
//            el.slideDown(200); }
//    });

    jQuery('.panel .tools .fa-times').click(function () {
        jQuery(this).parents(".panel").parent().remove();
    });


//    tool tips

    $('.tooltips').tooltip();

//    popovers

    $('.popovers').popover();



// custom bar chart

    if ($(".custom-bar-chart")) {
        $(".bar").each(function () {
            var i = $(this).find(".value").html();
            $(this).find(".value").html("");
            $(this).find(".value").animate({
                height: i
            }, 2000)
        })
    }






}();
