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
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="esemenyek">Események</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="diakok">Diákok</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-target="rangsorok">Rangsorok</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="#" data-target="osztalyfonokok">Osztályfőnökök</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
    <div id="esemenyek" class="content-section">
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
                    <!-- <tr>
                        <td><input type="text" class="form-control event-data" data-field="name" value="Táncpróba" readonly></td>
                        <td><input type="text" class="form-control event-data" data-field="date" value="2024-03-15" readonly></td>
                        <td><input type="text" class="form-control event-data" data-field="location" value="Iskola aula" readonly></td>
                        <td><input type="text" class="form-control event-data" data-field="loadLevel" value="magas" readonly></td>
                        <td>
                            <button class="btn btn-primary btn-sm edit-button" id="edit-event-btn-1">Szerkesztés</button>
                            <button class="btn btn-danger btn-sm delete-button" id="delete-event-btn-1">Törlés</button>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="text" class="form-control event-data" data-field="name" value="Szavalóverseny" readonly></td>
                        <td><input type="text" class="form-control event-data" data-field="date" value="2024-03-22" readonly></td>
                        <td><input type="text" class="form-control event-data" data-field="location" value="Tornaterem" readonly></td>
                        <td><input type="text" class="form-control event-data" data-field="loadLevel" value="alacsony" readonly></td>
                        <td>
                            <button class="btn btn-primary btn-sm edit-button" id="edit-event-btn-2">Szerkesztés</button>
                            <button class="btn btn-danger btn-sm delete-button" id="delete-event-btn-2">Törlés</button>
                        </td>
                    </tr> -->

                </tbody>
            </table>
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

    <!-- Modal for New Event -->
    <div class="modal fade" id="newEventModal" tabindex="-1" role="dialog" aria-labelledby="newEventModalLabel" aria-hidden="true">
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
    <script src="js/events.js"></script>
</body>
</html>