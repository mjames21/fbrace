<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StatusHistoryFilters
{
    /**
     * @param Builder $q  DelegateCandidateStatusEvent::query()
     * @param array<string,mixed> $filters
     */
    public static function apply(Builder $q, array $filters): Builder
    {
        $needle = trim((string)($filters['q'] ?? ''));
        if ($needle !== '') {
            $q->whereHas('delegate', fn (Builder $d) => $d->where('full_name', 'like', "%{$needle}%"));
        }

        if (!empty($filters['candidateId'])) {
            $q->where('candidate_id', (int)$filters['candidateId']);
        }

        if (!empty($filters['action'])) {
            $q->where('action', (string)$filters['action']);
        }

        if (!empty($filters['regionId'])) {
            $regionId = (int)$filters['regionId'];
            $q->whereHas('delegate.district', fn (Builder $d) => $d->where('region_id', $regionId));
        }

        if (!empty($filters['districtId'])) {
            $districtId = (int)$filters['districtId'];
            $q->whereHas('delegate', fn (Builder $d) => $d->where('district_id', $districtId));
        }

        if (!empty($filters['groupId'])) {
            $groupId = (int)$filters['groupId'];
            $q->whereHas('delegate.groups', fn (Builder $g) => $g->where('groups.id', $groupId));
        }

        if (!empty($filters['dateFrom'])) {
            $from = Carbon::parse((string)$filters['dateFrom'])->startOfDay();
            $q->where('created_at', '>=', $from);
        }

        if (!empty($filters['dateTo'])) {
            $to = Carbon::parse((string)$filters['dateTo'])->endOfDay();
            $q->where('created_at', '<=', $to);
        }

        return $q;
    }
}
