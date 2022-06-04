var tSearchTimout;
var administratorDatatable;

var administratorDatatableColumnDefinitions = {
    0: {
        'name': 'name',
        'filter': true,
        'filterType': 'text'
    },
    1: {
        'name': 'email',
        'filter': true,
        'filterType': 'text'
    },
    2: {
        'name': 'date_added',
        'filter': false,
        'filterType': null
    },
    3: {
        'name': 'tools',
        'filter': false,
        'filterType': null
    }
}

function renderAdministratorFilters(binitialLoad)
{
    var $administratorDatatable = $('#administrator-datatable');
    var $administratorDatatableTHead = $administratorDatatable.find('thead');

    if(!binitialLoad)
    {
        $administratorDatatableTHead.find('tr:last').remove();
    }

    $administratorDatatableTHead.append('<tr></tr>');

    $administratorDatatable.dataTable().api().columns().every( function (i) {
        if(this.visible())
        {
            $administratorDatatableTHead.find('tr:eq(1)').append('<th></th>');

            if(administratorDatatableColumnDefinitions[i].filter)
            {
                var column = this;

                if(administratorDatatableColumnDefinitions[i].filterType == 'text')
                {
                    var input = $('<input class="form-control" type="text" placeholder="Search ' + $(column.header()).text() + '" value="' + column.search() + '" />')
                        .appendTo( $administratorDatatableTHead.find('tr:eq(1) th:last') )
                        .on( 'keyup change clear', function () {
                            var term = this.value;

                            clearTimeout(tSearchTimout);
                            tSearchTimout = setTimeout(function() { column.search(term, false, false ).draw(); }, 500);
                        } );
                }
                else
                {
                    var select = $('<select class="form-control"><option value="">Any</option></select>')
                        .appendTo( $administratorDatatableTHead.find('tr:eq(1) th:last') )
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

                            for(var i = 0; i < chapters.length; i++)
                            {    
                                if(column.search() == chapters[i].urlName)
                                {
                                    select.append('<option value="' + chapters[i].urlName + '" selected="selected">' + chapters[i].name + '</option>');
                                } 
                                else 
                                {
                                    select.append('<option value="' + chapters[i].urlName + '">' + chapters[i].name + '</option>');
                                }
                            }

                            break;  
                    }

                }
            }
        }
    });
}

$(function(){
    administratorDatatable = $('#administrator-datatable').DataTable({
        initComplete: function() {  setTimeout('renderAdministratorFilters(true);', 50); },
        orderCellsTop: true,
        fixedHeader: true,
        bStateSave: true,
        fnStateSave: function (oSettings, oData) {
            localStorage.setItem('Administrator_DataTables', JSON.stringify(oData) );
        },
        fnStateLoad: function (oSettings) {
            return JSON.parse( localStorage.getItem('Administrator_DataTables') );
        },
        dom: '<"top"lip<"clear">>rt<"bottom"ip<"clear">>',
        processing: true,
        serverSide: true,
        language: {
            info: 'Showing administrator _START_ to _END_ of _TOTAL_ administrators',
            paginate: {
                previous: '<i class="fal fa-chevron-left"></i>',
                next: '<i class="fal fa-chevron-right"></i>'
            },
            processing: '<img src="/images/loading.svg" class="dataTables_processing__loading" alt="Processing">'
        },
        lengthMenu: [ 20, 40, 50, 80, 100 ],
        order: [[ 1, "asc" ]],
        ajax: '/ajax/get-administrators.html',
        columnDefs: [
            {
                targets: 0,
                name: "administrator_name",
                orderable: true,
                searchable : true,
                data: null,
                render: function ( data, type, row, meta ) {
                    return '<a href="' + sSiteURL + 'administrators/view.html?administrator=' + data.code + '">' + data.name + '</a>';
                }
            },
            {
                targets: 1,
                name: "email",
                orderable: true,
                searchable : true,
                data: 'email'
            },
            {
                targets: 2,
                name: "date_added",
                orderable: true,
                searchable : false,
                data: "date_added"
            },
            {
                targets: 3,
                visible: true,
                orderable: false,
                searchable : false,
                data: null,
                className: 'datatable_actions',
                render: function ( data, type, row, meta ) {
                    return (data.enabled ? '<a href="' + sSiteURL + 'administrators/?disable=' + data.code + '" class="text-success edit-link"><i class="fal fa-eye"></i></a>' : '<a href="' + sSiteURL + 'administrators/?enable=' + data.code + '" class="red edit-link"><i class="fal fa-eye-slash"></i></a>') + 
                    '&nbsp;&nbsp;<a href="' + sSiteURL + 'administrators/?delete=' + data.code + '" class="red delete-link" onclick="return confirm(\'Are you sure you wish to delete ' + data.name + '?\');"><i class="fal fa-trash-alt"></i></a>';  
                }
            }
        ]
    });


    $('#administrator-datatable').on('column-visibility.dt', function() { renderAdministratorFilters(false); });


    var administratorJSON = JSON.parse(localStorage.getItem('DataTables_administrator-datatable_/administrators/'));

    if(administratorJSON != null)
    {
        administratorJSON = administratorJSON.columns;
        
        $('#administrator-columns option').prop('selected', '');

        for(var i = 0; i < administratorJSON.length; i++)
        {
            if(administratorJSON[i].visible)
            {
                $('#administrator-columns option[value=\'' + administratorDatatableColumnDefinitions[i].name + '\']').prop('selected', 'selected');
            }
        }

        $('#administrator-columns').multiselect('reload');
    }
});