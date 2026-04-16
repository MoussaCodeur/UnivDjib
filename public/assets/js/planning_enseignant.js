// Mise à jour de la date et de l'heure en temps réel
function updateDateTime() {
    const dateElement = document.getElementById("date-display");
    const timeElement = document.getElementById("time-display");

    const now = new Date();

    // Format de la date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    let dateStr = now.toLocaleDateString('fr-FR', options);
    dateElement.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

    // Format de l'heure
    const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    timeElement.textContent = timeStr;
}

document.addEventListener('DOMContentLoaded', function () {
    // Démarrer l'horloge
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Initialiser DataTables
    const table = $('#planningsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        },
        responsive: true,
        order: [[2, 'desc']],
        pageLength: 6,
        lengthMenu: [[6, 12, 18, -1], [6, 12, 18, "Tous"]],
        dom: '<"top"<"left-col"l><"center-col"f><"right-col"B>>rt<"bottom"ip>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                className: 'btn-export'
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn-export'
            }
        ],
        initComplete: function () {
            $('.dataTables_length select').addClass('form-filter');
            $('.dataTables_filter input').addClass('form-filter');
        }
    });

    // Filtres personnalisés
    $('#semestreFilter').on('change', function () {
        table.column(1).search(this.value).draw();
    });

    $('#typeFilter').on('change', function () {
        table.column(0).search(this.value).draw();
    });

    $('#dateFilter').on('change', function () {
        const days = parseInt(this.value);
        if (isNaN(days)) {
            table.columns().search('').draw();
            return;
        }

        const date = new Date();
        date.setDate(date.getDate() - days);
        const isoDate = date.toISOString().split('T')[0];

        table.column(2).search(isoDate).draw();
    });

    $('#resetFilters').on('click', function () {
        $('#semestreFilter, #typeFilter, #dateFilter').val('');
        table.columns().search('').draw();
    });

    // Styliser les boutons d'export
    $('.btn-export').addClass('btn-reset').removeClass('dt-button');
});
