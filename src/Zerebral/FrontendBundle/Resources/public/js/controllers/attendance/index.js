$(document).ready(function(){
    $('.pick-date').datepicker().on('changeDate', function(e) {
        location.href = '?date=' + (e.date.getTime() - (e.date.getTimezoneOffset() * 60000))/ 1000;
    });

    $('th.status input[type="radio"]').change(function(e) {
        $('td input[type="radio"]').removeAttr('checked');
        $('td[statusName="'+$(e.target).val()+'"] input[type="radio"]').attr('checked', 'checked');

        $('th.status input[type="radio"]').removeAttr('checked');
        $(e.target).attr('checked', 'checked');
    });

    $('td.status input[type="radio"]').change(function(e) {
        var statusName = $(e.target).parents('td').attr('statusName');
        var studentsCount = $('table tr.student').length;
        if ($('td[statusName="' + statusName + '"] input:checked').length == studentsCount) {
            $('th[statusName="' + statusName + '"] input[type="radio"]').attr('checked', 'checked');
        } else {
            $('th input[type="radio"]').removeAttr('checked');
        }
    });

    $('.create-record').click(function() {
        $('.no-attendance').hide();
        $('.attendance-record').show();
    });

    $('.cancel-record').click(function() {
        $('.no-attendance').show();
        $('.attendance-record').hide();
    });
});