class Event {
    constructor(id, name, date, location, status) {
        this.id = id;
        this.name = name;
        this.date = date;
        this.location = location;
        this.status = status;
    }

    getFormattedDate() {
        return this.date;
    }

    update(newData) {
        if (newData.name) this.name = newData.name;
        if (newData.date) this.date = newData.date;
        if (newData.location) this.location = newData.location;
        if (newData.status) this.status = newData.status;
    }
    toJson() {
        return {
            name: this.name,
            date: this.date,
            location: this.location,
            status: this.status,
    };
    }
}
class EventContainer {
    constructor() {
        this.events = []; // Array to store Event objects
    }

    addEvent(event) {
        if (!(event instanceof Event)) {
            throw new Error("Invalid event object. Must be an instance of Event.");
        }
        this.events.push(event);
    }

    getEventById(id) {
        return this.events.find(event => event.id === id);
    }
    //Find all events
    getAllEvents() {
        return this.events;
    }

    removeEventById(id) {
        const initialLength = this.events.length;
        this.events = this.events.filter(event => event.id !== id);
        // Check if an event was actually removed
        return this.events.length < initialLength;
    }

    updateEvent(id, newData) {
        const event = this.getEventById(id);
        if (event) {
            event.update(newData);
            return true; // Indicate successful update
        }
        return false; // Indicate event not found
    }
}
var eventContainer = new EventContainer();
const retrievedEvent = eventContainer.getEventById(1);


