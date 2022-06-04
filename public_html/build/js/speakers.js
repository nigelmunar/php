$(function() 
{
    $('.todo-list').sortable(
        {
            placeholder: 'sort-highlight',
            handle: '.handle',
            forcePlaceholderSize: true,
            update: function(event, ui)
            {
                $.post('/admin/ajax/update-speakers.html', $(this).sortable('serialize'),
                    function(json)
                    {
                        window.location.href = window.location.href;
                    } 
                ); 

            },
        }
    );
});