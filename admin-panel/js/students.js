
// --- Student Class and Container ---
class Student {
    constructor(id, username, name, email, headTeacherName, headTeacherId, schoolName, schoolId, totalHoursWorked) {
        this.id = id;
        this.username = username;
        this.name = name;
        this.email = email;
        this.headTeacherName = headTeacherName;
        this.headTeacherId = headTeacherId;
        this.schoolName = schoolName;
        this.schoolId = schoolId; // OM azonosító
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

class StudentContainer {
    constructor() {
        this.students = [];
    }

    addStudent(student) {
        if (!(student instanceof Student)) {
            throw new Error("Invalid student object. Must be an instance of Student.");
        }
        this.students.push(student);
    }
    getAllStudents() {
        return this.students;
    }

    getStudentById(id) {
        return this.students.find(student => student.id === id);
    }

    removeStudentById(id) {
        const initialLength = this.students.length;
        this.students = this.students.filter(student => student.id !== id);
        return this.students.length < initialLength;
    }

    updateStudent(id, newData) {
        const student = this.getStudentById(id);
        if (student) {
            student.update(newData);
            return true;
        }
        return false;
    }
}
// --- HeadTeacher Class and Container ---
class HeadTeacher {
    constructor(id, name, email, phone) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.phone = phone;
    }
    update(newData) {
        if (newData.name) this.name = newData.name;
        if (newData.email) this.email = newData.email;
        if (newData.phone) this.phone = newData.phone;
    }

    toJson() {
        return {
            name: this.name,
            email: this.email,
            phone: this.phone
        };
    }
}
class HeadTeacherContainer {
    constructor() {
        this.headTeachers = [];
    }

