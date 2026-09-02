<?php

class SofascoreApi {
    private string $baseUrl = 'https://api.sofascore.com/api/v1';

    /**
     * Realiza a requisição HTTP com stream_context para a API do Sofascore
     */
    private function request(string $endpoint): ?array {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0\r\n" .
                            "Referer: https://www.sofascore.com/\r\n" .
                            "Accept: application/json, text/plain, */*\r\n" .
                            "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
                "ignore_errors" => true,
                "timeout" => 12,
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false || empty($response)) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Obtém lista de ligas / torneios populares
     */
    public function getFeaturedTournaments(): array {
        $data = $this->request('config/unique-tournaments/football');
        if (!empty($data['uniqueTournaments'])) {
            return $data['uniqueTournaments'];
        }

        return [
            // Brasil & América do Sul
            ['id' => 325,   'name' => 'Brasileirão Série A', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
            ['id' => 390,   'name' => 'Brasileirão Série B', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
            ['id' => 1281,  'name' => 'Brasileirão Série C', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
            ['id' => 10326, 'name' => 'Brasileirão Série D', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
            ['id' => 373,   'name' => 'Copa do Brasil', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
            ['id' => 384,   'name' => 'Copa CONMEBOL Libertadores', 'category' => ['name' => 'América do Sul', 'flag' => 'south-america']],
            ['id' => 480,   'name' => 'Copa CONMEBOL Sudamericana', 'category' => ['name' => 'América do Sul', 'flag' => 'south-america']],
            ['id' => 155,   'name' => 'Liga Profesional Argentina', 'category' => ['name' => 'Argentina', 'flag' => 'argentina']],

            // Europa - Competidoras Continentais
            ['id' => 7,     'name' => 'UEFA Champions League', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
            ['id' => 679,   'name' => 'UEFA Europa League', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
            ['id' => 17015, 'name' => 'UEFA Conference League', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
            ['id' => 10783, 'name' => 'UEFA Nations League', 'category' => ['name' => 'Europa', 'flag' => 'europe']],

            // Inglaterra
            ['id' => 17,    'name' => 'Premier League', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
            ['id' => 18,    'name' => 'Championship', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
            ['id' => 19,    'name' => 'FA Cup', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],

            // Espanha
            ['id' => 8,     'name' => 'LaLiga', 'category' => ['name' => 'Espanha', 'flag' => 'spain']],
            ['id' => 54,    'name' => 'LaLiga 2', 'category' => ['name' => 'Espanha', 'flag' => 'spain']],

            // Itália
            ['id' => 23,    'name' => 'Serie A', 'category' => ['name' => 'Itália', 'flag' => 'italy']],
            ['id' => 53,    'name' => 'Serie B', 'category' => ['name' => 'Itália', 'flag' => 'italy']],

            // Alemanha
            ['id' => 35,    'name' => 'Bundesliga', 'category' => ['name' => 'Alemanha', 'flag' => 'germany']],
            ['id' => 44,    'name' => '2. Bundesliga', 'category' => ['name' => 'Alemanha', 'flag' => 'germany']],

            // França
            ['id' => 34,    'name' => 'Ligue 1', 'category' => ['name' => 'França', 'flag' => 'france']],
            ['id' => 182,   'name' => 'Ligue 2', 'category' => ['name' => 'França', 'flag' => 'france']],

            // Outras Principais Ligas Europeias
            ['id' => 238,   'name' => 'Liga Portugal', 'category' => ['name' => 'Portugal', 'flag' => 'portugal']],
            ['id' => 239,   'name' => 'Liga Portugal 2', 'category' => ['name' => 'Portugal', 'flag' => 'portugal']],
            ['id' => 37,    'name' => 'Eredivisie', 'category' => ['name' => 'Holanda', 'flag' => 'netherlands']],
            ['id' => 38,    'name' => 'Belgian Pro League', 'category' => ['name' => 'Bélgica', 'flag' => 'belgium']],
            ['id' => 36,    'name' => 'Scottish Premiership', 'category' => ['name' => 'Escócia', 'flag' => 'scotland']],
            ['id' => 52,    'name' => 'Süper Lig', 'category' => ['name' => 'Turquia', 'flag' => 'turkey']],

            // Ásia & Américas
            ['id' => 955,   'name' => 'Saudi Pro League', 'category' => ['name' => 'Arábia Saudita', 'flag' => 'saudi-arabia']],
            ['id' => 242,   'name' => 'MLS (Major League Soccer)', 'category' => ['name' => 'EUA', 'flag' => 'usa']],
        ];
    }

    /**
     * Obtém as temporadas de uma liga
     */
    public function getTournamentSeasons(int $tournamentId): array {
        $data = $this->request("unique-tournament/{$tournamentId}/seasons");
        return $data['seasons'] ?? [];
    }

    /**
     * Obtém as rodadas disponíveis em uma temporada
     */
    public function getSeasonRounds(int $tournamentId, int $seasonId): array {
        $data = $this->request("unique-tournament/{$tournamentId}/season/{$seasonId}/rounds");
        return $data ?? [];
    }

    /**
     * Obtém os jogos de uma rodada específica
     */
    public function getRoundEvents(int $tournamentId, int $seasonId, int $round): array {
        $data = $this->request("unique-tournament/{$tournamentId}/season/{$seasonId}/events/round/{$round}");
        return $data['events'] ?? [];
    }

    /**
     * Obtém estatísticas detalhadas de uma partida
     */
    public function getEventStatistics(int $eventId): array {
        $data = $this->request("event/{$eventId}/statistics");
        return $data['statistics'] ?? [];
    }

    /**
     * Obtém dados do Confronto Direto (H2H - Head to Head) entre dois times para um evento
     */
    public function getEventH2H(int $eventId): array {
        $data = $this->request("event/{$eventId}/h2h");
        return $data ?? [];
    }

    /**
     * Obtém eventos agendados para uma data (YYYY-MM-DD)
     */
    public function getScheduledEvents(string $date): array {
        $data = $this->request("sport/football/scheduled-events/{$date}");
        return $data['events'] ?? [];
    }

    /**
     * Obtém TODOS os jogos de uma temporada varrendo todas as páginas (hasNextPage)
     */
    public function getAllSeasonEvents(int $tournamentId, int $seasonId): array {
        $allEvents = [];
        
        $page = 0;
        do {
            $data = $this->request("unique-tournament/{$tournamentId}/season/{$seasonId}/events/last/{$page}");
            if (!empty($data['events'])) {
                $allEvents = array_merge($allEvents, $data['events']);
            }
            $hasNext = $data['hasNextPage'] ?? false;
            $page++;
        } while ($hasNext && $page < 40);

        $page = 0;
        do {
            $data = $this->request("unique-tournament/{$tournamentId}/season/{$seasonId}/events/next/{$page}");
            if (!empty($data['events'])) {
                $allEvents = array_merge($allEvents, $data['events']);
            }
            $hasNext = $data['hasNextPage'] ?? false;
            $page++;
        } while ($hasNext && $page < 40);

        $indexed = [];
        foreach ($allEvents as $evt) {
            if (isset($evt['id'])) {
                $indexed[$evt['id']] = $evt;
            }
        }

        $result = array_values($indexed);

        usort($result, function($a, $b) {
            return ($a['startTimestamp'] ?? 0) <=> ($b['startTimestamp'] ?? 0);
        });

        return $result;
    }
}
