define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'message/message/index',
                    add_url: 'message/message/add',
                    edit_url: 'message/message/edit',
                    del_url: 'message/message/del',
                    multi_url: 'message/message/multi',
                    table: 'message',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showColumns: false,
                showExport: false,
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title')},
                        {field: 'send_user', title: __('Send_user'), searchList: {"1-1":__('Send_user 1-1'),"1-2":__('Send_user 1-2'),"1-3":__('Send_user 1-3'),"1-4":__('Send_user 1-4'),"2-1":__('Send_user 2-1'),"2-2":__('Send_user 2-2'),"2-3":__('Send_user 2-3'),"3-1":__('Send_user 3-1'),"4-1":__('Send_user 4-1')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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