

// --- Ranking Class and Container ---
class Ranking {
    constructor(rankingId, eventOccupationId, studentUsername, rankingNumber) {
        this.rankingId = rankingId;       // Added rankingId
        this.eventOccupationId = eventOccupationId; // Added eventOccupationId
        this.studentUsername = studentUsername;
        this.rankingNumber = rankingNumber;
    }
}

class RankingContainer {
    constructor() {
        this.rankings = [];
    }

    addRanking(ranking) {
        if (!(ranking instanceof Ranking)) {
            throw new Error("Invalid ranking object. Must be an instance of Ranking.");
        }
        this.rankings.push(ranking);
    }

    getRankingsByEventOccupation(eventOccupationId) {
        return this.rankings.filter(ranking => ranking.eventOccupationId === eventOccupationId);
    }

    removeRanking(rankingId) {
        const initialLength = this.rankings.length;
        this.rankings = this.rankings.filter(ranking => ranking.rankingId !== rankingId);
        return this.rankings.length < initialLength;
    }
}

const rankingContainer = new RankingContainer();


$(document).ready(function() {

    // --- Event Handlers ---
    $('#showRankingsBtn').click(showRankings);
    $('#addRankingBtn').click(addRanking);
    $('#eventSelectRanking').change(updateOccupationDropdown);
    $('#addEventSelectRanking').change(updateAddOccupationDropdown);

    $(document).on('eventAdded', loadEventsIntoSelect);
    $(document).on('workshopAdded', updateOccupationDropdown);

    // --- Load Initial Data (Placeholder) ---

    loadEventsIntoSelect("#eventSelectRanking");
    loadEventsIntoSelect("#addEventSelectRanking");
    updateOccupationDropdown(); //call for placeholder
    updateAddOccupationDropdown(); //call for placeholder
    // --- Function Definitions ---

    function showRankings() {
        let eventId = parseInt($('#eventSelectRanking').val(), 10);
        let occupationId = parseInt($('#occupationSelectRanking').val(), 10);
    
        if (!eventId || !occupationId) {
            alert('Kérlek válassz eseményt és foglalkozást!');
            return;
        }
    
        // Find the EventOccupation object (you need this for the ID)
        let eventOccupation = eventOccupationContainer.getAllEventOccupations().find(eo => eo.eventId === eventId && eo.occupationId === occupationId);
    
        if (!eventOccupation) {
            alert('Nincs hozzárendelve foglalkozás ehhez az eseményhez.');
            return;
        }
    
        let eventOccupationId = eventOccupation.eventOccupationId;
        //TODO: Check if working
        $.ajax({
            url: "../backend/api/rankings/get_ranking.php", 
            type: "GET",
            data: { event_workshop_id: eventOccupationId },
            dataType: "json",
            success: function(data) {
                $('#rankingsTable tbody').empty();
                rankingContainer.rankings = [];   
    
                data.forEach(function(rankingData) {
                    const ranking = new Ranking(
                        rankingData.ranking_id,
                        rankingData.event_workshop_id,
                        rankingData.username,
                        rankingData.ranking_number
                    );
    
                    rankingContainer.addRanking(ranking); 
                    addRankingRow(ranking);
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error loading rankings:", textStatus, errorThrown, jqXHR.responseText);
                if (jqXHR.status === 404) {
                    alert("No rankings found for this event and workshop.");
                } else if (jqXHR.status === 400) {
                    alert("Invalid request.  Please check the data.");
                }else {
                    alert("Failed to load rankings. Error: " + jqXHR.status);
                }
            }
        });
    }


    function addRankingRow(ranking) {
        let row = $('<tr>');
        row.append($('<td>').text(ranking.studentUsername));
        row.append($('<td>').text(ranking.rankingNumber));
        $('#rankingsTable tbody').append(row);
    }


     function updateOccupationDropdown() {
        let eventId = parseInt($(this).val(), 10);
         loadOccupationsIntoSelect(eventId, "#occupationSelectRanking");
    }
    function updateAddOccupationDropdown(){
        let eventId = parseInt($(this).val(), 10);
        loadOccupationsIntoSelect(eventId, "#addOccupationSelectRanking");
    }

    function loadEventsIntoSelect(selectId) {
        //FIXME: Duplicate code from event.js
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
                 $(selectId).html(options);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching events:", error);
                alert("Hiba történt az események betöltésekor. Kérlek, próbáld újra később.");
            }
        });

    }

    function loadOccupationsIntoSelect(eventId, selectId) {
        //FIXME: Duplicate code from occupations.js
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
                    let options = '<option value="">Válassz foglalkozást</option>';

                    if (eventId) {
                        const filteredEventOccupations = eventOccupationContainer.getAllEventOccupations().filter(eo => eo.eventId === eventId);
            
                        filteredEventOccupations.forEach(eventOccupation => {
                            const occupation = occupationContainer.getOccupationById(eventOccupation.occupationId);
            
                            if (occupation) {
                                options += `<option value="${occupation.id}">${occupation.name}</option>`;
                            }
                        });
                    }
            
                    $(selectId).html(options);
                });
            },
            error: function (xhr, status, error) {
                console.error("Error loading occupations:", status, error);
                alert("Hiba történt a foglalkozások betöltésekor. Kérlek próbáld újra később.");
            }
        });
    }
    function addRanking() {
        //FIXME: duplicate call from students.js
        $.ajax({
            url: "../backend/api/students/get_students.php",
            type: "GET",
            dataType: "json",
            success: function(data) {
                $('#studentsTable tbody').empty(); 
                studentContainer.students = [];
                data.forEach(function(studentData) {
                    const student = new Student(
                        studentData.user_id,
                        studentData.username,
                        studentData.student_name,
                        studentData.email,
                        studentData.headTeacherName,
                        studentData.headTeacherId,
                        studentData.schoolName,
                        studentData.school_id,
                        studentData.total_hours_worked
                    );
    
                    studentContainer.addStudent(student);
                });
                let eventId = parseInt($('#addEventSelectRanking').val(), 10);
        let occupationId = parseInt($('#addOccupationSelectRanking').val(), 10);
        let studentUsername = $('#studentUsernameRanking').val();
        let rankingNumber = parseInt($('#rankingNumber').val(), 10);
    
        if (!eventId || !occupationId || !studentUsername || isNaN(rankingNumber)) {
            alert('Kérlek válassz eseményt, foglalkozást, add meg a diák felhasználónevét, és egy érvényes sorszámot!');
            return;
        }
    
        // Get eventOccupationId
        let eventOccupation = eventOccupationContainer.getAllEventOccupations().find(eo => eo.eventId === eventId && eo.occupationId === occupationId);
        if (!eventOccupation) {
            alert("Nincs ilyen esemény-foglalkozás hozzárendelés!");
            return;
        }
        let eventOccupationId = eventOccupation.eventOccupationId;
    
        // Find the student by username
        let student = null;
        studentContainer.getAllStudents().forEach(s => {
            if (s.username === studentUsername) {
                student = s;
            }
        });
        
        if (!student) {
            alert("Nincs ilyen felhasználónévvel diák!");
            return;
        }
        let studentId = student.id;
    
        // AJAX call to add_ranking.php
        $.ajax({
            url: "../backend/api/rankings/add_ranking.php", // Correct URL
            type: "POST",
            data: {
                event_workshop_id: eventOccupationId,
                user_id: studentId,
                ranking_number: rankingNumber
            },
            success: function(response) {
                // Server returns the new ranking_id
                const newRankingId = parseInt(response);
    
                // Create the Ranking object *after* the server confirms success
                const newRanking = new Ranking(newRankingId, eventOccupationId, studentId, studentUsername, rankingNumber);
                rankingContainer.addRanking(newRanking); //add to container
    
                console.log("Ranking added with ID:", newRankingId);
                alert("Ranking successfully added!");
    
                // Clear form (optional)
                $('#addEventSelectRanking').val('');
                $('#addOccupationSelectRanking').val('');
                $('#studentUsernameRanking').val('');
                $('#rankingNumber').val('');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error adding ranking:", textStatus, errorThrown, jqXHR.responseText);
                 if (jqXHR.status === 409) {
                    alert("A ranking for this student and event workshop already exists.");
                } else if (jqXHR.status === 400) {
                    alert("Invalid input.  Please check the data.");
                } else {
                    alert("Failed to add ranking. Error: " + jqXHR.status);
                }
            }
        });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error loading students:", textStatus, errorThrown, jqXHR.responseText);
                alert("Failed to load students.  Error: " + jqXHR.status);
            }
        });
    }
});