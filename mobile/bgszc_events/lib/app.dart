import 'package:flutter/material.dart';
import 'package:bgszc_events/screens/login_screen.dart';
import 'package:bgszc_events/screens/home_screen.dart'; // HomeScreen importálása
import 'package:bgszc_events/services/auth_service.dart'; // AuthService importálása

class MyApp extends StatelessWidget {
  final AuthService _authService = AuthService();

  MyApp({super.key}); // Korrekt konstruktor

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'BGSZC Events',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: FutureBuilder<bool>(
        future: _authService.isLoggedIn(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          } else if (snapshot.hasError) {
            // Hibakezelés: hibaüzenet megjelenítése
            return Scaffold(
              body: Center(
                child: Text('Hiba történt: ${snapshot.error}'),
              ),
            );
          } else {
            // Ha be van jelentkezve, irány a HomeScreen
            if (snapshot.data == true) {
              return HomeScreen(); // HomeScreen visszaadása
            } else {
              // Ha nincs bejelentkezve, irány a LoginScreen
              return LoginScreen();
            }
          }
        },
      ),
      routes: {
        '/login': (context) => LoginScreen(),
        '/home': (context) => HomeScreen(), // /home route definiálása
      },
    );
  }
}