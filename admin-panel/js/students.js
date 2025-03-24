// --- School Class and Container ---
class School {
    constructor(id, name, address) {
        this.id = id;
        this.name = name;
        this.address = address;
    }
}

class SchoolContainer {
    constructor() {
        this.schools = [];
    }

    addSchool(school) {
        if (!(school instanceof School)) {
            throw new Error("Invalid school object. Must be an instance of School.");
        }
        this.schools.push(school);
    }

    getAllSchools() {
        return this.schools;
    }

    getSchoolById(id) {
        return this.schools.find(school => school.id === id);
    }
    empty() {
        this.schools = [];
    }
}

// --- HeadTeacher Class and Container ---
class HeadTeacher {  // Modified
    constructor(id, name, email, phone, schoolId) { // Added schoolId
        this.id = id;
        this.name = name;
        this.email = email;
        this.phone = phone;
        this.schoolId = schoolId; // Store schoolId
    }

    update(newData) {
        if (newData.name) this.name = newData.name;
        if (newData.email) this.email = newData.email;
        if (newData.phone) this.phone = newData.phone;
        //  schoolId is typically NOT updated directly.  Handled separately.
    }

    toJson() {
        return {
            name: this.name,
            email: this.email,
            phone: this.phone,
            school_id: this.schoolId
        };
    }
}

class HeadTeacherContainer {  // Modified
    constructor() {
        this.headTeachers = [];
    }

    addHeadTeacher(headTeacher) {
        if (!(headTeacher instanceof HeadTeacher)) {
            throw new Error("Invalid headTeacher object. Must be an instance of HeadTeacher");
        }
        this.headTeachers.push(headTeacher);
    }

    getAllHeadTeachers() {
        return this.headTeachers;
    }

    getHeadTeacherById(id) {
        return this.headTeachers.find(ht => ht.id === id);
    }
    getHeadTeachersBySchoolId(schoolId) { //  *** CRUCIAL METHOD ***
        return this.headTeachers.filter(ht => ht.schoolId === schoolId);
    }

    updateHeadTeacher(id, newData) {
        const headTeacher = this.getHeadTeacherById(id);
        if (headTeacher) {
            headTeacher.update(newData);
            return true;
        }
        return false;
    }
    empty() {
        this.headTeachers = [];
    }

    removeHeadTeacherById(id) {
        const initialLength = this.headTeachers.length;
        this.headTeachers = this.headTeachers.filter(headTeacher => headTeacher.id !== id);
        return this.headTeachers.length < initialLength;
    }
}

// --- Participant Class and Container --- (Renamed from Student)
class Participant {
    constructor(id, username, name, email, headTeacherName, headTeacherId, schoolName, schoolId, totalHoursWorked) {
        this.id = id;
        this.username = username;
        this.name = name;
        this.email = email;
        this.headTeacherName = headTeacherName;
        this.headTeacherId = headTeacherId;
        this.schoolName = schoolName;
        this.schoolId = schoolId;
        this.totalHoursWorked = totalHoursWorked;
    }
    update(newData) {
        if (newData.username) this.username = newData.username;
        if (newData.name) this.name = newData.name;
        if (newData.email) this.email = newData.email;
        if (newData.headTeacherName) this.headTeacherName = newData.headTeacherName;
        if (newData.headTeacherId) this.headTeacherId = newData.headTeacherId;
        if (newData.schoolName) this.schoolName = newData.schoolName;
        if (newData.schoolId) this.schoolId = newData.schoolId;
        // totalHoursWorked is *not* updated here - it should be updated through a separate mechanism
    }
}

class ParticipantContainer {
    constructor() {
        this.participants = [];
    }

    addParticipant(participant) {
        if (!(participant instanceof Participant)) {
            throw new Error("Invalid participant object. Must be an instance of Participant.");
        }
        this.participants.push(participant);
    }
    getAllParticipants() {
        return this.participants;
    }

    getParticipantById(id) {
        return this.participants.find(participant => participant.id === id);
    }

    removeParticipantById(id) {
        const initialLength = this.participants.length;
        this.participants = this.participants.filter(participant => participant.id !== id);
        return this.participants.length < initialLength;
    }

