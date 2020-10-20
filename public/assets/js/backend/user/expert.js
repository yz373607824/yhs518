define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/expert/index',
                    add_url: 'user/expert/add',
                    edit_url: 'user/expert/edit',
                    del_url: 'user/expert/del',
                    multi_url: 'user/expert/multi',
                    table: 'user_expert',
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
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'user.nickname', title: __('User.nickname'),operate: '='},
                        {field: 'user.no', title: __('用户ID'),operate: '='},
                        {
                            field: 'name',
                            title: __('Name'),
                            operate: '=',
                            formatter: function (value, row, index) {
                                return '<span'+ (row['is_sensitive'] == 1 ? ' style="color: #db3733"' : '') +'>' + value + '</span>';
                            }
                        },
                        // {field: 'sex', title: __('Sex'), searchList: {"男":__('Sex 男'),"女":__('Sex 女')}, formatter: Table.api.formatter.normal},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'job_number', title: __('Job_number')},
                        // {field: 'email', title: __('Email')},
                        // {field: 'education', title: __('Education')},
                        // {field: 'college', title: __('College')},
                        // {field: 'company', title: __('Company')},
                        // {field: 'address', title: __('Address')},
                        // {field: 'duty', title: __('Duty')},
                        // {field: 'job', title: __('Job')},
                        // {field: 'technosphere', title: __('Technosphere')},
                        // {field: 'workage', title: __('Workage')},
                        // {field: 'adept', title: __('Adept')},
                        {field: 'level', title: __('Level'), searchList: {"1":__('Level 1'),"2":__('Level 2'),"3":__('Level 3')}, formatter: Table.api.formatter.label},
                        {field: 'service', title: __('Service'), searchList: {"online":__('Service online'),"locale":__('Service locale')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        // {field: 'service_starttime', title: __('Service_starttime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'service_endtime', title: __('Service_endtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'deadline_starttime', title: __('Deadline_starttime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'deadline_endtime', title: __('Deadline_endtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'admin_id', title: __('Admin_id')},
                        {field: 'index_recommend', title: __('Index_recommend'), searchList: {"0":__('Index_recommend 0'),"1":__('Index_recommend 1')}, formatter: Table.api.formatter.normal},
                        {field: 'index_list_recommend', title: __('Index_list_recommend'), searchList: {"0":__('Index_list_recommend 0'),"1":__('Index_list_recommend 1')}, formatter: Table.api.formatter.normal},
                        {field: 'mobile_index_recommend', title: __('Mobile_index_recommend'), searchList: {"0":__('Mobile_index_recommend 0'),"1":__('Mobile_index_recommend 1')}, formatter: Table.api.formatter.normal},
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