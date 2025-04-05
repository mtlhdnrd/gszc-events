import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:bgszc_events/models/invitation.dart';
import 'package:bgszc_events/utils/api_constants.dart';
import 'package:bgszc_events/services/auth_service.dart';

class InvitationService {
  final AuthService _authService = AuthService();

  static const _getInvitationEndpoint =
      '/invitations/get_participant_invitation_by_id.php'; // GET - Aktuális meghívó
  static const _updateInvitationStatusEndpoint =
      '/invitations/update_invitation_status.php'; // POST - Státusz frissítése

  Future<Invitation?> getInvitation() async {
    final token = await _authService.getToken();
    final user = await _authService.getUser();
    if (token == null || user == null) {
      throw Exception('Not logged in');
    }

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}$_getInvitationEndpoint?userId=${user.userId}'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final dynamic responseData = jsonDecode(response.body);

        if (responseData is List) {
          for (var invitationData in responseData) {
            final invitation = Invitation.fromJson(invitationData);
            if (invitation.status == 'pending' ||
                invitation.status == 'accepted' ||
                invitation.status == 'reaccepted') {
              return invitation;
            }
          }
          return null;
        } else {
          throw Exception('Invalid response format from server.');
        }
      } else if (response.statusCode == 404) {
        return null;
      } else {
        throw Exception('Failed to load invitation: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Failed to load invitation: $e');
    }
  }

  Future<void> updateInvitationStatus(int invitationId, String status) async {
    final token = await _authService.getToken();
    if (token == null) {
      throw Exception('Not logged in');
    }

    final response = await http.post(
    Uri.parse('${ApiConstants.baseUrl}$_updateInvitationStatusEndpoint'),
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Authorization': 'Bearer $token',
    },
    body: {
      'invitationId': invitationId.toString(),
      'newStatus': status,
    },
  );

    if (response.statusCode != 200) {
      throw Exception('Failed to update invitation status: ${response.statusCode}, ${response.body}');
    }
    print('Update successful: ${response.body}');
  }
}