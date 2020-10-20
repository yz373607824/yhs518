define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'banner/content/index',
                    add_url: 'banner/content/add',
                    edit_url: 'banner/content/edit',
                    del_url: 'banner/content/del',
                    multi_url: 'banner/content/multi',
                    table: 'banner_content',
                }
            });

            var table = $("#table");

            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "搜索广告名称";};

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'manage_id', title: __('Manage_id')},
                        {field: 'manage.name', title: __('Manage.name'), operate: false},
                        {field: 'name', title: __('Name')},
                        {field: 'url', title: __('Url'), formatter: Table.api.formatter.url, operate: false},
                        {field: 'pic', title: __('Pic'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'picimg', title: __('Picimg'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'is_time', title: __('Is_time'), searchList: {"0":__('Is_time 0'),"1":__('Is_time 1')}, formatter: Table.api.formatter.normal},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).closest("ul").data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    params.filter = '{"manage_id":' + value +'}';
                    return params;
                };
                table.bootstrapTable('refresh', {});
                Table.api.init({
                    extend: {
                        index_url: 'banner/content/index',
                        add_url: 'banner/content/add?manage_id=' + value,
                        edit_url: 'banner/content/edit',
                        del_url: 'banner/content/del',
                        multi_url: 'banner/content/multi',
                        table: 'banner_content',
                    }
                });
                return false;
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            getSize($('#c-manage_id'));

            $('#c-manage_id').change(function () {
                getSize($(this));
            });

            Controller.api.bindevent();
        },
        edit: function () {
            getSize($('#c-manage_id'));

            $('#c-manage_id').change(function () {
                getSize($(this));
            });

            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };

    function getSize(obj) {
        $.post('banner/content/getSize', {param:obj.val()}, function (data) {
            if (data.code == 1) {
                $('#pic-size').text(data.data.pic);
                $('#picimg-size').text(data.data.picimg);
            } else {
                $('#pic-size').text('');
                $('#picimg-size').text('');
            }
        });
    }

    return Controller;
});