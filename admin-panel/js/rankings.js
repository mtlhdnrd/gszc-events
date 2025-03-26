// --- Classes ---
class Ranking {
    constructor(rankingId, eventWorkshopId, userId, userName, rankingNumber, userType, workshopName, eventName) {
        this.rankingId = rankingId;
        this.eventWorkshopId = eventWorkshopId;
        this.userId = userId;
        this.userName = userName;
        this.rankingNumber = rankingNumber;
        this.userType = userType;
        this.workshopName = workshopName;
        this.eventName = eventName;
    }
}

class RankingContainer {
    constructor() {
        this.rankings = [];
    }
    addRanking(ranking) {
        if (!(ranking instanceof Ranking)) {
            throw new Error("Invalid Ranking object.");
        }
        this.rankings.push(ranking);
        this.sortRankings(); // Keep sorted
    }
    getAllRankings() {
        return this.rankings;
    }
    clearRankings() {
        this.rankings = [];
    }
    sortRankings() {
        this.rankings.sort((a, b) => a.rankingNumber - b.rankingNumber);
    }
    // Add methods for reordering later (e.g., moveUp, moveDown)
}

// --- Global Variables ---
let rankingUserType = 'student'; // Default to student
const rankingContainer = new RankingContainer();
//TODO: Make this a global method so every file can use it
function loadRankingEvents() {
    const $eventSelect = $('#rankingEventSelect');
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
            eventContainer.getAllEvents().forEach(event => {
                $eventSelect.append(`<option value="${event.id}">${event.name} (${event.date})</option>`);
            });
        },
        error: function (xhr, status, error) {
            console.error("Error fetching events:", error);
            alert("Hiba történt az események betöltésekor. Kérlek, próbáld újra később.");
        }
    });
}

function loadRankingWorkshops(eventId) {
    const $workshopSelect = $('#rankingWorkshopSelect');
    $workshopSelect.empty(); // Clear previous options

    if (!eventId) {
        $workshopSelect.append('<option value="">Előbb válassz eseményt</option>').prop('disabled', true);
        $('#showRankingBtn').prop('disabled', true);
        $('#rankingTableContainer').hide(); // Hide table if event is deselected
        return;
    }

    $workshopSelect.append('<option value="">Válassz foglalkozást</option>');

    let workshopsFound = false;
    if (typeof eventOccupationContainer !== 'undefined' && typeof occupationContainer !== 'undefined') {
        const eventOccupations = eventOccupationContainer.getEventOccupationsByEventId(parseInt(eventId));

        eventOccupations.forEach(eo => {
            const occupation = occupationContainer.getOccupationById(eo.occupationId);
            if (occupation) {
                // IMPORTANT: Store event_workshop_id in the value
                $workshopSelect.append(`<option value="${eo.eventOccupationId}">${occupation.name}</option>`);
                workshopsFound = true;
            }
        });
    } else {
        console.error("eventOccupationContainer or occupationContainer not available.");
    }

    if (workshopsFound) {
        $workshopSelect.prop('disabled', false);
    } else {
        $workshopSelect.append('<option value="">Nincs foglalkozás ehhez az eseményhez</option>').prop('disabled', true);
         $('#showRankingBtn').prop('disabled', true);
         $('#rankingTableContainer').hide();
    }
     $('#showRankingBtn').prop('disabled', true); // Disable button until workshop is selected
     $('#rankingTableContainer').hide(); // Hide table when event changes
}

