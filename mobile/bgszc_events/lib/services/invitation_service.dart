import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:bgszc_events/models/invitation.dart';
import 'package:bgszc_events/utils/api_constants.dart';
import 'package:bgszc_events/services/auth_service.dart';

class InvitationService {
  final AuthService _authService = AuthService();

  // Endpoint remains the same conceptually, but behavior changed on backend
  static const _getInvitationEndpoint =
      '/invitations/get_participant_invitation_by_id.php'; // GET - Single prioritized invitation or null
  static const _updateInvitationStatusEndpoint =
      '/invitations/update_invitation_status.php'; // POST - Status update

  Future<Invitation?> getInvitation() async {
    // We still need the user object to get the userId
    final user = await _authService.getUser();
    // Token might still be needed if backend requires it for general API access,
    // even if not using it for user identification in this specific endpoint.
    final token = await _authService.getToken();

    if (user == null) {
      // User not logged in
      print('InvitationService: User not found, cannot get userId.');
      return null; // Return null if user is not logged in
    }
    if (token == null) {
        // Handle missing token if your API generally requires it
        print('InvitationService: Token not found. API might require authentication.');
        // Depending on your API setup, you might return null or throw exception
         return null; // Or throw Exception('Authentication token missing');
    }


    try {
      // --- SEND userId AS QUERY PARAMETER ---
      final response = await http.get(
        // Add userId to the query parameters
        Uri.parse('${ApiConstants.baseUrl}$_getInvitationEndpoint?userId=${user.userId}'),
        headers: {
          'Content-Type': 'application/json',
          // Include Authorization header if your backend still expects it, even if not used for userId
          'Authorization': 'Bearer $token',
        },
      );
      // --- END SEND userId ---


      if (response.statusCode == 200) {
        if (response.body.isEmpty || response.body.toLowerCase() == 'null') {
           print('InvitationService: No relevant invitation found (200 with null body).');
           return null;
        }
        final dynamic responseData = jsonDecode(response.body);

        if (responseData != null && responseData is Map<String, dynamic>) {
          print('InvitationService: Invitation data received: $responseData');
          return Invitation.fromJson(responseData);
        } else {
          print('InvitationService: Received 200 OK but response data is not a valid object or is null.');
          return null;
        }
      } else if (response.statusCode == 404) {
        print('InvitationService: No relevant invitation found (404).');
        return null;
      } else if (response.statusCode == 401) {
         print('InvitationService: Unauthorized (401). Token might be invalid or expired.');
         // Consider logout or refresh
         await _authService.logout();
         return null;
      }
      else {
        print('InvitationService: Failed to load invitation. Status: ${response.statusCode}, Body: ${response.body}');
        throw Exception('Failed to load invitation: ${response.statusCode}');
      }
    } catch (e) {
      print('InvitationService: Exception during getInvitation: $e');
      throw Exception('Failed to load invitation: $e');
    }
  }

   Future<Invitation> updateInvitationStatus(int invitationId, String status) async {
    final token = await _authService.getToken();
    if (token == null) {
       print('InvitationService: No token for update.');
      throw Exception('Not logged in');
    }

    try {
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

        if (response.statusCode == 200) {
            final dynamic responseData = jsonDecode(response.body);

            // Check if the backend returned the full invitation object
            if (responseData != null && responseData is Map<String, dynamic> && responseData.containsKey('invitation_id')) {
                 print('Update successful, received updated invitation data.');
                 // Parse and return the Invitation object
                 return Invitation.fromJson(responseData);
            } else {
                 // Backend sent a generic message or fetch failed after update
                 print('Update likely successful, but full invitation data not returned. Response: ${response.body}');
                 // We MUST return an Invitation. What do we do?
                 // Option A: Throw an error, forcing the caller to handle the refresh.
                 // Option B: Try calling getInvitation() immediately (might cause infinite loop if getInvitation fails?) - Risky
                 // Option C: Return a dummy/placeholder or the old one? - Misleading
                 // Let's throw an error indicating manual refresh might be needed.
                 throw Exception('Status updated, but failed to retrieve updated details. Please refresh.');
            }
        } else if (response.statusCode == 401) {
            // ... (handle 401 as before) ...
            print('InvitationService: Unauthorized (401) during update.');
            await _authService.logout();
            throw Exception('Authorization failed during update.');
        } else {
           // ... (handle other errors as before) ...
           print('InvitationService: Failed to update status. Status: ${response.statusCode}, Body: ${response.body}');
           throw Exception('Failed to update invitation status: ${response.statusCode}');
        }
    } catch (e) {
        print('InvitationService: Exception during updateInvitationStatus: $e');
        // Rethrow the specific exception if it came from the block above
        if (e is Exception && e.toString().contains('Status updated, but failed')) {
            rethrow;
        }
        throw Exception('Failed to update status: $e');
    }
  }
}