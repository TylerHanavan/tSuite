document.addEventListener('DOMContentLoaded', function(){
    console.log('DOM fully loaded and parsed');
    $('.commit-retest').click(function(){
        $.ajax({
            url: '/api/v1/commit',
            data: JSON.stringify({ id: $(this).attr('commit-id'), do_retest_flag: true }),
            type: 'PUT',
            success: function(response) {
                console.log('Response:', response);
                //alert('Retest triggered successfully!');
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                //alert('Failed to trigger retest.');
            }
        });
    });
});