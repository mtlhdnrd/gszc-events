$(document).ready(function() {
    // --- Event Handlers ---
    $('#sendInvitationsBtn').click(sendInvitations);

    // --- Load Initial Data ---
    loadEventsIntoSelect('#eventSelectInvitations');

    // --- Function Definitions ---

    function loadEventsIntoSelect(eventId) {
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
                var events = eventContainer.getAllEvents();
                let options = '<option value="">Válassz eseményt</option>';
                events.forEach(event => {
                        options += `<option value="${event.id}">${event.name} - ${event.date}</option>`;
                });
                 $(eventId).html(options);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching events:", error);
                alert("Hiba történt az események betöltésekor. Kérlek, próbáld újra később.");
            }
        });
    }


    function sendInvitations() {
        let eventId = parseInt($('#eventSelectInvitations').val(), 10);
    
        if (!eventId) {
            alert('Kérlek válassz egy eseményt!');
            return;
        }
    
        // 1. Get all EventOccupations for the selected event.
        let eventOccupations = eventOccupationContainer.getAllEventOccupations().filter(eo => eo.eventId === eventId);
    
        if (eventOccupations.length === 0) {
            alert('Nincsenek foglalkozások hozzárendelve ehhez az eseményhez.');
            return;
        }
    
        // 2. Iterate through each EventOccupation
        eventOccupations.forEach(eventOccupation => {
            let eventOccupationId = eventOccupation.eventOccupationId;
            console.log("Evop: "+eventOccupation);
            // 3. AJAX call to get rankings for the *current* EventOccupation
            $.ajax({
                url: "../backend/api/rankings/get_ranking.php",
                type: "GET",
                data: { event_workshop_id: eventOccupationId },
                dataType: "json",
                success: function(rankingData) {
                    // rankingData is now an *array* of ranking objects for the *current* eventOccupation
    
                    // 4. Iterate through the rankings for the *current* EventOccupation
                    rankingData.forEach(ranking => {
                        let student = null;
                        studentContainer.getAllStudents().forEach(s => {
                            if (s.username === ranking.username) { //use ranking.username
                                student = s;
                            }
                        });
    
                        if (!student) {
                            console.error("Student with username", ranking.username, "not found.");
                            return;
                        }
    
                        // 5. AJAX call to add_invitation.php for the *current* ranking
                        $.ajax({
                            url: "../backend/api/student_invitations/add_student_invitation.php", 
                            type: "POST",
                            data: {
                                event_workshop_id: ranking.event_workshop_id,
                                user_id: student.id,
                                ranking_number: ranking.ranking_number,
                                status: "pending"
                            },
                            success: function(invitationResponse) {
                                console.log(`Invitation sent for ranking ID: ${ranking.ranking_id}, student: ${student.username}, eventOccupation: ${ranking.event_workshop_id}`);
                            },
                            error: function(invitationJqXHR, invitationTextStatus, invitationErrorThrown) {
                                console.error("Error sending invitation:", invitationTextStatus, invitationErrorThrown, invitationJqXHR.responseText);
                                alert("Failed to send invitation. Error: " + invitationJqXHR.status);
                            }
                        }); // End add_invitation AJAX call
                    });
                    alert("Meghívók elküldve");
                },
                error: function(rankingJqXHR, rankingTextStatus, rankingErrorThrown) {
                    console.error("Error loading rankings:", rankingTextStatus, rankingErrorThrown, rankingJqXHR.responseText);
                    if (rankingJqXHR.status === 404) {
                        console.log("No rankings found for eventOccupationId:", eventOccupationId);
                    } else if (rankingJqXHR.status === 400) {
                        alert("Invalid request.  Please check the data.");
                    } else {
                        alert("Failed to load rankings. Error: " + rankingJqXHR.status);
                    }
                }
            }); // End get_ranking AJAX call
        }); // End eventOccupations.forEach
    }
});