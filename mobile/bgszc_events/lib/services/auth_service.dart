import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:developer';
import 'package:bgszc_events/models/user.dart';
import 'package:bgszc_events/utils/api_constants.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:crypto/crypto.dart'; // Importáljuk a crypto csomagot

class AuthService {
  static const _loginEndpoint = '/auth/login.php'; //Végpont
  static const _profileEndpoint = '/user/profile';

  Future<AuthResult> login(String username, String password) async {
    try {
      // Jelszó hashelése (SHA-256-tal) - most kikommentezzük, de itt marad, ha később kell
      // final hashedPassword = _hashPassword(password);
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}$_loginEndpoint'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'username': username, 'password': password}), // NYÍLT SZÖVEGŰ jelszót küldünk
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
          print(response.statusCode);
        return AuthResult.failure(message: 'Hiba a bejelentkezés során: ${response.statusCode}');
      }
    } catch (e) {
        print(e);
      return AuthResult.failure(message: 'Hiba a bejelentkezés során: $e');
    }
  }
    // Felhasználói profil lekérése (opcionális, ha az API-d támogatja)
  Future<AuthResult> getProfile() async {
    final token = await getToken();
    if (token == null) {
      return AuthResult.failure(message: 'Nincs bejelentkezve.');
    }
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}$_profileEndpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token', // Fontos: Bearer token használata!
        },
      );

        if (response.statusCode == 200) {
          final Map<String, dynamic> data = jsonDecode(response.body);
          final Map<String, dynamic> userJson = data['user']; // User adatok
          final User user = User.fromJson(userJson); // User objektum létrehozása
          return AuthResult.success(user: user);

        }else if(response.statusCode == 401){
            return AuthResult.failure(message: "Nincs jogosultsága a profil megtekintéséhez!");
        }
        else {
          return AuthResult.failure(message: 'Hiba a profil lekérése során: ${response.statusCode}');
        }

    } catch (e) {
       return AuthResult.failure(message: 'Hiba a profil lekérése során: $e');
    }
  }

  // Jelszó hashelő függvény (SHA-256) - Itt marad, de most nem használjuk
  String _hashPassword(String password) {
    final bytes = utf8.encode(password);
    final digest = sha256.convert(bytes);
    return digest.toString();
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

    // Ellenőrzés, hogy be van-e jelentkezve a felhasználó
  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null;
  }
}


// Segédosztály a bejelentkezés eredményének kezelésére
class AuthResult {
  final User? user;
  final String? token;
  final String? message;
  final bool success;

  AuthResult.success({this.user, this.token}) : message = null, success = true;
  AuthResult.failure({required this.message}) : user = null, token = null, success = false;
}