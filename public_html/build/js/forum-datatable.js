var tSearchTimout;
var forumDatatable;

var forumDatatableColumnDefinitions = {
    0: {
        'name': 'name',
        'filter': true,
        'filterType': 'text'
    },
    1: {
        'name': 'members',
        'filter': true,
        'filterType': null
    },
    2: {
        'name': 'tools',
        'filter': false,
        'filterType': null
    }
}


function renderForumFilters(binitialLoad)
{
    var $forumDatatable = $('#forum-datatable');
    var $forumDatatableTHead = $forumDatatable.find('thead');

    if(!binitialLoad)
    {
        $forumDatatableTHead.find('tr:last').remove();
    }

    $forumDatatableTHead.append('<tr></tr>');

    $forumDatatable.dataTable().api().columns().every( function (i) {
        if(this.visible())
        {
            $forumDatatableTHead.find('tr:eq(1)').append('<th></th>');

            if(forumDatatableColumnDefinitions[i].filter)
            {
                var column = this;

                if(forumDatatableColumnDefinitions[i].filterType == 'text')
                {
                    var input = $('<input class="form-control" type="text" placeholder="Search ' + $(column.header()).text() + '" value="' + column.search() + '" />')
                        .appendTo( $forumDatatableTHead.find('tr:eq(1) th:last') )
                        .on( 'keyup change clear', function () {
                            var term = this.value;

                            clearTimeout(tSearchTimout);
                            tSearchTimout = setTimeout(function() { column.search(term, false, false ).draw(); }, 500);
                        } );
                }
                else if(forumDatatableColumnDefinitions[i].filterType === 'select')
                {
                    var select = $('<select class="form-control"><option value="">Any</option></select>')
                        .appendTo( $forumDatatableTHead.find('tr:eq(1) th:last') )
                        .on( 'change', function () {
                            var term = this.value;

                            column.search(term, false, false ).draw();
                        } );

                    switch(i)
                    {
                        case 2:
                            if(column.search() == 'none')
                            {
                                select.append('<option value="none" selected="selected">None</option>');
                            } 
                            else 
                            {
                                select.append('<option value="none">None</option>');
                            }

                            break;  
                    }

                }
            }
        }
    });
}

$(function(){
    forumDatatable = $('#forum-datatable').DataTable({
        initComplete: function() {  setTimeout('renderForumFilters(true);', 50); },
        orderCellsTop: true,
        fixedHeader: true,
        bStateSave: true,
        fnStateSave: function (oSettings, oData) {
            localStorage.setItem('Forum_DataTables', JSON.stringify(oData) );
        },
        fnStateLoad: function (oSettings) {
            return JSON.parse( localStorage.getItem('Forum_DataTables') );
        },
        dom: '<"top"lip<"clear">>rt<"bottom"ip<"clear">>',
        processing: true,
        serverSide: true,
        language: {
            info: 'Showing forum _START_ to _END_ of _TOTAL_ forums',
            paginate: {
                previous: '<i class="fal fa-chevron-left"></i>',
                next: '<i class="fal fa-chevron-right"></i>'
            },
            processing: '<img src="/admin/images/loading.svg" class="dataTables_processing__loading" alt="Processing">'
        },
        lengthMenu: [ 20, 40, 50, 80, 100 ],
        order: [[ 1, "asc" ]],
        ajax: '/admin/ajax/get-forums.html',
        columnDefs: [
            {
                targets: 0,
                name: "name",
                orderable: true,
                searchable : true,
                data: null,
                render: function ( data, type, row, meta ) {
                    return '<a href="' + sSiteURL + 'admin/forums/edit.html?forum=' + data.code + '">' + data.name + '</a>';
                }
            },
            {
                targets: 1,
                name: "members",
                orderable: true,
                searchable : false,
                data: 'members'
            },
            {
                targets: 2,
                visible: true,
                orderable: false,
                searchable : false,
                data: null,
                className: 'datatable_actions',
                render: function ( data, type, row, meta ) 
                {
                    return '<a href="' + sSiteURL + 'admin/forums/edit.html?forum=' + data.code + '" class="text-success edit-link"><i class="fal fa-pen"></i></a>' + 
                    '&nbsp;&nbsp;<a href="' + sSiteURL + 'admin/forums/?delete=' + data.code + '" class="red delete-link" onclick="return confirm(\'Are you sure you wish to delete ' + data.name + '?\');"><i class="fal fa-trash-alt"></i></a>';  
                }
            }
        ]
    });


    $('#forum-datatable').on('column-visibility.dt', function() { renderAdministratorFilters(false); });


    var forumJSON = JSON.parse(localStorage.getItem('DataTables_forum-datatable_/currencies/'));

    if(forumJSON != null)
    {
        forumJSON = forumJSON.columns;
        
        $('#forum-columns option').prop('selected', '');

        for(var i = 0; i < forumJSON.length; i++)
        {
            if(forumJSON[i].visible)
            {
                $('#forum-columns option[value=\'' + forumDatatableColumnDefinitions[i].name + '\']').prop('selected', 'selected');
            }
        }

        $('#forum-columns').multiselect('reload');
    }
});