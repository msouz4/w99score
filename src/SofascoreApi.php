<?php

class SofascoreApi {
    private string $baseUrl = 'https://api.sofascore.com/api/v1';

    /**
     * Realiza a requisição HTTP com cURL / stream_context para a API do Sofascore
     */
    private function request(string $endpoint): ?array {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $response = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0',
                    'Referer: https://www.sofascore.com/',
                    'Accept: application/json, text/plain, */*',
                    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
                ]
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($res)) {
                $response = $res;
            }
        }

        if ($response === null) {
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
        }

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
     * Retorna a lista de países e categorias principais organizadas
     */
    public function getCategories(): array {
        $popular = [
            ['id' => 13,   'name' => 'Brasil',         'slug' => 'brazil',        'flag' => 'brazil',        'alpha2' => 'BR', 'emoji' => '🇧🇷', 'priority' => 100],
            ['id' => 1,    'name' => 'Inglaterra',     'slug' => 'england',       'flag' => 'england',       'alpha2' => 'EN', 'emoji' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'priority' => 99],
            ['id' => 32,   'name' => 'Espanha',        'slug' => 'spain',         'flag' => 'spain',         'alpha2' => 'ES', 'emoji' => '🇪🇸', 'priority' => 98],
            ['id' => 31,   'name' => 'Itália',         'slug' => 'italy',         'flag' => 'italy',         'alpha2' => 'IT', 'emoji' => '🇮🇹', 'priority' => 97],
            ['id' => 30,   'name' => 'Alemanha',       'slug' => 'germany',       'flag' => 'germany',       'alpha2' => 'DE', 'emoji' => '🇩🇪', 'priority' => 96],
            ['id' => 7,    'name' => 'França',         'slug' => 'france',        'flag' => 'france',        'alpha2' => 'FR', 'emoji' => '🇫🇷', 'priority' => 95],
            ['id' => 44,   'name' => 'Portugal',       'slug' => 'portugal',      'flag' => 'portugal',      'alpha2' => 'PT', 'emoji' => '🇵🇹', 'priority' => 94],
            ['id' => 48,   'name' => 'Argentina',      'slug' => 'argentina',     'flag' => 'argentina',     'alpha2' => 'AR', 'emoji' => '🇦🇷', 'priority' => 93],
            ['id' => 1465, 'name' => 'Europa (UEFA)',  'slug' => 'europe',        'flag' => 'europe',        'alpha2' => 'EU', 'emoji' => '🇪🇺', 'priority' => 92],
            ['id' => 1470, 'name' => 'América do Sul', 'slug' => 'south-america', 'flag' => 'south-america', 'alpha2' => 'SA', 'emoji' => '🌎', 'priority' => 91],
            ['id' => 35,   'name' => 'Holanda',        'slug' => 'netherlands',   'flag' => 'netherlands',   'alpha2' => 'NL', 'emoji' => '🇳🇱', 'priority' => 90],
            ['id' => 38,   'name' => 'Bélgica',        'slug' => 'belgium',       'flag' => 'belgium',       'alpha2' => 'BE', 'emoji' => '🇧🇪', 'priority' => 89],
            ['id' => 310,  'name' => 'Arábia Saudita', 'slug' => 'saudi-arabia',  'flag' => 'saudi-arabia',  'alpha2' => 'SA', 'emoji' => '🇸🇦', 'priority' => 88],
            ['id' => 26,   'name' => 'Estados Unidos', 'slug' => 'usa',           'flag' => 'usa',           'alpha2' => 'US', 'emoji' => '🇺🇸', 'priority' => 87],
            ['id' => 52,   'name' => 'Turquia',        'slug' => 'turkey',        'flag' => 'turkey',        'alpha2' => 'TR', 'emoji' => '🇹🇷', 'priority' => 86],
            ['id' => 1468, 'name' => 'Internacional',  'slug' => 'world',         'flag' => 'international', 'alpha2' => 'WO', 'emoji' => '🌐', 'priority' => 85],
        ];

        // Tenta buscar lista completa de categorias do Sofascore
        $data = $this->request('sport/football/categories');
        if (!empty($data['categories']) && is_array($data['categories'])) {
            $existingIds = array_column($popular, 'id');
            foreach ($data['categories'] as $cat) {
                $cid = (int)$cat['id'];
                if (in_array($cid, $existingIds, true)) {
                    continue;
                }
                $alpha2 = strtoupper($cat['alpha2'] ?? '');
                $emoji = $this->alpha2ToEmoji($alpha2);
                $popular[] = [
                    'id' => $cid,
                    'name' => $cat['name'] ?? '',
                    'slug' => $cat['slug'] ?? '',
                    'flag' => $cat['flag'] ?? '',
                    'alpha2' => $alpha2,
                    'emoji' => $emoji,
                    'priority' => (int)($cat['priority'] ?? 0)
                ];
            }
        }

        return $popular;
    }

    /**
     * Converte código Alpha-2 em emoji de bandeira
     */
    private function alpha2ToEmoji(string $alpha2): string {
        if ($alpha2 === 'EN') return '🏴󠁧󠁢󠁥󠁮󠁧󠁿';
        if ($alpha2 === 'WL') return '🏴󠁧󠁢󠁷󠁬󠁳󠁿';
        if ($alpha2 === 'SC') return '🏴󠁧󠁢󠁳󠁣󠁴󠁿';
        if ($alpha2 === 'EU') return '🇪🇺';
        if (strlen($alpha2) !== 2) return '⚽';
        
        $codePoints = [];
        for ($i = 0; $i < 2; $i++) {
            $codePoints[] = 127397 + ord($alpha2[$i]);
        }
        return mb_chr($codePoints[0], 'UTF-8') . mb_chr($codePoints[1], 'UTF-8');
    }

    /**
     * Obtém as ligas de um país/categoria
     * Filtra apenas masculinas e limita às 5 principais
     */
    public function getCategoryTournaments(int $categoryId, bool $maleOnly = true, int $limit = 5): array {
        $data = $this->request("category/{$categoryId}/unique-tournaments");
        $tournaments = [];

        if (!empty($data['groups']) && is_array($data['groups'])) {
            foreach ($data['groups'] as $group) {
                if (!empty($group['uniqueTournaments']) && is_array($group['uniqueTournaments'])) {
                    foreach ($group['uniqueTournaments'] as $ut) {
                        $tournaments[] = $ut;
                    }
                }
            }
        }

        // Se não houver retorno da API, tenta buscar da lista estática
        if (empty($tournaments)) {
            $featured = $this->getFeaturedTournaments();
            foreach ($featured as $f) {
                $tournaments[] = $f;
            }
        }

        if ($maleOnly) {
            $tournaments = array_filter($tournaments, function ($t) {
                $name = strtolower($t['name'] ?? '');
                $slug = strtolower($t['slug'] ?? '');
                $text = $name . ' ' . $slug;

                // Filtro para remover futebol feminino
                $womenPatterns = [
                    '/\bwomen\b/', '/\bfeminino\b/', '/\bfeminina\b/', '/\bfemenina\b/',
                    '/\bfemenino\b/', '/\bladies\b/', '/\bfem\b/', '/\bfem\./',
                    '/\bfrauen\b/', '/\bdamen\b/', '/\bféminine\b/', '/\bvrouwen\b/', '/\bdonne\b/'
                ];
                foreach ($womenPatterns as $pattern) {
                    if (preg_match($pattern, $text)) {
                        return false;
                    }
                }

                // Filtro para remover categorias de base / juvenis (sub-20, etc)
                $youthPatterns = [
                    '/\bu\d{2}\b/', '/sub-\d{2}/', '/sub\d{2}/', '/\byouth\b/',
                    '/\bjúnior\b/', '/\bjuniores\b/', '/\bprimavera\b/', '/\bcopinha\b/'
                ];
                foreach ($youthPatterns as $pattern) {
                    if (preg_match($pattern, $text)) {
                        return false;
                    }
                }

                return true;
            });
        }

        // Ordena pela popularidade no Sofascore (userCount decrescente)
        usort($tournaments, function ($a, $b) {
            $ua = $a['userCount'] ?? 0;
            $ub = $b['userCount'] ?? 0;
            return $ub <=> $ua;
        });

        // Limita às 5 principais
        if ($limit > 0) {
            $tournaments = array_slice($tournaments, 0, $limit);
        }

        return array_values($tournaments);
    }

    /**
     * Filtra temporadas para trazer apenas 3 anos:
     * - Ano anterior
     * - Ano atual
     * - Próximo ano (se existir)
     */
    public function filterThreeYearsSeasons(array $seasons): array {
        $currentYear = (int)date('Y'); // ex: 2026
        $prevYear = $currentYear - 1;   // 2025
        $nextYear = $currentYear + 1;   // 2027
        $targetYears = [$prevYear, $currentYear, $nextYear];

        $filtered = [];
        foreach ($seasons as $s) {
            $yearStr = $s['year'] ?? '';
            $nameStr = $s['name'] ?? '';
            $text = $yearStr . ' ' . $nameStr;
            $matched = false;

            // Anos de 4 dígitos: 2025, 2026, 2027
            if (preg_match_all('/\b(20\d\d)\b/', $text, $m4)) {
                foreach ($m4[1] as $y) {
                    if (in_array((int)$y, $targetYears, true)) {
                        $matched = true;
                        break;
                    }
                }
            }

            // Anos divididos no modelo europeu: 24/25, 25/26, 26/27
            if (!$matched && preg_match_all('/\b(\d{2})\/(\d{2})\b/', $text, $m2)) {
                foreach ($m2[2] as $yShort) {
                    $fullYear = 2000 + (int)$yShort;
                    if (in_array($fullYear, $targetYears, true)) {
                        $matched = true;
                        break;
                    }
                }
            }

            if ($matched) {
                $filtered[] = $s;
            }
        }

        return !empty($filtered) ? array_values($filtered) : array_slice($seasons, 0, 3);
    }

    /**
     * Obtém as temporadas de uma liga (opção de limitar aos 3 anos)
     */
    public function getTournamentSeasons(int $tournamentId, bool $threeYearsOnly = false): array {
        $data = $this->request("unique-tournament/{$tournamentId}/seasons");
        $seasons = $data['seasons'] ?? [];
        if ($threeYearsOnly) {
            return $this->filterThreeYearsSeasons($seasons);
        }
        return $seasons;
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
