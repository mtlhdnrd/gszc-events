$(document).ready(function() {
    // --- Event Handlers ---
    $('#sendInvitationsBtn').click(sendInvitations);

    // --- Load Initial Data ---
    loadEventsIntoSelect('#eventSelectInvitations');

    // --- Function Definitions ---

    function loadEventsIntoSelect(eventId) {
        // TODO: Replace with AJAX
        var events = eventContainer.getAllEvents();
        let options = '<option value="">Válassz eseményt</option>';
        events.forEach(event => {
                options += `<option value="${event.id}">${event.name} - ${event.date}</option>`;
        });
         $(eventId).html(options);
    }

    function sendInvitations() {
        let eventId = parseInt($('#eventSelectInvitations').val(), 10);

        if (!eventId) {
            alert('Kérlek válassz egy eseményt!');
            return;
        }

        let eventOccupations = eventOccupationContainer.getAllEventOccupations().filter(eo => eo.eventId === eventId);

        if (eventOccupations.length === 0) {
            alert('Nincsenek foglalkozások hozzárendelve ehhez az eseményhez.');
            return;
        }

        eventOccupations.forEach(eventOccupation => {
            console.log("Sending invitation for EventOccupation:", eventOccupation);
            alert(`Sending invitation for Event ID: ${eventOccupation.eventId}, Occupation ID: ${eventOccupation.occupationId}.  (Replace with AJAX)`);
            // TODO: AJAX call to php/send_invitation.php
        });
    }
});