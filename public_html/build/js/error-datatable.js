var errorDatatable;

var errorDatatableColumns = 
{
	'error_code' : 0,
	'error_message' : 1, 
	'page_id' : 2,
	'date_added': 3
}
$(document).ready(function() {
	
	errorDatatable = $('#error-datatable').DataTable({
		"autoWidth": false,
		responsive: true,
        fixedHeader: true,
        bStateSave: true,
        fnStateSave: function (oSettings, oData) {
            localStorage.setItem( 'Error_DataTables', JSON.stringify(oData) );
        },
        fnStateLoad: function (oSettings) {
            return JSON.parse( localStorage.getItem('Error_DataTables') );
        },
        dom: '<"top"lip<"clear">>rt<"bottom"ip<"clear">>',
        processing: true,
        serverSide: true,
        language: {
            info: 'Showing location _START_ to _END_ of _TOTAL_ errors',
            paginate: {
                previous: '<i class="fa fa-chevron-left"></i>',
                next: '<i class="fa fa-chevron-right"></i>'
            }
            //,
            //processing: '<img src="/images/loading.svg" class="dataTables_processing__loading" alt="Processing">'
        },
        lengthMenu:  [[50, 100, 250], [50, 100, 250]],
        order: [[ 3, "desc" ]],
        ajax: '/admin/ajax/get-error-data.html',
        columns: [
        	
            {   
        	    data: 'error_code'
            },
            {   
               data: 'error_message'
            },
            {   
               data: 'page_id'
            }
            ,
            {   
               data: 'date_added'
            }
            
        ],
        columnDefs: [
            {
            	
                targets: 0,
                name: "error_code",
                orderable: false,
                searchable : false
            },
            {
            	
                targets: 1,
                name: "error_message",
                orderable: false,
                searchable : false
            },
            {
            	
                targets: 2,
                name: "page_id",
                orderable: false,
                searchable : false
            },
            {
            	
                targets: 3,
                name: "date_added",
                orderable: true,
                searchable : false
            }
        ],
        error: function (xhr, error, code)
        {
            console.log(xhr);
            console.log(code);
        }
    });


});