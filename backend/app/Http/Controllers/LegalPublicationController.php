<?php

namespace App\Http\Controllers;

use App\Http\Requests\LegalPublicationIndexRequest;
use App\Http\Requests\LegalPublicationLinkRequest;
use App\Http\Requests\LegalPublicationReviewRequest;
use App\Models\LegalPublication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class LegalPublicationController extends Controller
{
    public function index(
        LegalPublicationIndexRequest $request,
    ): JsonResponse {
        $query =
            LegalPublication::query()
                ->with([
                    'folder:id,name,process_number',

                    'barRegistrations:id,lawyer_name,bar_number,state',

                    'reviewer:id,name',
                ]);

        $this->applyFilters(
            $query,
            $request->validated(),
        );

        $publications =
            $query
                ->orderByDesc(
                    'available_on'
                )
                ->orderByDesc(
                    'id'
                )
                ->paginate(
                    (int) $request->validated(
                        'per_page',
                        25,
                    )
                )
                ->withQueryString();

        return response()->json(
            $publications
        );
    }

    public function show(
        LegalPublication $legalPublication,
    ): JsonResponse {
        $legalPublication->load([
            'folder:id,name,process_number',

            'barRegistrations:id,lawyer_name,bar_number,state',

            'reviewer:id,name',
        ]);

        return response()->json(
            $legalPublication
        );
    }

    public function link(
        LegalPublicationLinkRequest $request,
        LegalPublication $legalPublication,
    ): JsonResponse {
        $legalPublication->folder_id =
            $request->validated(
                'folder_id'
            );

        $legalPublication->save();

        return response()->json(
            $legalPublication
                ->refresh()
                ->load(
                    'folder:id,name,process_number'
                )
        );
    }

    public function review(
        LegalPublicationReviewRequest $request,
        LegalPublication $legalPublication,
    ): JsonResponse {
        $legalPublication->forceFill([
            'review_status' => $request->validated(
                'review_status'
            ),

            'reviewed_by' => $request
                ->user('api')
                ?->getKey(),

            'reviewed_at' => now(),
        ])->save();

        return response()->json(
            $legalPublication
                ->refresh()
                ->load(
                    'reviewer:id,name'
                )
        );
    }

    private function applyFilters(
        Builder $query,
        array $filters,
    ): void {
        if (
            ($filters['link_status'] ?? null)
                === 'linked'
        ) {
            $query->whereNotNull(
                'folder_id'
            );
        }

        if (
            ($filters['link_status'] ?? null)
                === 'unlinked'
        ) {
            $query->whereNull(
                'folder_id'
            );
        }

        if (
            ($filters['review_status'] ?? null)
                !== null
        ) {
            $query->where(
                'review_status',
                $filters['review_status'],
            );
        }

        if (
            ($filters['available_from'] ?? null)
                !== null
        ) {
            $query->whereDate(
                'available_on',
                '>=',
                $filters['available_from'],
            );
        }

        if (
            ($filters['available_to'] ?? null)
                !== null
        ) {
            $query->whereDate(
                'available_on',
                '<=',
                $filters['available_to'],
            );
        }

        $search =
            trim(
                (string) (
                    $filters['search']
                    ?? ''
                )
            );

        if ($search === '') {
            return;
        }

        $normalizedSearch =
            preg_replace(
                '/\D+/',
                '',
                $search,
            );

        $query->where(
            function (Builder $nestedQuery) use (
                $search,
                $normalizedSearch,
            ): void {
                $nestedQuery
                    ->where(
                        'process_number',
                        'like',
                        "%{$search}%",
                    )
                    ->orWhere(
                        'court_acronym',
                        'like',
                        "%{$search}%",
                    )
                    ->orWhere(
                        'judicial_body',
                        'like',
                        "%{$search}%",
                    );

                if ($normalizedSearch !== '') {
                    $nestedQuery->orWhere(
                        'normalized_process_number',
                        'like',
                        "%{$normalizedSearch}%",
                    );
                }
            }
        );
    }
}
