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
                <li class="nav-item">
                    <a href="#" class="nav-link" data-target="invitations">Meghívók</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div id="events" class="content-section" style="display: none;">
            <h1>Események kezelése</h1>
            <button id="newEventBtn" class="btn btn-primary mb-3">Új esemény</button>

            <table class="table table-striped table-bordered" id="eventsTable">
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Dátum</th>
                        <th>Helyszín</th>
                        <th>Státusz</th>
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
                                    <label for="eventStatus">Státusz:</label>
                                    <select class="form-control" id="eventStatus" name="eventStatus">
                                        <option value="pending">Függőben</option>
                                        <option value="ready">Kész</option>
                                        <option value="failed">Sikertelen</option>
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
            <div class="form-inline mb-3">
                <input type="text" class="form-control mr-2" id="newOccupationName" placeholder="Új foglalkozás neve">
                <button id="addOccupationBtn" class="btn btn-primary">Foglalkozás hozzáadása</button>
            </div>

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
                        <!-- Events will be loaded dynamically -->
                    </select>
                </div>

                <div id="eventOccupationsTableContainer" style="display: none;">
                    <table class="table mt-3" id="eventOccupationsTable">
                        <thead>
                            <tr>
                                <th>Név</th>
                                <th>Van e az eseményen</th>
                                <th>Kellő mentordiák szám</th>
                                <th>Kellő mentor tanár szám</th>
                                <th>Ledolgozható órák</th>
                                <th>Leterheltség</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <button id="saveOccupationsBtn" class="btn btn-primary">Mentés</button>
                </div>
            </div>
        </div>

        <div id="students" class="content-section" style="display: none;">
            <h1>Diákok kezelése</h1>
            <button id="newStudentBtn" class="btn btn-primary mb-3">Új diák</button>

            <table class="table table-striped table-bordered" id="studentsTable">
                <thead>
                    <tr>
                        <th>Azonosító</th>
                        <th>Felhasználónév</th>
                        <th>Név</th>
                        <th>Email</th>
                        <th>Osztályfőnök</th>
                        <th>Iskola</th>
                        <th>OM Azonosító</th>
                        <th>Ledolgozott órák</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Students will be loaded here -->
                </tbody>
            </table>

            <h2 class="mt-4">Diák Foglalkozás Hozzárendelés</h2>
            <div class="form-group">
                <label for="studentSelect">Diák:</label>
                <select class="form-control" id="studentSelect">
                    <option value="">Válassz diákot</option>
                    <!-- Students will be loaded here -->
                </select>
            </div>
            <div class="form-group">
                <label for="occupationSelect">Foglalkozás:</label>
                <select class="form-control" id="occupationSelectStudent">
                    <option value="">Válassz foglalkozást</option>
                    <!-- Occupations will be loaded here (from occupations.js) -->
                </select>
            </div>
            <button id="addStudentOccupationBtn" class="btn btn-primary">Hozzárendelés</button>
            <!-- Modal for New Student -->
            <div class="modal fade" id="newStudentModal" tabindex="-1" role="dialog"
                aria-labelledby="newStudentModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newStudentModalLabel">Új diák felvétele</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="newStudentForm">
                                <div class="form-group">
                                    <label for="studentUsername">Felhasználónév:</label>
                                    <input type="text" class="form-control" id="studentUsername" name="studentUsername">
                                </div>
                                <div class="form-group">
                                    <label for="studentPassword">Jelszó:</label>
                                    <input type="password" class="form-control" id="studentPassword"
                                        name="studentPassword">
                                </div>
                                <div class="form-group">
                                    <label for="studentName">Név:</label>
                                    <input type="text" class="form-control" id="studentName" name="studentName">
                                </div>
                                <div class="form-group">
                                    <label for="studentEmail">Email:</label>
                                    <input type="email" class="form-control" id="studentEmail" name="studentEmail">
                                </div>
                                <div class="form-group">
                                    <label for="schoolSelect">Iskola:</label>
                                    <select class="form-control" id="schoolSelect" name="schoolSelect">
                                        <!-- Schools will be loaded here -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="headTeacherSelect">Osztályfőnök:</label>
                                    <select class="form-control" id="headTeacherSelect" name="headTeacherSelect">
                                        <!-- Head teachers will be loaded here -->
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                            <button type="button" class="btn btn-primary" id="saveNewStudentBtn">Mentés</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="rankings" class="content-section" style="display: none;">
            <h1>Rangsorok kezelése</h1>

            <h2>Rangsor Megjelenítése</h2>
            <div class="form-group">
                <label for="eventSelectRanking">Esemény:</label>
                <select class="form-control" id="eventSelectRanking">
                    <option value="">Válassz eseményt</option>
                    <!-- Events will be loaded here -->
                </select>
            </div>
            <div class="form-group">
                <label for="occupationSelectRanking">Foglalkozás:</label>
                <select class="form-control" id="occupationSelectRanking">
                    <option value="">Válassz foglalkozást</option>
                    <!-- Occupations for the selected event will be loaded here -->
                </select>
            </div>
            <button id="showRankingsBtn" class="btn btn-primary">Rangsor Megjelenítése</button>

            <table class="table table-striped table-bordered" id="rankingsTable">
                <thead>
                    <tr>
                        <th>Diák Felhasználónév</th>
                        <th>Sorszám</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rankings will be displayed here -->
                </tbody>
            </table>

            <h2>Rangsor Hozzáadása</h2>
            <div class="form-group">
                <label for="addEventSelectRanking">Esemény:</label>
                <select class="form-control" id="addEventSelectRanking">
                    <option value="">Válassz eseményt</option>
                    <!-- Events will be loaded here -->
                </select>
            </div>
            <div class="form-group">
                <label for="addOccupationSelectRanking">Foglalkozás:</label>
                <select class="form-control" id="addOccupationSelectRanking">
                    <option value="">Válassz foglalkozást</option>
                    <!-- Occupations for the selected event will be loaded here -->
                </select>
            </div>
            <div class="form-group">
                <label for="studentUsernameRanking">Diák Felhasználónév:</label>
                <input type="text" class="form-control" id="studentUsernameRanking">
            </div>
            <div class="form-group">
                <label for="rankingNumber">Sorszám:</label>
                <input type="number" class="form-control" id="rankingNumber" min="1">
            </div>
            <button id="addRankingBtn" class="btn btn-primary">Felvitel</button>
        </div>
        <div id="headTeachers" class="content-section" style="display: none;">
            <h1>Osztályfőnökök kezelése</h1>
            <button id="newHeadTeacherBtn" class="btn btn-primary mb-3">Új osztályfőnök</button>

            <table class="table table-striped table-bordered" id="headTeachersTable">
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Email</th>
                        <th>Telefonszám</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Head Teachers will be loaded here -->
                </tbody>
            </table>

            <!-- Modal for New Head Teacher -->
            <div class="modal fade" id="newHeadTeacherModal" tabindex="-1" role="dialog"
                aria-labelledby="newHeadTeacherModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newHeadTeacherModalLabel">Új osztályfőnök felvétele</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="newHeadTeacherForm">
                                <div class="form-group">
                                    <label for="headTeacherName">Név:</label>
                                    <input type="text" class="form-control" id="headTeacherName" name="headTeacherName">
                                </div>
                                <div class="form-group">
                                    <label for="headTeacherEmail">Email:</label>
                                    <input type="email" class="form-control" id="headTeacherEmail"
                                        name="headTeacherEmail">
                                </div>
                                <div class="form-group">
                                    <label for="headTeacherPhoneNumber">Telefonszám:</label>
                                    <input type="text" class="form-control" id="headTeacherPhoneNumber"
                                        name="headTeacherPhoneNumber">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                            <button type="button" class="btn btn-primary" id="saveNewHeadTeacherBtn">Mentés</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="invitations" class="content-section">
            <h1>Meghívók kezelése</h1>
            <div class="form-group">
                <label for="eventSelectInvitations">Esemény:</label>
                <select class="form-control" id="eventSelectInvitations">
                    <option value="">Válassz eseményt</option>
                    <!-- Events will be loaded here -->
                </select>
            </div>
            <button id="sendInvitationsBtn" class="btn btn-primary">Meghívók Küldése</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
    <script src="js/events.js"></script>
    <script src="js/occupations.js"></script>
    <script src="js/students.js"></script>
    <!--script src="js/rankings.js"></script>
    <script src="js/headteachers.js"></script>
    <script src="js/invitations.js"></script-->
</body>

</html>