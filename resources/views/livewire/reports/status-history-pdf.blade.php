<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
    .title { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
    .meta { font-size: 10px; color: #475569; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
    th { background: #f1f5f9; font-weight: 700; font-size: 10px; }
    .small { font-size: 10px; color: #475569; }
  </style>
</head>
<body>
  <div class="title">FBrace — Status History</div>
  <div class="meta">Generated: {{ $generatedAt->toDateTimeString() }} • Limit: 2000 rows</div>

  <table>
    <thead>
      <tr>
        <th style="width: 130px;">Time</th>
        <th style="width: 240px;">Delegate</th>
        <th style="width: 180px;">Location</th>
        <th style="width: 160px;">Candidate</th>
        <th style="width: 120px;">Action</th>
        <th style="width: 160px;">Change</th>
        <th style="width: 120px;">User</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      @foreach($events as $e)
        <tr>
          <td>{{ optional($e->created_at)->toDateTimeString() }}</td>
          <td>
            <strong>{{ $e->delegate?->full_name }}</strong>
            <div class="small">{{ $e->delegate?->category ?: '—' }}</div>
            <div class="small">{{ $e->delegate?->groups?->pluck('name')->implode(', ') ?: '—' }}</div>
          </td>
          <td>
            <div>{{ $e->delegate?->district?->region?->name ?? '—' }}</div>
            <div class="small">{{ $e->delegate?->district?->name ?? '—' }}</div>
          </td>
          <td>{{ $e->candidate?->name ?? '—' }}</td>
          <td>{{ $e->action }}</td>
          <td>
            {{ $e->from_stance ?? '—' }} → <strong>{{ $e->to_stance ?? '—' }}</strong>
            <div class="small">({{ $e->from_confidence ?? '—' }} → {{ $e->to_confidence ?? '—' }})</div>
          </td>
          <td>{{ $e->user?->name ?? 'Unknown' }}</td>
          <td class="small">{{ $e->to_notes }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>