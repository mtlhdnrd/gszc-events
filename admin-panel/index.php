<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Felület</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Admin Panel</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="events">Események</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="#" data-target="occupations">Foglalkozások</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="students">Diákok</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="rankings">Rangsorok</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="headTeachers">Osztályfőnökök</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <div id="events" class="content-section" style="display: none;">
            <h1>Események kezelése</h1>
            <button id="newEventBtn" class="btn btn-primary mb-3">Új esemény</button>

            <table class="table table-striped table-bordered" id="eventsTable">
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Dátum</th>
                        <th>Helyszín</th>
                        <th>Terheltség</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <!-- Modal for New Event -->
            <div class="modal fade" id="newEventModal" tabindex="-1" role="dialog" aria-labelledby="newEventModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newEventModalLabel">Új esemény létrehozása</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="newEventForm">
                                <div class="form-group">
                                    <label for="eventName">Esemény neve:</label>
                                    <input type="text" class="form-control" id="eventName" name="eventName">
                                </div>
                                <div class="form-group">
                                    <label for="eventDate">Dátum:</label>
                                    <input type="date" class="form-control" id="eventDate" name="eventDate">
                                </div>
                                <div class="form-group">
                                    <label for="eventLocation">Helyszín:</label>
                                    <input type="text" class="form-control" id="eventLocation" name="eventLocation">
                                </div>
                                <div class="form-group">
                                    <label for="eventLoadLevel">Terheltség:</label>
                                    <select class="form-control" id="eventLoadLevel" name="eventLoadLevel">
                                        <option value="magas">Magas</option>
                                        <option value="alacsony">Alacsony</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                            <button type="button" class="btn btn-primary" id="saveNewEventBtn">Mentés</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="occupations" class="content-section" style="display: none;">
            <h1>Foglalkozások kezelése</h1>

            <table class="table table-striped table-bordered" id="occupationsTable">
                <thead>
                    <tr>
                        <th>Azonosító</th>
                        <th>Név</th>
                        <th>Műveletek</th> <!-- Added for Edit/Delete -->
                    </tr>
                </thead>
                <tbody>
                    <!-- Occupations will be loaded here -->
                </tbody>
            </table>

            <div id="addOccupationEventForm">
                <h2 class="mt-4">Foglalkozás hozzárendelése eseményhez</h2>
                <div class="form-group">
                    <label for="eventSelect">Esemény:</label>
                    <select class="form-control" id="eventSelect">
                        <option value="">Válassz eseményt</option>
                        <!-- Events will be loaded here -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="occupationSelect">Foglalkozás:</label>
                    <select class="form-control" id="occupationSelect">
                        <option value="">Válassz foglalkozást</option>
                        <!-- Occupations will be loaded here -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="mentorCount">Szükséges mentorok száma:</label>
                    <input type="number" class="form-control" id="mentorCount" min="1" value="1">
                </div>
                <div class="form-group">
                    <label for="hoursCount">Ledolgozható órák száma:</label>
                    <input type="number" class="form-control" id="hoursCount" min="1" value="1">
                </div>
                <button id="addOccupationToEventBtn" class="btn btn-primary">Hozzárendelés</button>
            </div>
            <table class="table table-striped table-bordered" id="eventOccupationsTable">
                <thead>
                    <tr>
                        <th>Esemény neve</th>
                        <th>Foglalkozás neve</th>
                        <th>Szükséges mentorok</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Event-Occupation assignments will be loaded here -->
                </tbody>
            </table>


        </div>

        <div id="students" class="content-section" style="display: none;">
            <h1>Diákok kezelése</h1>
        </div>

        <div id="rankings" class="content-section" style="display: none;">
            <h1>Rangsorok kezelése</h1>
        </div>
        <div id="headTeachers" class="content-section" style="display: none;">
            <h1>Osztályfőnökök kezelése</h1>
        </div>
    </div>

    <div id="diakok" class="content-section" style="display: none;">
        <h1>Diákok kezelése</h1>
    </div>

    <div id="rangsorok" class="content-section" style="display: none;">
        <h1>Rangsorok kezelése</h1>
    </div>
    <div id="osztalyfonokok" class="content-section" style="display: none;">
        <h1>Osztályfőnökök kezelése</h1>
    </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
    <script src="js/events.js"></script>
    <script src="js/occupations.js"></script>
</body>

</html>