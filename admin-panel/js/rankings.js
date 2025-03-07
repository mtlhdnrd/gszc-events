

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

    getRankingsByEventOccupation(eventOccupationId) { // Changed to use eventOccupationId
        return this.rankings.filter(ranking => ranking.eventOccupationId === eventOccupationId);
    }

    removeRanking(rankingId) { // Changed to use rankingId
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

        // Get rankings by eventOccupationId
        let rankings = rankingContainer.getRankingsByEventOccupation(eventOccupationId);

        $('#rankingsTable tbody').empty();
        rankings.forEach(ranking => {
            addRankingRow(ranking);
        });
    }


    function addRankingRow(ranking) {
        let row = $('<tr>');
        // You *could* add a hidden <td> for rankingId here, similar to other tables
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
        var events = eventContainer.getAllEvents();
        let options = '<option value="">Válassz eseményt</option>';
        events.forEach(event => {
                options += `<option value="${event.id}">${event.name} - ${event.date}</option>`;
        });
         $(selectId).html(options);
    }

    function loadOccupationsIntoSelect(eventId, selectId) {
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
    }
     function addRanking() {
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
        if(!eventOccupation){
            alert("Nincs ilyen esemény-foglalkozás hozzárendelés!");
            return;
        }
        let eventOccupationId = eventOccupation.eventOccupationId;

        // Find the next available rankingId (similar to how you handle other IDs)
        let maxId = 0;
        rankingContainer.rankings.forEach(ranking => {
            if (ranking.rankingId > maxId) {  // Use rankingId here
                maxId = ranking.rankingId;
            }
        });
        let newRankingId = maxId + 1;

        const newRanking = new Ranking(newRankingId, eventOccupationId, studentUsername, rankingNumber);
        rankingContainer.addRanking(newRanking);

        alert("Rangsor hozzáadva! (Replace with AJAX)");  // Replace with AJAX
        // TODO: AJAX call to php/add_rangsor.php

        // Clear form (optional)
        $('#addEventSelectRanking').val('');
        $('#addOccupationSelectRanking').val('');
        $('#studentUsernameRanking').val('');
        $('#rankingNumber').val('');
    }
});