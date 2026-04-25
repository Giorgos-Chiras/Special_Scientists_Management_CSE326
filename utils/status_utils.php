<?php
function getStatusCssClass(string $status): string
{
    return match ($status) {
        'draft' => 'status-pending',
        'submitted' => 'status-submitted',
        'under_review' => 'status-review',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-pending',
    };
}

function getStatusLabel(string $status): string
{
    return match ($status) {
        'draft'        => 'Draft',
        'submitted'    => 'Submitted',
        'under_review' => 'Under Review',
        'approved'     => 'Approved',
        'rejected'     => 'Rejected',
        default        => ucfirst(str_replace('_', ' ', $status)),
    };
}


//Returns key value pair
function getApplicationStatuses(): array
{
    return [
        'draft'        => getStatusLabel('draft'),
        'submitted'    => getStatusLabel('submitted'),
        'under_review' => getStatusLabel('under_review'),
        'approved'     => getStatusLabel('approved'),
        'rejected'     => getStatusLabel('rejected'),
    ];
}

