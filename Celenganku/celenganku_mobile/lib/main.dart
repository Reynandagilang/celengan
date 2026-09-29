import 'package:flutter/material.dart';

void main() {
  runApp(const CelengankuApp());
}

class CelengankuApp extends StatelessWidget {
  const CelengankuApp({super.key});
  
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Celenganku',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
        useMaterial3: true,
      ),
      home: const Placeholder(), // TODO: add screens
    );
  }
}
