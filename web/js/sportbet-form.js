jQuery.datetimepicker.setLocale('fr');
jQuery('.js-datepicker').datetimepicker({
    format:'d/m/Y H:i',
    //value: new Date(),
    step: 15,
    startDate:new Date(),
    dayOfWeekStart: 1
});


function resetChampionship(e) {
    $('.championship').dropdown('clear');
    e.preventDefault();
}

$('#clearChampionship').bind('click', resetChampionship);

$('.dropdown')
    .dropdown()
;

$('#forecastbundle_sportbet_sport').bind(
    {
        change: function() {
            $('.championship').dropdown('clear');
            var sportId = $('#forecastbundle_sportbet_sport option:selected').val()
            $.ajax("/api/championship/" + sportId, {
                success: function(data) {
                    var dropdown = $('#forecastbundle_sportbet_championship');
                    dropdown.empty();
                    $.each(JSON.parse(data), function(index, item) {
                        dropdown.append(
                            $('<option>', {
                                value: item.id,
                                text: item.name
                            }, '</option>')
                        );
                    });
                }
            })
        }
    }
)