    updateParticipant(id, newData) {
        const participant = this.getParticipantById(id);
        if (participant) {
            participant.update(newData);
            return true;
        }
        return false;
    }
}
// --- ParticipantOccupation Class and Container --- (Renamed from StudentOccupation)
class ParticipantOccupation {
    constructor(participantId, participantUsername, participantName, occupationId, occupationName) {
        this.participantId = participantId;
        this.participantUsername = participantUsername;
        this.participantName = participantName;
        this.occupationId = occupationId;
        this.occupationName = occupationName;
    }
}

class ParticipantOccupationContainer {
    constructor() {
        this.participantOccupations = [];
    }

    addParticipantOccupation(participantOccupation) {
        if (!(participantOccupation instanceof ParticipantOccupation)) {
            throw new Error("Invalid participantOccupation object. Must be an instance of ParticipantOccupation.");
        }
        this.participantOccupations.push(participantOccupation);
    }
    getParticipantOccupations() {
        return this.participantOccupations;
    }
    getParticipantOccupationByParticipantId(participantId) { //New method to find ParticipantOccupation by Participant
        return this.participantOccupations.find(so => so.participantId === participantId);
    }
    removeParticipantOccupation(participantId, occupationId) {
        const initialLength = this.participantOccupations.length;
        this.participantOccupations = this.participantOccupations.filter(po => !(po.participantId === participantId && po.occupationId === occupationId));
        return this.participantOccupations.length < initialLength;
    }
    removeParticipantOccupationByParticipantId(participantId) { //New method to delete by only participantId
        const initialLength = this.participantOccupations.length;
        this.participantOccupations = this.participantOccupations.filter(po => po.participantId !== participantId);
        return this.participantOccupations.length < initialLength;
    }
}
// --- Global Instances ---
const schoolContainer = new SchoolContainer();
const headTeacherContainer = new HeadTeacherContainer();
const participantContainer = new ParticipantContainer();  // Renamed
const participantOccupationContainer = new ParticipantOccupationContainer(); // Renamed


