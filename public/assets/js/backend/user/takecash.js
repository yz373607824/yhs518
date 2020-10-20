define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/takecash/index',
                    add_url: 'user/takecash/add',
                    edit_url: 'user/takecash/edit',
                    del_url: 'user/takecash/del',
                    multi_url: 'user/takecash/multi',
                    table: 'take_cash',
                }
            });

            var table = $("#table");
            function RndNum(n){
                var rnd="";
                for(var i=0;i<n;i++)
                    rnd+=Math.floor(Math.random()*10);
                return rnd;
            }
            var date = new Date();
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                showToggle: false,
                exportOptions: {
                    fileName: date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate() + '_' + RndNum(5),
                    ignoreColumn: [0, 'id', 'operate'], //默认不导出第一列(checkbox)与操作(operate)列
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'user.nickname', title: __('User.nickname'),operate: '='},
                        {field: 'name', title: __('Name')},
                        {field: 'type', title: __('Type'),operate: false},
                        {field: 'cardno', title: __('Cardno')},
                        {field: 'username', title: __('Username')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'amount', title: __('Amount'),operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
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