
class Invitation {
  final int invitationId, eventWorkshopId, studentId;
  final String eventName, workshopName, studentUsername, status;
  final DateTime date;

  Invitation({required this.invitationId, required this.eventWorkshopId, required this.studentId, required this.date, required this.eventName, required this.workshopName, required this.studentUsername, required this.status});

  factory Invitation.fromJson(Map<String, dynamic> json) {
    return Invitation(
      invitationId: int.parse(json['invitation_id'].toString()),
      eventWorkshopId: int.parse(json['event_workshop_id'].toString()),
      studentId: int.parse(json['user_id'].toString()),
      date: DateTime.parse(json['date']),
      studentUsername: json['student_name'],
      status: json['status'],
      eventName: json['event_name'],
      workshopName: json['workshop_name']
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'invitation_id': invitationId,
      'event_workshop_id': eventWorkshopId,
      'student_id': studentId,
      'date':date,
      'student_username': studentUsername,
      'status': status,
      'event_name': eventName,
      'workshop_name': workshopName
    };
  }
}
