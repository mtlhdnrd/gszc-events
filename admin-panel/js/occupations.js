class Occupation {
    constructor(id, name) {
        this.id = id;
        this.name = name;
        this.description = "Iskolai foglalkozás";
    }
    update(newData) {
        if (newData.name) this.name = newData.name;
        if (newData.description) this.description = newData.description;
    }
    toJson() {
        return {
            name: this.name,
            description: this.description
        };
    }
}

class OccupationContainer {
    constructor() {
        this.occupations = [];
    }

    addOccupation(occupation) {
        if (!(occupation instanceof Occupation)) {
            throw new Error("Invalid occupation object. Must be an instance of Occupation.");
        }
        this.occupations.push(occupation);
    }

    getOccupationById(id) {
        return this.occupations.find(occupation => occupation.id === id);
    }

    getAllOccupations() {
        return this.occupations;
    }

    removeOccupationById(id) {
        const initialLength = this.occupations.length;
        this.occupations = this.occupations.filter(occupation => occupation.id !== id);
        return this.occupations.length < initialLength;
    }
    updateOccupation(id, newData) {
        const occupation = this.getOccupationById(id);
        if (occupation) {
            occupation.update(newData);
            return true;
        }
        return false;
    }
}

// --- EventOccupation Class and Container ---
class EventOccupation {
    constructor(eventOccupationId, eventId, eventName, occupationId, occupationName, mentorCount, hoursCount, busyness) {
        this.eventOccupationId = eventOccupationId; // Added eventOccupationId
        this.eventId = eventId;
        this.eventName = eventName;
        this.occupationId = occupationId;
        this.occupationName = occupationName;
        this.mentorCount = mentorCount;
        this.hoursCount = hoursCount;
        this.busyness = busyness;
    }
    toJson() {
        return {
            event_workshop_id: this.eventOccupationId,
            evenet_id: this.eventId,
            workshop_id: this.occupationId,
            hours_count: this.hoursCount,
            mentor_count: this.mentorCount,
            busyness: this.busyness == "magas" ? "high" : "low"

        };
    }
}

class EventOccupationContainer {
    constructor() {
        this.eventOccupations = [];
    }

    addEventOccupation(eventOccupation) {
        if (!(eventOccupation instanceof EventOccupation)) {
            throw new Error("Invalid eventOccupation object. Must be an instance of EventOccupation.");
        }
        this.eventOccupations.push(eventOccupation);
    }

    getAllEventOccupations() {
        return this.eventOccupations;
    }

    getEventOccupationById(eventOccupationId) { // Added get by ID
        return this.eventOccupations.find(eo => eo.eventOccupationId === eventOccupationId);
    }

    removeEventOccupationById(eventOccupationId) { // Changed to remove by ID
        const initialLength = this.eventOccupations.length;
        this.eventOccupations = this.eventOccupations.filter(eo => eo.eventOccupationId !== eventOccupationId);
        return this.eventOccupations.length < initialLength;
    }
}

// --- Global Instances ---
const occupationContainer = new OccupationContainer();
const eventOccupationContainer = new EventOccupationContainer();

