@php
  /** @var array $p */
  $perPlant = $p['perPlant'] ?? [];
  $examples = $p['perPlantExamples'] ?? [];
  ksort($perPlant);
@endphp

<div class="space-y-4">
  <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700">
    <div class="font-semibold text-gray-900">Preview Duplikasi Jadwal Preventive</div>
    <div class="mt-1">Tahun sumber: <strong>{{ $p['srcYear'] }}</strong> → Tahun target: <strong>{{ $p['tgtYear'] }}</strong></div>
    <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1">
      <div>📊 Kandidat: <strong>{{ $p['totalSource'] }}</strong></div>
      <div>✅ Bisa dibuat: <strong>{{ $p['canCreate'] }}</strong></div>
      <div>⏳ Tanpa tanggal: <strong>{{ $p['withoutDate'] }}</strong></div>
      <div>🚫 Tanggal penuh (skip): <strong>{{ count($p['perDateSkipped'] ?? []) }}</strong></div>
    </div>
    <div class="mt-2 text-xs text-gray-600">
      Pengaturan: Shift weekend <strong>{{ ($p['shiftWeekend'] ?? false) ? 'YA' : 'TIDAK' }}</strong>,
      Copy notes <strong>{{ ($p['copyNotes'] ?? false) ? 'YA' : 'TIDAK' }}</strong>
      @if(($p['filterCount'] ?? 0) > 0)
        , Filter plant: <strong>{{ $p['filterCount'] }}</strong>
      @endif
    </div>
  </div>

  <div class="overflow-hidden rounded-lg border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Plant</th>
          <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Jumlah Task</th>
          <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Contoh Mesin</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        @forelse($perPlant as $plant => $count)
          <tr>
            <td class="px-4 py-2 text-sm text-gray-800 font-medium">{{ $plant }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ $count }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">
              @php($list = $examples[$plant] ?? [])
              @if(empty($list))
                <span class="text-gray-400">-</span>
              @else
                <span>{{ implode(', ', $list) }}</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">Tidak ada data per plant.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="text-sm text-gray-700">
    <span class="font-medium">Catatan:</span>
    Sebanyak <strong>{{ $p['canCreate'] }}</strong> jadwal akan dibuat di tahun <strong>{{ $p['tgtYear'] }}</strong>
    tanpa menimpa data lama. Jadwal pada tanggal penuh akan otomatis dilewati.
  </div>
</div>
