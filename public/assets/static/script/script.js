"use strict";

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

// JavaScript Document
//按需写入所需的函数名
$(function () {
  // 用于测试
  var h = 0;
  checkBrowser(); // 阻止默认行为写法，Chrome56以上版本
  // document.addEventListener('touchmove', func, isPassive() ? {
  //         capture: false,
  //         passive: false
  //     } : false);
}); // 单页图片放大

function enlargeImg() {
  var imgZoom = new IScroll('#show_wrap', {
    zoom: true,
    scrollX: true,
    scrollY: true,
    mouseWheel: true,
    wheelAction: 'zoom'
  });
  $('.con-box').find('img').on('click', function () {
    var img = $(this).attr('src');

    if (!$(this).parent().is('a')) {
      $('body').css('overflow', 'hidden');
      $('.big-img-box').fadeIn('linear').find('.show-img img').attr('src', img);
      setTimeout(function () {
        imgZoom.refresh();
      }, 200);
    }
  });
  $('.big-img-box').on('click', '.big-close-btn', function () {
    $('body').css('overflow', 'visible');
    $(this).parents('.big-img-box').fadeOut('linear');
  });
} // 首页事件


function indexEvent() {
  var indexBulletArr = ['表', '面', '处', '理', '联', '盟'];
  var $indexBannerSwiper = new Swiper('.index-banner-box .swiper-container', {
    speed: 1000,
    effect: 'fade',
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    loop: true,
    simulateTouch: false,
    pagination: {
      el: '.index-banner-box .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active',
      renderBullet: function renderBullet(index, className) {
        return '<span class="' + className + '">' + '<b>' + indexBulletArr[index] + '</b>' + '</span>';
      }
    }
  });
  $('.index-banner-box').on('mouseenter', '.swiper-container', function () {
    $indexBannerSwiper.autoplay.stop();
  });
  $('.index-banner-box').on('mouseleave', '.swiper-container', function () {
    $indexBannerSwiper.autoplay.start();
  });
  var $indexExpertSwiper = new Swiper('.expert-list-swiper .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false
    },
    loop: true,
    simulateTouch: false,
    slidesPerView: 3,
    slidesPerGroup: 1,
    spaceBetween: 10,
    navigation: {
      nextEl: '.expert-list-swiper .button-next',
      prevEl: '.expert-list-swiper .button-prev'
    }
  });
  $('.expert-list-swiper').on('mouseenter', '.swiper-container', function () {
    $indexExpertSwiper.autoplay.stop();
  });
  $('.expert-list-swiper').on('mouseleave', '.swiper-container', function () {
    $indexExpertSwiper.autoplay.start();
  });
  var $indexIndustrySwiper_01 = new Swiper('.con-box-01 .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    loop: true,
    simulateTouch: false,
    pagination: {
      el: '.con-box-01 .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active'
    }
  });
  var $indexIndustrySwiper_02a = new Swiper('.con-box-02-swiper', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    loop: true,
    simulateTouch: false,
    pagination: {
      el: '.con-box-02-swiper .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active'
    }
  }); // 之前行业新闻的滑动实现方式

  var i;
  var swp_list = $('.con-box-02 .swiper-list').children();
  var wrapper_list = $('.con-box-02 .swiper-wrapper').children();
  swp_list.hide();
  wrapper_list.each(function () {
    if ($(this).hasClass('on')) i = $(this).index();
  });
  swp_list.eq(i).show();
  var $indexIndustrySwiper_02 = new Swiper('.con-box-02 .conbox-swp', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    slidesPerView: 4,
    slidesPerGroup: 1,
    spaceBetween: 10,
    slidesOffsetAfter: 359,
    simulateTouch: false,
    navigation: {
      nextEl: '.conbox-swp .button-next',
      prevEl: '.conbox-swp .button-prev'
    },
    on: {
      slideChangeTransitionStart: function slideChangeTransitionStart() {
        // alert(this.activeIndex);
        // console.log(this)
        var index = this.activeIndex;
        this.slides.removeClass('on').eq(index).addClass('on');
        swp_list.hide().eq(index).fadeIn(1500, 'linear');
      }
    }
  }); // $('.con-box-02').on('mouseenter', '.swiper-container', function () {
  //     $indexIndustrySwiper_02.autoplay.stop()
  // })
  // $('.con-box-02').on('mouseleave', '.swiper-container', function () {
  //     $indexIndustrySwiper_02.autoplay.start()
  // })
} // 首页头部广告


