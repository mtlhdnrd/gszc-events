import 'package:http/http.dart' as http; 
import 'dart:convert';
import 'package:bgszc_events/models/user.dart';
import 'package:bgszc_events/utils/api_constants.dart'; // API URL-ek
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  static const _loginEndpoint = '/backend/api/auth/login.php'; // Helyes végpont

  // Bejelentkezés
  Future<AuthResult> login(String username, String password) async {
    try {
      final response = await http.post( // http.post helyes használata
        Uri.parse('${ApiConstants.baseUrl}$_loginEndpoint'), // ApiConstants helyes használata
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'username': username, 'password': password}),
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> data = jsonDecode(response.body);
        final String token = data['token'];
        final Map<String, dynamic> userJson = data['user'];
        final User user = User.fromJson(userJson);
        await _saveToken(token);
        await _saveUser(user);
        return AuthResult.success(user: user, token: token);
      } else if (response.statusCode == 401) {
        return AuthResult.failure(message: 'Hibás felhasználónév vagy jelszó.');
      } else {
        return AuthResult.failure(message: 'Hiba a bejelentkezés során: ${response.statusCode}');
      }
    } catch (e) {
      return AuthResult.failure(message: 'Hiba a bejelentkezés során: $e');
    }
  }

    // Token tárolása
  Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  // Token lekérése
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

    // User tárolása
  Future<void> _saveUser(User user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user', jsonEncode(user.toJson())); //JSON formátumban tároljuk.
  }

  // User lekérése
  Future<User?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userString = prefs.getString('user');
      if(userString != null){
          final Map<String, dynamic> userJson = jsonDecode(userString);
          return User.fromJson(userJson);
      }
      return null;
  }

  // Kijelentkezés
  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user');
  }

  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null;
  }
}

class AuthResult {
  final User? user;
  final String? token;
  final String? message;
  final bool success;

  AuthResult.success({this.user, this.token})
      : message = null,
        success = true;
  AuthResult.failure({required this.message})
      : user = null,
        token = null,
        success = false;
}