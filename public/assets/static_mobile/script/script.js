"use strict";

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

/*global Swiper, IScroll*/
// JavaScript Document
//按需写入所需的函数名
$(function () {
  checkBrowser();
  returnTop(); // 软甲盘弹起 底部固定的导航会被弹起

  var winHeight = $(window).height();
  $('.input').on('focus', function () {
    var thisHeight = $(this).height();

    if (winHeight - thisHeight > 50) {
      $('body').css('height', winHeight + 'px');
    } else {
      $('body').css('height', '100%');
    }
  }); // 阻止默认行为写法，Chrome56以上版本
  // document.addEventListener('touchmove', func, isPassive() ? {
  //         capture: false,
  //         passive: false
  //     } : false);
});

function footerEvent() {
  var qqBox = $('.sideBar-wrap .qq-box');
  qqBox.on('click', function (e) {
    e.stopPropagation();

    if (!$(this).hasClass('active')) {
      $(this).addClass('active').find('.word').fadeIn();
      $('body').addClass('body-cursor');
    }
  });
  $(document).on('click', function (e) {
    if (!qqBox.is(e.target) && qqBox.has(e.target).length === 0) {
      qqBox.removeClass('active').find('.word').hide();
      $('body').removeClass('body-cursor');
    }
  });
} // 头部事件


function headerEvent() {
  $('.js-search').on('click', function () {
    var searchBox = $(this).next();

    if (searchBox.hasClass('anim')) {
      searchBox.removeClass('anim');
    } else {
      searchBox.addClass('anim');
    }
  }); // 移动端侧边栏引用

  var nav = new NavScrollMobile('#nav_wrap', '#nav_list_wrap');
  $('#menu').on('click', function () {
    nav.show();
  });
  $('#back, #nav_bg').on('click', function () {
    nav.remove();
  });
} // 首页事件


function indexEvent() {
  var indexBulletArr = ['表', '面', '处', '理', '联', '盟'];
  var $indexBannerSwiper = new Swiper('.index-banner-wrap', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    loop: true,
    simulateTouch: false,
    pagination: {
      el: '.index-banner-wrap .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active',
      renderBullet: function renderBullet(index, className) {
        return '<span class="' + className + '">' + indexBulletArr[index] + '</span>';
      }
    }
  });
  var $indexSwiperBox01 = new Swiper('.index-swiper-wrap-01 .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    slidesPerView: 2,
    slidesPerGroup: 2,
    spaceBetween: 15,
    pagination: {
      el: '.index-swiper-wrap-01 .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active'
    }
  });
  var $indexSwiperBox02 = new Swiper('.index-swiper-wrap-02 .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    slidesPerView: 'auto',
    spaceBetween: 8,
    centeredSlides: true
  });
  var $indexSwiperBox03 = new Swiper('.news-content-box .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    slidesPerView: 'auto',
    spaceBetween: 8,
    centeredSlides: true,
    pagination: {
      el: '.news-content-box .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active'
    }
  });
  var $indexSwiperBox04 = new Swiper('.index-swiper-wrap-03 .swiper-container', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    slidesPerView: 'auto',
    spaceBetween: 8,
    centeredSlides: true,
    pagination: {
      el: '.index-swiper-wrap-03 .swiper-pagination',
      clickable: true,
      bulletClass: 'my-bullet',
      bulletActiveClass: 'my-bullet-active'
    }
  });
  $('.slide-down-btn').on('click', function () {
    if (!$(this).hasClass('show')) {
      $(this).addClass('show').next().addClass('flex-show').stop('true', 'true').fadeIn('linear');
    } else {
      $(this).removeClass('show').next().removeClass('flex-show').stop('true', 'true').fadeOut('linear');
    }
  });
} // 专家事件


function expertEvent() {
  var expertAskSwiper = new Swiper('.expert-ask-swiper', {
    speed: 1000,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    navigation: {
      nextEl: '.expert-ask-box .button-next',
      prevEl: '.expert-ask-box .button-prev'
    }
  });
} // 上传图片


function uploadImg() {
  // 专家申请头像
  $('.input-file').on('change', function () {
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
  }); // 身份证上传

  $('.js-example').on('click', function () {
    var img = $(this).attr('data-example-img');
    $('.images-fixed-box').find('.img-box img').attr('src', img).end().fadeIn('linear');
  });
  $('.js-show-upload-img').on('click', function () {
    var img = $(this).attr('data-upload-img');
    $('.images-fixed-box').find('.img-box img').attr('src', img).end().fadeIn('linear');
  });
  $('.close-btn').click(function () {
    $('.images-fixed-box').fadeOut('linear');
  });
  $('.js-upload').on('change', '.input-file', function () {
    var file = $(this).get(0).files[0];
    var hiddenInp = $(this).closest('.upload-row').find('.file-hidden');
    var showUploadImg = $(this).closest('.upload-row').find('.js-show-upload-img');

    if (file.type.indexOf('image') === 0) {
      var reader = new FileReader();
      reader.readAsDataURL(file);

      reader.onload = function () {
        var newUrl = this.result;
        showUploadImg.attr('data-upload-img', newUrl);
        hiddenInp.val(newUrl);
      };

      $(this).parents('.upload-row').find('.files-img-box').addClass('on');
    } else {
      alert('请选择图片!');
      $(this).parents('.upload-row').find('.files-img-box').removeClass('on');
    }
  });
} // 评价星级