function adv_close() {
  $('.js-icon-close').on('click', function () {
    $(this).parent().fadeOut();
  });
} // 栏目页滑动效果


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
  }); // 会员活动

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
  }); // 抢购详情

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
  }); // 入驻企业

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
  }); // 等级页面--企业详情

  var $_swiper_05 = new Swiper('.honor-swiper .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    simulateTouch: false,
    slidesPerView: 4,
    slidesPerGroup: 1,
    spaceBetween: 10,
    navigation: {
      nextEl: '.honor-swiper .button-next',
      prevEl: '.honor-swiper .button-prev'
    }
  });
} // 栏目页事件


function articleEvent() {
  // 抢购详情页面
  $('#del-btn').click(function () {
    var old_val = $('.sinp').val();

    if (old_val < 1) {
      return false;
    } else {
      old_val--;
      $('.sinp').val(old_val);
    }
  });
  $('#add-btn').click(function () {
    var old_val = $('.sinp').val();
    old_val++;
    $('.sinp').val(old_val);
  });
} // 登录注册事件


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
} // 返回顶部


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
} // 会员中心侧边栏事件


function memberLeftNavEvent() {
  $('.nav-list-box li').each(function () {
    $(this).find('.sub').children().each(function () {
      if ($(this).hasClass('active')) {
        $(this).parent().slideDown('linear').end().closest('li').addClass('on');
      }
    });
  });
  $('.nav-list-box').on('click', 'li', function () {
    if ($(this).hasClass('on')) {
      $(this).removeClass('on').find('.sub').slideUp('linear');
    } else {
      $(this).addClass('on').find('.sub').slideDown('linear').end().siblings().removeClass('on').find('.sub').slideUp('linear');
    }
  });
} // 会员中心事件


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
  }); // 我要提现--查看金额

  $('.js-check-btn').click(function () {
    var cash = $('.cash-num').attr('data-cash');

    if ($('.cash-num').hasClass('look')) {
      $('.cash-num').removeClass('look').text('******');
    } else {
      $('.cash-num').addClass('look').text(cash);
    }
  });
}

function commonTable() {
  $('#checkAll').on('click', function () {
    if ($(this).hasClass('on')) {
      $(this).removeClass('on').find('input[type="checkbox"]').prop('checked', false);
      $('.tbody').each(function () {
        $(this).find('.option-cb').removeClass('on').children().prop('checked', false);
      });
    } else {
      $(this).addClass('on').find('input[type="checkbox"]').prop('checked', true);
      $('.tbody').each(function () {
        $(this).find('.option-cb').addClass('on').children().prop('checked', true);
      });
    }
  });
  $('.tbody').on('click', '.option-cb', function () {
    if ($(this).hasClass('on')) {
      $(this).removeClass('on').find('input[type="checkbox"]').prop('checked', false);
    } else {
      $(this).addClass('on').find('input[type="checkbox"]').prop('checked', true);
    }
  });
} // 图片上传


function uploadImg() {
  // let imgFiles = []
  $('.c--member-input-file').on('change', function () {
    var hiddenInp = $(this).next();
    var parentOuter = $(this).closest('.outer');
    var preview = $(this).parent();
    var file = $(this).get(0).files[0];
    var fileName = file.name; // console.log(file,fileName)

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
      }; // imgFiles.push(file)

    }
  });
  $('.file-img-box').on('click', '.c--member-remove-btn', function () {
    $(this).parent().find('.inner-file-img').children().show().end().find('input[type="file"]').val('').end().find('.file-hidden').val('').end().find('.c--member-upload-img').remove().end().parents('.outer').find('.c--member-remove-btn').remove();
  }); // 修改个人资料

  $('.personal-input-file').on('change', function () {
    var hiddenInp = $(this).next();
    var preview = $(this).parent();
    var file = $(this).get(0).files[0];

    if (file.type.indexOf('image') === 0) {
      var reader = new FileReader();
      reader.readAsDataURL(file);

      reader.onload = function () {
        var newUrl = this.result;
        preview.find('img').attr('src', newUrl);
        hiddenInp.val(newUrl);
      };
    }
  });
} // 企业人才招聘页事件


function recruitmentEvent() {
  $('.recruitment-list').on('click', '.inner-recruitment', function () {
    if ($(this).hasClass('on')) {
      $(this).removeClass('on').parent().find('.sub').slideUp('linear');
    } else {
      $(this).addClass('on').parent().find('.sub').slideDown('linear').end().siblings().find('.sub').slideUp('linear').end().find('.inner-recruitment').removeClass('on');
    }
  });
} // 简单的星级评价


