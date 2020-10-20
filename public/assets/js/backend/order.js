define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index',
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
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
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'user.no', title: __('用户ID'),operate: 'LIKE'},
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'trade_sn', title: __('Trade_sn')},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"4":__('Type 4'),"5":__('Type 5')}, formatter: Table.api.formatter.label},
                        {field: 'title', title: __('Title')},
                        // {field: 'goods_table', title: __('Goods_table')},
                        // {field: 'goods_id', title: __('Goods_id')},
                        // {field: 'goods_num', title: __('Goods_num'), operate: false},
                        // {field: 'goods_price', title: __('Goods_price'), operate:'BETWEEN'},
                        {field: 'totalprice', title: __('Totalprice'), operate:'BETWEEN'},
                        {field: 'star', title: __('Star'),visible:false},
                        {field: 'appraise', title: __('Appraise'),visible:false},
                        {field: 'appraisetime', title: __('Appraisetime'),visible:false, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'replyappraise', title: __('Replyappraise'),visible:false},
                        {field: 'replytime', title: __('Replytime'),visible:false, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'paytype', title: __('Paytype'), searchList: {"1":__('Paytype 1'),"2":__('Paytype 2'),"3":__('Paytype 3'),"4":__('Paytype 4'),"5":__('Paytype 5')}, formatter: Table.api.formatter.label},
                        {field: 'paytime', title: __('Paytime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
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

            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                //这里我们手动设置底部的值
                $("#money").text(data.money);
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