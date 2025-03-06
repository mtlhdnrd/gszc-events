// --- Student Class and Container ---
class Student {
    constructor(id, username, password, name, email, headTeacherName, schoolName, schoolId, totalHoursWorked) {
        this.id = id;
        this.username = username;
        this.password = password; 
        this.name = name;
        this.email = email;
        this.headTeacherName = headTeacherName;
        this.schoolName = schoolName;
        this.schoolId = schoolId; // OM azonosító
        this.totalHoursWorked = totalHoursWorked;
    }
    update(newData) {
        if (newData.username) this.username = newData.username;
        if (newData.password) this.password = newData.password; 
        if (newData.name) this.name = newData.name;
        if (newData.email) this.email = newData.email;
        if (newData.headTeacherName) this.headTeacherName = newData.headTeacherName;
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
    constructor(id, name, email) {
        this.id = id;
        this.name = name;
        this.email = email;
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

$(document).ready(function() {
    // --- Event Handlers ---
    $('#studentsTable tbody').on('click', '.edit-button', handleEditStudentClick);
    $('#studentsTable tbody').on('click', '.cancel-button', handleCancelStudentClick);
    $('#studentsTable tbody').on('click', '.delete-button', handleDeleteStudentClick);
    $('#newStudentBtn').click(showNewStudentModal);
    $('#saveNewStudentBtn').click(handleSaveNewStudent); //modal save button
    $('#addStudentOccupationBtn').click(handleAddStudentOccupation); //add student occupation button


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

        row.find('input.student-data, select.head-teacher-select').each(function() {
            $(this).data('original-value', $(this).val());
        });
    }

    function finishEditingStudent(row) {
         let updatedData = { //get data
            username: row.find('input[data-field="username"]').val(),
            password: row.find('input[data-field="password"]').val(), // Hash this on the server!
            name: row.find('input[data-field="name"]').val(),
            email: row.find('input[data-field="email"]').val(),
            headTeacherName: row.find('.head-teacher-select').val(),
            schoolName: row.find('input[data-field="schoolName"]').val(),
            schoolId: row.find('input[data-field="schoolId"]').val()
        };

        let studentId = parseInt(row.find('.student-id').text(), 10);

        if (studentContainer.updateStudent(studentId, updatedData)) {
             console.log("Student updated in container. Ready to save to server:", studentId, updatedData);
            alert("Student updated! (Replace this with AJAX)"); // Replace with AJAX call
            //TODO: Add ajax

            row.find('input.student-data').attr('readonly', true);
            row.find('.head-teacher-select').prop('disabled', true); // Disable the select
            row.find('.edit-button').text('Szerkesztés');
            row.find('.cancel-button').remove();

        } else {
            console.error("Student with ID " + studentId + " not found for update.");
            alert("Student with ID " + studentId + " not found for update.");
        }
    }

    function handleCancelStudentClick() {
        let row = $(this).closest('tr');
        row.find('input.student-data, select.head-teacher-select').each(function() {
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
            if(studentContainer.removeStudentById(studentId)){
                row.remove();
                 alert("Student removed! (Replace this with AJAX)"); // Replace with AJAX call
                 //TODO: Add ajax
            } else {
                 console.error("Student with ID " + studentId + " not found for deletion.");
            }
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
            password: $('#studentPassword').val(), // Hash this on the server!
            name: $('#studentName').val(),
            email: $('#studentEmail').val(),
            headTeacherName: $('#headTeacherSelect').val(),
            schoolName: $('#studentSchoolName').val(),
            schoolId: $('#studentSchoolId').val(),
            totalHoursWorked: 0 // Set initial hours to 0
        };
         //Input validation
        if (!studentData.username || !studentData.password || !studentData.name || !studentData.email || !studentData.headTeacherName || !studentData.schoolName || !studentData.schoolId) {
            alert('Kérlek tölts ki minden mezőt!'); // Or a more user-friendly message
            return;
        }
        if (isNaN(parseInt(studentData.schoolId))) {
            alert("Az OM azonosító egy szám kell, hogy legyen!");
            return;
        }
        // Find the next available ID
        let maxId = 0;
        studentContainer.getAllStudents().forEach(function(student) {
            if (student.id > maxId) {
                maxId = student.id;
            }
        });
        let newId = maxId + 1;
        // Create a new Student object
        const newStudent = new Student(newId, studentData.username, studentData.password, studentData.name, studentData.email, studentData.headTeacherName, studentData.schoolName, studentData.schoolId, studentData.totalHoursWorked);
        studentContainer.addStudent(newStudent); //add to container
        addStudentRow(newStudent); //add to table
        $('#newStudentModal').modal('hide'); //hide modal
        alert("Student added! (Replace this with AJAX)"); // Replace with AJAX
        //TODO: Add ajax call
    }
    //--Load data--
    function loadStudents() {
        // TODO: Replace with AJAX call
        // Placeholder data:
        const student1 = new Student(1, "user1", "pass1", "John Doe", "john@example.com", "Teacher Smith", "Example School", "12345678901", 10);
        const student2 = new Student(2, "user2", "pass2", "Jane Doe", "jane@example.com", "Teacher Jones", "Another School", "98765432109", 5);
        studentContainer.addStudent(student1);
        studentContainer.addStudent(student2);

        $('#studentsTable tbody').empty();
        studentContainer.getAllStudents().forEach(function(student) {
            addStudentRow(student);
        });
    }

    function loadHeadTeachers() {
        // TODO: Replace with AJAX call
        // Placeholder data:
        const headTeacher1 = new HeadTeacher(1, "Teacher Smith", "smith@example.com");
        const headTeacher2 = new HeadTeacher(2, "Teacher Jones", "jones@example.com");
        headTeacherContainer.addHeadTeacher(headTeacher1);
        headTeacherContainer.addHeadTeacher(headTeacher2);

        // Populate the select dropdown in the modal:
        let options = '';
        headTeacherContainer.getAllHeadTeachers().forEach(ht => {
            options += `<option value="${ht.name}">${ht.name}</option>`;
        });
        $('#headTeacherSelect').html(options);
    }
     function loadStudentsIntoSelect() {
        let options = '<option value="">Válassz diákot</option>';
        studentContainer.getAllStudents().forEach(student => {
            options += `<option value="${student.id}">${student.name} - ${student.username}</option>`;
        });
        $('#studentSelect').html(options);
    }
      function loadOccupationsIntoSelect() { //from occupations.js
        let options = '<option value="">Válassz foglalkozást</option>';
        console.log(occupationContainer.getAllOccupations());
        occupationContainer.getAllOccupations().forEach(occupation => {
            options += `<option value="${occupation.id}">${occupation.name}</option>`;
        });
        console.log(options);
        $('#occupationSelectStudent').html(options);
    }

    // -- Add Row Function --

    function addStudentRow(student) {
        let row = $('<tr>');
        row.append('<td hidden><span class="student-id">' + student.id + '</span></td>');
        row.append($('<td>').text(student.id));
        row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="username" readonly>').val(student.username)));
        row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="password" readonly>').val(student.password))); // Show hashed password (or a placeholder)
        row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="name" readonly>').val(student.name)));
        row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="email" readonly>').val(student.email)));

        // Head teacher dropdown (for editing)
        let headTeacherSelect = $('<select class="form-control head-teacher-select" disabled></select>'); // Initially disabled
        headTeacherContainer.getAllHeadTeachers().forEach(ht => { //get all head teachers
            let option = $('<option>').val(ht.name).text(ht.name);
            if (ht.name === student.headTeacherName) {
                option.attr('selected', 'selected'); // Select the current head teacher
            }
            headTeacherSelect.append(option);
        });
        row.append($('<td>').append(headTeacherSelect));

        row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="schoolName" readonly>').val(student.schoolName)));
        row.append($('<td>').append($('<input type="text" class="form-control student-data" data-field="schoolId" readonly>').val(student.schoolId)));
        row.append($('<td>').text(student.totalHoursWorked)); // Display total hours

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
        let occupationId = $('#occupationSelect').val();
        if(!studentId || !occupationId){
            alert("Kérlek válassz diákot és foglalkozást!");
            return;
        }

        //Get student and occupation objects
        let student = studentContainer.getStudentById(parseInt(studentId));
        let occupation = occupationContainer.getOccupationById(parseInt(occupationId));

        if(!student || !occupation){
            console.error("Student or occupation not found");
            return;
        }

        //Create new StudentOccupation object
        const studentOccupation = new StudentOccupation(parseInt(studentId), student.username, student.name, parseInt(occupationId), occupation.name);
        studentOccupationContainer.addStudentOccupation(studentOccupation); //add to container
        //TODO: Add ajax
         console.log("Adding occupation to student:", { studentId, occupationId});
        alert("Adding occupation to student! (Replace this with AJAX)");
        //Clear form (optional)
        $('#studentSelect').val('');
        $('#occupationSelect').val('');
    }
});