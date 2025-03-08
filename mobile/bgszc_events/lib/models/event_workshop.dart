
class EventWorkshop {
  final int eventWorkshopId, maxWorkableHours;
  final String eventName, workshopName;

  EventWorkshop(
      {required this.eventWorkshopId,
      required this.maxWorkableHours,
      required this.eventName,
      required this.workshopName});

  factory EventWorkshop.fromJson(Map<String, dynamic> json) {
    return EventWorkshop(
        eventWorkshopId: json['event_workshop_id'],
        maxWorkableHours: json['max_workable_hours'],
        eventName: json['event_name'],
        workshopName: json['workshop_name']);
  }
  Map<String, dynamic> toJson(){
    return{
      'event_workshop_id':eventWorkshopId,
      'max_workable_hours':maxWorkableHours,
      'event_name':eventName,
      'workshop_name':workshopName
    };
  }
}
