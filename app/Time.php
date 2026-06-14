<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Conversão UTC (armazenamento/lógica) <-> America/Sao_Paulo (exibição).
 */
final class Time
{
    private const WD = ['Sun' => 'Dom', 'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua', 'Thu' => 'Qui', 'Fri' => 'Sex', 'Sat' => 'Sáb'];
    private const MON = [1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'];

    public static function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function parse(string $iso): DateTimeImmutable
    {
        return new DateTimeImmutable($iso);
    }

    /** O instante já passou? (usado para travar palpites no kickoff) */
    public static function isPast(string $iso): bool
    {
        return self::parse($iso) <= self::nowUtc();
    }

    public static function local(string $iso): DateTimeImmutable
    {
        return self::parse($iso)->setTimezone(new DateTimeZone((string) config('tz_display', 'America/Sao_Paulo')));
    }

    /** Ex.: "Qua, 11/06 · 16:00" (horário de Brasília) */
    public static function friendly(string $iso): string
    {
        $d = self::local($iso);
        return self::WD[$d->format('D')] . ', ' . $d->format('d/m') . ' · ' . $d->format('H:i');
    }

    /** Só o horário local, ex.: "16:00" */
    public static function timeOnly(string $iso): string
    {
        return self::local($iso)->format('H:i');
    }

    /** Chave de agrupamento por dia local, ex.: "2026-06-11" */
    public static function dayKey(string $iso): string
    {
        return self::local($iso)->format('Y-m-d');
    }

    /** Chave do dia de hoje no fuso de exibição, ex.: "2026-06-14" */
    public static function todayKey(): string
    {
        return self::nowUtc()
            ->setTimezone(new DateTimeZone((string) config('tz_display', 'America/Sao_Paulo')))
            ->format('Y-m-d');
    }

    /** Cabeçalho de dia, ex.: "Quarta, 11 de jun" */
    public static function dayLabel(string $iso): string
    {
        $d = self::local($iso);
        $full = ['Dom' => 'Domingo', 'Seg' => 'Segunda', 'Ter' => 'Terça', 'Qua' => 'Quarta', 'Qui' => 'Quinta', 'Sex' => 'Sexta', 'Sáb' => 'Sábado'];
        $wd = self::WD[$d->format('D')];
        return $full[$wd] . ', ' . (int) $d->format('d') . ' de ' . self::MON[(int) $d->format('n')];
    }
}
