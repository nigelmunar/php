var tSearchTimout;
var meetingDatatable;

var meetingDatatableColumnDefinitions = {
    0: {
        'name': 'date',
        'filter': false,
        'filterType': null
    },
    1: {
        'name': 'forum_name',
        'filter': true,
        'filterType': null
    },
    2: {
        'name': 'p',
        'filter': false,
        'filterType': null
    },
    3: {
        'name': 'a',
        'filter': true,
        'filterType': null
    },
    4: {
        'name': 'm',
        'filter': false,
        'filterType': null
    },
    5: {
        'name': 's',
        'filter': false,
        'filterType': null
    },
    6: {
        'name': 'tools',
        'filter': false,
        'filterType': null
    }
}


function renderMeetingFilters(binitialLoad)
{
    var $meetingDatatable = $('#meeting-datatable');
    var $meetingDatatableTHead = $meetingDatatable.find('thead');

    if(!binitialLoad)
    {
        $meetingDatatableTHead.find('tr:last').remove();
    }

    $meetingDatatableTHead.append('<tr></tr>');

    $meetingDatatable.dataTable().api().columns().every( function (i) {
        if(this.visible())
        {
            $meetingDatatableTHead.find('tr:eq(1)').append('<th></th>');

            if(meetingDatatableColumnDefinitions[i].filter)
            {
                var column = this;

                if(meetingDatatableColumnDefinitions[i].filterType == 'text')
                {
                    var input = $('<input class="form-control" type="text" placeholder="Search ' + $(column.header()).text() + '" value="' + column.search() + '" />')
                        .appendTo( $meetingDatatableTHead.find('tr:eq(1) th:last') )
                        .on( 'keyup change clear', function () {
                            var term = this.value;

                            clearTimeout(tSearchTimout);
                            tSearchTimout = setTimeout(function() { column.search(term, false, false ).draw(); }, 500);
                        } );
                }
                else if(meetingDatatableColumnDefinitions[i].filterType === 'select')
                {
                    var select = $('<select class="form-control"><option value="">Any</option></select>')
                        .appendTo( $meetingDatatableTHead.find('tr:eq(1) th:last') )
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
    meetingDatatable = $('#meeting-datatable').DataTable({
        initComplete: function() {  setTimeout('renderMeetingFilters(true);', 50); },
        orderCellsTop: true,
        fixedHeader: true,
        bStateSave: true,
        fnStateSave: function (oSettings, oData) {
            localStorage.setItem('Meeting_DataTables', JSON.stringify(oData) );
        },
        fnStateLoad: function (oSettings) {
            return JSON.parse( localStorage.getItem('Meeting_DataTables') );
        },
        dom: '<"top"lip<"clear">>rt<"bottom"ip<"clear">>',
        processing: true,
        serverSide: true,
        language: {
            info: 'Showing meeting _START_ to _END_ of _TOTAL_ meetings',
            paginate: {
                previous: '<i class="fal fa-chevron-left"></i>',
                next: '<i class="fal fa-chevron-right"></i>'
            },
            processing: '<img src="/admin/images/loading.svg" class="dataTables_processing__loading" alt="Processing">'
        },
        lengthMenu: [ 20, 40, 50, 80, 100 ],
        order: [[ 1, "asc" ]],
        ajax: '/admin/ajax/get-meeting-data.html',
        columnDefs: [
            {
                targets: 0,
                name: "date",
                orderable: true,
                searchable : true,
                data: null,
                render: function ( data, type, row, meta ) {
                    return '<a href="' + sSiteURL + 'admin/meetings/view.html?meeting=' + data.code + '">' + data.date + '</a>';
                }
            },
            {
                targets: 1,
                name: "forum_name",
                orderable: true,
                searchable : false,
                data: 'forum_name'
            },
            {
                targets: 2,
                name: "p",
                orderable: false,
                searchable : false,
                data: 'p',
            },
            {
                targets: 3,
                name: "a",
                orderable: true,
                searchable : false,
                data: 'a'
            },
            {
                targets: 4,
                name: "m",
                orderable: true,
                searchable : true,
                data: 'm'
               
            },
            {
                targets: 5,
                name: "s",
                orderable: true,
                searchable : false,
                data: 's'
            },
            {
                targets: 6,
                visible: true,
                orderable: false,
                searchable : false,
                data: null,
                className: 'datatable_actions',
                render: function ( data, type, row, meta ) 
                {
                    return '<a href="' + sSiteURL + 'admin/meetings/view.html?meeting=' + data.code + '" class="text-success edit-link"><i class="fal fa-pen"></i></a>' + 
                    '&nbsp;&nbsp;<a href="' + sSiteURL + 'admin/meetings/?delete=' + data.code + '" class="red delete-link" onclick="return confirm(\'Are you sure you wish to delete ' + data.name + '?\');"><i class="fal fa-trash-alt"></i></a>';  
                }
            }
        ]
    });


    $('#meeting-datatable').on('column-visibility.dt', function() { renderAdministratorFilters(false); });


    var meetingJSON = JSON.parse(localStorage.getItem('DataTables_meeting-datatable_/currencies/'));

    if(meetingJSON != null)
    {
        meetingJSON = meetingJSON.columns;
        
        $('#meeting-columns option').prop('selected', '');

        for(var i = 0; i < meetingJSON.length; i++)
        {
            if(meetingJSON[i].visible)
            {
                $('#meeting-columns option[value=\'' + meetingDatatableColumnDefinitions[i].name + '\']').prop('selected', 'selected');
            }
        }

        $('#meeting-columns').multiselect('reload');
    }
});