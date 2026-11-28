<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The same filters as the live searchProfiles(): gender and marital_status match exactly,
 * religion, country, city, caste, profession and mother_tongue match "contains",
 * age_from / age_to are turned into a date of birth range.
 * Needs the ApiHelpers trait too (for like()).
 */
trait FiltersProfiles
{
    protected function applyProfileFilters(Builder $query, Request $request): Builder
    {
        foreach (['gender', 'marital_status'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, (string) $value);
            }
        }

        foreach (['religion', 'country', 'city', 'caste', 'profession', 'mother_tongue'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, 'like', $this->like((string) $value));
            }
        }

        if ($from = (int) $request->query('age_from')) {
            $query->whereDate('dob', '<=', now()->subYears($from)->toDateString());
        }

        if ($to = (int) $request->query('age_to')) {
            $query->whereDate('dob', '>=', now()->subYears($to)->toDateString());
        }

        // The live Discover page searches name, city and profession
        if ($q = $request->query('q')) {
            $query->whereRaw("CONCAT_WS(' ', first_name, last_name, city, profession) LIKE ?", [$this->like((string) $q)]);
        }

        return $query;
    }
}
