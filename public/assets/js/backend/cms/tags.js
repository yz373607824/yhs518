define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/tags/index',
                    add_url: 'cms/tags/add',
                    edit_url: 'cms/tags/edit',
                    del_url: 'cms/tags/del',
                    multi_url: 'cms/tags/multi',
                    table: 'tags',
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
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', sortable: true, title: __('Id')},
                        {field: 'name', sortable: true, title: __('Name'),operate: 'LIKE'},
                        {field: 'type', title: __('Type'), searchList: $.getJSON("cms/tags/select")},
                        {field: 'nums', sortable: true, title: __('Nums'), operate: false},
                        {field: 'weigh', sortable: true, title: __('Weigh'), operate: false},
                        // {field: 'url', title: __('Url'), operate: false, formatter: function(value, row, index){
                        //          return '<a href="' + value + '" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-link"></i></a>';
                        // }},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            //热搜标签显示或隐藏
            $(document).on('change','#c-type_text',function () {
                var selectClick = $("#c-type").val();
                if (selectClick == 133) {
                    $("#hotSearch").removeClass('hidden');
                }
            });
        },
        edit: function () {
            Controller.api.bindevent();
            //热搜标签显示或隐藏
            var selectClick = $("#c-type").val();
            if (selectClick == 133) {
                $("#hotSearch").removeClass('hidden');
            }
            $(document).on('change','#c-type_text',function () {
                var selectClick = $("#c-type").val();
                if (selectClick != 133) {
                    $("#hotSearch").addClass('hidden');
                }
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});