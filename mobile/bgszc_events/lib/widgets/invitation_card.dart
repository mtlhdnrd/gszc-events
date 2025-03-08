import 'package:flutter/material.dart';
import 'package:bgszc_events/models/invitation.dart';
import 'package:bgszc_events/services/invitation_service.dart';
import 'package:intl/intl.dart';

class InvitationCard extends StatelessWidget {
  final Invitation invitation;
  final VoidCallback onRefresh;

  InvitationCard({super.key, required this.invitation, required this.onRefresh});

  final InvitationService _invitationService = InvitationService();

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.all(8),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Esemény: ${invitation.eventName}',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            Text('Foglalkozás: ${invitation.workshopName}'),
            SizedBox(height: 8),
            Text('Dátum: ${DateFormat('yyyy-MM-dd – kk:mm').format(invitation.date.toLocal())}'),
            SizedBox(height: 16),
            _buildStatusOrButtons(context),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusOrButtons(BuildContext context) {
    if (invitation.status == 'pending') {
      return Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          ElevatedButton(
            onPressed: () async {
              try {
                await _invitationService.acceptInvitation(invitation.invitationId);
                onRefresh();
              } catch (e) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Hiba az elfogadás során: $e')),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: Text('Elfogad'),
          ),
          ElevatedButton(
            onPressed: () async {
              try {
                await _invitationService.rejectInvitation(invitation.invitationId);
                onRefresh();
              } catch (e) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Hiba az elutasítás során: $e')),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: Text('Elutasít'),
          ),
        ],
      );
    } else {
      // ... (a többi része változatlan) ...
      String statusText;
      Color backgroundColor;

      if (invitation.status == 'accepted') {
        statusText = 'Elfogadva';
        backgroundColor = Colors.green[100]!; // Világosabb zöld
      } else if (invitation.status == 'rejected') {
        statusText = 'Elutasítva';
        backgroundColor = Colors.red[100]!; // Világosabb piros
      } else if (invitation.status == 'reaccepted') {
        statusText = 'Újra elfogadva';
        backgroundColor = Colors.green[100]!;
      }
      else {
        statusText = 'Ismeretlen státusz';
        backgroundColor = Colors.grey[300]!; // Szürke, ha ismeretlen
      }

      return Container(
        padding: EdgeInsets.symmetric(vertical: 10, horizontal: 16),
        decoration: BoxDecoration(
          color: backgroundColor,
          borderRadius: BorderRadius.circular(8), // Lekerekített sarkok
        ),
        child: Text(
          statusText,
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          textAlign: TextAlign.center, // Középre igazított szöveg
        ),
      );
    }
  }
}