// --- HeadTeacher Class and Container ---
$(document).ready(function() {

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
        row.find('input.head-teacher-data').each(function() {
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
        row.find('input.head-teacher-data').each(function() {
            $(this).val($(this).data('original-value')).attr('readonly', true);
        });
        $(this).remove();
        row.find('.edit-button').text('Szerkesztés');
    }

    function handleDeleteHeadTeacherClick() {
         let row = $(this).closest('tr');
        if (confirm('Biztosan törölni szeretnéd?')) {
             let headTeacherId = parseInt(row.find('.head-teacher-id').text(), 10);
             console.log(headTeacherId);
            if(headTeacherContainer.removeHeadTeacherById(headTeacherId)){ //remove from container
                row.remove();
                 alert("Head teacher removed! (Replace this with AJAX)");
                 //TODO: Add ajax call
            } else {
                 console.error("Head teacher with ID " + headTeacherId + " not found for deletion.");
            }
        }
    }
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
        if(!headTeacherData.name || !headTeacherData.email || !headTeacherData.phoneNumber){
            alert("Kérlek tölts ki minden mezőt!");
            return;
        }
        if(isNaN(parseInt(headTeacherData.phoneNumber))){
            alert("A telefonszám egy szám kell, hogy legyen!");
            return;
        }

        //Find next available ID
         let maxId = 0;
        headTeacherContainer.getAllHeadTeachers().forEach(function(ht) {
            if (ht.id > maxId) {
                maxId = ht.id;
            }
        });
        let newId = maxId + 1;
        const newHeadTeacher = new HeadTeacher(newId, headTeacherData.name, headTeacherData.email, headTeacherData.phoneNumber); //create new object
        headTeacherContainer.addHeadTeacher(newHeadTeacher); //add to container
        addHeadTeacherRow(newHeadTeacher); //add to table
        $('#newHeadTeacherModal').modal('hide'); //hide modal
         alert("Head teacher added! (Replace this with AJAX)");
        // TODO: make ajax call for adding new head teacher

    }

    // -- Load Data --

    function loadHeadTeachers() {
        // TODO: Replace with AJAX call to php/get_osztalyfonokok.php

        $('#headTeachersTable tbody').empty();
        headTeacherContainer.getAllHeadTeachers().forEach(function(headTeacher) {
            addHeadTeacherRow(headTeacher);
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