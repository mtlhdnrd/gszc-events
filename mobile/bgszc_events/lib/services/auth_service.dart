import 'dart:convert';
import 'package:bgszc_events/models/user.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  // API endpoint
  static const _loginEndpoint = '/login';
  static const _profileEndpoint = '/user/profile';

  // Bejelentkezés
  Future<AuthResult> login(String username, String password) async {
    /*
    try {
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}$_loginEndpoint'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'username': username, 'password': password}),
      );

      if (response.statusCode == 200) {
        // Sikeres bejelentkezés
        final Map<String, dynamic> data = jsonDecode(response.body);
        final String token = data['token'];
        final Map<String, dynamic> userJson = data['user'];
        final User user = User.fromJson(userJson);
        await _saveToken(token);
        await _saveUser(user); 
        return AuthResult.success(user: user, token: token);
      } else if (response.statusCode == 401) {
        // Hitelesítési hiba (pl. rossz jelszó)
        return AuthResult.failure(message: 'Hibás felhasználónév vagy jelszó.');
      } else {
        // Egyéb szerver oldali hiba
        return AuthResult.failure(message: 'Hiba a bejelentkezés során: ${response.statusCode}');
      }
    } catch (e) {
      // Hálózati hiba, vagy egyéb kivétel
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
            'Authorization': 'Bearer $token',
          },
        );

          if (response.statusCode == 200) {
            final Map<String, dynamic> data = jsonDecode(response.body);
            final Map<String, dynamic> userJson = data['user']; 
            final User user = User.fromJson(userJson);
            return AuthResult.success(user: user);

          }else if(response.statusCode == 401){
              return AuthResult.failure(message: "Nincs jogosultsága a profil megtekintéséhez!");
          }
          else {
            return AuthResult.failure(message: 'Hiba a profil lekérése során: ${response.statusCode}');
          }

      } catch (e) {
        return AuthResult.failure(message: 'Hiba a profil lekérése során: $e');
      }*/
    final user = User(
        username: username,
        password: 'dummy_password'); // Fontos: A jelszót soha ne tárold így!

    // 2. Szimulálunk egy tokent (ez is csak tesztelésre)
    final token = 'dummy_token';

    // 3. Elmentjük a tokent és a felhasználót (mintha az API visszaadta volna)
    await _saveToken(token);
    await _saveUser(user);

    // 4. Visszaadjuk a sikeres eredményt
    return AuthResult.success(user: user, token: token);
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
    await prefs.setString('user', jsonEncode(user.toJson()));
  }

  // User lekérése
  Future<User?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userString = prefs.getString('user');
    if (userString != null) {
      final Map<String, dynamic> userJson = jsonDecode(userString);
      return User.fromJson(userJson);
    }
    return null;
  }

  // Kijelentkezés
  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user'); // User-t is töröljük.
  }

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

  AuthResult.success({this.user, this.token})
      : message = null,
        success = true;
  AuthResult.failure({required this.message})
      : user = null,
        token = null,
        success = false;
}
