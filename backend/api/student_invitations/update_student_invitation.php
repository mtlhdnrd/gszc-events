<?php
// update_student_invitation.php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("POST", array("invitation_id", "status"))) { 

    $invitation_id = intval($_POST["invitation_id"]);
    $status = $_POST["status"];

    // --- Input Validation ---

    // 1. Check if invitation_id exists
    $check_invitation_query = "SELECT 1 FROM student_invitations WHERE invitation_id = ?";
    $check_invitation_stmt = $conn->prepare($check_invitation_query);
    $check_invitation_stmt->bind_param("i", $invitation_id);
    $check_invitation_stmt->execute();
    if ($check_invitation_stmt->get_result()->num_rows === 0) {
        http_response_code(404); // 404 Not Found
        echo "Invitation with invitation_id $invitation_id not found.";
        $check_invitation_stmt->close();
        exit;
    }
    $check_invitation_stmt->close();

    // 2. Validate status
    $valid_statuses = ["pending", "accepted", "refused", "re-accepted"];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo "Invalid status. Must be one of: " . implode(", ", $valid_statuses);
        exit;
    }

    // --- Update `student_invitations` ---
    $query = "UPDATE `student_invitations` SET `status` = ? WHERE `invitation_id` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $invitation_id);

    if ($stmt->execute()) {
        http_response_code(204); // 204 No Content - Successful update
    } else {
        http_response_code(500);
        echo "Error updating invitation: " . $stmt->error;
        echo "<img src='https://http.cat/500'>";

    }
    $stmt->close();

} else {
    http_response_code(400);
    echo "Missing required parameters or invalid request method.";
     echo "<img src='https://http.cat/400'>";

}
?>