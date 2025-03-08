
// --- Ranking Class and Container ---
class Ranking {
    constructor(eventId, occupationId, studentUsername, rankingNumber) {
        this.eventId = eventId;
        this.occupationId = occupationId;
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

    getRankingsByEventAndOccupation(eventId, occupationId) {
        return this.rankings.filter(ranking => ranking.eventId === eventId && ranking.occupationId === occupationId);
    }

    removeRanking(eventId, occupationId, studentUsername) {
        const initialLength = this.rankings.length;
        this.rankings = this.rankings.filter(ranking => !(ranking.eventId === eventId && ranking.occupationId === occupationId && ranking.studentUsername === studentUsername));
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

        let rankings = rankingContainer.getRankingsByEventAndOccupation(eventId, occupationId);

        $('#rankingsTable tbody').empty();

        rankings.forEach(ranking => {
            addRankingRow(ranking);
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
        if(rankingNumber <= 0){
            alert("A sorszám egy 0-nál nagyobb szám kell, hogy legyen!");
            return;
        }
        const newRanking = new Ranking(eventId, occupationId, studentUsername, rankingNumber);

        rankingContainer.addRanking(newRanking);

        alert("Rangsor hozzáadva! (Replace with AJAX)");
        // TODO: AJAX call to php/add_rangsor.php
        $('#addEventSelectRanking').val('');
        $('#addOccupationSelectRanking').val('');
        $('#studentUsernameRanking').val('');
        $('#rankingNumber').val('');
    }
});