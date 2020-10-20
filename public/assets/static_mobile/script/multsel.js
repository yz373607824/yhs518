function multsel (scrollerObj) {
    var parent = $('.multse-select-wrap')

    // 展开收起
    parent.on('click', '.js-view', function (e) {
        e.stopPropagation()

        var $parent = $(this).parents('.multse-select-wrap')
        var scrollerData = $parent.find('.select-scroller').attr('data-scroller')
        var idStr = 'scroller' + scrollerData

        if ($parent.hasClass('expand')) {
            $parent.removeClass('expand').find('.select-scroller').stop('true', 'true').slideUp('linear')
                .end().find('.arrow').removeClass('rotate')
        } else {
            parent.removeClass('expand').find('.select-scroller').stop('true', 'true').slideUp('linear')
                .end().find('.arrow').removeClass('rotate')

            $parent.addClass('expand').find('.select-scroller').stop('true', 'true').slideDown('linear', function () {
                for (var i = 0; i < scrollerObj.length; i++) {
                    if (idStr === scrollerObj[i].wrapper.id) {
                        scrollerObj[i].refresh()
                    }
                }
            }).end().find('.arrow').addClass('rotate')
        }
    })

    // 页面加载，检查上一次多选记录
    parent.each(function () {
        multselCheck($(this).find('.select-list'))
    })

    // 多选操作
    parent.on('tap', '.select-item', function () {
        var self = $(this)

        if (self.hasClass('checked')) {
            self.removeClass('checked')
        } else {
            self.addClass('checked')
        }
        multselCheck(self.parent('.select-list'))
    })

    // 删除按钮事件
    $('.multse-select-wrap').on('click', '.i-close', function () {
        var thisOption = $(this).attr('data-del')
        $(this).closest('.multse-select-wrap').find('.select-item').each(function () {
            var thisSelect = $(this).attr('data-option')
            if (thisSelect === thisOption) {
                $(this).removeClass('checked')
                multselCheck($(this).parent('.select-list'))
            }
        })
    })

    $(document).click(function (e) {
        if (!parent.is(e.target) && parent.has(e.target).length === 0) {
            parent.removeClass('expand').find('.select-scroller').stop('true', 'true').slideUp('linear')
                .end().find('.arrow').removeClass('rotate')
        }
    });

}

// 选中值处理
function multselCheck (domParent) {
    var checkedHtml = '',
        hiddenText = null,
        optionText = '',
        optionItem = '',
        floatView = '<div class="js-view"></div>'

    domParent.find('.checked').each(function () {
        optionText = $(this).html()
        optionItem = $(this).attr('data-option')
        if (checkedHtml === '') {
            hiddenText = optionText
            checkedHtml = '<div class="multse-option"><span class="option">' + optionText + '</span><span class="iconfont icon-fork i-close" data-del="' + optionItem + '"></span></div>'
        } else {
            checkedHtml += '<div class="multse-option"><span class="option">' + optionText + '</span><span class="iconfont icon-fork i-close" data-del="' + optionItem + '"></span></div>'
            hiddenText += ',' + optionText;
        }
    })

    if (!checkedHtml) {
        checkedHtml = '<span class="placeholder">请选择类别</span>'
        hiddenText = ''
    }

    domParent.closest('.multse-select-wrap').find('.view').html(checkedHtml)
        .end().find('input[type="hidden"]').val(hiddenText)
        .end().find('.view').append(floatView)
}