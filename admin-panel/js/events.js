class Event {
    constructor(id, name, date, location, loadLevel, status) {
        this.id = id;
        this.name = name;
        this.date = date;
        this.location = location;
        this.loadLevel = loadLevel;
        this.status = status;
    }

    getFormattedDate() {
        return this.date;
    }

    update(newData) {
        if (newData.name) this.name = newData.name;
        if (newData.date) this.date = newData.date;
        if (newData.location) this.location = newData.location;
        if (newData.loadLevel) this.loadLevel = newData.loadLevel;
        if (newData.status) this.status = newData.status;
    }
    toJson() {
        return {
            name: this.name,
            date: this.date,
            location: this.location,
            status: this.status,
            busyness: this.loadLevel == "magas" ? "high" : "low"
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
const event1 = new Event(1, "Dance Rehearsal", "2024-03-15", "School Hall", "high", "ready");
const event2 = new Event(2, "Poetry Slam", "2024-03-22", "Gym", "low", "pending");
eventContainer.addEvent(event1);
eventContainer.addEvent(event2);
const retrievedEvent = eventContainer.getEventById(1);


$(document).ready(function () {

    $('#newEventBtn').click(showNewEventModal);
    $('#saveNewEventBtn').click(handleSaveNewEvent);
    loadEvents();
    setupEventDelegation();

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
        // Get the updated data from the input fields
        let updatedData = {
            name: row.find('input[data-field="name"]').val(),
            date: row.find('input[data-field="date"]').val(),
            location: row.find('input[data-field="location"]').val(),
            loadLevel: row.find('input[data-field="loadLevel"]').val(),
            status: row.find('select[data-field="status"]').val()
        };

        // Get the event ID from the hidden span
        let eventId = parseInt(row.find('.event-id').text(), 10);

        // Update the event in the container
        if (eventContainer.updateEvent(eventId, updatedData)) {
            console.log("Event updated in container.  Ready to save to server:", eventId, updatedData);
            //TODO: Make AJAX call for update
            row.find('input.event-data, select.event-status').attr('readonly', true);
            row.find('select.event-status').prop('disabled', true);
            row.find('.edit-button').text('Szerkesztés');
            row.find('.cancel-button').remove();
            updateRowVisuals(row, updatedData.status);
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
                type: "POST",
                url: "../backend/api/events/delete_event.php",
                dataType: 'json',
                data: {'event_id':id},
                success: function(data){
                    row.remove();
                    console.log(data.message);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching events:", error);
                    alert("Hiba történt az események betöltésekor. Kérlek, próbáld újra később.");
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
            loadLevel: $('#eventLoadLevel').val(),
            status: $('#eventStatus').val()
        };
        // Input validation
        if (!eventData.name || !eventData.date || !eventData.location || !eventData.loadLevel) {
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
                        eventData.busyness,
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
        let row = $('<tr>');

        // --- Hidden ID Cell ---
        let hiddenIdCell = $('<td hidden>').append($('<span class="event-id">' + event.id + '</span>'));
        row.append(hiddenIdCell);

        // --- Data Cells ---
        row.append($('<td>').append($('<input type="text" class="form-control event-data" data-field="name" readonly>').val(event.name)));
        row.append($('<td>').append($('<input type="text" class="form-control event-data" data-field="date" readonly>').val(event.date)));
        row.append($('<td>').append($('<input type="text" class="form-control event-data" data-field="location" readonly>').val(event.location)));
        row.append($('<td>').append($('<input type="text" class="form-control event-data" data-field="loadLevel" readonly>').val(event.loadLevel)));

        // --- Status Dropdown (Corrected) ---
        let statusSelect = $('<select class="form-control event-status" data-field="status" disabled></select>');
        // Helper function to create option elements
        function createOption(value, text, isSelected) {
            let option = $('<option>').val(value).text(text);
            if (isSelected) {
                option.attr('selected', 'selected');
            }
            return option;
        }

        statusSelect.append(createOption('pending', 'Függőben', event.status === 'pending'));
        statusSelect.append(createOption('ready', 'Sikeres', event.status === 'ready'));
        statusSelect.append(createOption('failed', 'Sikertelen', event.status === 'failed'));
        row.append($('<td>').append(statusSelect)); // Add the status select

        // --- Actions Cell ---
        let actionsCell = $('<td>');
        let editButton = $(`<button class="btn btn-primary btn-sm edit-button" id="edit-event-btn-${event.id}">Szerkesztés</button>`);
        let deleteButton = $(`<button class="btn btn-danger btn-sm delete-button" id="delete-event-btn-${event.id}">Törlés</button>`);
        actionsCell.append(editButton, deleteButton);
        row.append(actionsCell);

        // --- Add Row and Set Color ---
        $('#eventsTable tbody').append(row);
        updateRowVisuals(row, event.status); // Set initial color based on status
    }
    function updateRowVisuals(row, status) {
        // Remove existing status classes from ALL td elements within the row
        row.find('td').removeClass('status-pending status-ready status-failed');

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
        $.ajax({
            type: "POST",
            url: "../backend/api/events/add_event.php",
            dataType: 'json',
            data: newEvent.toJson(),
            success: function (data, textStatus, xhr) {
                console.log("Sikeres hozzáadás:", data);
                alert("Esemény sikeresen hozzáadva");
                let newId = maxId + 1;
                const newEvent = new Event(newId, eventData.name, eventData.date, eventData.location, eventData.loadLevel, eventData.status);
                eventContainer.addEvent(newEvent);
                addEventRow(newEvent); // Add to DOM

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
        $('#eventsTable tbody').on('click', '.edit-button', handleEditClick);
        $('#eventsTable tbody').on('click', '.cancel-button', handleCancelClick);
        $('#eventsTable tbody').on('click', '.delete-button', handleDeleteClick);
    }
    return {
        EventContainer: EventContainer, // Export the *class* itself
        Event: Event,
        eventContainer: eventContainer
    };
});