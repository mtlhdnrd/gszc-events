import 'package:flutter/material.dart';
import 'package:bgszc_events/models/invitation.dart';
import 'package:bgszc_events/services/invitation_service.dart';
import 'package:intl/intl.dart';

// 1. Alakítsd át StatefulWidget-té
class InvitationCard extends StatefulWidget {
  // Az eredeti meghívó, amit a szülőtől kap
  final Invitation initialInvitation;
  // A callback, ha a szülőnek is tudnia kell a változásról (opcionális lehet)
  final VoidCallback? onRefresh; // Legyen nullable, ha nem mindig kell

  const InvitationCard({
    super.key,
    required this.initialInvitation,
    this.onRefresh, // Nullable
  });

  @override
  State<InvitationCard> createState() => _InvitationCardState();
}

// 2. Hozd létre a State osztályt
class _InvitationCardState extends State<InvitationCard> {
  // Állapotváltozó a kártyán belül megjelenített meghívóhoz
  late Invitation _currentInvitation;
  // Állapotváltozó a folyamatban lévő frissítés jelzésére
  bool _isUpdating = false;

  // Szerviz példány
  final InvitationService _invitationService = InvitationService();

  @override
  void initState() {
    super.initState();
    // Inicializáljuk a belső állapotot a widgetnek átadott kezdő értékkel
    _currentInvitation = widget.initialInvitation;
  }

  // Ha a szülő widget új initialInvitation-t ad, frissítsük a belső állapotot
  // (Fontos lehet, ha a szülő pl. pull-to-refresh után új adatot küld)
  @override
  void didUpdateWidget(covariant InvitationCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.initialInvitation != oldWidget.initialInvitation) {
      setState(() {
        _currentInvitation = widget.initialInvitation;
      });
    }
  }

  // Metódus a státusz frissítésének kezelésére
  Future<void> _handleStatusUpdate(String newStatus) async {
    if (_isUpdating) return; // Ne indítsunk új kérést, ha már fut egy

    setState(() {
      _isUpdating = true; // Töltés jelzése
    });

    try {
      // Hívjuk az update metódust, ami már visszaadja a frissített Invitation-t
      final updatedInvitation = await _invitationService.updateInvitationStatus(
        _currentInvitation.invitationId, // A belső állapot ID-ját használjuk
        newStatus,
      );

      // Közvetlenül frissítjük a belső állapotot a visszakapott (frissített) adattal
      setState(() {
        _currentInvitation = updatedInvitation;
        _isUpdating = false; // Töltés vége
      });

      // Opcionális: Értesítjük a szülőt is, hogy történt valami
      widget.onRefresh?.call(); // Csak ha a szülőnek is kell tudnia

    } catch (e) {
      // Hiba kezelése
      setState(() {
        _isUpdating = false; // Töltés vége (hiba esetén is)
      });
       if (mounted) {
           ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Hiba a státusz frissítésekor: $e'),
              backgroundColor: Colors.red,
              ),
           );
       }
    }
  }

  @override
  Widget build(BuildContext context) {
    // A build metódus most már a _currentInvitation állapotot használja
    return Card(
      margin: const EdgeInsets.all(8),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Esemény: ${_currentInvitation.eventName}', // Használjuk a _currentInvitation-t
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text('Foglalkozás: ${_currentInvitation.workshopName}'), // Használjuk a _currentInvitation-t
            const SizedBox(height: 8),
            Text(
                'Dátum: ${DateFormat('yyyy-MM-dd – kk:mm').format(_currentInvitation.date.toLocal())}'), // Használjuk a _currentInvitation-t
            const SizedBox(height: 16),
            // _isUpdating ? Center(child: CircularProgressIndicator()) : _buildStatusOrButtons(context), // VAGY gombok letiltása
             _buildStatusOrButtons(context), // A helper metódus is a _currentInvitation-t fogja használni
          ],
        ),
      ),
    );
  }

  Widget _buildStatusOrButtons(BuildContext context) {
    // Ez a metódus is a _currentInvitation-t használja
    if (_currentInvitation.status == 'pending') {
      return Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          ElevatedButton(
            // Ha _isUpdating igaz, a gomb le van tiltva (onPressed: null)
            onPressed: _isUpdating ? null : () => _handleStatusUpdate('accepted'),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: _isUpdating ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2,)) : const Text('Elfogad'),
          ),
          ElevatedButton(
            onPressed: _isUpdating ? null : () => _handleStatusUpdate('rejected'),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
             child: _isUpdating ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2,)) : const Text('Elutasít'),
          ),
        ],
      );
    } else {
      // A státusz megjelenítő rész változatlan maradhat,
      // mert az is a (most már frissített) _currentInvitation alapján dolgozik.
      String statusText;
      Color backgroundColor;

      if (_currentInvitation.status == 'accepted' || _currentInvitation.status == 'reaccepted') {
        statusText = 'Elfogadva'; // Egységesíthetjük a "reaccepted"-et is
        backgroundColor = Colors.green[100]!;
      } else if (_currentInvitation.status == 'rejected') {
        statusText = 'Elutasítva';
        backgroundColor = Colors.red[100]!;
      } else {
        statusText = 'Ismeretlen státusz: ${_currentInvitation.status}'; // Írjuk ki az ismeretlen státuszt
        backgroundColor = Colors.grey[300]!;
      }

      return Container(
         width: double.infinity, // Foglalja el a teljes szélességet
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
        decoration: BoxDecoration(
          color: backgroundColor,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          statusText,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          textAlign: TextAlign.center,
        ),
      );
    }
  }
}