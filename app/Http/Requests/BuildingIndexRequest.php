<?php

namespace App\Http\Requests;

/**
 * Building listing: inherits pagination/sorting/search from PaginatedRequest.
 * Building has no faceted or date filters yet, so no extra rules are needed.
 */
class BuildingIndexRequest extends PaginatedRequest
{
    //
}
