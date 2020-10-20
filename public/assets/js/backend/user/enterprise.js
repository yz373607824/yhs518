define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/enterprise/index',
                    add_url: 'user/enterprise/add',
                    edit_url: 'user/enterprise/edit',
                    del_url: 'user/enterprise/del',
                    multi_url: 'user/enterprise/multi',
                    table: 'user_enterprise',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                search:false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                searchFormVisible: true,//显示通用搜索
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'level', title: __('Level'), searchList: {"1":__('Level 1'),"2":__('Level 2'),"3":__('Level 3'),"4":__('Level 4')}, formatter: Table.api.formatter.normal,custom : {1:'primary',2:'primary',3:'primary',4:'primary'}},
                        {field: 'apply_level', title: __('Apply_level'), searchList: {"0":__('Apply_level 0'),"2":__('Apply_level 2'),"3":__('Apply_level 3'),"4":__('Apply_level 4')}, formatter: Table.api.formatter.normal,custom : {1:'danger',2:'danger',3:'danger',4:'danger'}},
                        {field: 'user.nickname', title: __('用户昵称'),operate: '='},
                        {field: 'user.username', title: __('User.username'),operate: false},
                        {field: 'user.no', title: __('用户ID'),operate: 'LIKE'},
                        {
                            field: 'company',
                            title: __('Company'),
                            operate: false,
                            formatter: function (value, row, index) {
                                return '<span'+ (row['is_sensitive'] == 1 ? ' style="color: #db3733"' : '') +'>' + value + '</span>';
                            },operate: 'LIKE'
                        },
                        {field: 'code', title: __('Code'),operate: false},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"-1":__('Status -1')}, formatter: Table.api.formatter.normal,custom : {0:'primary',1:'danger',2:'success'}},
                        {field: 'is_excellent', title: __('Is_excellent'), searchList: {"0":__('Is_excellent 0'),"1":__('Is_excellent 1')}, formatter: Table.api.formatter.normal},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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