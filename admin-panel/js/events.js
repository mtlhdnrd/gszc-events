class Event {
    constructor(id, name, date, location, loadLevel) {
        this.id = id;
        this.name = name;
        this.date = date;
        this.location = location;
        this.loadLevel = loadLevel;
    }

    getFormattedDate() {
        return this.date;
    }

    update(newData) {
        if (newData.name) this.name = newData.name;
        if (newData.date) this.date = newData.date;
        if (newData.location) this.location = newData.location;
        if (newData.loadLevel) this.loadLevel = newData.loadLevel;
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
const eventContainer = new EventContainer();

const event1 = new Event(1, "Dance Rehearsal", "2024-03-15", "School Hall", "high");
const event2 = new Event(2, "Poetry Slam", "2024-03-22", "Gym", "low");
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
            row.remove();  //TODO: Placeholder for now.  Later, add AJAX.
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
        // Basic date validation
        if (isNaN(Date.parse(eventData.date))) {
            alert('Érvénytelen dátum formátum!');
            return;
        }
        addNewEvent(eventData);
    }

    function loadEvents() {
        $('#eventsTable tbody').empty();
        eventContainer.getAllEvents().forEach(function(event) {
            addEventRow(event);
        });

    }

      function addEventRow(event) {
        let row = $('<tr>');
        let nameInput = $('<input type="text" class="form-control event-data" data-field="name" readonly>').val(event.name);
        let dateInput = $('<input type="text" class="form-control event-data" data-field="date" readonly>').val(event.date);
        let locationInput = $('<input type="text" class="form-control event-data" data-field="location" readonly>').val(event.location);
        let loadLevelInput = $('<input type="text" class="form-control event-data" data-field="loadLevel" readonly>').val(event.loadLevel);
         let hiddenIdCell = $('<td class="hidden-data">').append($('<span class="event-id">' + event.id + '</span>'));
        row.append(hiddenIdCell);
        row.append($('<td>').append(nameInput));
        row.append($('<td>').append(dateInput));
        row.append($('<td>').append(locationInput));
        row.append($('<td>').append(loadLevelInput));

        let actionsCell = $('<td>');
        let editButton = $(`<button class="btn btn-primary btn-sm edit-button" id="edit-event-btn-${event.id}">Szerkesztés</button>`);
        let deleteButton = $(`<button class="btn btn-danger btn-sm delete-button" id="delete-event-btn-${event.id}">Törlés</button>`);

        actionsCell.append(editButton, deleteButton);
        row.append(actionsCell);
        $('#eventsTable tbody').append(row);
    }

    function addNewEvent(eventData) {
        //Find next available ID
        let maxId = 0;
         eventContainer.getAllEvents().forEach(function(event) {
            if (event.id > maxId) {
                maxId = event.id;
            }
        });
        let newId = maxId + 1;
        const newEvent = new Event(newId, eventData.name, eventData.date, eventData.location, eventData.loadLevel);
        eventContainer.addEvent(newEvent);
        addEventRow(newEvent); // Add to DOM
        $('#newEventModal').modal('hide');
        $('#newEventForm')[0].reset();
        alert("Esemény sikeresen hozzáadva");
    }

      function setupEventDelegation() {
        $('#eventsTable tbody').on('click', '.edit-button', handleEditClick);
        $('#eventsTable tbody').on('click', '.cancel-button', handleCancelClick);
        $('#eventsTable tbody').on('click', '.delete-button', handleDeleteClick);
    }
});