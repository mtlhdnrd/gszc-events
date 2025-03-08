// --- HeadTeacher Class and Container ---
$(document).ready(function () {

    // --- Event Handlers ---
    $('#headTeachersTable tbody').on('click', '.edit-button', handleEditHeadTeacherClick);
    $('#headTeachersTable tbody').on('click', '.cancel-button', handleCancelHeadTeacherClick);
    $('#headTeachersTable tbody').on('click', '.delete-button', handleDeleteHeadTeacherClick);
    $('#newHeadTeacherBtn').on('click', showNewHeadTeacherModal);
    $('#saveNewHeadTeacherBtn').click(handleSaveNewHeadTeacher); //modal save button


    // --- Load Initial Data ---
    loadHeadTeachers();


    // --- Function Definitions ---

    // -- Head Teacher CRUD --
    function handleEditHeadTeacherClick() {
        let row = $(this).closest('tr');
        if ($(this).text() === 'Szerkesztés') {
            startEditingHeadTeacher(row);
        } else {
            finishEditingHeadTeacher(row);
        }
    }
    function startEditingHeadTeacher(row) {
        row.find('input.head-teacher-data').removeAttr('readonly');
        row.find('.edit-button').text('Mentés');
        let cancelBtn = $('<button class="btn btn-secondary btn-sm cancel-button">Mégse</button>');
        row.find('.edit-button').after(cancelBtn);
        row.find('input.head-teacher-data').each(function () {
            $(this).data('original-value', $(this).val());
        });
    }
    function finishEditingHeadTeacher(row) {
        //TODO: make ajax call for updating head teacher
        let updatedData = {
            name: row.find('input[data-field = "name"]').val(),
            email: row.find('input[data-field = "email"]').val(),
            phoneNumber: row.find('input[data-field = "phoneNumber"]').val()
        };
        let headTeacherId = parseInt(row.find('.head-teacher-id').text(), 10);

        if (headTeacherContainer.updateHeadTeacher(headTeacherId, updatedData)) {
            console.log("Head teacher updated in container.  Ready to save to server:", headTeacherId, updatedData);
            alert("Head teacher updated! (Replace this with AJAX)"); // Replace with AJAX call
            row.find('input.head-teacher-data').attr('readonly', true);
            row.find('.edit-button').text('Szerkesztés');
            row.find('.cancel-button').remove();

        } else {
            console.error("Head teacher with ID " + headTeacherId + " not found for update.");
            alert("Head teacher with ID " + headTeacherId + " not found for update.");
        }

    }

    function handleCancelHeadTeacherClick() {
        let row = $(this).closest('tr');
        row.find('input.head-teacher-data').each(function () {
            $(this).val($(this).data('original-value')).attr('readonly', true);
        });
        $(this).remove();
        row.find('.edit-button').text('Szerkesztés');
    }

    function handleDeleteHeadTeacherClick() {
        let row = $(this).closest('tr');
        let headTeacherId = parseInt(row.find('.head-teacher-id').text(), 10);
        if (headTeacherContainer.removeHeadTeacherById(headTeacherId)) { //remove from container
            //TODO: Add ajax call remove headteacher
            deleteTeacher(headTeacherId, row);
        } else {
            console.error("Head teacher with ID " + headTeacherId + " not found for deletion.");
        }

    }
    function deleteTeacher(teacherId, row) {
        if (confirm('Biztosan törölni szeretnéd ezt a tanárt?')) {
            $.ajax({
                type: "DELETE",
                url: `../backend/api/teachers/delete_teacher.php?teacher_id=${teacherId}`,
                dataType: 'json',
                success: function (data) {
                    console.log("Tanár sikeresen törölve:", data);
                    row.remove(); // Remove the row from the table
                    alert(data.message);
                },
                error: function (xhr, status, error) {
                    console.error("Hiba a tanár törlése közben:", xhr, status, error);
                    let errorMessage = "Ismeretlen hiba történt.";

                    if (xhr.status === 400) {
                        try {
                            let errorData = JSON.parse(xhr.responseText);
                            errorMessage = errorData.message;
                        } catch (e) {
                            errorMessage = "Érvénytelen kérés.";
                        }
                    } else if (xhr.status === 404) {
                        errorMessage = "A törlendő tanár nem található.";
                    } else if (xhr.status === 500) {
                        errorMessage = "Szerverhiba történt. Kérlek, próbáld újra később.";
                    }
                    alert("Hiba: " + errorMessage);
                }
            });
        }
    }

    // Example usage (assuming you have a delete button in each table row):
    // Event Delegation - this is the BEST way to handle dynamically added rows
    $('#teachersTable tbody').on('click', '.delete-teacher-button', function () {
        let row = $(this).closest('tr');
        let teacherId = parseInt(row.find('td:first-child').text(), 10); // Get teacher ID from the first <td>
        deleteTeacher(teacherId, row);
    });

    // Non-delegated event handler (only works for elements present on page load) - NOT RECOMMENDED
    /*
    $('.delete-teacher-button').click(function() {
        let row = $(this).closest('tr');
        let teacherId = parseInt(row.find('td:first-child').text(), 10);
        deleteTeacher(teacherId, row);
    });
    */
    //--Modal Window--

    function showNewHeadTeacherModal() {
        $('#newHeadTeacherForm')[0].reset();
        $('#newHeadTeacherModal').modal('show');
    }

    function handleSaveNewHeadTeacher() {
        let headTeacherData = { //get data from modal
            name: $('#headTeacherName').val(),
            email: $('#headTeacherEmail').val(),
            phoneNumber: $('#headTeacherPhoneNumber').val()
        };
        //Input validation
        if (!headTeacherData.name || !headTeacherData.email || !headTeacherData.phoneNumber) {
            alert("Kérlek tölts ki minden mezőt!");
            return;
        }
        if (isNaN(parseInt(headTeacherData.phoneNumber))) {
            alert("A telefonszám egy szám kell, hogy legyen!");
            return;
        }

        //Find next available ID
        let maxId = 0;
        headTeacherContainer.getAllHeadTeachers().forEach(function (ht) {
            if (ht.id > maxId) {
                maxId = ht.id;
            }
        });
        let newId = maxId + 1;
        const newHeadTeacher = new HeadTeacher(newId, headTeacherData.name, headTeacherData.email, headTeacherData.phoneNumber); //create new object
        headTeacherContainer.addHeadTeacher(newHeadTeacher); //add to container
        addHeadTeacherRow(newHeadTeacher); //add to table
        $('#newHeadTeacherModal').modal('hide'); //hide modal
        let headteacherData = newHeadTeacher.toJson();
        // TODO: error handling
        $.ajax({
            type: "POST",
            url: "../backend/api/teachers/add_teacher.php",
            dataType: "json",
            data: headteacherData,
            success: function (data) {
                console.log(data.message);
            }
        });

    }

    // -- Load Data --

    function loadHeadTeachers() {
        // TODO: Replace with AJAX call to php/get_osztalyfonokok.php
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

                $('#headTeachersTable tbody').empty();
                headTeacherContainer.getAllHeadTeachers().forEach(function (headTeacher) {
                    addHeadTeacherRow(headTeacher);
                });
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



    // -- Add Row --

    function addHeadTeacherRow(headTeacher) {
        let row = $('<tr>');
        row.append('<td hidden><span class="head-teacher-id">' + headTeacher.id + '</span></td>');
        row.append($('<td>').append($('<input type="text" class="form-control head-teacher-data" data-field="name" readonly>').val(headTeacher.name)));
        row.append($('<td>').append($('<input type="text" class="form-control head-teacher-data" data-field="email" readonly>').val(headTeacher.email)));
        row.append($('<td>').append($('<input type="text" class="form-control head-teacher-data" data-field="phoneNumber" readonly>').val(headTeacher.phoneNumber)));


        let actionsCell = $('<td>');
        let editButton = $('<button class="btn btn-primary btn-sm edit-button">Szerkesztés</button>');
        let deleteButton = $('<button class="btn btn-danger btn-sm delete-button">Törlés</button>');
        actionsCell.append(editButton, deleteButton);

        row.append(actionsCell);
        $('#headTeachersTable tbody').append(row);
    }
});