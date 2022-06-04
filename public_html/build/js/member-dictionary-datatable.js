var tSearchTimout;
var memberDictionaryDatatable;

var memberDictionaryDatatableColumnDefinitions = {
    0: {
        'name': 'name',
        'filter': true,
        'filterType': 'text'
    },
    1: {
        'name': 'company',
        'filter': true,
        'filterType': 'text'
    },
    2: {
        'name': 'email',
        'filter': true,
        'filterType': 'text'
    },
    3: {
        'name': 'phone',
        'filter': false,
        'filterType': null
    },
    4: {
        'name': 'tools',
        'filter': false,
        'filterType': null
    }
}

function renderMemberDictionaryFilters(binitialLoad)
{
    var $memberDictionaryDatatable = $('#member-dictionary-datatable');
    var $memberDictionaryDatatableTHead = $memberDictionaryDatatable.find('thead');

    if(!binitialLoad)
    {
        $memberDictionaryDatatableTHead.find('tr:last').remove();
    }

    $memberDictionaryDatatableTHead.append('<tr></tr>');

    $memberDictionaryDatatable.dataTable().api().columns().every( function (i) {
        if(this.visible())
        {
            $memberDictionaryDatatableTHead.find('tr:eq(1)').append('<th></th>');

            if(memberDictionaryDatatableColumnDefinitions[i].filter)
            {
                var column = this;

                if(memberDictionaryDatatableColumnDefinitions[i].filterType == 'text')
                {
                    var input = $('<input class="form-control" type="text" placeholder="Search ' + $(column.header()).text() + '" value="' + column.search() + '" />')
                        .appendTo( $memberDictionaryDatatableTHead.find('tr:eq(1) th:last') )
                        .on( 'keyup change clear', function () {
                            var term = this.value;

                            clearTimeout(tSearchTimout);
                            tSearchTimout = setTimeout(function() { column.search(term, false, false ).draw(); }, 500);
                        } );
                }
                else
                {
                    var select = $('<select class="form-control"><option value="">Any</option></select>')
                        .appendTo( $memberDictionaryDatatableTHead.find('tr:eq(1) th:last') )
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
    memberDictionaryDatatable = $('#member-dictionary-datatable').DataTable({
        initComplete: function() {  setTimeout('renderMemberDictionaryFilters(true);', 50); },
        orderCellsTop: true,
        fixedHeader: true,
        bStateSave: true,
        fnStateSave: function (oSettings, oData) {
            localStorage.setItem('Member_Dictionary_DataTables', JSON.stringify(oData) );
        },
        fnStateLoad: function (oSettings) {
            return JSON.parse( localStorage.getItem('Member_Dictionary_DataTables') );
        },
        dom: '<"top"lip<"clear">>rt<"bottom"ip<"clear">>',
        processing: true,
        serverSide: true,
        language: {
            info: 'Showing member _START_ to _END_ of _TOTAL_ member',
            paginate: {
                previous: '<i class="fal fa-chevron-left"></i>',
                next: '<i class="fal fa-chevron-right"></i>'
            },
            processing: '<img src="/admin/images/loading.svg" class="dataTables_processing__loading" alt="Processing">'
        },
        lengthMenu: [ 20, 40, 50, 80, 100 ],
        order: [[ 1, "asc" ]],
        ajax: '/admin/ajax/get-member-dictionary.html',
        columnDefs: [
            {
                targets: 0,
                name: "name",
                orderable: true,
                searchable : true,
                data: null,
                render: function ( data, type, row, meta ) {
                    return '<a href="' + sSiteURL + 'admin/member-dictionary/view.html?member-code=' + data.code + '">' + data.name + '</a>';
                }
            },
            {
                targets: 1,
                name: "company",
                orderable: true,
                searchable : true,
                data: 'company'
            },
            {
                targets: 2,
                name: "email",
                orderable: true,
                searchable : true,
                data: "email"
            },
            {
                targets: 3,
                name: "phone",
                orderable: true,
                searchable : false,
                data: "phone"
            },
            {
                targets: 4,
                visible: true,
                orderable: false,
                searchable : false,
                data: null,
                className: 'datatable_actions',
                render: function ( data, type, row, meta ) {
                    return'<a href="' + sSiteURL + 'admin/member-dictionary/view.html?member-code=' + data.code + '">View</a>';  
                }
            }
        ]
    });


    $('#member-dictionary-datatable').on('column-visibility.dt', function() { renderMemberDictionaryFilters(false); });


    var memberDictionaryJSON = JSON.parse(localStorage.getItem('DataTables_member-dictionary-datatable_/memberDictionary/'));

    if(memberDictionaryJSON != null)
    {
        memberDictionaryJSON = memberDictionaryJSON.columns;
        
        $('#member-dictionary-columns option').prop('selected', '');

        for(var i = 0; i < memberDictionaryJSON.length; i++)
        {
            if(memberDictionaryJSON[i].visible)
            {
                $('#member-dictionary-columns option[value=\'' + memberDictionaryColumnDefinitions[i].name + '\']').prop('selected', 'selected');
            }
        }

        $('#member-dictionary-columns').multiselect('reload');
    }
});