$(document).ready(function () {
     // --- Table name change ---
    const participantTableName = "participantsTable"; // Use a variable for easier updates
    const $participantTable = $(`#${participantTableName}`);

    // --- Event Handlers ---
    $participantTable.find('tbody').on('click', '.edit-button', handleEditParticipantClick);
    $participantTable.find('tbody').on('click', '.cancel-button', handleCancelParticipantClick);
    $participantTable.find('tbody').on('click', '.delete-button', handleDeleteParticipantClick);

    $('#newParticipantBtn').click(showNewParticipantModal);
    $('#saveNewParticipantBtn').click(handleSaveNewParticipant); //modal save button
    $('#addParticipantOccupationBtn').click(handleAddParticipantOccupation); //add participant occupation button

    //  --- IMPORTANT:  Listen for school selection changes ---
    $('#schoolSelect').on('change', handleSchoolSelectionChange);

     $(document).on('participantUpdated', loadParticipantsIntoSelect);
    $(document).on('participantAdded', loadParticipants);
    $(document).on('workshopAdded', loadOccupationsIntoSelect);


    // --- Load Initial Data ---
    loadSchools();       // Load schools *first*
    loadHeadTeachers();  // Then load head teachers
    loadParticipants(); //Renamed function calls
    loadParticipantsIntoSelect();
    loadOccupationsIntoSelect(); // Load occupations into select

    // --- Function Definitions ---
    //--Participant CRUD--
    function handleEditParticipantClick() {
        let row = $(this).closest('tr');
        if ($(this).text() === 'Szerkesztés') {
            startEditingParticipant(row);
        } else {
            finishEditingParticipant(row);
        }
    }

    function startEditingParticipant(row) {
        row.find('input.participant-data').removeAttr('readonly');
        //  DO NOT disable the school select.  Allow changing schools.
        // row.find('.school-select').prop('disabled', false);  // REMOVED
        row.find('.head-teacher-select').prop('disabled', false);


        row.find('.edit-button').text('Mentés');
        let cancelBtn = $('<button class="btn btn-secondary btn-sm cancel-button">Mégse</button>');
        row.find('.edit-button').after(cancelBtn);

        // Store original values
        row.find('input.participant-data').each(function () {
            $(this).data('original-value', $(this).val());
        });
        // Also store the original selected options for the selects
        row.find('.school-select').data('original-value', row.find('.school-select').val());
        row.find('.head-teacher-select').data('original-value', row.find('.head-teacher-select').val());
    }

    function finishEditingParticipant(row) {
        let updatedData = { //get data
            username: row.find('input[data-field="username"]').val(),
            name: row.find('input[data-field="name"]').val(),
            email: row.find('input[data-field="email"]').val(),
            teacher_id: row.find('.head-teacher-select').val(),
            school_id: row.find('.school-select').val(), // Get selected school ID
        };

        let participantId = parseInt(row.find('.participant-id').text(), 10);

        // Add user_id to updatedData - REQUIRED for the server
        updatedData.user_id = participantId;

        // AJAX call to update the participant
        $.ajax({
            url: "../backend/api/participants/update_participant.php", //Changed url
            type: "POST",
            data: updatedData,
            success: function (response, textStatus, jqXHR) {
                if (jqXHR.status === 204) {
                    let participant = participantContainer.getParticipantById(participantId);
                    if (participant) {
                        // Update the participant object in the container
                        participant.username = updatedData.username;
                        participant.name = updatedData.name;
                        participant.email = updatedData.email;
                        participant.headTeacherId = updatedData.teacher_id;
                        participant.schoolId = updatedData.school_id; // Update school ID

                         // Update head teacher name (find by ID)
                        let teacher = headTeacherContainer.getHeadTeacherById(parseInt(updatedData.teacher_id));
                        participant.headTeacherName = teacher ? teacher.name : "";

                         // Update school name (find by ID)
                        let school = schoolContainer.getSchoolById(parseInt(updatedData.school_id));
                        participant.schoolName = school ? school.name : "";
                        $(document).trigger('participantUpdated', participantContainer);

                    }

                    // Update the displayed values in the row
                    row.find('input[data-field="username"]').val(updatedData.username);
                    row.find('input[data-field="name"]').val(updatedData.name);
                    row.find('input[data-field="email"]').val(updatedData.email);
                    row.find('.head-teacher-select').val(updatedData.teacher_id);
                    row.find('.school-select').val(updatedData.school_id);

                    // Set inputs to readonly and clean up
                    row.find('input.participant-data').attr('readonly', true);
                    row.find('.head-teacher-select').prop('disabled', true);
                    row.find('.edit-button').text('Szerkesztés');
                    row.find('.cancel-button').remove();

                    console.log("Participant updated on server:", participantId);

                } else {
                    console.error("Unexpected success status:", jqXHR.status);
                    alert("An unexpected error occurred.  Status: " + jqXHR.status);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Error updating participant:", textStatus, errorThrown, jqXHR.responseText);
                if (jqXHR.status === 404) {
                    alert("Participant not found on the server.");
                } else if (jqXHR.status === 409) {
                    alert("Username or email already exists.");
                } else if (jqXHR.status === 400) {
                    alert("Invalid input. Please check the data.");
                }
                else {
                    alert("Failed to update participant. Error: " + jqXHR.status);
                }
            }
        });
    }

    function handleCancelParticipantClick() {
        let row = $(this).closest('tr');

        // Restore original values for inputs
        row.find('input.participant-data').each(function () {
            $(this).val($(this).data('original-value')).attr('readonly', true);
        });

        // Restore original selected options for selects
        row.find('.school-select').val(row.find('.school-select').data('original-value')).prop('disabled', false);
        row.find('.head-teacher-select').val(row.find('.head-teacher-select').data('original-value')).prop('disabled', true);

        row.find('.cancel-button').remove(); // Remove the cancel button
        row.find('.edit-button').text('Szerkesztés'); // Change edit button text back
    }

    function handleDeleteParticipantClick() {
        let row = $(this).closest('tr');
        if (confirm('Biztosan törölni szeretnéd?')) {
            let participantId = parseInt(row.find('.participant-id').text(), 10);
            $.ajax({
                url: `../backend/api/participants/delete_participant.php?user_id=${participantId}`,  //Changed url
                type: "DELETE",
                success: function(response, textStatus, jqXHR) {
                    if (jqXHR.status === 204) {
                        if (participantContainer.removeParticipantById(participantId)) {
                            row.remove();
                            console.log("Participant removed");
                        } else {
                            console.error("Participant not found locally for deletion.");
                            alert("Participant deleted from the server, but not found locally. Please refresh.");
                        }
                    } else {
                        console.error("Unexpected success status:", jqXHR.status);
                        alert("An unexpected error occurred.  Status: " + jqXHR.status);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error deleting participant:", textStatus, errorThrown, jqXHR.status, jqXHR.responseText);

                    if (jqXHR.status === 404) {
                        alert("Participant not found on the server.");
                        if (participantContainer.removeParticipantById(participantId)) {
                            row.remove();
                        }
                    } else if (jqXHR.status === 400) {
                        alert("Invalid request. Please check the data.");
                    } else {
                        alert("Failed to delete participant. Error: " + jqXHR.status);
                    }
                }
            });
        }
    }
    //--Modal functions--
    function showNewParticipantModal() {
        $('#newParticipantForm')[0].reset();
        $('#newParticipantModal').modal('show');
        //  Crucially, *clear* any previous options in the head teacher select.
        $('#headTeacherSelect').html('');
    }

    function handleSaveNewParticipant() {
        let participantData = { //get all data from modal
            username: $('#participantUsername').val(),
            password: $('#participantPassword').val(),
            name: $('#participantName').val(),
            email: $('#participantEmail').val(),
            teacher_id: $('#headTeacherSelect').val(),  // Selected teacher ID
            school_id: $('#schoolSelect').val(),        // Selected school ID
        };
        //Input validation
        if (!participantData.username || !participantData.password || !participantData.name || !participantData.email || !participantData.teacher_id || !participantData.school_id) {
            alert('Kérlek tölts ki minden mezőt!');
            return;
        }

        $.ajax({
            url: "../backend/api/participants/add_participant.php", //Changed url
            type: "POST",
            data: participantData,
            success: function (response) {
                const newUserId = parseInt(response);

                // Find the selected school and teacher objects
                const selectedSchool = schoolContainer.getSchoolById(parseInt(participantData.school_id));
                const selectedTeacher = headTeacherContainer.getHeadTeacherById(parseInt(participantData.teacher_id));

                const newParticipant = new Participant(
                    newUserId,
                    participantData.username,
                    participantData.name,
                    participantData.email,
                    selectedTeacher ? selectedTeacher.name : "", // Teacher name, or empty string if not found
                    participantData.teacher_id,
                    selectedSchool ? selectedSchool.name : "",  // School name, or empty string if not found
                    participantData.school_id,
                    0
                );

                participantContainer.addParticipant(newParticipant);
                $('#newParticipantModal').modal('hide');
                console.log("Participant added with user_id:", newUserId);
                $(document).trigger('participantAdded', [participantContainer]);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Error adding participant:", textStatus, errorThrown, jqXHR.responseText);
                if (jqXHR.status === 409) {
                    alert("Username or email already exists.");
                } else if (jqXHR.status === 400) {
                    alert("Invalid input.  Please check the data.");
                } else {
                    alert("Failed to add participant.  Error: " + jqXHR.status + " " + jqXHR.responseText);
                }
            }
        });
    }
    //--Load data--
    function loadParticipants() {
        $.ajax({
            url: "../backend/api/participants/get_participants.php", //Changed url
            type: "GET",
            dataType: "json",
            success: function (data) {
                $participantTable.find('tbody').empty();
                participantContainer.participants = [];  // Clear existing participants
                data.forEach(function (participantData) {
                    const participant = new Participant(
                        participantData.user_id,
                        participantData.username,
                        participantData.participant_name,
                        participantData.email,
                        participantData.headTeacherName,
                        participantData.headTeacherId,
                        participantData.schoolName,
                        participantData.school_id,
                        participantData.total_hours_worked
                    );

                    participantContainer.addParticipant(participant); // Add to container
                    addParticipantRow(participant);
                    loadParticipantsIntoSelect();
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Error loading participants:", textStatus, errorThrown, jqXHR.responseText);
                alert("Failed to load participants.  Error: " + jqXHR.status);
            }
        });
    }
    function loadSchools() {
        $.ajax({
            url: "../backend/api/schools/get_schools.php", // You'll need this API endpoint
            type: "GET",
            dataType: "json",
            success: function (data) {
                schoolContainer.empty(); // Clear existing schools
                data.forEach(function (schoolData) {
                    const school = new School(
                        schoolData.school_id,
                        schoolData.name,
                        schoolData.address
                    );
                    schoolContainer.addSchool(school);
                });

                // Populate the school select options
                let schoolOptions = '';
                schoolContainer.getAllSchools().forEach(school => {
                    schoolOptions += `<option value="${school.id}">${school.name}</option>`;
                });
                $('#schoolSelect').html(schoolOptions);

                // Trigger the change event to load initial head teachers
                $('#schoolSelect').trigger('change');
            },
            error: function (xhr, status, error) {
                console.error("Error loading schools:", status, error);
                alert("Hiba történt az iskolák betöltésekor. Kérlek próbáld újra később.");
            }
        });
    }

    function loadHeadTeachers() {
        $.ajax({
            type: "GET",
            url: "../backend/api/teachers/get_teachers.php",
            dataType: 'json',
            success: function (data) {
                headTeacherContainer.empty(); // Clear existing teachers
                data.forEach(function (teacherData) {
                    let teacher = new HeadTeacher(
                        teacherData.teacher_id,
                        teacherData.name,
                        teacherData.email,
                        teacherData.phone,
                        teacherData.school_id // Include schoolId
                    );
                    headTeacherContainer.addHeadTeacher(teacher);
                });

                //  Populate head teachers based on initial school selection (if any)
                handleSchoolSelectionChange();
            },
            error: function (xhr, status, error) {
                console.error("Hiba a tanárok lekérése közben:", xhr, status, error);
                alert("Hiba a tanárok betöltésekor. Kérlek próbáld újra később.");
            }
        });
    }

    // --- IMPORTANT:  Handler for school selection changes ---
    function handleSchoolSelectionChange() {
        const selectedSchoolId = parseInt($('#schoolSelect').val(), 10);

        if (selectedSchoolId) {
            // Filter head teachers by the selected school ID
            const filteredTeachers = headTeacherContainer.getHeadTeachersBySchoolId(selectedSchoolId);

            // Update the head teacher select options
            let teacherOptions = '';
            filteredTeachers.forEach(teacher => {
                teacherOptions += `<option value="${teacher.id}">${teacher.name}</option>`;
            });
            $('#headTeacherSelect').html(teacherOptions);
        } else {
            // If no school is selected, clear the head teacher options
            $('#headTeacherSelect').html('');
        }
    }


    function loadParticipantsIntoSelect() {
        let options = '<option value="">Válassz diákot</option>';
        participantContainer.getAllParticipants().forEach(participant => {
            options += `<option value="${participant.id}">${participant.name} - ${participant.username}</option>`;
        });
        $('#participantSelect').html(options);
    }
      function loadOccupationsIntoSelect() {
        $.ajax({
            url: '../backend/api/workshops/get_workshops.php',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                let options = '<option value="">Válassz foglalkozást</option>';
                data.forEach(function(workshop) {
                    options += `<option value="${workshop.workshop_id}">${workshop.name}</option>`;
                })
                $('#occupationSelectParticipant').html(options);
            },
            error: function (xhr, status, error) {
                console.error("Error loading occupations:", status, error);
                alert("Hiba történt a foglalkozások betöltésekor. Kérlek próbáld újra később.");
            }
        });
    }

    // -- Add Row Function --

    function addParticipantRow(participant) {
        let row = $('<tr>');
        row.append('<td hidden><span class="participant-id">' + participant.id + '</span></td>');
        row.append($('<td>').text(participant.id));
        row.append($('<td>').append($('<input type="text" class="form-control participant-data" data-field="username" readonly>').val(participant.username)));
        row.append($('<td>').append($('<input type="text" class="form-control participant-data" data-field="name" readonly>').val(participant.name)));
        row.append($('<td>').append($('<input type="text" class="form-control participant-data" data-field="email" readonly>').val(participant.email)));

        // --- School Select ---
        let schoolSelect = $('<select class="form-control school-select" disabled></select>');
        schoolContainer.getAllSchools().forEach(school => {
            let option = $('<option>').val(school.id).text(school.name);
            if (parseInt(school.id) === parseInt(participant.schoolId)) {
                option.prop('selected', true);
            }
            schoolSelect.append(option);
        });
        row.append($('<td>').append(schoolSelect));

        // --- Head Teacher Select ---
        let headTeacherSelect = $('<select class="form-control head-teacher-select" disabled></select>');
        headTeacherContainer.getAllHeadTeachers().forEach(ht => { // Use headTeacherContainer
            let option = $('<option>').val(ht.id).text(ht.name);

            if (parseInt(ht.id) === parseInt(participant.headTeacherId)) {
                option.prop('selected', true);
            }

            headTeacherSelect.append(option);
        });
        row.append($('<td>').append(headTeacherSelect));

        row.append($('<td>').append($('<input type="text" class="form-control participant-data" data-field="schoolId" readonly>').val(participant.schoolId)));
        row.append($('<td>').text(participant.totalHoursWorked));

        let actionsCell = $('<td>');
        let editButton = $('<button class="btn btn-primary btn-sm edit-button">Szerkesztés</button>');
        let deleteButton = $('<button class="btn btn-danger btn-sm delete-button">Törlés</button>');
        actionsCell.append(editButton, deleteButton);
        row.append(actionsCell);

        $participantTable.find('tbody').append(row); //Use variable.
    }
    //--Participant Occupation--

    function handleAddParticipantOccupation() {
        let participantId = $('#participantSelect').val();
        let occupationId = $('#occupationSelectParticipant').val();
        if (!participantId || !occupationId) {
            alert("Kérlek válassz diákot és foglalkozást!");
            return;
        }
        participantId = parseInt(participantId);
        occupationId = parseInt(occupationId);

        //Get participant and occupation objects
        let participant = participantContainer.getParticipantById(participantId);
        // let occupation = occupationContainer.getOccupationById(occupationId); //Not used

        if (!participant) { //Check only participant
            console.error("Participant not found");
            return;
        }

        //Create new ParticipantOccupation object
        const participantOccupation = new ParticipantOccupation(participantId, participant.username, participant.name, occupationId, "");

        // Check for existing association
        const existingAssociation = participantOccupationContainer.getParticipantOccupationByParticipantId(participantId);

        //If already exists, remove it first.
        if (existingAssociation) {
            $.ajax({
                url: "../backend/api/participant_workshops/delete_participant_workshop.php", //Changed url
                type: "DELETE",
                data: {
                    user_id: existingAssociation.participantId,
                    workshop_id: existingAssociation.occupationId
                },
                success: function(response) {
                    participantOccupationContainer.removeParticipantOccupationByParticipantId(participantId);
                    console.log("Existing participant-workshop association removed.");
                    addParticipantWorkshop(participantOccupation) //Proceed adding after delete
                },
                error:  function(jqXHR, textStatus, errorThrown) {
                console.error("Error deleting participant-workshop association:", textStatus, errorThrown, jqXHR.responseText);

                    if (jqXHR.status === 400) {
                        alert("Invalid input.  Please check the data.");
                    } else {
                        alert("Failed to delete participant-workshop association. Error: " + jqXHR.status);
                    }
                }

            });
        } else{
            addParticipantWorkshop(participantOccupation);
        }
    }

    function addParticipantWorkshop(participantOccupation){
        $.ajax({
                url: "../backend/api/participant_workshops/add_participant_workshop.php", //Changed url
                type: "POST",
                data: {
                    user_id: participantOccupation.participantId,
                    workshop_id: participantOccupation.occupationId
                },
                success: function (response) {
                    const newMentorWorkshopId = parseInt(response);
                    participantOccupationContainer.addParticipantOccupation(participantOccupation);
                    console.log("Participant-Workshop association added with ID:", newMentorWorkshopId);
                    alert("Mentor-foglalkozás sikeresen hozzárendelve!");

                    $('#participantSelect').val('');
                    $('#occupationSelectParticipant').val('');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("Error adding participant-workshop association:", textStatus, errorThrown, jqXHR.responseText);

                    if (jqXHR.status === 400) {
                        alert("Invalid input.  Please check the data.");
                    } else if(jqXHR.status === 409){
                        alert("Participant workshop already exists")
                    }else{
                        alert("Failed to add participant-workshop association. Error: " + jqXHR.status);
                    }
                }
            });
    }
     return {
        HeadTeacher: HeadTeacher,
        HeadTeacherContainer: HeadTeacherContainer,
        headTeacherContainer: headTeacherContainer
    };
});