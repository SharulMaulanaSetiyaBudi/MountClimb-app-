import 'package:flutter_test/flutter_test.dart';
import 'package:mountclimb/models/mountain.dart';

void main() {
  group('Mountain.fromJson', () {
    final json = {
      'id': 'semeru',
      'name': 'Gunung Semeru',
      'location': 'Jawa Timur',
      'height_meters': 3676,
      'rating': 4.8,
      'price': 90000,
      'difficulty': 'Sulit',
      'description': 'Deskripsi uji',
      'hiking_info': 'Info uji',
      'thumbnail': 'assets/images/semeru.jpg',
      'images': ['assets/images/semeru.jpg', 'assets/images/bromo.jpg'],
      'daily_quota': 100,
    };

    test('memetakan field JSON API ke properti model', () {
      final m = Mountain.fromJson(json);

      expect(m.id, 'semeru');
      expect(m.name, 'Gunung Semeru');
      expect(m.heightMeters, 3676);
      expect(m.price, 90000);
      expect(m.difficulty, 'Sulit');
      expect(m.hikingInfo, 'Info uji');
      expect(m.images.length, 2);
      expect(m.dailyQuota, 100);
    });

    test('rating berbentuk string tetap terbaca sebagai angka', () {
      final m = Mountain.fromJson({...json, 'rating': '4.8'});
      expect(m.rating, 4.8);
    });

    test('daily_quota kosong memakai default 500', () {
      final data = Map<String, dynamic>.from(json)..remove('daily_quota');
      expect(Mountain.fromJson(data).dailyQuota, 500);
    });
  });

  group('remainingQuotaFor', () {
    test('hasil selalu di antara 0 dan kuota harian', () {
      for (final m in dummyMountains) {
        for (var day = 1; day <= 28; day++) {
          final sisa = remainingQuotaFor(m, DateTime(2026, 12, day));
          expect(sisa, inInclusiveRange(0, m.dailyQuota));
        }
      }
    });

    test('tanggal dan gunung yang sama menghasilkan angka yang sama', () {
      final m = dummyMountains.first;
      final tanggal = DateTime(2026, 12, 1);
      expect(remainingQuotaFor(m, tanggal), remainingQuotaFor(m, tanggal));
    });
  });

  group('dummyMountains', () {
    test('berisi 5 gunung dengan id unik', () {
      expect(dummyMountains.length, 5);
      expect(dummyMountains.map((m) => m.id).toSet().length, 5);
    });

    test('tingkat kesulitan hanya Mudah, Sedang, atau Sulit', () {
      for (final m in dummyMountains) {
        expect(['Mudah', 'Sedang', 'Sulit'], contains(m.difficulty));
      }
    });
  });
}
