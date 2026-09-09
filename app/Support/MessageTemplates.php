<?php

namespace App\Support;

use App\Models\Member;

class MessageTemplates
{
    public const PLACEHOLDERS = [
        '{cliente}' => 'Nombre del cliente',
        '{gimnasio}' => 'Nombre del gimnasio',
        '{vencimiento}' => 'Fecha de vencimiento de su membresía',
    ];

    public static function expiring(): string
    {
        return 'Hola {cliente}, te recordamos que tu membresía en {gimnasio} vence el {vencimiento}. ¡Te esperamos!';
    }

    public static function expired(): string
    {
        return 'Hola {cliente}, tu membresía en {gimnasio} venció el {vencimiento}. Pasa a renovarla cuando gustes, ¡te esperamos!';
    }

    public static function inactive(): string
    {
        return 'Hola {cliente}, hace días que no te vemos por {gimnasio}. ¡Te esperamos pronto!';
    }

    public static function fill(string $template, Member $member): string
    {
        $endsAt = $member->currentMembership?->ends_at;

        return strtr($template, [
            '{cliente}' => $member->full_name,
            '{gimnasio}' => $member->gym->name,
            '{vencimiento}' => $endsAt?->format('d/m/Y') ?? '',
        ]);
    }
}
