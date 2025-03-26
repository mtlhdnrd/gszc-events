<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Felület</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                    <a class="nav-link" href="#" data-target="participants">Résztvevők</a>
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

        <div id="participants" class="content-section" style="display: none;">
            <h1>Résztvevők Kezelése</h1>

            <div class="btn-group mb-3" role="group">
                <button type="button" class="btn btn-primary" id="showStudentsBtn">Mentordiák</button>
                <button type="button" class="btn btn-secondary" id="showTeachersBtn">Mentortanár</button>
            </div>

            <button type="button" class="btn btn-success mb-3" id="addParticipantBtn">Új résztvevő</button>

            <table class="table table-striped table-bordered" id="participantsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Név</th>
                        <th>Email</th>
                        <th>Iskola</th>
                        <th>Osztályfőnök (Diák)</th> <!-- Only for students -->
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Participants will be loaded here -->
                </tbody>
            </table>

            <!-- Add Student Modal -->
            <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog"
                aria-labelledby="addStudentModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addStudentModalLabel">Új Mentordiák Hozzáadása</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="studentName">Név</label>
                                <input type="text" class="form-control" id="studentName">
                            </div>
                            <div class="form-group">
                                <label for="studentEmail">Email</label>
                                <input type="email" class="form-control" id="studentEmail">
                            </div>
                            <div class="form-group"> <!-- Username -->
                                <label for="studentUsername">Felhasználónév</label>
                                <input type="text" class="form-control" id="studentUsername">
                            </div>
                            <div class="form-group"><!--  Password -->
                                <label for="studentPassword">Jelszó</label>
                                <input type="password" class="form-control" id="studentPassword">
                            </div>
                            <div class="form-group">
                                <label for="studentSchool">Iskola</label>
                                <select class="form-control" id="studentSchool">
                                    <option value="">Válassz iskolát</option>
                                    <!-- Schools will be loaded here -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="studentTeacher">Osztályfőnök</label>
                                <select class="form-control" id="studentTeacher">
                                    <option value="">Válassz osztályfőnököt</option>
                                    <!-- Teachers will be loaded here -->
                                </select>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                            <button type="button" class="btn btn-primary" id="saveStudentBtn">Mentés</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Teacher Modal -->
            <div class="modal fade" id="addTeacherModal" tabindex="-1" role="dialog"
                aria-labelledby="addTeacherModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addTeacherModalLabel">Új Mentortanár Hozzáadása</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="teacherName">Név</label>
                                <input type="text" class="form-control" id="teacherName">
                            </div>
                            <div class="form-group">
                                <label for="teacherEmail">Email</label>
                                <input type="email" class="form-control" id="teacherEmail">
                            </div>
                            <div class="form-group"> <!-- Username -->
                                <label for="teacherUsername">Felhasználónév</label>
                                <input type="text" class="form-control" id="teacherUsername">
                            </div>
                            <div class="form-group"><!--  Password -->
                                <label for="teacherPassword">Jelszó</label>
                                <input type="password" class="form-control" id="teacherPassword">
                            </div>
                            <div class="form-group">
                                <label for="teacherSchool">Iskola</label>
                                <select class="form-control" id="teacherSchool">
                                    <option value="">Válassz iskolát</option>
                                    <!-- Schools will be loaded here -->
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                            <button type="button" class="btn btn-primary" id="saveTeacherBtn">Mentés</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Participant Modal (Shared) -->
            <div class="modal fade" id="editParticipantModal" tabindex="-1" role="dialog"
                aria-labelledby="editParticipantModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editParticipantModalLabel">Résztvevő Szerkesztése</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="editParticipantId">
                            <input type="hidden" id="editParticipantType">
                            <div class="form-group">
                                <label for="editParticipantName">Név</label>
                                <input type="text" class="form-control" id="editParticipantName">
                            </div>
                            <div class="form-group">
                                <label for="editParticipantEmail">Email</label>
                                <input type="email" class="form-control" id="editParticipantEmail">
                            </div>
                            <div class="form-group">
                                <label for="editParticipantSchool">Iskola</label>
                                <select class="form-control" id="editParticipantSchool">
                                    <!-- Schools will be loaded here -->
                                </select>
                            </div>
                            <div class="form-group student-only-field">
                                <label for="editParticipantTeacher">Osztályfőnök</label>
                                <select class="form-control" id="editParticipantTeacher">
                                    <!-- Teachers will be loaded here -->
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                            <button type="button" class="btn btn-primary" id="saveEditedParticipantBtn">Mentés</button>
                        </div>
                    </div>
                </div>
            </div>
            <h2>Mentordiák/Mentortanár hozzárendelése foglalkozáshoz</h2>
            <div class="form-group">
                <label for="mentorSelect">Válassz Mentort:</label>
                <select class="form-control" id="mentorSelect">
                    <option value="">Válassz mentort</option>
                    <!-- Mentors (students/teachers) will be loaded here -->
                </select>
            </div>
            <div class="form-group">
                <label for="occupationSelect">Válassz Foglalkozást:</label>
                <select class="form-control" id="occupationSelect">
                    <option value="">Válassz foglalkozást</option>
                    <!-- Occupations will be loaded here -->
                </select>
            </div>
            <button type="button" class="btn btn-info" id="addMentorOccupationBtn">Hozzárendelés</button>
        </div>
        <div id="rankings" class="content-section" style="display: none;">
            <h1>Rangsorok kezelése</h1>

            <!-- Student/Teacher Toggle -->
            <div class="btn-group mb-3" role="group">
                <button type="button" class="btn btn-primary" id="showStudentRankingsBtn">Mentordiák Rangsor</button>
                <button type="button" class="btn btn-secondary" id="showTeacherRankingsBtn">Mentortanár Rangsor</button>
            </div>

            <!-- Select Options -->
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="rankingEventSelect">Esemény:</label>
                        <select class="form-control" id="rankingEventSelect">
                            <option value="">Válassz eseményt</option>
                            <!-- Events will be loaded here -->
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="rankingWorkshopSelect">Foglalkozás:</label>
                        <select class="form-control" id="rankingWorkshopSelect" disabled>
                            <option value="">Előbb válassz eseményt</option>
                            <!-- Workshops for the selected event will be loaded here -->
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-info mb-3 w-100" id="showRankingBtn" disabled>Sorrend</button>
                </div>
            </div>

            <!-- Ranking Table -->
            <div id="rankingTableContainer" class="mt-4" style="display: none;">
                <h3 id="rankingTableHeader">Rangsor</h3>
                <table class="table table-striped table-bordered" id="rankingsTable">
                    <thead>
                        <tr>
                            <th>Helyezés</th>
                            <th>Mentor Neve</th>
                            <th>Foglalkozás</th>
                            <th>Esemény</th>
                            <th>Műveletek</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Ranking rows will be loaded here -->
                    </tbody>
                </table>
            </div>
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
    <script src="js/participants.js"></script>
    <script src="js/rankings.js"></script>
    <!--script src="js/headteachers.js"></script>
    <script src="js/invitations.js"></script-->
</body>

</html>