    addHeadTeacher(headTeacher) {
        if (!(headTeacher instanceof HeadTeacher)) {
            throw new Error("Invalid headTeacher object.  Must be an instance of HeadTeacher");
        }
        this.headTeachers.push(headTeacher);
    }
    getAllHeadTeachers() {
        return this.headTeachers;
    }
    getHeadTeacherById(id) {
        return this.headTeachers.find(ht => ht.id === id);
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

// --- StudentOccupation Class and Container ---
class StudentOccupation {
    constructor(studentId, studentUsername, studentName, occupationId, occupationName) {
        this.studentId = studentId;
        this.studentUsername = studentUsername;
        this.studentName = studentName;
        this.occupationId = occupationId;
        this.occupationName = occupationName;
    }
}

class StudentOccupationContainer {
    constructor() {
        this.studentOccupations = [];
    }

    addStudentOccupation(studentOccupation) {
        if (!(studentOccupation instanceof StudentOccupation)) {
            throw new Error("Invalid studentOccupation object. Must be an instance of StudentOccupation.");
        }
        this.studentOccupations.push(studentOccupation);
    }
    getStudentOccupations() {
        return this.studentOccupations;
    }
    removeStudentOccupation(studentId, occupationId) {
        const initialLength = this.studentOccupations.length;
        this.studentOccupations = this.studentOccupations.filter(so => !(so.studentId === studentId && so.occupationId === occupationId));
        return this.studentOccupations.length < initialLength;
    }
}


// --- Global Instances ---
const studentContainer = new StudentContainer();
const headTeacherContainer = new HeadTeacherContainer();
const studentOccupationContainer = new StudentOccupationContainer();

$(document).ready(function () {
    // --- Event Handlers ---
    $('#studentsTable tbody').on('click', '.edit-button', handleEditStudentClick);
    $('#studentsTable tbody').on('click', '.cancel-button', handleCancelStudentClick);
    $('#studentsTable tbody').on('click', '.delete-button', handleDeleteStudentClick);
    $('#newStudentBtn').click(showNewStudentModal);
    $('#saveNewStudentBtn').click(handleSaveNewStudent); //modal save button
    $('#addStudentOccupationBtn').click(handleAddStudentOccupation); //add student occupation button

    $(document).on('headTeacherAdded', loadHeadTeachers);
    $(document).on('headTeacherAdded', loadStudents);
    $(document).on('studentAdded', loadStudents);
    $(document).on('workshopAdded', loadOccupationsIntoSelect);


    // --- Load Initial Data ---
    loadHeadTeachers(); // Load head teachers *first* (needed for the dropdown)
    loadStudents();
    loadStudentsIntoSelect(); //for the student-occupation table
    loadOccupationsIntoSelect(); // Load occupations into select

    // --- Function Definitions ---
    //--Student CRUD--
    function handleEditStudentClick() {
        let row = $(this).closest('tr');
        if ($(this).text() === 'Szerkesztés') {
            startEditingStudent(row);
        } else {
            finishEditingStudent(row);
        }
    }

    function startEditingStudent(row) {
        row.find('input.student-data').removeAttr('readonly');
        // Make the head teacher select editable (it's not an input)
        row.find('.head-teacher-select').prop('disabled', false);

        row.find('.edit-button').text('Mentés');
        let cancelBtn = $('<button class="btn btn-secondary btn-sm cancel-button">Mégse</button>');
        row.find('.edit-button').after(cancelBtn);

        row.find('input.student-data, select.head-teacher-select').each(function () {
            $(this).data('original-value', $(this).val());
        });
    }

    function finishEditingStudent(row) {
        let updatedData = { //get data
            username: row.find('input[data-field="username"]').val(),
            name: row.find('input[data-field="name"]').val(),
            email: row.find('input[data-field="email"]').val(),
            teacher_id: row.find('.head-teacher-select').val(),
        };
    
        let studentId = parseInt(row.find('.student-id').text(), 10);
    
        // Add user_id to updatedData - REQUIRED for the server
        updatedData.user_id = studentId;
    
        // AJAX call to update the student
        $.ajax({
            url: "../backend/api/students/update_student.php",
            type: "POST", 
            data: updatedData,
            success: function(response, textStatus, jqXHR) {
                if (jqXHR.status === 204) {
                    let student = studentContainer.getStudentById(studentId);
                    if (student) {
                        student.username = updatedData.username;
                        student.name = updatedData.name;
                        student.email = updatedData.email;
                        student.headTeacherId = updatedData.teacher_id;
                        let teacherName = "";
                        headTeacherContainer.getAllHeadTeachers().forEach(t => {
                            if(parseInt(t.id) === parseInt(updatedData.teacher_id)){
                                teacherName = t.name;
                            }
                        });
                        student.headTeacherName = teacherName;
                     }
    
    
                    row.find('input[data-field="username"]').val(updatedData.username);
                    row.find('input[data-field="name"]').val(updatedData.name);
                    row.find('input[data-field="email"]').val(updatedData.email);
                    row.find('.head-teacher-select').val(updatedData.teacher_id);
    
    
                    row.find('input.student-data').attr('readonly', true);
                    row.find('.head-teacher-select').prop('disabled', true); 
                    row.find('.edit-button').text('Szerkesztés');
                    row.find('.cancel-button').remove();
    
    
                    console.log("Student updated on server:", studentId);
    
                } else {
                    console.error("Unexpected success status:", jqXHR.status);
                    alert("An unexpected error occurred.  Status: " + jqXHR.status);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error updating student:", textStatus, errorThrown, jqXHR.responseText);
                if (jqXHR.status === 404) {
                    alert("Student not found on the server.");
                } else if (jqXHR.status === 409) {
                    alert("Username or email already exists.");
                } else if (jqXHR.status === 400) {
                    alert("Invalid input. Please check the data.");
                }
                else {
                    alert("Failed to update student. Error: " + jqXHR.status);
                }
            }
        });
    }

    function handleCancelStudentClick() {
        let row = $(this).closest('tr');
        row.find('input.student-data, select.head-teacher-select').each(function () {
            $(this).val($(this).data('original-value')).attr('readonly', true);
            row.find('.head-teacher-select').prop('disabled', true);
        });
        $(this).remove();
        row.find('.edit-button').text('Szerkesztés');
    }

    function handleDeleteStudentClick() {
        let row = $(this).closest('tr');
        if (confirm('Biztosan törölni szeretnéd?')) {
            let studentId = parseInt(row.find('.student-id').text(), 10);
                $.ajax({
                url: `../backend/api/students/delete_student.php?user_id=${studentId}`,
                type: "DELETE",
                success: function(response, textStatus, jqXHR) {
                    if (jqXHR.status === 204) {
                        if (studentContainer.removeStudentById(studentId)) {
                            row.remove();
                            console.log("Student removed");
                        } else {
                             console.error("Student not found locally for deletion.");
                             alert("Student deleted from the server, but not found locally. Please refresh.");
                        }
                    } else {
                        console.error("Unexpected success status:", jqXHR.status);
                        alert("An unexpected error occurred.  Status: " + jqXHR.status);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                  console.error("Error deleting student:", textStatus, errorThrown, jqXHR.status, jqXHR.responseText);
    
                    if (jqXHR.status === 404) {
                        alert("Student not found on the server.");
                        if (studentContainer.removeStudentById(studentId)) {
                            row.remove();
                        }
                    } else if (jqXHR.status === 400) {
                        alert("Invalid request. Please check the data.");
                    } else {
                        alert("Failed to delete student. Error: " + jqXHR.status);
                    }
                }
            });
        }
    }
    //--Modal functions--
    function showNewStudentModal() {
        $('#newStudentForm')[0].reset();
        $('#newStudentModal').modal('show');
    }

    function handleSaveNewStudent() {
        let studentData = { //get all data from modal
            username: $('#studentUsername').val(),
            password: $('#studentPassword').val(),
            name: $('#studentName').val(),
            email: $('#studentEmail').val(),
            teacher_id: $('#headTeacherSelect').val(),
            school_id: $('#studentSchoolId').val(),
        };
        //Input validation
        if (!studentData.username || !studentData.password || !studentData.name || !studentData.email || !studentData.teacher_id || !studentData.school_id) {
            alert('Kérlek tölts ki minden mezőt!'); 
            return;
        }
        if (isNaN(parseInt(studentData.school_id))) {
            alert("Az OM azonosító egy szám kell, hogy legyen!");
            return;
        }
            $.ajax({
            url: "../backend/api/students/add_student.php",
            type: "POST",
            data: studentData,
            success: function(response) {
                const newUserId = parseInt(response);
                const newStudent = new Student(
                    newUserId, 
                    studentData.username,
                    studentData.name,
                    studentData.email,
                    $('#headTeacherSelect').text(), 
                    studentData.teacher_id,
                    $('#studentSchoolName').val(), 
                    studentData.school_id,
                    0 
                );
    
                studentContainer.addStudent(newStudent);
                $('#newStudentModal').modal('hide');
                console.log("Student added with user_id:", newUserId);
                $(document).trigger('studentAdded', [studentContainer]);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error adding student:", textStatus, errorThrown, jqXHR.responseText);
                if (jqXHR.status === 409) {
                    alert("Username or email already exists.");
                } else if (jqXHR.status === 400) {
                    alert("Invalid input.  Please check the data.");
                } else {
                    alert("Failed to add student.  Error: " + jqXHR.status + " " + jqXHR.responseText);
                }
            }
        });
    }
    //--Load data--
    function loadStudents() {
        $.ajax({
            url: "../backend/api/students/get_students.php",
            type: "GET",
            dataType: "json",
            success: function(data) {
                $('#studentsTable tbody').empty(); 
                studentContainer.students = [];
                data.forEach(function(studentData) {
                    const student = new Student(
                        studentData.user_id,
                        studentData.username,
                        studentData.student_name,
                        studentData.email,
                        studentData.headTeacherName,
                        studentData.headTeacherId,
                        studentData.schoolName,
                        studentData.school_id,
                        studentData.total_hours_worked
                    );
    
                    studentContainer.addStudent(student);
                    addStudentRow(student);
                    loadStudentsIntoSelect();
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error loading students:", textStatus, errorThrown, jqXHR.responseText);
                alert("Failed to load students.  Error: " + jqXHR.status);
            }
        });
    }

    function loadHeadTeachers() {
        $.ajax({
            type: "GET",
            url: "../backend/api/teachers/get_teachers.php",
            dataType: 'json',
            success: function (data) {
                headTeacherContainer.empty();
                data.forEach(function (teacherData) {
                    let teacher = new HeadTeacher(
                        teacherData.teacher_id,
                        teacherData.name,
                        teacherData.email,
                        teacherData.phone
                    );
                    headTeacherContainer.addHeadTeacher(teacher);
                });
                let options = '';
                headTeacherContainer.getAllHeadTeachers().forEach(ht => {
                    options += `<option value="${ht.id}">${ht.name}</option>`;
                });
                $('#headTeacherSelect').html(options);
            },
            error: function (xhr, status, error) {
                console.error("Hiba a tanárok lekérése közben:", xhr, status, error);
                let errorMessage = "Ismeretlen hiba történt.";
                if (xhr.status === 500) {
                    errorMessage = "Szerverhiba történt. Kérlek, próbáld újra később.";
                }
                alert("Hiba: " + errorMessage);
            }
        });

    }
    function loadStudentsIntoSelect() {
        let options = '<option value="">Válassz diákot</option>';
        studentContainer.getAllStudents().forEach(student => {
            options += `<option value="${student.id}">${student.name} - ${student.username}</option>`;
        });
        $('#studentSelect').html(options);
    }
    function loadOccupationsIntoSelect() {
        $.ajax({
            url: '../backend/api/workshops/get_workshops.php',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                $('#occupationsTable tbody').empty();
                occupationContainer.occupations = [];
                data.forEach(function (occupationData) {
                    const occupation = new Occupation(occupationData.workshop_id, occupationData.name, occupationData.description);
                    occupationContainer.addOccupation(occupation);
                });
                let options = '<option value="">Válassz foglalkozást</option>';
                occupationContainer.getAllOccupations().forEach(occupation => {
                    options += `<option value="${occupation.id}">${occupation.name}</option>`;
                });
                $('#occupationSelectStudent').html(options);
            },
            error: function (xhr, status, error) {
                console.error("Error loading occupations:", status, error);
                alert("Hiba történt a foglalkozások betöltésekor. Kérlek próbáld újra később.");
            }
        });
    }

    // -- Add Row Function --

    function addStudentRow(student) {
        let row = $('<tr>');
    row.append('<td hidden><span class="student-id">' + student.id + '</span></td>');
    row.append($('<td>').text(student.id));
    row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="username" readonly>').val(student.username)));
    row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="name" readonly>').val(student.name)));
    row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="email" readonly>').val(student.email)));

    let headTeacherSelect = $('<select class="form-control head-teacher-select" disabled></select>'); // Initially disabled

    headTeacherContainer.getAllHeadTeachers().forEach(ht => {
        let option = $('<option>').val(ht.id).text(ht.name);

        if (parseInt(ht.id) === parseInt(student.headTeacherId)) {
            option.prop('selected', true); 
        }

        headTeacherSelect.append(option);
    });

    row.append($('<td>').append(headTeacherSelect));

    row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="schoolName" readonly>').val(student.schoolName)));
    row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="schoolId" readonly>').val(student.schoolId)));
    row.append($('<td>').text(student.totalHoursWorked)); 

    let actionsCell = $('<td>');
    let editButton = $('<button class="btn btn-primary btn-sm edit-button">Szerkesztés</button>');
    let deleteButton = $('<button class="btn btn-danger btn-sm delete-button">Törlés</button>');
    actionsCell.append(editButton, deleteButton);
    row.append(actionsCell);

    $('#studentsTable tbody').append(row);
    }
    //--Student Occupation--

    function handleAddStudentOccupation() {
        let studentId = $('#studentSelect').val();
        let occupationId = $('#occupationSelectStudent').val();
        if (!studentId || !occupationId) {
            alert("Kérlek válassz diákot és foglalkozást!");
            return;
        }
    
        //Get student and occupation objects
        let student = studentContainer.getStudentById(parseInt(studentId));
        let occupation = occupationContainer.getOccupationById(parseInt(occupationId));
    
        if (!student || !occupation) {
            console.error("Student or occupation not found");
            return;
        }
    
        //Create new StudentOccupation object
        const studentOccupation = new StudentOccupation(parseInt(studentId), student.username, student.name, parseInt(occupationId), occupation.occupationName);
    
    
        $.ajax({
            url: "../backend/api/student_workshops/add_student_workshop.php",
            type: "POST",
            data: {
                user_id: studentOccupation.studentId, 
                workshop_id: studentOccupation.occupationId
            },
            success: function(response) {
                const newMentorWorkshopId = parseInt(response);
                studentOccupationContainer.addStudentOccupation(studentOccupation);
                console.log("Student-Workshop association added with ID:", newMentorWorkshopId);
                alert("Mentor-foglalkozás sikeresen felvéve!");
    
                $('#studentSelect').val('');
                $('#occupationSelectStudent').val(''); 
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error adding student-workshop association:", textStatus, errorThrown, jqXHR.responseText);
    
                if (jqXHR.status === 400) {
                    alert("Invalid input.  Please check the data.");
                } else if(jqXHR.status === 409){
                    alert("Student workshop already exists")
                }else{
                    alert("Failed to add student-workshop association. Error: " + jqXHR.status);
                }
            }
        });
    }
    return {
        // ... other exports ...
        HeadTeacher: HeadTeacher,
        HeadTeacherContainer: HeadTeacherContainer,
        headTeacherContainer: headTeacherContainer
    };
});