import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:bgszc_events/models/invitation.dart';
import 'package:bgszc_events/utils/api_constants.dart';
import 'package:bgszc_events/services/auth_service.dart';

class InvitationService {
  final AuthService _authService = AuthService();

  // API endpoint-ok (most már a helyes formátumban)
  static const _getInvitationEndpoint = '/invitation'; // GET - Aktuális meghívó lekérése
  static const _acceptInvitationEndpoint = '/invitation'; // POST - Elfogadás (része lesz az URL-nek)
  static const _rejectInvitationEndpoint = '/invitation';  // POST - Elutasítás (része lesz az URL-nek)
  static const _reAcceptInvitationEndpoint = '/invitation';

  Future<Invitation?> getInvitation() async {
    final token = await _authService.getToken();
    final user = await _authService.getUser(); //Kell a user ID
    if (token == null || user == null) {
      throw Exception('Not logged in');
    }

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}$_getInvitationEndpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
          final Map<String, dynamic> data = jsonDecode(response.body);
          if(data.isNotEmpty){ //Csak akkor konvertáljuk, ha nem üres.
            return Invitation.fromJson(data);
          } else {
            return null;
          }

      } else if (response.statusCode == 404) {
        return null; // Nincs meghívó
      } else {
        throw Exception('Failed to load invitation: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Failed to load invitation: $e');
    }
  }

  Future<void> acceptInvitation(int invitationId) async {
    final token = await _authService.getToken();
    if (token == null) {
      throw Exception('Not logged in');
    }

    final response = await http.post(
      Uri.parse('${ApiConstants.baseUrl}$_acceptInvitationEndpoint/$invitationId/accept'), // HELYES URL
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      // NEM kell body, mert az ID az URL-ben van
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to accept invitation: ${response.statusCode}');
    }
  }

  Future<void> rejectInvitation(int invitationId) async {
    final token = await _authService.getToken();
    if (token == null) {
      throw Exception('Not logged in');
    }

    final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}$_rejectInvitationEndpoint/$invitationId/reject'), // HELYES URL
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        // NEM kell body
    );

    if (response.statusCode != 200) {
        throw Exception('Failed to reject invitation: ${response.statusCode}');
    }
  }
    Future<void> reAcceptInvitation(int invitationId) async {
      final token = await _authService.getToken();
      if (token == null) {
        throw Exception('Not logged in');
      }

      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}$_reAcceptInvitationEndpoint/$invitationId/accept'), // HELYES URL, újra accept
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        // NEM kell body
      );

      if (response.statusCode != 200) {
        throw Exception('Failed to re-accept invitation: ${response.statusCode}');
      }
    }
}