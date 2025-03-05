$(document).ready(function () { // Important: Wrap in $(document).ready()

    // --- Event Handling (using event delegation) ---
    $('#esemenyekTabla tbody').on('click', '.edit-button', handleEditClick);
    $('#esemenyekTabla tbody').on('click', '.cancel-button', handleCancelClick);
    $('#esemenyekTabla tbody').on('click', '.delete-button', handleDeleteClick);
    $('#newEventBtn').click(showNewEventModal);
    $('#saveNewEventBtn').click(handleSaveNewEvent);

    // --- Function Definitions ---
    function handleEditClick() {
        let row = $(this).closest('tr');

        if ($(this).text() === 'Szerkesztés') {
            startEditing(row);
        } else {
            finishEditing(row);
        }
    }
    function addNewEvent() {
        //new event adding logic
    }
    function startEditing(row) {
        row.find('input.event-data').removeAttr('readonly');
        row.find('.edit-button').text('Mentés');

        let cancelBtn = $('<button class="btn btn-secondary btn-sm cancel-button">Mégse</button>');
        row.find('.edit-button').after(cancelBtn);

        // Store original values
        row.find('input.event-data').each(function () {
            $(this).data('original-value', $(this).val());
        });
    }

    function finishEditing(row) {
        row.find('input.event-data').attr('readonly', true);
        row.find('.edit-button').text('Szerkesztés');
        row.find('.cancel-button').remove();
    }

    function handleCancelClick() {
        let row = $(this).closest('tr');
        row.find('input.event-data').each(function () {
            $(this).val($(this).data('original-value')).attr('readonly', true);
        });
        $(this).remove(); // Remove the Cancel button itself
        row.find('.edit-button').text('Szerkesztés');
    }

    function handleDeleteClick() {
        let row = $(this).closest('tr');
        if (confirm('Biztosan törölni szeretnéd?')) {
            row.remove();  // Placeholder for now.  Later, add AJAX.
        }
    }
    function showNewEventModal() {
        $('#newEventForm')[0].reset();
        $('#newEventModal').modal('show');
    }

    function handleSaveNewEvent() {
        let eventData = {
            name: $('#eventName').val(),
            date: $('#eventDate').val(),
            location: $('#eventLocation').val(),
            loadLevel: $('#eventLoadLevel').val()
        };
        // Input validation
        if (!eventData.name || !eventData.date || !eventData.location || !eventData.loadLevel) {
            alert('Kérlek tölts ki minden mezőt!');
            return;
        }
        // Basic date validation (you might want a more robust solution)
        if (isNaN(Date.parse(eventData.date))) {
            alert('Érvénytelen dátum formátum!');
            return;
        }
        //AJAX to the API

        addNewEvent(eventData);
    }

    function loadEvents() {
        //load events from the database
    }
    function addNewEvent(eventData) { }
});