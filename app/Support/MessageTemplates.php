<?php

namespace App\Support;

use App\Models\Member;
use Carbon\CarbonInterface;

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
        return self::render(
            $template,
            $member->full_name,
            $member->gym->name,
            $member->currentMembership?->ends_at,
        );
    }

    /**
     * Mismo reemplazo que al enviar, con datos de ejemplo: así la vista previa
     * de Ajustes muestra justo lo que le llegaría al cliente.
     */
    public static function render(string $template, string $client, string $gym, ?CarbonInterface $endsAt): string
    {
        return strtr($template, [
            '{cliente}' => $client,
            '{gimnasio}' => $gym,
            '{vencimiento}' => $endsAt?->format('d/m/Y') ?? '',
        ]);
    }
}
