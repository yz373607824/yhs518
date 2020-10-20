define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'expert/locale/index',
                    add_url: 'expert/locale/add',
                    edit_url: 'expert/locale/edit',
                    del_url: 'expert/locale/del',
                    multi_url: 'expert/locale/multi',
                    table: 'expert_locale',
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
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'user.nickname', title: __('User.nickname'), operate: '='},
                        {field: 'user.no', title: __('用户ID'),operate: '='},
                        {field: 'expert.name', title: __('Expert.name'), operate: '='},
                        // {field: 'expert_id', title: __('Expert_id')},
                        {field: 'linkman', title: __('Linkman')},
                        {field: 'enterprise', title: __('Enterprise')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'reservation_time', title: __('Reservation_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'reservation_address', title: __('Reservation_address')},
                        {field: 'title', title: __('Title')},
                        // {field: 'file', title: __('File'), operate: false},
                        {field: 'appraise', title: '用户评价', operate: false},
                        {field: 'replyappraise', title: '专家评价', operate: false},
                        {field: 'status', title: '支付状态', searchList: {"0":'待支付',"1":'已支付',"2":"已过期"}, formatter: Table.api.formatter.status},
                        {field: 'totalprice', title: '价格', operate:'BETWEEN'},
                        {field: 'paytime', title: '支付时间', operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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