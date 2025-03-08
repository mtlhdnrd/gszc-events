import 'package:flutter/material.dart';
import 'package:bgszc_events/services/auth_service.dart'; // AuthService importálása

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  // Form kulcs (a form állapotának eléréséhez)
  final _formKey = GlobalKey<FormState>();

  // Szövegmező vezérlők (a beírt szöveg eléréséhez)
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();

  // AuthService példány
  final _authService = AuthService();

  // Betöltés jelző (amíg a bejelentkezés folyamatban van)
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Bejelentkezés')),
      body: Padding(
        padding: const EdgeInsets.all(16.0), // Körbefogó padding
        child: Form(
          key: _formKey, // Form kulcs hozzárendelése
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center, // Középre igazítás
            children: [
              // Felhasználónév mező
              TextFormField(
                controller: _usernameController, // Vezérlő hozzárendelése
                decoration: InputDecoration(
                  labelText: 'Felhasználónév', // Címke
                  border: OutlineInputBorder(), // Keret
                  prefixIcon: Icon(Icons.person), // Ikon
                ),
                validator: (value) {
                  // Validáció (ellenőrzés)
                  if (value == null || value.isEmpty) {
                    return 'Kérlek, add meg a felhasználóneved!';
                  }
                  return null; // Nincs hiba
                },
              ),
              SizedBox(height: 16), // Térköz

              // Jelszó mező
              TextFormField(
                controller: _passwordController,
                decoration: InputDecoration(
                  labelText: 'Jelszó',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.lock),
                ),
                obscureText: true, // Jelszó elrejtése
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Kérlek, add meg a jelszavad!';
                  }
                  return null;
                },
              ),
              SizedBox(height: 24),

              // Bejelentkezés gomb
              ElevatedButton(
                onPressed: _isLoading ? null : _login,
                style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue, // Gomb színe
                    foregroundColor: Colors.white, // Szöveg színe a gombon
                    padding: EdgeInsets.symmetric(horizontal: 32, vertical: 12) // Gomb mérete
                ), // Kattintás eseménykezelő
                child: _isLoading
                    ? CircularProgressIndicator(color: Colors.white,) // Betöltés jelző, ha _isLoading == true
                    : Text('Bejelentkezés'),

              ),
            ],
          ),
        ),
      ),
    );
  }

  // Bejelentkezés függvény
  Future<void> _login() async {
    // Validáció ellenőrzése
    if (_formKey.currentState!.validate()) {
      // Betöltés jelző bekapcsolása
      setState(() {
        _isLoading = true;
      });

      // AuthService hívása
      final result = await _authService.login(
        _usernameController.text,
        _passwordController.text,
      );

      // Betöltés jelző kikapcsolása (akár sikeres, akár sikertelen volt a bejelentkezés)
      setState(() {
        _isLoading = false;
      });

      // Eredmény kezelése
      if (result.success) {
        // Sikeres bejelentkezés: navigáció a főképernyőre
        Navigator.pushReplacementNamed(context, '/home'); // '/home' a home_screen route-ja
      } else {
        // Sikertelen bejelentkezés: hibaüzenet megjelenítése
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result.message!)),
        );
      }
    }
  }

  // Widget lebontása (memory leak elkerülése)
    @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }
}