class Invitation {
  final int invitationId, eventWorkshopId, studentId;
  final String eventName, workshopName, studentUsername, status;
  final DateTime date;

  Invitation({required this.invitationId, required this.eventWorkshopId, required this.studentId, required this.date, required this.eventName, required this.workshopName, required this.studentUsername, required this.status});

  factory Invitation.fromJson(Map<String, dynamic> json) {
    return Invitation(
      invitationId: json['invitation_id'] as int,
      eventWorkshopId: json['event_workshop_id'] as int,
      studentId: json['student_id'] as int,
      date: json['date'] as DateTime, //TODO: Nem biztos hogy jól parseol
      studentUsername: json['student_username'] as String,
      status: json['status'] as String,
      eventName: json['event_name'] as String,
      workshopName: json['workshop_name'] as String
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
