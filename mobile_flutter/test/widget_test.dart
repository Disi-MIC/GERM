import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:germ_mobile/core/network/api_client.dart';
import 'package:germ_mobile/core/network/api_client_provider.dart';
import 'package:germ_mobile/main.dart';

void main() {
  testWidgets('GermApp démarre sur le splash sans lever d\'exception', (WidgetTester tester) async {
    final apiClient = await ApiClient.create();

    await tester.pumpWidget(
      ProviderScope(
        overrides: [apiClientProvider.overrideWithValue(apiClient)],
        child: const GermApp(),
      ),
    );
    await tester.pump();

    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
