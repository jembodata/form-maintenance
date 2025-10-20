@php($lines = explode("\n", $text ?? ''))
<div class="rounded-lg bg-gray-50 p-4 space-y-1 text-sm text-gray-700">
  @foreach($lines as $l)
    <div>{{ $l }}</div>
  @endforeach
</div>
