<?php
    require_once "../../config.php";
    require_once "../../api_utils.php";

if (validate_request("POST", array("name", "date", "location", "status"))) {
    $name = htmlspecialchars($_POST["name"]);
    $date = htmlspecialchars($_POST["date"]);
    $location = htmlspecialchars($_POST["location"]);
    $status = htmlspecialchars($_POST["status"]);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // 1. Insert the new event
        $query = "INSERT INTO `events` (`name`, `date`, `location`, `status`) VALUES (?, ?, ?, ?);";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $date, $location, $status);  // Corrected bind_param type

        if (!$stmt->execute()) {
            throw new Exception("Error inserting event: " . $stmt->error);
        }

        $event_id = $stmt->insert_id;
        $stmt->close();

        // 2. Get all workshops
        $query = "SELECT `workshop_id` FROM `workshops`;";
        $workshop_stmt = $conn->prepare($query);
        if (!$workshop_stmt->execute()) {
           throw new Exception("Error fetching workshops: " . $workshop_stmt->error);
        }

        $workshop_result = $workshop_stmt->get_result();
        $workshop_ids = [];
        while ($row = $workshop_result->fetch_assoc()) {
            $workshop_ids[] = $row['workshop_id'];
        }
        $workshop_stmt->close();

        // 3. Insert event_workshop entries for each workshop
        $insert_query = "INSERT INTO `event_workshop` (`event_id`, `workshop_id`) VALUES (?, ?);"; // Using default values.
        $insert_stmt = $conn->prepare($insert_query);

        foreach ($workshop_ids as $workshop_id) {
            $insert_stmt->bind_param("ii", $event_id, $workshop_id);
            if (!$insert_stmt->execute()) {
               throw new Exception("Error inserting event_workshop: " . $insert_stmt->error);
            }
        }
        $insert_stmt->close();

        // Commit the transaction
        $conn->commit();
        echo $event_id;
        http_response_code(201);

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
        echo "<img src='https://http.cat/500'>";
        http_response_code(500);
    }

} else {
    echo "<img src='https://http.cat/400'>";
    http_response_code(400);
}
?>