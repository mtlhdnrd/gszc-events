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
    getRankingById(rankingId) {
        return this.rankings.find(r => r.rankingId === parseInt(rankingId, 10));
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

    updateRankingNumbers(id1, newRank1, id2, newRank2) {
        const rankObj1 = this.getRankingById(id1);
        const rankObj2 = this.getRankingById(id2);
        if (rankObj1) rankObj1.rankingNumber = newRank1;
        if (rankObj2) rankObj2.rankingNumber = newRank2;
        this.sortRankings(); // Re-sort after updating numbers
    }
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
        success: function (rankingsData) {
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
        error: function (xhr, status, error) {
            console.error("Error loading rankings:", status, error, xhr.responseText);
            alert("Hiba történt a rangsor betöltésekor.");
            $('#rankingTableContainer').hide();
        }
    });
}

function addRankingRow(ranking) {
    const upArrow = '<i class="fas fa-arrow-up text-primary action-arrow move-up" style="cursor: pointer; margin-right: 5px;"></i>';
    const downArrow = '<i class="fas fa-arrow-down text-primary action-arrow move-down" style="cursor: pointer;"></i>';

    const row = `
        <tr data-ranking-id="${ranking.rankingId}" data-user-id="${ranking.userId}" data-rank-number="${ranking.rankingNumber}">
            <td>${ranking.rankingNumber}</td>
            <td>${ranking.userName}</td>
            <td>${ranking.workshopName}</td>
            <td>${ranking.eventName}</td>
            <td class="ranking-actions">
                ${upArrow}
                ${downArrow}
            </td>
        </tr>`;
    $('#rankingsTable tbody').append(row);
}

function updateRankingOrder(swapData) {
    console.log('Sending swapData to server:', JSON.stringify(swapData));

    $.ajax({
        url: '../backend/api/rankings/update_rankings.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(swapData),
        dataType: 'json',
        success: function(response) {
            console.log("Ranking updated successfully:", response.message);

            // Select rows using the correct data-ranking-id attribute
            const row1 = $(`#rankingsTable tbody tr[data-ranking-id="${swapData[0].id}"]`);
            const row2 = $(`#rankingsTable tbody tr[data-ranking-id="${swapData[1].id}"]`);

            if (row1.length === 0 || row2.length === 0) {
                console.error("Could not find rows to update in UI. Reloading.");
                displayRankings(); // Reload if rows aren't found
                return;
            }

            const rank1Cell = row1.find('td:first');
            const rank2Cell = row2.find('td:first');

            // Swap ranking numbers in the UI text
            rank1Cell.text(swapData[0].rank);
            rank2Cell.text(swapData[1].rank);

            // Update data-rank-number attributes
            row1.attr('data-rank-number', swapData[0].rank);
            row2.attr('data-rank-number', swapData[1].rank);

            // Physically swap rows in the table
            if (swapData[0].rank < swapData[1].rank) { // row1 has the lower NEW rank (moved up)
                 row2.before(row1); // Place row1 before row2
            } else { // row2 has the lower NEW rank (row1 moved down)
                 row1.before(row2); // Place row2 before row1
            }

            // Update data container
            rankingContainer.updateRankingNumbers(swapData[0].id, swapData[0].rank, swapData[1].id, swapData[1].rank);
        },
        error: function(xhr, status, error) {
            console.error("Error updating ranking:", status, error, xhr.responseText);
            alert("Hiba történt a rangsor frissítésekor. Az oldal frissítése javasolt.");
            displayRankings(); // Reload to show correct state from DB
        }
    });
}

// --- Document Ready ---
$(document).ready(function () {

    // --- Initial Load ---
    loadRankingEvents();

    // --- Event Handlers ---

    // Student/Teacher Toggle
    $('#showStudentRankingsBtn').click(function () {
        rankingUserType = 'student';
        $(this).removeClass('btn-secondary').addClass('btn-primary');
        $('#showTeacherRankingsBtn').removeClass('btn-primary').addClass('btn-secondary');
        // If a workshop is selected, refresh the ranking display
        if ($('#rankingWorkshopSelect').val()) {
            displayRankings();
        }
    });

    $('#showTeacherRankingsBtn').click(function () {
        rankingUserType = 'teacher';
        $(this).removeClass('btn-secondary').addClass('btn-primary');
        $('#showStudentRankingsBtn').removeClass('btn-primary').addClass('btn-secondary');
        // If a workshop is selected, refresh the ranking display
        if ($('#rankingWorkshopSelect').val()) {
            displayRankings();
        }
    });

    // Event Selection Change
    $('#rankingEventSelect').change(function () {
        const selectedEventId = $(this).val();
        loadRankingWorkshops(selectedEventId);
    });

    // Workshop Selection Change
    $('#rankingWorkshopSelect').change(function () {
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

    // --- Arrow Click Handlers ---
    $('#rankingsTable tbody').on('click', '.move-up', function () {
        const currentRow = $(this).closest('tr');
        const prevRow = currentRow.prev();
        // Boundary check: Cannot move the first item up
        if (prevRow.length === 0) {
            return;
        }

        // Get data for API call
        const currentId = parseInt(currentRow.attr('data-ranking-id'));
        const currentRank = parseInt(currentRow.attr('data-rank-number'));
        const prevId = parseInt(prevRow.attr('data-ranking-id'));
        const prevRank = parseInt(prevRow.attr('data-rank-number'));

        if (isNaN(currentId) || isNaN(currentRank) || isNaN(prevId) || isNaN(prevRank)) {
            console.error("Error parsing ranking data from attributes (move up).", { currentId, currentRank, prevId, prevRank });
            alert("Hiba történt az adatok feldolgozásakor. Próbáld újra.");
            return; // Stop execution
        }
        // Prepare data for swapping (current gets prevRank, prev gets currentRank)
        const swapData = [
            { id: currentId, rank: prevRank },
            { id: prevId, rank: currentRank }
        ];

        updateRankingOrder(swapData);
    });

    $('#rankingsTable tbody').on('click', '.move-down', function () {
        const currentRow = $(this).closest('tr');
        const nextRow = currentRow.next();

        // Boundary check: Cannot move the last item down
        if (nextRow.length === 0) {
            return;
        }

        // Get data for API call
        const currentId = parseInt(currentRow.attr('data-ranking-id'));
        const currentRank = parseInt(currentRow.attr('data-rank-number'));
        const nextId = parseInt(nextRow.attr('data-ranking-id'));
        const nextRank = parseInt(nextRow.attr('data-rank-number')); 

        if (isNaN(currentId) || isNaN(currentRank) || isNaN(nextId) || isNaN(nextRank)) {
            console.error("Error parsing ranking data from attributes (move down).", { currentId, currentRank, nextId, nextRank });
            alert("Hiba történt az adatok feldolgozásakor. Próbáld újra.");
            return; // Stop execution
        }

        // Prepare data for swapping (current gets nextRank, next gets currentRank)
        const swapData = [
            { id: currentId, rank: nextRank },
            { id: nextId, rank: currentRank }
        ];

        updateRankingOrder(swapData);
    });
});