function displayRankings() {
    const eventWorkshopId = $('#rankingWorkshopSelect').val();
    const eventName = $('#rankingEventSelect option:selected').text(); // Get selected text
    const workshopName = $('#rankingWorkshopSelect option:selected').text();

    if (!eventWorkshopId) {
        alert("Kérlek válassz foglalkozást!");
        $('#rankingTableContainer').hide();
        return;
    }

    $.ajax({
        url: '../backend/api/rankings/get_rankings_by_event_workshop.php',
        type: 'GET',
        data: {
            event_workshop_id: eventWorkshopId,
            user_type: rankingUserType
        },
        dataType: 'json',
        success: function(rankingsData) {
            rankingContainer.clearRankings();
            const $tbody = $('#rankingsTable tbody');
            $tbody.empty(); // Clear previous ranking rows

            if (rankingsData.length === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center">Nincs rangsor ehhez a kombinációhoz.</td></tr>');
            } else {
                rankingsData.forEach(data => {
                    const ranking = new Ranking(
                        data.ranking_id,
                        data.event_workshop_id,
                        data.user_id,
                        data.user_name, 
                        data.ranking_number,
                        data.user_type,
                        data.workshop_name,
                        data.event_name
                    );
                    rankingContainer.addRanking(ranking); // Add to container (already sorted)
                    addRankingRow(ranking); // Add row to table
                });
            }

            // Update header and show table
            $('#rankingTableHeader').text(`Rangsor: ${eventName} - ${workshopName} (${rankingUserType === 'student' ? 'Diákok' : 'Tanárok'})`);
            $('#rankingTableContainer').show();
        },
        error: function(xhr, status, error) {
            console.error("Error loading rankings:", status, error, xhr.responseText);
            alert("Hiba történt a rangsor betöltésekor.");
            $('#rankingTableContainer').hide();
        }
    });
}

function addRankingRow(ranking) {
    // Placeholder arrow icons
    const upArrow = '<i class="fas fa-arrow-up text-secondary action-arrow move-up" style="cursor: pointer;"></i>'; // Add classes for event handling later
    const downArrow = '<i class="fas fa-arrow-down text-secondary action-arrow move-down" style="cursor: pointer;"></i>'; // Add classes for event handling later

    const row = `
        <tr data-ranking-id="${ranking.rankingId}" data-user-id="${ranking.userId}">
            <td>${ranking.rankingNumber}</td>
            <td>${ranking.userName}</td>
            <td>${ranking.workshopName}</td>
            <td>${ranking.eventName}</td>
            <td>
                ${upArrow}
                ${downArrow}
            </td>
        </tr>`;
    $('#rankingsTable tbody').append(row);
}

// --- Document Ready ---
$(document).ready(function() {

    // --- Initial Load ---
    loadRankingEvents();

    // --- Event Handlers ---

    // Student/Teacher Toggle
    $('#showStudentRankingsBtn').click(function() {
        rankingUserType = 'student';
        $(this).removeClass('btn-secondary').addClass('btn-primary');
        $('#showTeacherRankingsBtn').removeClass('btn-primary').addClass('btn-secondary');
        // If a workshop is selected, refresh the ranking display
        if ($('#rankingWorkshopSelect').val()) {
            displayRankings();
        }
    });

    $('#showTeacherRankingsBtn').click(function() {
        rankingUserType = 'teacher';
        $(this).removeClass('btn-secondary').addClass('btn-primary');
        $('#showStudentRankingsBtn').removeClass('btn-primary').addClass('btn-secondary');
        // If a workshop is selected, refresh the ranking display
        if ($('#rankingWorkshopSelect').val()) {
            displayRankings();
        }
    });

    // Event Selection Change
    $('#rankingEventSelect').change(function() {
        const selectedEventId = $(this).val();
        loadRankingWorkshops(selectedEventId);
    });

     // Workshop Selection Change
    $('#rankingWorkshopSelect').change(function() {
        if ($(this).val()) {
             $('#showRankingBtn').prop('disabled', false); //Enable ranking button
             $('#rankingTableContainer').hide(); // Hide table until button is clicked
        } else {
            $('#showRankingBtn').prop('disabled', true);
             $('#rankingTableContainer').hide();
        }
    });

    // Show Ranking Button Click
    $('#showRankingBtn').click(displayRankings);

    // --- Placeholder for Arrow Click Handlers (Implement Later) ---
    $('#rankingsTable tbody').on('click', '.move-up', function() {
        const rankingId = $(this).closest('tr').data('ranking-id');
        alert(`Up arrow clicked for ranking ID: ${rankingId} (implement functionality)`);
        // TODO: Implement logic to move item up and update server/UI
    });

    $('#rankingsTable tbody').on('click', '.move-down', function() {
        const rankingId = $(this).closest('tr').data('ranking-id');
        alert(`Down arrow clicked for ranking ID: ${rankingId} (implement functionality)`);
        // TODO: Implement logic to move item down and update server/UI
    });

});