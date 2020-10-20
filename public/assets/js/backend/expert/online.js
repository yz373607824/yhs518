define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'expert/online/index',
                    add_url: 'expert/online/add',
                    edit_url: 'expert/online/edit',
                    del_url: 'expert/online/del',
                    multi_url: 'expert/online/multi',
                    table: 'expert_online',
                }
            });

            var table = $("#table");

            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "搜索标题";};

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user.nickname', title: __('User.nickname'), operate: '='},
                        {field: 'user.no', title: __('用户ID'),operate: '='},
                        // {field: 'user_id', title: __('User_id')},
                        // {field: 'expert_id', title: __('Expert_id')},
                        {field: 'title', title: __('Title')},
                        {field: 'is_recommend', title: __('经典案例推荐位'), searchList: {"0":__('否'),"1":__('是')}, formatter: Table.api.formatter.normal},
                        {field: 'is_flag', title: __('全网提问'), searchList: {"0":__('否'),"1":__('是')}, formatter: Table.api.formatter.normal},
                        // {field: 'file', title: __('File'), operate: false},
                        {field: 'expert.name', title: __('Expert.name'), operate: '='},
                        {field: 'replycontent', title: __('Replycontent'), operate: false},
                        {field: 'appraise', title: '用户评价', operate: false},
                        {field: 'replyappraise', title: '专家评价', operate: false},
                        {field: 'status', title: '支付状态', searchList: {"0":'待支付',"1":'已支付',"2":"已过期"}, formatter: Table.api.formatter.status},
                        {field: 'totalprice', title: '价格', operate:'BETWEEN'},
                        {field: 'paytime', title: '支付时间', operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'is_reply', title: __('Is_reply'), searchList: {"0":__('Is_reply 0'),"1":__('Is_reply 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'is_rollback', title: __('Is_rollback'), searchList: {"0":__('Is_rollback 0'),"1":__('Is_rollback 1')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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