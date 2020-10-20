define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/diydata/index/diyform_id/' + Config.diyform_id,
                    add_url: 'cms/diydata/add/diyform_id/' + Config.diyform_id,
                    edit_url: 'cms/diydata/edit/diyform_id/' + Config.diyform_id,
                    del_url: 'cms/diydata/del/diyform_id/' + Config.diyform_id,
                    multi_url: '',
                    table: '',
                }
            });

            var table = $("#table");
            //默认字段
            var columns = [
                {checkbox: true},
                {field: 'id', title: __('Id'), operate: false},
                {
                    field: 'userinfo.nickname',
                    title: __('会员昵称'),
                    // addclass: 'selectpage',
                    // extend: 'data-source="user/user/index" data-field="nickname"',
                    operate: false,
                    // formatter: Table.api.formatter.search
                }
            ];
            //动态追加字段
            $.each(Config.fields, function (i, j) {
                var data = {field: j.field, title: j.title, operate: 'like'};
                //如果是图片,加上formatter
                if (j.type == 'image') {
                    data.formatter = Table.api.formatter.image;
                } else if (j.type == 'images') {
                    data.formatter = Table.api.formatter.images;
                } else if (j.type == 'radio' || j.type == 'check' || j.type == 'select' || j.type == 'selects') {
                    if (j.field == 'pay_type') {
                        data.searchList = {"0":__('Pay_type 0'),"1":__('Pay_type 1'),"2":__('Pay_type 2'),"3":__('Pay_type 3')};
                        data.formatter =  Table.api.formatter.status;
                        data.width = '5%'
                    } else {
                        data.formatter = Controller.api.formatter.content;
                        data.extend = j.content;
                    }
                }
                columns.push(data);
            });
            columns.push({field: 'createtime', sortable: true, title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime});
            //columns.push({field: 'updatetime', sortable: true, title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime});
            //追加操作字段
            columns.push({
                field: 'operate',
                title: __('Operate'),
                table: table,
                events: Table.api.events.operate,
                formatter: Table.api.formatter.operate
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,//关闭快速搜索
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: columns
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                content: function (value, row, index) {
                    var extend = this.extend;
                    if (!value) {
                        return '';
                    }
                    var valueArr = value.toString().split(/,/);
                    var result = [];
                    $.each(valueArr, function (i, j) {
                        result.push(typeof extend[j] !== 'undefined' ? extend[j] : j);
                    });
                    return result.join(',');
                }
            },
            bindevent: function () {
                $.validator.config({
                    rules: {
                        diyname: function (element) {
                            if (element.value.toString().match(/^\d+$/)) {
                                return __('Can not be digital');
                            }
                            return $.ajax({
                                url: 'cms/archives/check_element_available',
                                type: 'POST',
                                data: {id: $("#archive-id").val(), name: element.name, value: element.value},
                                dataType: 'json'
                            });
                        }
                    }
                });
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});