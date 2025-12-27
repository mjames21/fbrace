<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\DelegateCandidateStatusEvent;
use App\Support\StatusHistoryFilters;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatusHistoryExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $query = DelegateCandidateStatusEvent::query()
            ->with([
                'candidate:id,name',
                'user:id,name',
                'delegate:id,full_name,category,district_id',
                'delegate.district:id,name,region_id',
                'delegate.district.region:id,name',
                'delegate.groups:id,name',
            ]);

        StatusHistoryFilters::apply($query, $request->all());
        $query->orderBy('id');

        $filename = 'status_history_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'timestamp','delegate','category','region','district','groups',
                'candidate','action','from_stance','to_stance',
                'from_confidence','to_confidence','user','notes',
            ]);

            $query->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    $groups = $e->delegate?->groups?->pluck('name')->implode('|') ?? '';
                    fputcsv($out, [
                        optional($e->created_at)->toDateTimeString(),
                        $e->delegate?->full_name,
                        $e->delegate?->category,
                        $e->delegate?->district?->region?->name,
                        $e->delegate?->district?->name,
                        $groups,
                        $e->candidate?->name,
                        $e->action,
                        $e->from_stance,
                        $e->to_stance,
                        $e->from_confidence,
                        $e->to_confidence,
                        $e->user?->name,
                        $e->to_notes,
                    ]);
                }
            }, 'id');

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request)
    {
        $query = DelegateCandidateStatusEvent::query()
            ->with([
                'candidate:id,name',
                'user:id,name',
                'delegate:id,full_name,category,district_id',
                'delegate.district:id,name,region_id',
                'delegate.district.region:id,name',
                'delegate.groups:id,name',
            ]);

        StatusHistoryFilters::apply($query, $request->all());

        $events = $query->latest()->limit(2000)->get();

        $pdf = Pdf::loadView('reports.status-history-pdf', [
            'events' => $events,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'status_history_' . now()->format('Ymd_His') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
