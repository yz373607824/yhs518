define([], function () {
    require.config({
    paths: {
        'geetest': '../addons/geetest/js/geetest.min'
    }
});

require(['geetest'], function (Geet) {
    var geetInit = false;
    window.renderGeetest = function () {
        $("input[name='captcha']:visible").each(function () {
            var obj = $(this);
            var form = obj.closest('form');
            obj.parent()
                .removeClass('input-group')
                .html('<div class="embed-captcha"><input type="hidden" name="captcha" class="form-control" data-msg-required="请完成验证码验证" data-rule="required" /> </div> <p class="wait show" style="min-height:44px;line-height:44px;">正在加载验证码...</p>');

            Fast.api.ajax("/addons/geetest/index/start", function (data) {
                // 参数1：配置参数
                // 参数2：回调，回调的第一个参数验证码对象，之后可以使用它做appendTo之类的事件
                initGeetest({
                    gt: data.gt,
                    https: true,
                    challenge: data.challenge,
                    new_captcha: data.new_captcha,
                    product: Config.geetest.product, // 产品形式，包括：float，embed，popup。注意只对PC版验证码有效
                    width: '100%',
                    offline: !data.success // 表示用户后台检测极验服务器是否宕机，一般不需要关注
                }, function (captchaObj) {
                    // 将验证码加到id为captcha的元素里，同时会有三个input的值：geetest_challenge, geetest_validate, geetest_seccode
                    geetInit = captchaObj;
                    captchaObj.appendTo($(".embed-captcha", form));
                    captchaObj.onReady(function () {
                        $(".wait", form).remove();
                    });
                    captchaObj.onSuccess(function () {
                        var result = captchaObj.getValidate();
                        if (result) {
                            $('input[name="captcha"]', form).val('ok');
                        }
                    });
                    captchaObj.onError(function () {
                        geetInit.reset();
                    });
                });
                // 监听表单错误事件
                form.on("error.form", function (e, data) {
                    geetInit.reset();
                });
                return false;
            });
        });
    };
    renderGeetest();
});

require.config({
    paths: {
        'nkeditor': '../addons/nkeditor/js/customplugin',
        'nkeditor-core': '../addons/nkeditor/nkeditor',
        'nkeditor-lang': '../addons/nkeditor/lang/zh-CN',
    },
    shim: {
        'nkeditor': {
            deps: [
                'nkeditor-core',
                'nkeditor-lang'
            ]
        },
        'nkeditor-core': {
            deps: [
                'css!../addons/nkeditor/themes/black/editor.min.css'
            ],
            exports: 'window.KindEditor'
        },
        'nkeditor-lang': {
            deps: [
                'nkeditor-core'
            ]
        }
    }
});
if ($(".editor").size() > 0) {
    require(['nkeditor', 'upload'], function (Nkeditor, Upload) {
        var getImageFromClipboard, getImagesFromDrop;
        getImageFromClipboard = function (data) {
            var i, item;
            i = 0;
            while (i < data.clipboardData.items.length) {
                item = data.clipboardData.items[i];
                if (item.type.indexOf("image") !== -1) {
                    return item.getAsFile() || false;
                }
                i++;
            }
            return false;
        };
        getImagesFromDrop = function (data) {
            var i, item, images;
            i = 0;
            images = [];
            while (i < data.dataTransfer.files.length) {
                item = data.dataTransfer.files[i];
                if (item.type.indexOf("image") !== -1) {
                    images.push(item);
                }
                i++;
            }
            return images;
        };
        $(".editor").each(function () {
            var that = this;
            Nkeditor.create(that, {
                width: '100%',
                filterMode: false,
                wellFormatMode: false,
                allowMediaUpload: true, //是否允许媒体上传
                allowFileManager: true,
                allowImageUpload: true,
                fillDescAfterUploadImage: false, //是否在上传后继续添加描述信息
                themeType: typeof Config.nkeditor != 'undefined' ? Config.nkeditor.theme : 'black', //编辑器皮肤,这个值从后台获取
                fileManagerJson: Fast.api.fixurl("/addons/nkeditor/index/attachment/module/" + Config.modulename),
                items: [
                    'source', 'undo', 'redo', 'preview', 'print', 'template', 'code', 'quote', 'cut', 'copy', 'paste',
                    'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
                    'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
                    'superscript', 'clearhtml', 'quickformat', 'selectall',
                    'formatblock', 'fontname', 'fontsize', 'forecolor', 'hilitecolor', 'bold',
                    'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', 'image', 'multiimage', 'graft',
                    'flash', 'media', 'insertfile', 'table', 'hr', 'emoticons', 'baidumap', 'pagebreak',
                    'anchor', 'link', 'unlink', 'about', 'fullscreen'
                ],
                afterCreate: function () {
                    var self = this;
                    //Ctrl+回车提交
                    Nkeditor.ctrl(document, 13, function () {
                        self.sync();
                        $(that).closest("form").submit();
                    });
                    Nkeditor.ctrl(self.edit.doc, 13, function () {
                        self.sync();
                        $(that).closest("form").submit();
                    });
                    //粘贴上传
                    $("body", self.edit.doc).bind('paste', function (event) {
                        var image, pasteEvent;
                        pasteEvent = event.originalEvent;
                        if (pasteEvent.clipboardData && pasteEvent.clipboardData.items) {
                            image = getImageFromClipboard(pasteEvent);
                            if (image) {
                                event.preventDefault();
                                Upload.api.send(image, function (data) {
                                    self.exec("insertimage", Fast.api.cdnurl(data.url));
                                });
                            }
                        }
                    });
                    //挺拽上传
                    $("body", self.edit.doc).bind('drop', function (event) {
                        var image, pasteEvent;
                        pasteEvent = event.originalEvent;
                        if (pasteEvent.dataTransfer && pasteEvent.dataTransfer.files) {
                            images = getImagesFromDrop(pasteEvent);
                            if (images.length > 0) {
                                event.preventDefault();
                                $.each(images, function (i, image) {
                                    Upload.api.send(image, function (data) {
                                        self.exec("insertimage", Fast.api.cdnurl(data.url));
                                    });
                                });
                            }
                        }
                    });
                },
                //FastAdmin自定义处理
                beforeUpload: function (callback, file) {
                    var file = file ? file : $("input.ke-upload-file", this.form).prop('files')[0];
                    Upload.api.send(file, function (data) {
                        var data = {code: '000', data: {url: Fast.api.cdnurl(data.url)}, title: '', width: '', height: '', border: '', align: ''};
                        callback(data);
                    });

                },
                //错误处理 handler
                errorMsgHandler: function (message, type) {
                    try {
                        console.log(message, type);
                    } catch (Error) {
                        alert(message);
                    }
                }
            });
        });
    });
}

});