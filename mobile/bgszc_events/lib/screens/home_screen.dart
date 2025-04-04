import 'package:flutter/material.dart';
import 'package:bgszc_events/models/invitation.dart';
import 'package:bgszc_events/services/invitation_service.dart';
import 'package:bgszc_events/widgets/invitation_card.dart';
import 'package:bgszc_events/services/auth_service.dart';
import 'package:bgszc_events/models/user.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _invitationService =
      InvitationService(); // Akkor is példányosítjuk, ha nincs API
  final _authService = AuthService(); // AuthService is kell
  Invitation? _invitation;
  bool _isLoading = false;
  User? _user;

  @override
  void initState() {
    super.initState();
    //_loadTestData(); // Tesztadatok betöltése initState-ben
    _loadData();
  }

  // Valós API hívás
  
    Future<void> _loadData() async {
  setState(() {
    _isLoading = true;
  });
  try {
    _user = await _authService.getUser();
    _invitation = await _invitationService.getInvitation();
    setState(() { // CSAK akkor állítjuk false-ra, ha NEM volt hiba
      _isLoading = false;
    });
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Hiba a betöltés közben: $e')),
    );
      setState(() { // Akkor is false-ra kell állítani, ha hiba volt
        _isLoading = false;
      });
  }
}

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_user?.name ?? 'Home'), // Felhasználónév, vagy "Home"
        actions: [
          IconButton(
            icon: Icon(Icons.logout),
            onPressed: () async {
              await _authService.logout();
              Navigator.pushReplacementNamed(context, '/login');
            },
          ),
        ],
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _buildContent(),
    );
  }

  Widget _buildContent() {
    if (_invitation == null) {
      return Center(child: Text('Nincs aktív meghívó.'));
    } else {
      return InvitationCard(
        invitation: _invitation!,
        onRefresh:
            _loadData, // Amikor elfogad/elutasít, a tesztadatokat töltjük be újra
      );
    }
  }
}
