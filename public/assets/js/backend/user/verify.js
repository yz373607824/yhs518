define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/verify/index',
                    add_url: 'user/verify/add',
                    edit_url: 'user/verify/edit',
                    del_url: 'user/verify/del',
                    multi_url: 'user/verify/multi',
                    table: 'user_verify',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                searchFormVisible: true,//显示通用搜索
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'user.nickname', title: __('User.nickname'),operate: '='},
                        {field: 'user.no', title: __('用户ID'),operate: 'LIKE'},
                        {field: 'name', title: __('Name')},
                        {field: 'idcard', title: __('Idcard')},
                        {field: 'idcardfrontimage', title: __('Idcardfrontimage'), formatter: Table.api.formatter.image,operate: false},
                        {field: 'idcardversoimage', title: __('Idcardversoimage'), formatter: Table.api.formatter.image,operate: false},
                        {field: 'idcardhandimage', title: __('Idcardhandimage'), formatter: Table.api.formatter.image,operate: false},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            
            // 绑定TAB事件
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).closest("ul").data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    var filter = {};
                    if (value !== '') {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
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
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});