$(document).ready(function () {

    // --- Event Handlers (using event delegation) ---
    $('#occupationsTable tbody').on('click', '.edit-button', handleEditOccupationClick);
    $('#occupationsTable tbody').on('click', '.cancel-button', handleCancelOccupationClick);
    $('#occupationsTable tbody').on('click', '.delete-button', handleDeleteOccupationClick);
    $('#eventOccupationsTable tbody').on('click', '.delete-event-occupation-button', handleDeleteEventOccupationClick);

    $(document).on('eventAdded', loadEventsIntoSelect); //TODO: REFACTOR get a eventContainer parameter, so it doesnt need to call ajax again in the function
    $(document).on('workshopAdded', loadOccupations);

    $('#addOccupationToEventBtn').click(handleAddOccupationToEvent);
    $('#newOccupationEventBtn').click(showAddOccupationEventForm);
    $('#addOccupationBtn').click(handleAddOccupation);



    // --- Load Initial Data ---
    loadOccupations();
    loadEventsIntoSelect();
    loadEventOccupations();


    // --- Function Definitions ---

    function handleAddOccupation() {
        let occupationName = $('#newOccupationName').val().trim();

        if (!occupationName) {
            alert('Kérlek adj meg egy foglalkozás nevet!');
            return;
        }

        let maxId = 0;
        occupationContainer.getAllOccupations().forEach(occupation => {
            if (occupation.id > maxId) {
                maxId = occupation.id;
            }
        });
        let newId = maxId + 1;

        const newOccupation = new Occupation(newId, occupationName);

        $('#newOccupationName').val('');
        $.ajax({
            type: "POST",
            url: "../backend/api/workshops/add_workshop.php",
            data: newOccupation.toJson(),
            success: function (data) {
                occupationContainer.addOccupation(newOccupation);
                $(document).trigger('workshopAdded', [newOccupation]);
            },
            error: function (xhr, status, error) {
                console.error("Hiba a foglalkozás frissítése közben:", xhr, status, error);
            }
        });
    }
    function handleEditOccupationClick() {
        let row = $(this).closest('tr');
        if ($(this).text() === 'Szerkesztés') {
            startEditingOccupation(row);
        } else {
            finishEditingOccupation(row);
        }
    }

    function startEditingOccupation(row) {
        row.find('input.occupation-data').removeAttr('readonly');
        row.find('.edit-button').text('Mentés');
        let cancelBtn = $('<button class="btn btn-secondary btn-sm cancel-button">Mégse</button>');
        row.find('.edit-button').after(cancelBtn);
        row.find('input.occupation-data').each(function () {
            $(this).data('original-value', $(this).val());
        });
    }

    function finishEditingOccupation(row) {
        let occupationId = parseInt(row.find('.occupation-id').text(), 10);
        let updatedData = {
            name: row.find('input[data-field="name"]').val(),
            description: 'Iskolai foglakozás'
        };
        if (!updatedData.name) {
            alert("A foglalkozás neve nem lehet üres!");
            return;
        }
        if (occupationContainer.updateOccupation(occupationId, updatedData)) {
            $.ajax({
                type: "POST",
                url: "../backend/api/workshops/update_workshop.php",
                data: {
                    workshop_id: occupationId,
                    ...updatedData
                },
                success: function (data) {
                    console.log("Occupation updated on server:", data);
                    row.find('input.occupation-data').attr('readonly', true);
                    row.find('.edit-button').text('Szerkesztés');
                    row.find('.cancel-button').remove();
                    loadOccupations();
                },
                error: function (xhr, status, error) {
                    console.error("Hiba a foglalkozás frissítése közben:", xhr, status, error);
                    let errorMessage = "Ismeretlen hiba történt.";

                    if (xhr.status === 400) {
                        try {
                            let errorData = JSON.parse(xhr.responseText);
                            errorMessage = errorData.message ? errorData.message : "Érvénytelen adatok lettek elküldve.";
                            if (errorData && errorData.errors) {
                                errorMessage = errorData.errors.join("<br>");
                            }
                        } catch (e) {
                            errorMessage = "Érvénytelen kérés.";
                        }
                    } else if (xhr.status === 404) {
                        errorMessage = "A frissítendő foglalkozás nem található.";
                    } else if (xhr.status === 500) {
                        errorMessage = "Szerverhiba történt. Kérlek, próbáld újra később.";
                    }
                    alert("Hiba: " + errorMessage);
                }
            });
        } else {
            console.error("Occupation with ID " + occupationId + " not found for update.");
            alert("Occupation with ID " + occupationId + " not found for update.");
        }
    }
    function handleCancelOccupationClick() {
        let row = $(this).closest('tr');
        row.find('input.occupation-data').each(function () {
            $(this).val($(this).data('original-value')).attr('readonly', true);
        });
        $(this).remove();
        row.find('.edit-button').text('Szerkesztés');
    }

    function handleDeleteOccupationClick() {
        let row = $(this).closest('tr');
        let occupationId = parseInt(row.find('.occupation-id').text(), 10);
        if (confirm('Biztosan törölni szeretnéd?')) {
            $.ajax({
                type: "DELETE",
                url: `../backend/api/workshops/delete_workshop.php?workshop_id=${occupationId}`,
                success: function (response) {
                    if (occupationContainer.removeOccupationById(occupationId)) {
                        row.remove();
                        loadOccupations();
                        loadOccupationsIntoSelect();
                    } else {
                        console.error("Occupation with ID " + occupationId + " not found locally.");
                        alert("Foglalkozás nem található.");
                    }

                },
                error: function (xhr, status, error) {
                    console.error("Hiba a foglalkozás törlése közben:", xhr.responseText, status, error);
                    alert("Hiba a foglalkozás törlése közben: " + xhr.responseText);
                }
            });
        }
    }

    function handleDeleteEventOccupationClick() {
        let row = $(this).closest('tr');
        if (confirm('Biztosan törölni szeretnéd?')) {
            let eventOccupationId = parseInt(row.find('.event-occupation-id').text(), 10); // Get by ID

            // AJAX call to delete the event_workshop
            $.ajax({
                type: "DELETE",
                url: `../backend/api/event_workshops/delete_event_workshop.php?event_workshop_id=${eventOccupationId}`,
                success: function (response, textStatus, jqXHR) { 
                    if (jqXHR.status === 204) {
                        if (eventOccupationContainer.removeEventOccupationById(eventOccupationId)) {
                            row.remove();
                            console.log("Event-Occupation removed");
                        } else {
                            console.error("Event-Occupation not found locally for deletion.");
                            alert("Event-Occupation deleted from the server, but not found locally. Please refresh.");
                        }

                    } else {
                        console.error("Unexpected success status:", jqXHR.status);
                        alert("An unexpected error occurred.  Status: " + jqXHR.status);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    // Handle errors
                    console.error("Error deleting event workshop:", textStatus, errorThrown, jqXHR.status, jqXHR.responseText);
                    if (jqXHR.status === 404) {
                        alert("Event-Occupation not found on the server.");
                        if (eventOccupationContainer.removeEventOccupationById(eventOccupationId)) {
                            row.remove(); 
                        }
                    } else if (jqXHR.status === 400) {
                        alert("Invalid request.  Please check the data.");
                    }
                    else {
                        alert("Failed to delete event workshop.  Error: " + jqXHR.status);
                    }
                }
            });
        }
    }

    function loadOccupations() {
        $.ajax({
            url: '../backend/api/workshops/get_workshops.php',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                $('#occupationsTable tbody').empty();
                occupationContainer.occupations = [];
                data.forEach(function (occupationData) {
                    const occupation = new Occupation(occupationData.workshop_id, occupationData.name, occupationData.description);
                    occupationContainer.addOccupation(occupation);
                    addOccupationRow(occupation);
                    loadOccupationsIntoSelect();
                });
            },
            error: function (xhr, status, error) {
                console.error("Error loading occupations:", status, error);
                alert("Hiba történt a foglalkozások betöltésekor. Kérlek próbáld újra később.");
            }
        });
    }
    function loadEventsIntoSelect() {
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
                var events = eventContainer.getAllEvents(); // Use the existing eventContainer
                let options = '<option value="">Válassz eseményt</option>';
                events.forEach(event => {
                    options += `<option value="${event.id}">${event.name} - ${event.date}</option>`;
                });
                $('#eventSelect').html(options);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching events:", error);
                alert("Hiba történt az események betöltésekor. Kérlek, próbáld újra később.");
            }
        });
    }
    function loadOccupationsIntoSelect() {
        let options = '<option value="">Válassz foglalkozást</option>';
        occupationContainer.getAllOccupations().forEach(occupation => {
            options += `<option value="${occupation.id}">${occupation.name}</option>`;
        });
        $('#occupationSelectEvent').html(options);
    }

    function loadEventOccupations() {
        $.ajax({
            url: "../backend/api/event_workshops/get_event_workshops.php",
            type: "GET",
            dataType: "json",
            success: function (data) {
                $('#eventOccupationsTable tbody').empty();
                eventOccupationContainer.eventOccupations = [];

                data.forEach(function (eventWorkshop) {
                    const eo = new EventOccupation(
                        eventWorkshop.event_workshop_id,
                        eventWorkshop.event_id,
                        eventWorkshop.event_name,
                        eventWorkshop.workshop_id,
                        eventWorkshop.workshop_name,
                        eventWorkshop.number_of_mentors_required,
                        eventWorkshop.max_workable_hours
                    );

                    eventOccupationContainer.addEventOccupation(eo);
                    addEventOccupationRow(eo);
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Error loading event workshops:", textStatus, errorThrown);
                alert("Failed to load event workshops. Please try again later.");
            }
        });
    }

    // -- Add Row Functions --
    function addOccupationRow(occupation) {
        let row = $('<tr>');
        row.append('<td hidden><span class="occupation-id">' + occupation.id + '</span></td>');
        row.append($('<td>').text(occupation.id)); // Display ID
        row.append($('<td>').append($('<input type="text" class="form-control occupation-data" data-field="name" readonly>').val(occupation.name)));

        let actionsCell = $('<td>');
        let editButton = $('<button class="btn btn-primary btn-sm edit-button">Szerkesztés</button>');
        let deleteButton = $('<button class="btn btn-danger btn-sm delete-button">Törlés</button>');
        actionsCell.append(editButton, deleteButton);

        row.append(actionsCell);
        $('#occupationsTable tbody').append(row);
    }

    function addEventOccupationRow(eventOccupation) {
        let row = $('<tr>');
        // Add the hidden ID cell:
        row.append('<td hidden><span class="event-occupation-id">' + eventOccupation.eventOccupationId + '</span></td>');
        row.append($('<td>').text(eventOccupation.eventName));
        row.append($('<td>').text(eventOccupation.occupationName));
        row.append($('<td>').text(eventOccupation.mentorCount));
        row.append($('<td>').text(eventOccupation.hoursCount));

        let deleteButton = $('<button class="btn btn-danger btn-sm delete-event-occupation-button">Törlés</button>');
        row.append($('<td>').append(deleteButton));

        $('#eventOccupationsTable tbody').append(row);
    }

    // -- Form Show/Hide Functions --

    function showAddOccupationEventForm() {
        loadEventsIntoSelect();
        loadOccupationsIntoSelect();
        $('#addOccupationEventForm').show();
    }
    function handleAddOccupationToEvent() {
        let eventId = $('#eventSelect').val();
        let occupationId = $('#occupationSelectEvent').val();
        let mentorCount = $('#mentorCount').val();
        let hoursCount = $('#hoursCount').val();
        let busyness = $('#busyness').val();
    
        if (!eventId || !occupationId || !mentorCount || !hoursCount) {
            alert('Kérlek válassz eseményt, foglalkozást, és add meg a szükséges mentorok számát és óraszámot!');
            return;
        }
        if (isNaN(parseInt(hoursCount)) || parseInt(hoursCount) <= 0) {
            alert("Az órák száma egy 0-nál nagyobb szám kell, hogy legyen!");
            return;
        }
        if (isNaN(parseInt(mentorCount)) || parseInt(mentorCount) <= 0) {
            alert('A szükséges mentorok száma egy 0-nál nagyobb szám kell, hogy legyen!');
            return;
        }
    
        let event = eventContainer.getEventById(parseInt(eventId));
        let occupation = occupationContainer.getOccupationById(parseInt(occupationId));
        if (!event || !occupation) {
            console.error("Event or occupation not found")
            return;
        }
    
        // Create the EventOccupation object *before* the AJAX call
        const eventOccupation = new EventOccupation(
            null, 
            parseInt(eventId),
            event.name, 
            parseInt(occupationId),
            occupation.name, 
            parseInt(mentorCount),
            parseInt(hoursCount)
        );
    
        $.ajax({
            url: "../backend/api/event_workshops/add_event_workshop.php",
            type: "POST",
            data: {
                event_id: eventOccupation.eventId,  
                workshop_id: eventOccupation.occupationId,
                max_workable_hours: eventOccupation.hoursCount,
                number_of_mentors_required: eventOccupation.mentorCount
            },
            success: function(response) {
                const newEventWorkshopId = parseInt(response);
                eventOccupation.eventOccupationId = newEventWorkshopId;
    
                eventOccupationContainer.addEventOccupation(eventOccupation);
                addEventOccupationRow(eventOccupation);
                $(document).trigger('eventWorkshopAdded', [eventOccupationContainer]);
                console.log("Event-Occupation added with ID:", newEventWorkshopId);    
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error adding event workshop:", textStatus, errorThrown, jqXHR.responseText);
                alert("Failed to add event workshop.  Error: " + jqXHR.status + " - " + jqXHR.responseText); // Show detailed error
            }
        });
    }
    return {
        Occupation: Occupation,
        OccupationContainer: OccupationContainer,
        occupationContainer: occupationContainer,
        EventOccupation: EventOccupation,
        EventOccupationContainer: EventOccupationContainer,
        eventOccupationContainer: eventOccupationContainer
    };
});
