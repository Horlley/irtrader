window.initDataTable = function(tableId, options = {}) {

    let defaultOptions = {

        responsive: {
            details: {
                type: 'column',
                target: 0
            }
        },

        columnDefs: [
            {
                className: 'dtr-control',
                orderable: false,
                targets: 0
            }
        ],

        order: [[1, 'desc']],

        pageLength: 25,

        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
        }

    };

    let config = Object.assign({}, defaultOptions, options);

    return $(tableId).DataTable(config);
};