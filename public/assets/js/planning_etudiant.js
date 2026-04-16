// ✅ Affichage de la date et de l'heure en temps réel
function updateDateTime() {
    const dateElement = document.getElementById("date-display");
    const timeElement = document.getElementById("time-display");

    const now = new Date();

    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    let dateStr = now.toLocaleDateString('fr-FR', options);
    dateElement.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

    const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    timeElement.textContent = timeStr;
}

document.addEventListener('DOMContentLoaded', function () {
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ✅ Initialisation de DataTables
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
                text: '<i class="bi-file-earmark-excel"></i> Excel',
                className: 'btn-export'
            },
            {
                extend: 'pdf',
                text: '<i class="bi-file-earmark-pdf"></i> PDF',
                className: 'btn-export'
            }
        ],
        initComplete: function () {
            $('.dataTables_length select').addClass('form-filter');
            $('.dataTables_filter input').addClass('form-filter');
        }
    });

    // ✅ Filtres personnalisés
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

        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() - days);

        // Conversion de la date en chaîne compatible avec le format de DataTables si nécessaire
        const yyyy = targetDate.getFullYear();
        const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
        const dd = String(targetDate.getDate()).padStart(2, '0');
        const formattedDate = `${yyyy}-${mm}-${dd}`;

        table.column(2).search(formattedDate).draw();
    });

    $('#resetFilters').on('click', function () {
        $('#semestreFilter, #typeFilter, #dateFilter').val('');
        table.columns().search('').draw();
    });

    $('.btn-export').addClass('btn-reset').removeClass('dt-button');
});