function starRate() {
  var parent = $('.star-rate');
  var starLi = parent.find('.star-default');
  var isClick = false;
  starLi.on('click', function () {
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
  }); // 重置星级

  $('#reset').on('click', function () {
    isClick = false;
    starLi.removeClass('light-star');
    $('#starCount').val(0);
    starLi.each(function () {
      $(this).css('display', 'inline-block');
    });
  });
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

    if (diffTime < 0) {
      return false;
    }

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
} // 移动端侧边栏


var NavScrollMobile =
/*#__PURE__*/
function () {
  function NavScrollMobile(dom, scroller) {
    _classCallCheck(this, NavScrollMobile);

    this.scroller = null;
    this.Dom = dom;
    this.scrollDom = scroller;
  }

  _createClass(NavScrollMobile, [{
    key: "init",
    value: function init() {
      var _this = this;

      _this.scroller = new IScroll(this.scrollDom, {
        tap: true,
        click: true,
        scrollX: false,
        scrollY: true,
        mouseWheel: false
      });
      $('.nav-list-btn').on('tap', function (e) {
        e.stopPropagation();

        if ($(this).hasClass('open')) {
          $(this).removeClass('open');
          $(this).parent().next().css('display', 'none');
          $(this).find('.iconfont').removeClass('rotate');
        } else {
          $(this).addClass('open');
          $(this).parent().next().css('display', 'block');
          $(this).find('.iconfont').addClass('rotate');
        }

        _this.scroller.refresh();
      });
    }
  }, {
    key: "show",
    value: function show() {
      var _this2 = this;

      $(this.Dom).show();
      setTimeout(function () {
        $(_this2.scrollDom).addClass('trans');
      }, 200);
      document.addEventListener('touchmove', func, isPassive() ? {
        capture: false,
        passive: false
      } : false);
      this.init();
    }
  }, {
    key: "remove",
    value: function remove() {
      var _this3 = this;

      var _this = this;

      $(this.scrollDom).removeClass('trans');
      setTimeout(function () {
        $(_this3.Dom).css('display', 'none');
      }, 200);
      $('.nav-list-btn').each(function () {
        $(this).removeClass('open').parent().next().css('display', 'none');
        $(this).off('tap');
        $(this).find('.iconfont').removeClass('rotate');
      });
      document.removeEventListener('touchmove', func, isPassive() ? {
        capture: false,
        passive: false
      } : false);

      _this.scroller.destroy();
    }
  }]);

  return NavScrollMobile;
}(); // 以下不用可以删除
// 返回顶部


function returnTop() {
  $("#backTop").on("click", function () {
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
} //表单相关


function forms() {
  //输入框文字清空还原，控制value
  // <input type="text" value="请输入关键字" />
  $(".deaSearch .inp").focus(function () {
    if ($(this).val() === this.defaultValue) {
      $(this).val("");
    }
  }).blur(function () {
    if ($(this).val() === '') {
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
  var oDivLi = $(tit).children();
  var oBoxLi = $(box).children();
  var i;
  oBoxLi.hide();
  oDivLi.each(function () {
    if ($(this).hasClass('on')) {
      i = $(this).index();
    }
  });
  oBoxLi.eq(i).show();
  oDivLi.click(function () {
    $(this).addClass("on").siblings().removeClass("on");
    var index = oDivLi.index(this);
    oBoxLi.eq(index).fadeIn("linear").siblings().hide();
  });
} // 判断浏览器


var checkBrowser = function checkBrowser() {
  var userAgent = window.navigator.userAgent.toLowerCase();
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
  var bIsIpad = sUserAgent.match(/ipad/i) === "ipad";
  var bIsIphoneOs = sUserAgent.match(/iphone os/i) === "iphone os";
  var bIsMidp = sUserAgent.match(/midp/i) === "midp";
  var bIsUc7 = sUserAgent.match(/rv:1.2.3.4/i) === "rv:1.2.3.4";
  var bIsUc = sUserAgent.match(/ucweb/i) === "ucweb";
  var bIsAndroid = sUserAgent.match(/android/i) === "android";
  var bIsCE = sUserAgent.match(/windows ce/i) === "windows ce";
  var bIsWM = sUserAgent.match(/windows mobile/i) === "windows mobile";

  if (bIsIpad || bIsIphoneOs || bIsMidp || bIsUc7 || bIsUc || bIsAndroid || bIsCE || bIsWM) {
    return true;
  } else {
    return false;
  }
};

function func(e) {
  e.preventDefault();
}

function isPassive() {
  return true;
}