$(document).ready(function () {

    $('#newEventBtn').click(showNewEventModal);
    $('#saveNewEventBtn').click(handleSaveNewEvent);
    loadEvents();
    setupEventDelegation();

    function SendInvitationsByEvent(eventId) {
        console.log("Attempting to send invitations for event ID:", eventId);
        // TODO: Make api call for sending invitatins --> here starts the cooking
    }

    function handleEditClick() {
        let row = $(this).closest('tr');

        if ($(this).text() === 'Szerkesztés') {
            startEditing(row);
        } else {
            finishEditing(row);
        }
    }

    function startEditing(row) {
        row.find('input.event-data, select.event-status').removeAttr('readonly');
        row.find('select.event-status').prop('disabled', false);
        row.find('.edit-button').text('Mentés');

        let cancelBtn = $('<button class="btn btn-secondary btn-sm cancel-button">Mégse</button>');
        row.find('.edit-button').after(cancelBtn);

        // Store original values
        row.find('input.event-data, select.event-status').each(function () {
            $(this).data('original-value', $(this).val());
        });
    }
    function finishEditing(row) {
        let eventId = parseInt(row.find('.event-id').text(), 10);
        // Get the updated data from the input fields
        let updatedData = {
            event_id: eventId,
            name: row.find('input[data-field="name"]').val(),
            date: row.find('input[data-field="date"]').val(),
            location: row.find('input[data-field="location"]').val(),
            status: row.find('select[data-field="status"]').val()
        };

        // Update the event in the container
        if (eventContainer.updateEvent(eventId, updatedData)) {
            console.log("Event updated in container.  Ready to save to server:", updatedData);
            $.ajax({
                type: "POST",
                url: "../backend/api/events/update_event.php",
                dataType: 'json',
                data: updatedData,
                success: function(data) {
                    console.log("Event updated on server:", data);
                    alert("Esemény sikeresen frissítve!");
    
                    // Update UI *after* successful server update
                    row.find('input.event-data, select.event-status').attr('readonly', true);
                    row.find('select.event-status').prop('disabled', true);
                    row.find('.edit-button').text('Szerkesztés');
                    row.find('.cancel-button').remove();
                    updateRowVisuals(row, updatedData.status);
                },
                error: function(xhr, status, error) {
                    console.error("Hiba az esemény frissítése közben:", xhr, status, error);
                }
            });
            row.find('input.event-data, select.event-status').attr('readonly', true);
            row.find('select.event-status').prop('disabled', true);
            row.find('.edit-button').text('Szerkesztés');
            row.find('.cancel-button').remove();
            updateRowVisuals(row, updatedData.status, eventId);
        } else {
            console.error("Event with ID " + eventId + " not found for update."); // Handle error
            alert("Event with ID " + eventId + " not found for update.");
        }

    }

    function handleCancelClick() {
        let row = $(this).closest('tr');
        row.find('input.event-data, select.event-status').each(function () {
            $(this).val($(this).data('original-value')).attr('readonly', true);
        });
        row.find('select.event-status').prop('disabled', true);
        $(this).remove(); // Remove the Cancel button itself
        row.find('.edit-button').text('Szerkesztés');
        // Restore original color
        let originalStatus = row.find('.event-status').data('original-value');
        updateRowVisuals(row, originalStatus);
    }

    function handleDeleteClick() {
        let row = $(this).closest('tr');
        let id = parseInt(row.find('td:first-child').text());
        if (confirm('Biztosan törölni szeretnéd?')) {
            $.ajax({
                type: "DELETE",
                url: `../backend/api/events/delete_event.php?event_id=${id}`,
                success: function(data){
                    row.remove();
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching events:"+ error);
                }

            });
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
            status: $('#eventStatus').val()
        };
        // Input validation
        if (!eventData.name || !eventData.date || !eventData.location) {
            alert('Kérlek tölts ki minden mezőt!');
            return;
        }
        // Basic date validation
        if (isNaN(Date.parse(eventData.date))) {
            alert('Érvénytelen dátum formátum!');
            return;
        }
        addNewEvent(eventData);
    }

    function loadEvents() {
        $('#eventsTable tbody').empty();

        $.ajax({
            type: "GET",
            url: "../backend/api/events/get_events.php",
            dataType: 'json',
            success: function (data) {
                eventContainer.events = [];
                data.forEach(function (eventData) {
                    const event = new Event(
                        eventData.event_id,
                        eventData.name,
                        eventData.date,
                        eventData.location,
                        eventData.status
                    );
                    eventContainer.addEvent(event);
                });

                eventContainer.getAllEvents().forEach(function (event) {
                    addEventRow(event);
                });
            },
            error: function (xhr, status, error) {
                console.error("Error fetching events:", error);
                alert("Hiba történt az események betöltésekor. Kérlek, próbáld újra később.");
            }
        });
    }

    function addEventRow(event) {
        let row = $('<tr>'); // Create the row element

        // Hidden ID Cell (Crucial for referencing)
        let hiddenIdCell = $('<td hidden>').append($('<span class="event-id">' + event.id + '</span>'));
        row.append(hiddenIdCell);

        // Data Cells with Inputs
        row.append($('<td>').append($('<input type="text" class="form-control event-data" data-field="name" readonly>').val(event.name)));
        // Use type="date" for better UX if backend supports YYYY-MM-DD
        row.append($('<td>').append($('<input type="date" class="form-control event-data" data-field="date" readonly>').val(event.date)));
        row.append($('<td>').append($('<input type="text" class="form-control event-data" data-field="location" readonly>').val(event.location)));

        // Status Dropdown
        let statusSelect = $('<select class="form-control event-status" data-field="status" disabled></select>');
        const statuses = { pending: 'Függőben', ready: 'Sikeres', failed: 'Sikertelen' };
        for (const [value, text] of Object.entries(statuses)) {
            let option = $('<option>').val(value).text(text);
            if (event.status === value) {
                option.prop('selected', true);
            }
            statusSelect.append(option);
        }
        row.append($('<td>').append(statusSelect));

        // Actions Cell
        let actionsCell = $('<td class="actions-cell text-nowrap">'); // Prevent button wrapping
        let editButton = $(`<button class="btn btn-primary btn-sm edit-button mx-1" id="edit-event-btn-${event.id}">Szerkesztés</button>`);
        let deleteButton = $(`<button class="btn btn-danger btn-sm delete-button mx-1" id="delete-event-btn-${event.id}">Törlés</button>`);

        actionsCell.append(editButton, deleteButton);

        row.append(actionsCell);

        $('#eventsTable tbody').append(row);
        updateRowVisuals(row, event.status);
    }

    function updateRowVisuals(row, status, eventId) {
        // --- Background Color Update ---
        row.find('td').removeClass('status-pending status-ready status-failed'); // Clear existing status classes from TDs

        // Add the appropriate status class to ALL td elements within the row
        switch (status) {
            case 'pending':
                row.find('td').addClass('status-pending');
                break;
            case 'ready':
                row.find('td').addClass('status-ready');
                break;
            case 'failed':
                row.find('td').addClass('status-failed');
                break;
        }

        // --- Invite Button Visibility Update ---
        if (eventId === undefined || eventId < 0) {
             try { // Attempt to get ID if not passed - defensive coding
                 eventId = parseInt(row.find('.event-id').text(), 10);
                 if (isNaN(eventId)) throw new Error("NaN");
             } catch(e) {
                console.error("updateRowVisuals called without a valid eventId for row:", row);
                return;
             }
        }

        const actionsCell = row.find('td.actions-cell');
        const buttonId = `invite-event-btn-${eventId}`; // Unique ID for the button in this row
        const inviteButtonSelector = `#${buttonId}`;
        const existingInviteButton = actionsCell.find(inviteButtonSelector); // Find *within* this row's actions cell

        if (status === 'pending') {
            // If status is pending, ensure the button exists
            if (existingInviteButton.length === 0) {
                let inviteButton = $(`<button class="btn btn-info btn-sm invite-button ms-1" id="${buttonId}">Meghívók küldése</button>`);
                 let deleteButton = actionsCell.find('.delete-button');
                 if (deleteButton.length > 0) {
                     deleteButton.after(inviteButton);
                 } else {
                     // Fallback: append to the end if delete button wasn't found
                     actionsCell.append(inviteButton);
                 }
            }
        } else {
            if (existingInviteButton.length > 0) {
                existingInviteButton.remove(); 
            }
        }
    }

    function addNewEvent(eventData) {
        //Find next available ID
        let maxId = 0;
        eventContainer.getAllEvents().forEach(function (event) {
            if (event.id > maxId) {
                maxId = event.id;
            }
        });

        $('#newEventModal').modal('hide');
        $('#newEventForm')[0].reset();
        let newId = maxId + 1;
        const newEvent = new Event(newId, eventData.name, eventData.date, eventData.location, eventData.status);
        $.ajax({
            type: "POST",
            url: "../backend/api/events/add_event.php",
            dataType: 'json',
            data: newEvent.toJson(),
            success: function (data, textStatus, xhr) {
                console.log("Sikeres hozzáadás:", data);
                eventContainer.addEvent(newEvent);
                addEventRow(newEvent); 
                $(document).trigger('eventAdded', [newEvent]);
            },
            error: function (xhr, status, error) {
                console.error("Hiba történt az esemény hozzáadása közben:", xhr, status, error);
        
                let errorMessage = "Ismeretlen hiba történt."; // Default error message
        
                if (xhr.status === 0) {
                    errorMessage = "Nincs kapcsolat a szerverrel. Ellenőrizd az internetkapcsolatodat.";
                } else if (xhr.status === 400) { 
                    try {
                        let errorData = JSON.parse(xhr.responseText);
                        if (errorData && errorData.message) {
                            errorMessage = errorData.message;
                        } else {
                            errorMessage = "Érvénytelen adatok lettek elküldve.";
                        }
                    } catch (e) {
                        errorMessage = "Érvénytelen adatok lettek elküldve.";
                    }
                } else if (xhr.status === 404) {
                    errorMessage = "A kért erőforrás nem található (404).";
                } else if (xhr.status === 500) {
                    errorMessage = "Szerverhiba történt (500). Kérlek, próbáld újra később.";
                } else {
                    errorMessage = `Hiba történt: ${status} - ${error}`;
                }
        
                alert("Hiba az esemény hozzáadásakor: " + errorMessage);
            }
        });
    }

    function setupEventDelegation() {
        const tableBody = $('#eventsTable tbody'); // Cache selector

        tableBody.on('click', '.edit-button', handleEditClick);
        tableBody.on('click', '.cancel-button', handleCancelClick);
        tableBody.on('click', '.delete-button', handleDeleteClick);

        tableBody.on('click', '.invite-button', function() {
            const row = $(this).closest('tr');
            const eventId = parseInt(row.find('.event-id').text(), 10);

            if (isNaN(eventId)) {
                console.error("Could not get event ID for sending invitations from row:", row);
                alert("Hiba: Esemény azonosító nem található a meghívók küldéséhez.");
                return;
            }

            // Call the dedicated function
            SendInvitationsByEvent(eventId);
        });
    }
    return {
        EventContainer: EventContainer, // Export the *class* itself
        Event: Event,
        eventContainer: eventContainer
    };
});