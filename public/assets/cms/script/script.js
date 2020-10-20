'use strict';

// JavaScript Document

//按需写入所需的函数名
$(function () {
    // 用于测试
    var h = 0;
    console.log(h);

    checkBrowser();

    // 阻止默认行为写法，Chrome56以上版本
    // document.addEventListener('touchmove', func, isPassive() ? {
    //         capture: false,
    //         passive: false
    //     } : false);
});

// 首页头部广告
function adv_close() {
    $('.js-icon-close').on('click', function () {
        $(this).parent().fadeOut();
    });
}

// 栏目页滑动效果
function swiperArticleEvent() {
    // 专家在线
    var $_swiper_01 = new Swiper('.right-sum-swiper', {
        speed: 1000,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false
        },
        loop: true,
        simulateTouch: false,
        navigation: {
            nextEl: '.right-sum-box .next-btn',
            prevEl: '.right-sum-box .prev-btn'
        }
    });
    $('.right-sum-box').on('mouseenter', '.swiper-container', function () {
        $_swiper_01.autoplay.stop();
    });
    $('.right-sum-box').on('mouseleave', '.swiper-container', function () {
        $_swiper_01.autoplay.start();
    });

    // 会员活动
    var $_swiper_02 = new Swiper('.member-activities-swiper', {
        speed: 1000,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false
        },
        loop: true,
        simulateTouch: false,
        pagination: {
            el: '.member-activities-swiper .swiper-pagination',
            clickable: true,
            bulletClass: 'my-bullet',
            bulletActiveClass: 'my-bullet-active'
        }
    });
    $('.member-activities-wrap').on('mouseenter', '.swiper-container', function () {
        $_swiper_02.autoplay.stop();
    });
    $('.member-activities-wrap').on('mouseleave', '.swiper-container', function () {
        $_swiper_02.autoplay.start();
    });

    // 抢购详情
    var $_swiper_03 = new Swiper('.scare-buying-swiper', {
        speed: 1000,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false
        },
        loop: true,
        simulateTouch: false,
        navigation: {
            nextEl: '.scare-buying-swiper .button-next',
            prevEl: '.scare-buying-swiper .button-prev'
        }
    });

    // 入驻企业
    var $_swiper_04 = new Swiper('.footer-swiper-wrap .swiper-container', {
        speed: 1000,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false
        },
        loop: true,
        simulateTouch: false,
        slidesPerView: 3,
        slidesPerGroup: 3,
        spaceBetween: 20,
        navigation: {
            nextEl: '.footer-swiper-wrap .button-next',
            prevEl: '.footer-swiper-wrap .button-prev'
        }
    });
    $('.footer-swiper-wrap').on('mouseenter', '.swiper-container', function () {
        $_swiper_04.autoplay.stop();
    });
    $('.footer-swiper-wrap').on('mouseleave', '.swiper-container', function () {
        $_swiper_04.autoplay.start();
    });
}

// 栏目页事件
function articleEvent() {
    // 抢购详情页面
    $('#del-btn').click(function () {
        var old_val = $('.inp').val();
        if (old_val < 1) {
            return false;
        } else {
            old_val--;
            $('.inp').val(old_val);
        }
    });
    $('#add-btn').click(function () {
        var old_val = $('.inp').val();

        old_val++;
        $('.inp').val(old_val);
    });
}

// 登录注册事件
function loginRegisterEvent() {
    var login_register = new Swiper('.register-login', {
        speed: 1000,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false
        },
        loop: true,
        pagination: {
            el: '.register-login .swiper-pagination',
            clickable: true,
            bulletClass: 'my-bullet',
            bulletActiveClass: 'my-bullet-active'
        }
    });
}

// 返回顶部
function returnTop() {
    $("#goback_top").on("click", function () {
        if ($("html").scrollTop()) {
            $("html").animate({
                scrollTop: 0
            }, 1000);
            return false;
        }
        $("body").animate({
            scrollTop: 0
        }, 1000);
        return false;
    });
}

// 会员中心侧边栏事件
function memberLeftNavEvent() {
    $('.nav-list-box').on('click', 'li', function () {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on').find('.sub').slideUp('linear');
        } else {
            $(this).addClass('on').find('.sub').slideDown('linear').end().siblings().removeClass('on').find('.sub').slideUp('linear');
        }
    });
}

// 会员中心事件
function memberCenterEvent() {
    // 抢购发布列表删除选项
    $('#js_all_checkbox').on('click', function () {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on').find('input[type="checkbox"]').prop('checked', false);
            $('.list-block').find('.js-list').each(function () {
                $(this).find('.js-checkbox').removeClass('on').children().prop('checked', false);
            });
        } else {
            $(this).addClass('on').find('input[type="checkbox"]').prop('checked', true);
            $('.list-block').find('.js-list').each(function () {
                $(this).find('.js-checkbox').addClass('on').children().prop('checked', true);
            });
        }
    });
    $('.release-scare-buying-wrap').on('click', '.js-checkbox', function () {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on').find('input[type="checkbox"]').prop('checked', false);
        } else {
            $(this).addClass('on').find('input[type="checkbox"]').prop('checked', true);
        }
    });

    // 我要提现--查看金额
    $('.js-check-btn').click(function () {
        var cash = $('.cash-num').attr('data-cash');
        if ($('.cash-num').hasClass('look')) {
            $('.cash-num').removeClass('look').text('******');
        } else {
            $('.cash-num').addClass('look').text(cash);
        }
    });
}