function starRate() {
  var parent = $('.star-rate');
  var starLi = parent.find('.star-default');
  var starV = arguments[0] ? arguments[0] : 0;
  var typeStr = arguments[1] ? arguments[1] : 'none';
  fnPoint(starLi, starV);
  $('#starCount').val(starV);

  if (typeStr === 'reset') {
    resetStar();
  } else if (typeStr === 'evaluateView') {
    starLi.each(function () {
      if (!$(this).hasClass('light-star')) {
        $(this).fadeOut('linear');
      }
    });
    $('#reset').hide();
  } else {
    var isClick = false;
    starLi.on('mouseover', function () {
      var index = $(this).attr('data-star');

      if (!isClick) {
        fnPoint(starLi, index);
      }
    }).on('mouseout', function () {
      var index = $(this).attr('data-star');

      if (!isClick) {
        for (var i = 0; i < 5; i++) {
          if (i < index) {
            starLi.eq(i).removeClass('light-star');
          }
        }
      }
    }).on('click', function () {
      var index = $(this).attr('data-star');

      if (!isClick) {
        fnPoint(starLi, index);
        $('#starCount').val(index);
        starLi.each(function () {
          if (!$(this).hasClass('light-star')) {
            $(this).fadeOut('linear');
          }
        });
        isClick = true;
      }
    });
    $('#reset').show();
  } // 重置星级


  $('#reset').on('click', function () {
    resetStar();
  });

  function resetStar() {
    starLi.removeClass('light-star');
    $('#starCount').val(0);
    starLi.each(function () {
      $(this).css('display', 'inline-block');
    });
  }
}

function fnPoint(starLi, index) {
  for (var i = 0; i < 5; i++) {
    if (i < index) {
      starLi.eq(i).addClass('light-star');
    }
  }
} // 倒计时


function countdown(countTime) {
  var nowTime = new Date(),
      endTime = parseInt(nowTime.getTime() / 1000) + countTime,
      timer = null;
  timer = setInterval(function () {
    var loopTime = parseInt(new Date().getTime() / 1000),
        diffTime = endTime - loopTime;
    if (diffTime < 0) return false;
    var days = parseInt(diffTime / (24 * 60 * 60));
    var hrs = parseInt(diffTime / (60 * 60) % 24);
    var mins = parseInt(diffTime / 60 % 60);
    var secs = parseInt(diffTime % 60);
    var daysArr = addZero(days),
        hrsArr = addZero(hrs),
        minsArr = addZero(mins),
        secsArr = addZero(secs);
    var daysVal = daysArr.length < 3 ? "".concat(daysArr[0]).concat(daysArr[1]) : "".concat(daysArr[0]).concat(daysArr[1]).concat(daysArr[2]),
        hrsVal = "".concat(hrsArr[0]).concat(hrsArr[1]),
        minsVal = "".concat(minsArr[0]).concat(minsArr[1]),
        secsVal = "".concat(secsArr[0]).concat(secsArr[1]);
    $('#days').html(daysVal);
    $('#hrs').html(hrsVal);
    $('#mins').html(minsVal);
    $('#secs').html(secsVal);

    if (secsVal == '00') {
      $('#indexFixedAdv').fadeOut('linear');
      $('.fixed-adv-wrap .second').html(secsVal);
    } else {
      $('.fixed-adv-wrap .second').html(secsVal);
    }
  }, 500);
}

function addZero(time) {
  var arr = [];

  if (time < 10) {
    arr[0] = 0;
    arr[1] = time;
  } else {
    var timeArr = time.toString().split('');
    arr.push.apply(arr, _toConsumableArray(timeArr));
  }

  return arr;
} // 以下不用可以删除
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
} //简单标签切换


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
} // 判断浏览器


var checkBrowser = function checkBrowser() {
  var userAgent = navigator.userAgent.toLowerCase();
  var msie9 = /msie 9\.0/i.test(userAgent);
  var msie8 = /msie 8\.0/i.test(userAgent);
  var msie7 = /msie 7\.0/i.test(userAgent);
  var msie6 = /msie 6\.0/i.test(userAgent);
  var checkHtml = ''; // if (msie9 || msie8 || msie7 || msie6) {
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
}; // 判断是否移动设备


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