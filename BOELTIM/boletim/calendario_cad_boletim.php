<!DOCTYPE html> 
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário de Boletins</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://pmrr.net/plugin_scriptcase/jquery_plugin/fullcalendar_3_4_0/fullcalendar.min.css" />

    <style>
        /* Custom styles for the calendar */
        body {
            background-color: #fff;
        }

        .sc-cal-page-container {
            display: flex;
            flex-direction: row;
        }

        .sc-cal-side-container {
            display: flex;
            flex-direction: column;
            min-width: 250px;
            max-width: 300px;
        }

        #calendar {
            max-width: 1200px;
            height: 800px;
            margin: 0 auto;
        }

        .legend-table {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="center-content">
    <div class="legend-table-container">
        <table class="legend-table">
            <tr>
                <td style="width: 20%; background-color: red; text-align: center; color: #ffffff;">Criado</td>
                <td style="width: 20%; background-color: DodgerBlue; text-align: center; color: #ffffff;">CONFERIR</td>
                <td style="width: 20%; background-color: Orange; text-align: center; color: #ffffff;">ASSINAR</td>
                <td style="width: 20%; background-color: green; text-align: center; color: #ffffff;">PRONTO</td>
                <td style="width: 20%; background-color: Rosybrown; text-align: center; color: #ffffff;">SEM BOLETIM</td>
            </tr>
        </table>
    </div>
    <div id="calendar"></div>
</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="boletimModal" tabindex="-1" aria-labelledby="boletimModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="boletimModalLabel">Cadastrar Boletim</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <iframe id="boletimIframe" src="" width="100%" height="600px" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- jQuery and FullCalendar JS -->
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://pmrr.net/plugin_scriptcase/fullcalendar/lib/moment.min.js"></script>
<script src="https://pmrr.net/plugin_scriptcase/jquery/js/jquery-ui.js"></script>
<script src="https://pmrr.net/plugin_scriptcase/jquery_plugin/fullcalendar_3_4_0/fullcalendar.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/locale/pt-br.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(function() {
        var boletimModal = new bootstrap.Modal(document.getElementById('boletimModal'), {
            keyboard: false
        });

        $('#calendar').fullCalendar({
            locale: 'pt-br', // Define a localidade para português brasileiro
            monthNames: [
                "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
            ],
            dayNamesShort: [
                "Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"
            ],
            allDayText: "Dia inteiro",
            buttonText: {
                today: "Hoje",
                month: "Mês",
                week: "Semana",
                day: "Dia",
                agenda: "Agenda",
                print: "Impressão",
                listMonth: "Agenda",
            },
            firstDay: 0,
            header: {
                left: 'prev,next today print',
                center: 'title',
                right: 'month,agendaWeek,agendaDay,listMonth'
            },
            editable: true,
            eventStartEditable: false,
            allDaySlot: true,
            noEventsMessage: "Não há eventos para exibir",
            events: 'json.php',
            dayClick: function(date) {
                var selectedDate = date.format('YYYY-MM-DD');
                // Define a URL para criação de um novo boletim com a data selecionada
                var url = 'cadastro_boletim.php?bole_data_publicacao=' + selectedDate;
                // Define o src do iframe
                $('#boletimIframe').attr('src', url);
                // Atualiza o título do modal
                $('#boletimModalLabel').text('Cadastrar Boletim para ' + selectedDate);
                // Abre o modal
                boletimModal.show();
            },
            eventClick: function(calEvent) {
                var bole_cod = calEvent.id;
                var selectedDate = calEvent.start.format('YYYY-MM-DD');
                // Define a URL para edição do boletim existente
                var url = 'cadastro_boletim.php?bole_cod=' + bole_cod;
                // Define o src do iframe
                $('#boletimIframe').attr('src', url);
                // Atualiza o título do modal
                $('#boletimModalLabel').text('Editar Boletim');
                // Abre o modal
                boletimModal.show();
            }
        });
    });
</script>

</body>
</html>