// 图片上传
function uploadImg() {
    // let imgFiles = []
    $('.c--member-input-file').on('change', function () {
        var hiddenInp = $(this).next();
        var parentOuter = $(this).closest('.outer');
        var preview = $(this).parent();
        var file = $(this).get(0).files[0];
        var fileName = file.name;
        // console.log(file,fileName)

        preview.children().hide();

        if (file.type.indexOf('image') === 0) {
            var reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function () {
                var newUrl = this.result;
                var imgHtml = '<img class="c--member-upload-img" data-name="' + fileName + '" src="' + newUrl + '" alt="" />';
                var removeBtn = '<span class="c--member-remove-btn"></span>';
                preview.append(imgHtml);
                parentOuter.append(removeBtn);
                hiddenInp.val(newUrl);
            };
            // imgFiles.push(file)
        }
    });
    $('.file-img-box').on('click', '.c--member-remove-btn', function () {
        $(this).parent().find('.inner-file-img').children().show().end().find('input[type="file"]').val('').end().find('.file-hidden').val('').end().find('.c--member-upload-img').remove().end().parents('.outer').find('.c--member-remove-btn').remove();
    });
}

// 企业人才招聘页事件
function recruitmentEvent() {
    $('.recruitment-list').on('click', '.inner-recruitment', function () {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on').parent().find('.sub').slideUp('linear');
        } else {
            $(this).addClass('on').parent().find('.sub').slideDown('linear').end().siblings().find('.sub').slideUp('linear').end().find('.inner-recruitment').removeClass('on');
        }
    });
}

// 以下不用可以删除

//表单相关
function forms() {

    //输入框文字清空还原，控制value
    // <input type="text" value="请输入关键字" />
    $(".deaSearch .inp").focus(function () {
        if ($(this).val() == this.defaultValue) {
            $(this).val("");
        }
    }).blur(function () {
        if ($(this).val() == '') {
            $(this).val(this.defaultValue);
        }
    });
}

//简单标签切换
function tabs(tit, box) {
    /*html结构
     <div class="tabs">
       <div class="tabhd">
     <ul>
     <li class="on">标题一</li>
     <li>标题二</li>
     </ul>
     </div>
       <div class="tabbd">
     <div>内容一</div>
     <div>内容二</div>
     </div>
       </div>
     */
    var $div_li = $(tit).children();
    var $box_li = $(box).children();
    var i;
    $box_li.hide();
    $div_li.each(function () {
        if ($(this).hasClass('on')) i = $(this).index();
    });
    $box_li.eq(i).show();
    $div_li.click(function () {
        $(this).addClass("on").siblings().removeClass("on");
        var index = $div_li.index(this);
        $box_li.eq(index).fadeIn("linear").siblings().hide();
    });
}

// 判断浏览器
var checkBrowser = function checkBrowser() {
    var userAgent = navigator.userAgent.toLowerCase();
    var msie9 = /msie 9\.0/i.test(userAgent);
    var msie8 = /msie 8\.0/i.test(userAgent);
    var msie7 = /msie 7\.0/i.test(userAgent);
    var msie6 = /msie 6\.0/i.test(userAgent);
    var checkHtml = '';

    // if (msie9 || msie8 || msie7 || msie6) {
    //     $('body').append(checkHtml);
    // };

    if (msie8) {
        checkHtml = '<div class="checkBrowser"><span>您现在使用的是IE8内核，版本过低！建议您升级到IE9+或者使用极速模式浏览，以体验最佳效果!</span><a title="关闭" onclick="checkBrowser.close();">×</a></div>';
        $('body').append(checkHtml);
    } else if (msie7) {
        checkHtml = '<div class="checkBrowser"><span>您现在使用的是IE7内核，版本过低！建议您升级到IE9+或者使用极速模式浏览，以体验最佳效果!</span><a title="关闭" onclick="checkBrowser.close();">×</a></div>';
        $('body').append(checkHtml);
    } else if (msie6) {
        checkHtml = '<div class="checkBrowser"><span>您现在使用的是IE6内核，版本过低！建议您升级到IE9+或者使用极速模式浏览，以体验最佳效果!</span><a title="关闭" onclick="checkBrowser.close();">×</a></div>';
        $('body').append(checkHtml);
    }

    checkBrowser.close = function () {
        $('.checkBrowser').remove();
    };
};

// 判断是否移动设备
var isMobile = function isMobile() {
    var sUserAgent = navigator.userAgent.toLowerCase();
    var bIsIpad = sUserAgent.match(/ipad/i) == "ipad";
    var bIsIphoneOs = sUserAgent.match(/iphone os/i) == "iphone os";
    var bIsMidp = sUserAgent.match(/midp/i) == "midp";
    var bIsUc7 = sUserAgent.match(/rv:1.2.3.4/i) == "rv:1.2.3.4";
    var bIsUc = sUserAgent.match(/ucweb/i) == "ucweb";
    var bIsAndroid = sUserAgent.match(/android/i) == "android";
    var bIsCE = sUserAgent.match(/windows ce/i) == "windows ce";
    var bIsWM = sUserAgent.match(/windows mobile/i) == "windows mobile";

    if (bIsIpad || bIsIphoneOs || bIsMidp || bIsUc7 || bIsUc || bIsAndroid || bIsCE || bIsWM) {
        return true;
    } else {
        return false;
    }
};

function func(e) {
    e.preventDefault();
}