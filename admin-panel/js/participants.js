// --- Classes ---
class MentorStudent {
    constructor(userId, name, email, schoolId, schoolName, teacherId, teacherName, username) {
        this.userId = userId;
        this.name = name;
        this.email = email;
        this.schoolId = schoolId;
        this.schoolName = schoolName;
        this.teacherId = teacherId;
        this.teacherName = teacherName;
        this.type = 'student';
        this.username = username;
    }

    toJson() {
        return {
            user_id: this.userId,
            name: this.name,
            email: this.email,
            type: this.type,
            school_id: this.schoolId,
            teacher_id: this.teacherId,
            username: this.username
        };
    }
}

class MentorTeacher {
    constructor(userId, name, email, schoolId, schoolName, username) {
        this.userId = userId;
        this.name = name;
        this.email = email;
        this.schoolId = schoolId;
        this.schoolName = schoolName;
        this.type = 'teacher';
        this.username = username;
    }

    toJson() {
        return {
            user_id: this.userId,
            name: this.name,
            email: this.email,
            type: this.type,
            school_id: this.schoolId,
            teacher_id: null, // Teachers don't have a teacher_id
            username: this.username
        };
    }
}

class MentorStudentContainer {
    constructor() {
        this.students = [];
    }
    addStudent(student) {
        this.students.push(student);
    }
    getStudentById(userId) {
        return this.students.find(s => s.userId === userId);
    }
    getAllStudents() {
        return this.students;
    }
    removeStudentById(userId) {
        const initialLength = this.students.length;
        this.students = this.students.filter(s => s.userId !== userId);
        return this.students.length < initialLength;
    }
    // No update method needed, as we'll update directly on the object
}

class MentorTeacherContainer {
    constructor() {
        this.teachers = [];
    }
    addTeacher(teacher) {
        this.teachers.push(teacher);
    }
    getTeacherById(userId) {
        return this.teachers.find(t => t.userId === userId);
    }
    getAllTeachers() {
        return this.teachers;
    }
    removeTeacherById(userId) {
        const initialLength = this.teachers.length;
        this.teachers = this.teachers.filter(t => t.userId !== userId);
        return this.teachers.length < initialLength;

    }
    // No update method needed
}
// --- Global Instances ---
const mentorStudentContainer = new MentorStudentContainer();
const mentorTeacherContainer = new MentorTeacherContainer();

let currentParticipantType = 'student'; // Keep track of current view

