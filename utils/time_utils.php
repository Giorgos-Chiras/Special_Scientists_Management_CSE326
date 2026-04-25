<?php


function formatFullDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }

    try {
        $date = new DateTime($datetime);
        return $date->format('d F Y H:i');
    } catch (Exception $e) {
        return $datetime;
    }
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return '-';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format('d F Y');
    } catch (Exception $e) {
        return $date;
    }
}
