<?php

declare(strict_types=1);

namespace App;

/**
 * Cliente da API oficial da FIFA. Todo o conhecimento sobre o formato da FIFA
 * está isolado aqui — se a API mudar, só este arquivo precisa de ajuste.
 *
 * Endpoint: /calendar/matches?idCompetition=17&idSeason=285023&count=300&language=en
 */
final class FifaClient
{
    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /** @return array<int,array> linhas já no formato da tabela matches */
    public function fetchMatches(): array
    {
        $f = config('fifa');
        $url = sprintf(
            '%s/calendar/matches?idCompetition=%s&idSeason=%s&count=300&language=%s',
            rtrim((string) $f['base'], '/'),
            rawurlencode((string) $f['competition']),
            rawurlencode((string) $f['season']),
            rawurlencode((string) $f['language'])
        );

        $body = $this->httpGet($url);
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !isset($data['Results']) || !is_array($data['Results'])) {
            throw new \RuntimeException('payload inesperado da FIFA (sem Results)');
        }

        $out = [];
        foreach ($data['Results'] as $m) {
            if (is_array($m)) {
                $out[] = $this->map($m);
            }
        }
        return $out;
    }

    /** Mapeia um match cru da FIFA para a linha da tabela matches. */
    public function map(array $m): array
    {
        $home = is_array($m['Home'] ?? null) ? $m['Home'] : [];
        $away = is_array($m['Away'] ?? null) ? $m['Away'] : [];

        $homeGoals = $this->intOrNull($m['HomeTeamScore'] ?? null);
        $awayGoals = $this->intOrNull($m['AwayTeamScore'] ?? null);

        $winner = null;
        if ($homeGoals !== null && $awayGoals !== null) {
            $winner = $homeGoals === $awayGoals ? 'DRAW' : ($homeGoals > $awayGoals ? 'HOME' : 'AWAY');
        }

        return [
            'fifa_id'      => (string) ($m['IdMatch'] ?? ''),
            'match_number' => $this->intOrNull($m['MatchNumber'] ?? null),
            'stage'        => $this->desc($m['StageName'] ?? null),
            'grp'          => $this->desc($m['GroupName'] ?? null),
            'utc_kickoff'  => (string) ($m['Date'] ?? ''),
            'status'       => (int) ($m['MatchStatus'] ?? 1),
            'home_country' => $this->str($home['IdCountry'] ?? null),
            'home_team'    => $this->desc($home['TeamName'] ?? null),
            'home_ph'      => $this->str($m['PlaceHolderA'] ?? null),
            'away_country' => $this->str($away['IdCountry'] ?? null),
            'away_team'    => $this->desc($away['TeamName'] ?? null),
            'away_ph'      => $this->str($m['PlaceHolderB'] ?? null),
            'home_goals'   => $homeGoals,
            'away_goals'   => $awayGoals,
            'home_pens'    => $this->intOrNull($m['HomeTeamPenaltyScore'] ?? null),
            'away_pens'    => $this->intOrNull($m['AwayTeamPenaltyScore'] ?? null),
            'winner'       => $winner,
            'stadium'      => $this->desc(($m['Stadium']['Name'] ?? null)) ?? $this->str($m['Stadium']['Name'] ?? null),
        ];
    }

    /** Extrai Description do array localizado da FIFA [{Locale,Description}]. */
    private function desc(mixed $v): ?string
    {
        if (is_array($v) && isset($v[0]['Description'])) {
            $d = trim((string) $v[0]['Description']);
            return $d === '' ? null : $d;
        }
        return null;
    }

    private function str(mixed $v): ?string
    {
        if ($v === null || is_array($v)) {
            return null;
        }
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }

    private function intOrNull(mixed $v): ?int
    {
        return ($v === null || $v === '') ? null : (int) $v;
    }

    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => self::UA,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('falha de rede: ' . $err);
        }
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code !== 200) {
            throw new \RuntimeException("HTTP {$code} da API FIFA");
        }
        return (string) $body;
    }
}