// --- Utility Functions ---
function populateSchoolsDropdown(dropdownId, selectedSchoolId = null) {
    $.ajax({
        url: '../backend/api/schools/get_schools.php',
        type: 'GET',
        dataType: 'json',
        success: function(schools) {
            const dropdown = $(`#${dropdownId}`);
            dropdown.empty();
            dropdown.append('<option value="">Válassz iskolát</option>');
            schools.forEach(school => {
                const option = `<option value="${school.school_id}" ${school.school_id == selectedSchoolId ? 'selected' : ''}>${school.name}</option>`;
                dropdown.append(option);
            });

             // Trigger change event to load teachers if a school is selected
            if(selectedSchoolId) {
                dropdown.trigger('change');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading schools:", status, error);
            alert("Hiba történt az iskolák betöltésekor.");
        }
    });
}

function populateTeachersDropdown(dropdownId, schoolId, selectedTeacherId = null) {
     if (!schoolId) {
        $(`#${dropdownId}`).html('<option value="">Válassz osztályfőnököt</option>');
        return;
    }
    $.ajax({
        url: `../backend/api/teachers/get_teachers.php?school_id=${schoolId}`,
        type: 'GET',
        dataType: 'json',
        success: function(teachers) {
            const dropdown = $(`#${dropdownId}`);
            dropdown.empty();
            dropdown.append('<option value="">Válassz osztályfőnököt</option>');
            teachers.forEach(teacher => {
                const option = `<option value="${teacher.teacher_id}"${teacher.teacher_id == selectedTeacherId ? 'selected' : ''}>${teacher.name}</option>`;
                dropdown.append(option);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error loading teachers:", status, error);
            alert("Hiba történt a tanárok betöltésekor.");
        }
    });
}
function addParticipantRow(participant) {
    const row = `
        <tr data-user-id="${participant.userId}" data-type="${participant.type}">
            <td>${participant.userId}</td>
            <td>${participant.name}</td>
            <td>${participant.email}</td>
            <td>${participant.schoolName}</td>
            <td>${participant.type === 'student' ? (participant.teacherName || '-') : '-'}</td>
            <td>
                <button class="btn btn-sm btn-primary edit-participant-btn">Szerkesztés</button>
                <button class="btn btn-sm btn-danger delete-participant-btn">Törlés</button>
            </td>
        </tr>`;
    $('#participantsTable tbody').append(row);
}

// --- Load Participants ---
function loadParticipants(type) {
    $.ajax({
        url: `../backend/api/participants/get_participants.php?type=${type}`,
        type: 'GET',
        dataType: 'json',
        success: function(participants) {
            $('#participantsTable tbody').empty();
            if (type === 'student') {
                mentorStudentContainer.students = []; //Clear local data
            } else {
                mentorTeacherContainer.teachers = [];
            }

            participants.forEach(participantData => {
                let participant;
                if (type === 'student') {
                    participant = new MentorStudent(
                        participantData.user_id,
                        participantData.name,
                        participantData.email,
                        participantData.school_id,
                        participantData.school_name,
                        participantData.teacher_id,
                        participantData.teacher_name,
                        participantData.username
                    );
                    mentorStudentContainer.addStudent(participant);

                } else {
                    participant = new MentorTeacher(
                        participantData.user_id,
                        participantData.name,
                        participantData.email,
                        participantData.school_id,
                        participantData.school_name,
                        participantData.username
                    );
                    mentorTeacherContainer.addTeacher(participant);
                }
                addParticipantRow(participant);
            });
            $(document).trigger("participantsLoaded");
        },
        error: function(xhr, status, error) {
            console.error("Error loading participants:", status, error);
            alert("Hiba történt a résztvevők betöltésekor.");
        }
    });
}
// --- Event Handlers ---

$(document).ready(function() {

    // Initial load (default to students)
    loadParticipants(currentParticipantType);
    $(document).on('participantsLoaded', initMentorWorkshops);
    $(document).on('participantAdded', loadMentorsIntoSelect);
    $(document).on('workshopAdded', loadOccupationsIntoSelect);
    $(document).on('participantDeleted', loadMentorsIntoSelect);
    $(document).on('workshopDeleted', loadOccupationsIntoSelect);
    $(document).on('workshopUpdated', loadOccupationsIntoSelect);
    // --- Button Clicks ---
    $('#showStudentsBtn').click(function() {
        currentParticipantType = 'student';
        $('#showStudentsBtn').removeClass('btn-secondary').addClass('btn-primary');
        $('#showTeachersBtn').removeClass('btn-primary').addClass('btn-secondary');
        loadParticipants('student');
        $('.student-only-field').show(); // Show student-specific fields
    });

    $('#showTeachersBtn').click(function() {
        currentParticipantType = 'teacher';
        $('#showTeachersBtn').removeClass('btn-secondary').addClass('btn-primary');
        $('#showStudentsBtn').removeClass('btn-primary').addClass('btn-secondary');
        loadParticipants('teacher');
        $('.student-only-field').hide();  //Hide student-specific fields.
    });

    $('#addParticipantBtn').click(function() {
        if (currentParticipantType === 'student') {
            // Clear input fields for the student modal
            $('#addStudentModal').find('input').val('');  
            $('#addStudentModal').find('select').val(''); 

            populateSchoolsDropdown('studentSchool');
            $('#addStudentModal').modal('show');
        } else {
            // Clear input fields for the teacher modal
            $('#addTeacherModal').find('input').val(''); 
            $('#addTeacherModal').find('select').val('');

            populateSchoolsDropdown('teacherSchool');
            $('#addTeacherModal').modal('show');
        }
    });

    // --- Save New Participants ---

    $('#saveStudentBtn').click(function() {
    const name = $('#studentName').val().trim();
    const email = $('#studentEmail').val().trim();
    const username = $('#studentUsername').val().trim();
    const password = $('#studentPassword').val().trim();
    const schoolId = $('#studentSchool').val();
    const teacherId = $('#studentTeacher').val();
    // Basic validation
    if (!name || !email || !schoolId || !teacherId || !username || !password) {
        alert("Minden mező kitöltése kötelező!");
        return;
    }

    const newStudent = new MentorStudent(null, name, email, parseInt(schoolId), null, parseInt(teacherId) || null, null, username);
    // Create a user
    $.ajax({
        url: '../backend/api/participants/add_user.php',
        type: 'POST',
        dataType: 'json', // Expect a JSON response
        data: { username: newStudent.username, password: password },
        success: function(userResponse) {
            const userId = parseInt(userResponse.user_id);  //Get user id
            $.ajax({
                url: '../backend/api/participants/add_participant.php',
                type: 'POST',
                dataType: 'json',
                data: {...newStudent.toJson(), user_id:userId},
                success: function(response) {
                    newStudent.userId = userId; // Set the user ID from the response
                    mentorStudentContainer.addStudent(newStudent);
                    addParticipantRow(newStudent);
                    $('#addStudentModal').modal('hide');

                    // Clear form fields
                    $('#studentName').val('');
                    $('#studentEmail').val('');
                    $('#studentSchool').val('');
                    $('#studentTeacher').val('');
                    $('#studentUsername').val('');
                    $('#studentPassword').val('');
                    $(document).trigger("participantAdded", newStudent);
                },
              error: function(xhr, status, error) {
                console.error("Error adding participant:", status, error, xhr.responseText);
                alert("Hiba történt a résztvevő hozzáadásakor.");
               }
            });

        },
        error: function(xhr, status, error) {
            console.error("Error creating user:", status, error, xhr.responseText);
            alert("Hiba történt a felhasználó hozzáadásakor.");
        }
    });
});

    $('#saveTeacherBtn').click(function() {
        const name = $('#teacherName').val().trim();
        const email = $('#teacherEmail').val().trim();
        const username = $('#teacherUsername').val().trim();
        const password = $('#teacherPassword').val().trim();
        const schoolId = $('#teacherSchool').val();

        if (!name || !email || !schoolId || !username || !password) {
            alert("Minden mező kitöltése kötelező!");
            return;
        }
         const newTeacher = new MentorTeacher(null, name, email, parseInt(schoolId), null, username);
        $.ajax({
            url: '../backend/api/participants/add_user.php',
            type: 'POST',
            dataType: 'json',
            data: {username: username, password: password},
            success: function(userResponse) {
                const userId = parseInt(userResponse.user_id);
                $.ajax({
                url: '../backend/api/participants/add_participant.php',
                type: 'POST',
                dataType: 'json',
                data: { ...newTeacher.toJson(), user_id: userId},
                success: function(response) {

                    newTeacher.userId = userId;
                    mentorTeacherContainer.addTeacher(newTeacher);
                    addParticipantRow(newTeacher);
                    $('#addTeacherModal').modal('hide');
                    // Clear form fields
                    $('#teacherName').val('');
                    $('#teacherEmail').val('');
                    $('#teacherSchool').val('');
                    $('#teacherUsername').val('');
                    $('#teacherPassword').val('');
                    $(document).trigger("participantAdded", newTeacher);
                },
                error: function(xhr, status, error) {
                    console.error("Error adding participant:", status, error, xhr.responseText);
                    alert("Hiba történt a résztvevő hozzáadásakor.");
                }
             });
            },
            error: function(xhr, status, error) {
                console.error("Error creating user:", status, error, xhr.responseText);
                alert("Hiba történt a felhasználó hozzáadásakor.");
        }
        });
    });

    // --- Edit Participant ---
    $('#participantsTable tbody').on('click', '.edit-participant-btn', function() {
    const row = $(this).closest('tr');
    const userId = parseInt(row.data('user-id'));
    const type = row.data('type');

    let participant;
    if (type === 'student') {
        participant = mentorStudentContainer.getStudentById(userId);
        populateTeachersDropdown('editParticipantTeacher', participant.schoolId, participant.teacherId);
    } else {
        participant = mentorTeacherContainer.getTeacherById(userId);
    }

    if (!participant) {
        console.error("Participant not found for editing.");
        alert("Résztvevő nem található.");
        return;
    }

    $('#editParticipantId').val(participant.userId);
    $('#editParticipantType').val(type);  // Store the type
    $('#editParticipantName').val(participant.name);
    $('#editParticipantEmail').val(participant.email);
    populateSchoolsDropdown('editParticipantSchool', participant.schoolId);


    if (type === 'student') {
        $('.student-only-field').show();
    } else {
        $('.student-only-field').hide();
    }

    $('#editParticipantModal').modal('show');
});

$('#saveEditedParticipantBtn').click(function() {
    const userId = parseInt($('#editParticipantId').val(), 10);
    const type = $('#editParticipantType').val();
    const name = $('#editParticipantName').val().trim();
    const email = $('#editParticipantEmail').val().trim();
    const schoolId = $('#editParticipantSchool').val();
    const teacherId = $('#editParticipantTeacher').val(); // Can be null.

    if (!name || !email || !schoolId) {
        alert("Minden mező kitöltése kötelező!");
        return;
    }
    // Get teacher id if student is edited
    if (type === 'student' && !teacherId) {
        alert("Osztályfőnök kiválasztása kötelező!");
        return;
    }

    let participant;
    if (type === 'student') {
        participant = mentorStudentContainer.getStudentById(userId);
        if (!participant) {
            console.error("Student cannot be found");
            return;
        }
        participant.name = name;
        participant.email = email;
        participant.schoolId = parseInt(schoolId);
        participant.teacherId = parseInt(teacherId) || null;
    } else {
        participant = mentorTeacherContainer.getTeacherById(userId);
        if (!participant) {
            console.error("Teacher cannot be found");
            return;
        }
        participant.name = name;
        participant.email = email;
        participant.schoolId = parseInt(schoolId);
    }

    $.ajax({
        url: '../backend/api/participants/update_participant.php',
        type: 'POST', // Your existing code uses POST, adjust if needed
        dataType: 'json',
        data: participant.toJson(),
        success: function(response) {
            // --- START: Update Table Row ---
            const row = $(`#participantsTable tbody tr[data-user-id="${userId}"]`);

            // Update Name and Email (already correct)
            row.find('td:eq(1)').text(name);
            row.find('td:eq(2)').text(email);

            // Update School Name (fetch it)
            $.ajax({
                url: `../backend/api/schools/get_schools.php?school_id=${participant.schoolId}`,
                type: 'GET',
                dataType: 'json', // Expect a single school object
                success: function(schoolData) {
                    if (schoolData && schoolData.name) {
                        row.find('td:eq(3)').text(schoolData.name);
                        participant.schoolName = schoolData.name; // Update local object
                    } else {
                        row.find('td:eq(3)').text('-'); // Fallback if name not found
                        participant.schoolName = null;
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error getting school data for update:", status, error);
                    row.find('td:eq(3)').text('-'); // Fallback on error
                    participant.schoolName = null;
                }
            });

            // Update Teacher Name (fetch it, only for students)
            if (type === 'student') {
                if (participant.teacherId) { // Check if there's a teacher ID
                    $.ajax({
                        url: `../backend/api/teachers/get_teachers.php?teacher_id=${participant.teacherId}`,
                        type: 'GET',
                        dataType: 'json', // Expect a single teacher object
                        success: function(teacherData) {
                            if (teacherData && teacherData.name) {
                                row.find('td:eq(4)').text(teacherData.name);
                                participant.teacherName = teacherData.name; // Update local object
                            } else {
                                row.find('td:eq(4)').text('-'); // Fallback if name not found
                                participant.teacherName = null;
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error getting teacher data for update:", status, error);
                            row.find('td:eq(4)').text('-'); // Fallback on error
                            participant.teacherName = null;
                        }
                    });
                } else {
                    // No teacher assigned
                    row.find('td:eq(4)').text('-');
                    participant.teacherName = null;
                }
            } else {
                // For teachers, the teacher column should be empty/placeholder
                row.find('td:eq(4)').text('-');
            }
            // --- END: Update Table Row ---

            $('#editParticipantModal').modal('hide');
            loadMentorsIntoSelect(); // Refresh the mentor dropdown
        },
        error: function(xhr, status, error) {
            console.error("Error updating participant:", status, error, xhr.responseText);
            alert("Hiba történt a résztvevő frissítésekor.");
        }
    });
});

    // --- Delete Participant ---
    $('#participantsTable tbody').on('click', '.delete-participant-btn', function() {
        const row = $(this).closest('tr');
        const userId = parseInt(row.data('user-id'));
        const type = row.data('type');

        if (confirm('Biztosan törölni szeretnéd?')) {
            $.ajax({
                url: `../backend/api/participants/delete_participant.php?user_id=${userId}`,
                type: 'DELETE',
                success: function(response) {
                      $.ajax({
                        url: `../backend/api/participants/delete_user.php?user_id=${userId}`,
                        type: 'DELETE',
                        success: function(response) {
                            if (type === 'student') {
                                mentorStudentContainer.removeStudentById(userId);
                             } else {
                                mentorTeacherContainer.removeTeacherById(userId);
                             }
                            row.remove();
                            $(document).trigger('participantDeleted');
                        },
                        error: function(xhr, status, error) {
                            console.error("Error deleting user:", status, error, xhr.responseText);
                            alert("Hiba történt a felhasználó törlésekor.");
                        }
                    });

                },
                error: function(xhr, status, error) {
                    console.error("Error deleting participant:", status, error, xhr.responseText);
                    alert("Hiba történt a résztvevő törlésekor.");
                }
            });
        }
    });
     // --- Populate schools and teachers dropdowns on change ---
    $('#studentSchool').change(function() {
        const schoolId = $(this).val();
        populateTeachersDropdown('studentTeacher', schoolId);
    });

    $('#editParticipantSchool').change(function() {
        const schoolId = $(this).val();
        if ($('#editParticipantType').val() === 'student') {
            populateTeachersDropdown('editParticipantTeacher', schoolId);
        }
    });
    function loadMentorsIntoSelect() {
        let mentors;
        if (currentParticipantType == 'student') {
            mentors = mentorStudentContainer.getAllStudents();
        } else {
            mentors = mentorTeacherContainer.getAllTeachers();
        }
        const $mentorSelect = $('#mentorSelect');
        $mentorSelect.empty().append('<option value="">Válassz mentort</option>'); // Clear and add default
        mentors.forEach(mentor => {
            $mentorSelect.append(`<option value="${mentor.userId}">${mentor.name}</option>`);
        });
    }
    function loadOccupationsIntoSelect() {
        $.ajax({
            url: '../backend/api/workshops/get_workshops.php',
            type: 'GET',
            dataType: 'json',
            success: function(workshops) {
                occupationContainer.occupations = []; //Clear local data
                const $occupationSelect = $('#occupationSelect'); // Use jQuery object
                $occupationSelect.empty().append('<option value="">Válassz foglalkozást</option>');

                workshops.forEach(workshop => {
                    // Add to your occupationContainer
                   const occupation = new Occupation(workshop.workshop_id, workshop.name);
                   occupationContainer.addOccupation(occupation);

                    // Add to the dropdown
                    $occupationSelect.append(`<option value="${workshop.workshop_id}">${workshop.name}</option>`);
                });
            },
            error: function(xhr, status, error) {
                console.error("Error loading occupations:", status, error);
                alert("Hiba történt a foglalkozások betöltésekor.");
            }
        });
    }

    $('#addMentorOccupationBtn').on('click', function() {
        const mentorId = $('#mentorSelect').val();
        const occupationId = $('#occupationSelect').val();
        if (!mentorId || !occupationId) {
            alert("Kérlek válassz mentort és foglalkozást is!");
            return;
        }
        const mentorWorkshopData = {
            user_id: parseInt(mentorId),
            workshop_id: parseInt(occupationId),
            ranking_number: 1
        };
        $.ajax({
            url: '../backend/api/mentor_workshops/add_mentor_workshop.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(mentorWorkshopData),
            success: function(response) {
                // Adding mentor to rankings
                $.ajax({
                    url: '../backend/api/rankings/add_mentor_to_rankings.php', 
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ // Send necessary data
                        user_id: parseInt(mentorId),
                        workshop_id: parseInt(occupationId)
                    }),
                    success: function(response2) {
                        console.log('Mentor added to rankings successfully:', response2);
                        console.log('Mentor-Occupation assignment successful:', response);
                        alert('Sikeres hozzárendelés!');
                    },
                    error: function(xhr2, status2, error2) {
                        console.error('Failed to add mentor to rankings:', error2, xhr2.responseText);
                        // Alert user about partial success
                        alert('Hiba történt a mentor rangsorokhoz adása közben. A mentor-foglalkozás hozzárendelés sikeres lehetett.');
                    }
                });

                $('#mentorSelect').val('');
                $('#occupationSelect').val('');
            },
            error: function(xhr, status, error) {
                console.error('Mentor-Occupation assignment failed:', error, xhr.responseText);
                alert('Hiba a hozzárendelés során! Részletek a konzolban.');
            }
        });
    });
    function initMentorWorkshops()
    {
        loadOccupationsIntoSelect();
        loadMentorsIntoSelect();
    }
});