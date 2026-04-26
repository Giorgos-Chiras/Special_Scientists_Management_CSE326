<?php
function getRoleLabel(string $role): string
{
    return match ($role) {
        'admin'      => 'Administrator',
        'evaluator'  => 'Evaluator',
        'candidate'  => 'Candidate',
        'hr'=> 'Human Resources',
        'ee' => "Special Scientist",
        default      => ucfirst(str_replace('_', ' ', $role)),
    };
}