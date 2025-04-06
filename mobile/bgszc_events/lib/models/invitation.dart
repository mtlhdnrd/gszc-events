import 'dart:convert'; // Ensure jsonDecode is available if testing standalone

class Invitation {
  final int invitationId;
  final int eventWorkshopId;
  final int studentId; // Renamed from userId to match PHP alias 'user_id'
  final String eventName;
  final String workshopName;
  final String studentUsername; // Corresponds to PHP alias 'participant_name' (previously 'student_name')
  final String status;
  final DateTime date; // Corresponds to PHP alias 'event_date'
  // Missing fields from PHP response: eventId, workshopId, rankingNumber, maxWorkableHours, numberOfMentorsRequired, eventLocation

  Invitation({
    required this.invitationId,
    required this.eventWorkshopId,
    required this.studentId,
    required this.eventName,
    required this.workshopName,
    required this.studentUsername,
    required this.status,
    required this.date,
  });

  factory Invitation.fromJson(Map<String, dynamic> json) {
    try {
      // --- Integer Parsing (Robust) ---
      // Use int.tryParse for safety, provide default if parsing fails or value is null
      final int invitationIdParsed = int.tryParse(json['invitation_id']?.toString() ?? '') ?? 0;
      final int eventWorkshopIdParsed = int.tryParse(json['event_workshop_id']?.toString() ?? '') ?? 0;
      final int studentIdParsed = int.tryParse(json['user_id']?.toString() ?? '') ?? 0; // Matches PHP 'user_id'

      // --- String Parsing (Robust - FIX HERE) ---
      final String statusParsed = json['status'] as String? ?? 'unknown';
      // Note: PHP alias was 'participant_name' or 'student_name' - ensure consistency
      final String studentUsernameParsed = json['participant_name'] as String? ?? json['student_name'] as String? ?? 'N/A';
      final String eventNameParsed = json['event_name'] as String? ?? 'N/A';
      final String workshopNameParsed = json['workshop_name'] as String? ?? 'N/A';

      // --- DateTime Parsing (Robust) ---
      DateTime? dateParsed;
      if (json['event_date'] != null) { // Matches PHP 'event_date'
        // Adjust parsing based on the EXACT format from PHP.
        // If PHP returns 'YYYY-MM-DD HH:MM:SS', DateTime.parse works.
        // If PHP returns just 'YYYY-MM-DD', use DateTime.parse for date only.
        // If PHP returns other formats, you might need Intl package's DateFormat.
        try {
            // Assuming PHP returns a format DateTime.parse understands (like ISO 8601 or YYYY-MM-DD HH:MM:SS)
             dateParsed = DateTime.parse(json['event_date'] as String);
        } catch (e) {
            print("Error parsing date string: ${json['event_date']}. Error: $e");
            dateParsed = DateTime.now(); // Fallback to current time or a default past/future date
        }
      } else {
          print("Warning: 'event_date' is null in JSON.");
          dateParsed = DateTime.now(); // Fallback if date is null
      }


      return Invitation(
        invitationId: invitationIdParsed,
        eventWorkshopId: eventWorkshopIdParsed,
        studentId: studentIdParsed, // Use parsed value
        status: statusParsed,           // Use parsed value
        studentUsername: studentUsernameParsed, // Use parsed value
        eventName: eventNameParsed,         // Use parsed value
        workshopName: workshopNameParsed,     // Use parsed value
        date: dateParsed, // Use parsed value (handle potential null from above)
      );
    } catch (e) {
      print("Error parsing Invitation JSON: $e");
      print("Problematic JSON: ${jsonEncode(json)}"); // Use jsonEncode for better readability
      // Rethrow or handle more gracefully
      throw FormatException("Failed to parse Invitation from JSON: $e");
    }
  }

  // toJson remains the same, assuming you don't need missing fields
  Map<String, dynamic> toJson() {
    return {
      'invitation_id': invitationId,
      'event_workshop_id': eventWorkshopId,
      // Ensure key matches what might be expected if sending this back
      'user_id': studentId, // Match common backend naming
      'event_name': eventName,
      'workshop_name': workshopName,
       // Ensure key matches what might be expected if sending this back
      'participant_name': studentUsername, // Match common backend naming
      'status': status,
       // Format date to string, typically ISO 8601 for JSON
      'event_date': date.toIso8601String(),
    };
  }
}