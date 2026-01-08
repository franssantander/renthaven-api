<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPropertiesListAction
{
    public function execute(array $params): LengthAwarePaginator
    {
        return Property::with('portfolio')
            ->paginate(15);
    }
}