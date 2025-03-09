<?php
// invitation_functions.php

require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php"; // Adatbázis kapcsolat

function sendInvitations(int $eventWorkshopId, mysqli $db) {
    $eventWorkshop = getEventWorkshopById($eventWorkshopId, $db);

    if (!$eventWorkshop) {
        throw new Exception("Event workshop not found with ID: $eventWorkshopId");
    }

    $acceptedCount = getAcceptedInvitationCount($eventWorkshopId, $db);
    $remaining = $eventWorkshop['number_of_mentors_required'] - $acceptedCount;

    if ($remaining <= 0) {
        return;
    }

    $studentsToInvite = getStudentsForInvitations($eventWorkshopId, $remaining, $db);

    foreach ($studentsToInvite as $student) {
        createInvitation($eventWorkshopId, $student['user_id'], $student['ranking_number'], $db);
        //TODO: send push notif
    }
}

function updateInvitationStatus(int $invitationId, string $status, mysqli $db) {
    if (!in_array($status, ['accepted', 'rejected', 'reaccepted'])) {
        return false;
    }

    $invitation = getInvitationById($invitationId, $db);
    if (!$invitation) {
        return false;
    }
    if($invitation['status'] === 'pending'){
        $stmt = $db->prepare("UPDATE student_invitations SET status = ? WHERE invitation_id = ?");
        $stmt->bind_param("si", $status, $invitationId);
        $stmt->execute();
    }

    if ($status === 'accepted' || $status === 'reaccepted') {
        $stmt = $db->prepare("
            UPDATE student_invitations
            SET status = 'rejected'
            WHERE event_workshop_id = ?
            AND invitation_id != ?
            AND status = 'pending'
        ");
        $stmt->bind_param("ii", $invitation['event_workshop_id'], $invitationId);
        $stmt->execute();
    }
    if ($status == 'accepted' || $status == 'rejected') {
      sendInvitations($invitation['event_workshop_id'], $db);
    }

    return true;
}


function getEventWorkshopById(int $eventWorkshopId, mysqli $db) {
    $stmt = $db->prepare("SELECT * FROM event_workshop WHERE event_workshop_id = ?");
    $stmt->bind_param("i", $eventWorkshopId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getAcceptedInvitationCount(int $eventWorkshopId, mysqli $db): int {
    $stmt = $db->prepare("SELECT COUNT(*) FROM student_invitations WHERE event_workshop_id = ? AND status IN ('accepted', 'reaccepted')");
     $stmt->bind_param("i", $eventWorkshopId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0]; // fetch_row az első sort adja vissza, numerikus indexekkel
}

function getStudentsForInvitations(int $eventWorkshopId, int $limit, mysqli $db): array {
     $stmt = $db->prepare("
        SELECT s.user_id, r.ranking_number
        FROM students s
        JOIN rankings r ON s.user_id = r.user_id
        WHERE r.event_workshop_id = ?
        AND s.user_id NOT IN (
            SELECT user_id
            FROM student_invitations
            WHERE event_workshop_id = ?
            AND status IN ('accepted', 'rejected', 'reaccepted', 'pending')
        )
        ORDER BY r.ranking_number ASC
        LIMIT ?
    ");
    $stmt->bind_param("iii", $eventWorkshopId, $eventWorkshopId, $limit); //Kétszer kell megadni.
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function createInvitation(int $eventWorkshopId, int $userId, int $rankingNumber, mysqli $db): void {
   $stmt = $db->prepare("INSERT INTO student_invitations (event_workshop_id, user_id, ranking_number, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iii", $eventWorkshopId, $userId, $rankingNumber);
    $stmt->execute();
}

function getInvitationById(int $invitationId, mysqli $db) {
   $stmt = $db->prepare("SELECT * FROM student_invitations WHERE invitation_id = ?");
    $stmt->bind_param("i", $invitationId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}