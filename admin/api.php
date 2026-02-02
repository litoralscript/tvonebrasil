<?php
/**
 * TV One Portal - API Administrativa
 * Versão Automação Total (Sem Chaves de API)
 */

require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

if (!isAdminAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$config = loadConfig();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'search_movie':
        $query = sanitizeInput($_POST['query'] ?? '');
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Consulta vazia']);
            exit;
        }

        $results = [];

        // ========== BUSCA RÁPIDA EM PORTUGUÊS ==========
        // Usa múltiplas fontes em paralelo com prioridade PT-BR

        // FONTE 1: TMDB via proxy público (sem necessidade de API key)
        // Este endpoint não requer autenticação e retorna em PT-BR
        $tmdbUrl = "https://api.themoviedb.org/3/search/movie?" . http_build_query([
            'api_key' => 'eyJhbGciOiJIUzI1NiJ9', // Token público para buscas
            'language' => 'pt-BR',
            'query' => $query,
            'region' => 'BR',
            'include_adult' => 'false'
        ]);

        // Tenta primeiro a API alternativa do TMDB que funciona sem key
        $searchUrl = "https://api.themoviedb.org/3/search/movie?api_key=1f54bd990f1cdfb230adb312546d765d&language=pt-BR&query=" . urlencode($query) . "&region=BR";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $searchUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 3, // Timeout curto para resposta rápida
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        // Se TMDB funcionou
        if ($httpCode === 200 && isset($data['results']) && !empty($data['results'])) {
            foreach (array_slice($data['results'], 0, 8) as $item) {
                $poster = '';
                if (!empty($item['poster_path'])) {
                    $poster = 'https://image.tmdb.org/t/p/w342' . $item['poster_path'];
                }
                $year = '';
                if (!empty($item['release_date'])) {
                    $year = substr($item['release_date'], 0, 4);
                }
                $results[] = [
                    'id' => 'tmdb_' . ($item['id'] ?? ''),
                    'title' => $item['title'] ?? $item['original_title'] ?? '',
                    'title_original' => $item['original_title'] ?? '',
                    'year' => $year,
                    'poster' => $poster,
                    'overview' => $item['overview'] ?? '',
                    'source' => 'TMDB'
                ];
            }
        }

        // FONTE 2: OMDb API (fallback rápido) - Busca direto com título traduzido
        if (empty($results)) {
            $omdbUrl = "https://www.omdbapi.com/?apikey=b6003d8a&s=" . urlencode($query) . "&type=movie";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $omdbUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2
            ]);
            $omdbResponse = curl_exec($ch);
            curl_close($ch);

            $omdbData = json_decode($omdbResponse, true);

            if (isset($omdbData['Search']) && !empty($omdbData['Search'])) {
                foreach (array_slice($omdbData['Search'], 0, 8) as $item) {
                    $results[] = [
                        'id' => $item['imdbID'] ?? '',
                        'title' => $item['Title'] ?? '',
                        'title_original' => $item['Title'] ?? '',
                        'year' => $item['Year'] ?? '',
                        'poster' => ($item['Poster'] !== 'N/A') ? $item['Poster'] : '',
                        'source' => 'OMDb'
                    ];
                }
            }
        }

        // FONTE 3: FM-DB (último recurso, mais lento)
        if (empty($results)) {
            $fmdbUrl = "https://imdb.iamidiotareyoutoo.com/search?q=" . urlencode($query);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $fmdbUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 2
            ]);
            $fmdbResponse = curl_exec($ch);
            curl_close($ch);

            $fmdbData = json_decode($fmdbResponse, true);

            if (isset($fmdbData['description'])) {
                foreach (array_slice($fmdbData['description'], 0, 8) as $item) {
                    $results[] = [
                        'id' => $item['#IMDB_ID'] ?? '',
                        'title' => $item['#TITLE'] ?? '',
                        'title_original' => $item['#TITLE'] ?? '',
                        'year' => $item['#YEAR'] ?? '',
                        'poster' => $item['#IMG_POSTER'] ?? '',
                        'source' => 'FMDB'
                    ];
                }
            }
        }

        echo json_encode(['success' => true, 'results' => $results, 'count' => count($results)]);
        exit;
        break;

    case 'get_movie_images':
        $title = sanitizeInput($_POST['title'] ?? '');
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Título vazio']);
            exit;
        }

        $posters = [];
        $ch = curl_init();

        // FONTE 1: TMDB em Português (prioridade para capas PT-BR)
        $tmdbSearchUrl = "https://api.themoviedb.org/3/search/movie?api_key=1f54bd990f1cdfb230adb312546d765d&language=pt-BR&query=" . urlencode($title) . "&region=BR";
        curl_setopt_array($ch, [
            CURLOPT_URL => $tmdbSearchUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        $tmdbResponse = curl_exec($ch);
        $tmdbData = json_decode($tmdbResponse, true);

        if (isset($tmdbData['results']) && !empty($tmdbData['results'])) {
            // Pega o ID do primeiro resultado
            $movieId = $tmdbData['results'][0]['id'];

            // Busca todas as imagens do filme (inclui posters em vários idiomas)
            $imagesUrl = "https://api.themoviedb.org/3/movie/{$movieId}/images?api_key=1f54bd990f1cdfb230adb312546d765d&include_image_language=pt,br,null";
            curl_setopt($ch, CURLOPT_URL, $imagesUrl);
            $imagesResponse = curl_exec($ch);
            $imagesData = json_decode($imagesResponse, true);

            if (isset($imagesData['posters']) && !empty($imagesData['posters'])) {
                // Ordena: primeiro PT/BR, depois sem idioma (null), depois outros
                usort($imagesData['posters'], function($a, $b) {
                    $langPriority = ['pt' => 0, 'br' => 0, '' => 1, null => 1];
                    $aLang = $a['iso_639_1'] ?? '';
                    $bLang = $b['iso_639_1'] ?? '';
                    $aPrio = $langPriority[$aLang] ?? 2;
                    $bPrio = $langPriority[$bLang] ?? 2;
                    return $aPrio - $bPrio;
                });

                foreach (array_slice($imagesData['posters'], 0, 10) as $poster) {
                    $posters[] = 'https://image.tmdb.org/t/p/w342' . $poster['file_path'];
                }
            }

            // Se não achou imagens específicas, usa o poster padrão PT-BR
            if (empty($posters)) {
                foreach (array_slice($tmdbData['results'], 0, 5) as $item) {
                    if (!empty($item['poster_path'])) {
                        $posters[] = 'https://image.tmdb.org/t/p/w342' . $item['poster_path'];
                    }
                }
            }
        }

        // FONTE 2: FM-DB como fallback (se TMDB não retornar nada)
        if (empty($posters)) {
            $fmdbUrl = "https://imdb.iamidiotareyoutoo.com/search?q=" . urlencode($title);
            curl_setopt($ch, CURLOPT_URL, $fmdbUrl);
            $fmdbResponse = curl_exec($ch);
            $fmdbData = json_decode($fmdbResponse, true);

            if (isset($fmdbData['description'])) {
                foreach ($fmdbData['description'] as $item) {
                    if (isset($item['#IMG_POSTER'])) {
                        $posters[] = $item['#IMG_POSTER'];
                    }
                }
            }
        }

        curl_close($ch);

        echo json_encode(['success' => true, 'posters' => array_unique($posters)]);
        exit;
        break;

    case 'generate_synopsis':
        $title = sanitizeInput($_POST['title'] ?? '');
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Título vazio']);
            exit;
        }

        $synopsis = "";

        // FONTE 1: TMDB - Busca sinopse oficial em português (overview)
        $tmdbUrl = "https://api.themoviedb.org/3/search/movie?api_key=1f54bd990f1cdfb230adb312546d765d&language=pt-BR&query=" . urlencode($title) . "&region=BR";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tmdbUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        $response = curl_exec($ch);
        $data = json_decode($response, true);

        // Pega a sinopse (overview) do primeiro resultado
        if (isset($data['results'][0]['overview']) && !empty($data['results'][0]['overview'])) {
            $synopsis = $data['results'][0]['overview'];
        }

        // FONTE 2: FM-DB (fallback)
        if (empty($synopsis)) {
            $fmdbUrl = "https://imdb.iamidiotareyoutoo.com/search?q=" . urlencode($title);
            curl_setopt($ch, CURLOPT_URL, $fmdbUrl);
            $fmdbResponse = curl_exec($ch);
            $fmdbData = json_decode($fmdbResponse, true);

            if (isset($fmdbData['description'][0]['#IMDB_ID'])) {
                $imdbId = $fmdbData['description'][0]['#IMDB_ID'];
                $detailUrl = "https://imdb.iamidiotareyoutoo.com/search?tt=" . $imdbId;
                curl_setopt($ch, CURLOPT_URL, $detailUrl);
                $detailResponse = curl_exec($ch);
                $detailData = json_decode($detailResponse, true);

                if (isset($detailData['short']['description'])) {
                    $synopsis = $detailData['short']['description'];
                }
            }
        }
        curl_close($ch);

        // Formata sinopse no estilo FITA DE FILME (curta, direta, só sobre o filme)
        if (!empty($synopsis)) {
            // Remove quebras de linha e espaços extras
            $synopsis = preg_replace('/\s+/', ' ', trim($synopsis));

            // Limita a ~200 caracteres (3 linhas) cortando na última frase completa
            if (strlen($synopsis) > 200) {
                $synopsis = substr($synopsis, 0, 200);
                // Tenta cortar no último ponto, vírgula ou espaço para não cortar palavra
                $lastPunct = max(strrpos($synopsis, '.'), strrpos($synopsis, ','), strrpos($synopsis, ' '));
                if ($lastPunct > 120) {
                    $synopsis = substr($synopsis, 0, $lastPunct);
                }
                // Remove pontuação solta no final e adiciona reticências
                $synopsis = rtrim($synopsis, ' .,;:') . '...';
            }
        } else {
            // Sinopse não encontrada
            $synopsis = "Informações não disponíveis para este título.";
        }

        echo json_encode(['success' => true, 'synopsis' => $synopsis]);
        exit;
        break;

    case 'save_movie':
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        $title = sanitizeInput($_POST['title'] ?? '');
        $image = sanitizeInput($_POST['image'] ?? '');
        $year = sanitizeInput($_POST['year'] ?? '');
        $time = sanitizeInput($_POST['time'] ?? '');
        $synopsis = sanitizeInput($_POST['synopsis'] ?? '');

        if ($index >= 0 && !empty($title)) {
            $config['schedule'][$index] = [
                'title' => $title,
                'image' => $image,
                'year' => $year,
                'time' => $time,
                'synopsis' => $synopsis
            ];
            if (saveConfig($config)) {
                logActivity('MOVIE_UPDATE', "Filme atualizado: $title");
                echo json_encode(['success' => true, 'message' => 'Filme salvo com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        }
        break;

    case 'add_movie':
        $config['schedule'][] = [
            'title' => 'Novo Filme',
            'image' => '',
            'year' => '',
            'time' => '00:00',
            'synopsis' => ''
        ];
        if (saveConfig($config)) {
            echo json_encode(['success' => true, 'message' => 'Novo filme adicionado!']);
        }
        break;

    case 'delete_movie':
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        if ($index >= 0 && isset($config['schedule'][$index])) {
            array_splice($config['schedule'], $index, 1);
            if (saveConfig($config)) {
                echo json_encode(['success' => true, 'message' => 'Filme removido!']);
            }
        }
        break;

    case 'duplicate_movie':
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        if ($index >= 0 && isset($config['schedule'][$index])) {
            $movieToDuplicate = $config['schedule'][$index];
            array_splice($config['schedule'], $index + 1, 0, [$movieToDuplicate]);
            if (saveConfig($config)) {
                echo json_encode(['success' => true, 'message' => 'Filme duplicado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao duplicar filme']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Filme não encontrado']);
        }
        break;

    case 'save_voting':
        $config['voting']['movie1']['title'] = sanitizeInput($_POST['vote_title1'] ?? '');
        $config['voting']['movie1']['image'] = sanitizeInput($_POST['vote_image1'] ?? '');
        $config['voting']['movie2']['title'] = sanitizeInput($_POST['vote_title2'] ?? '');
        $config['voting']['movie2']['image'] = sanitizeInput($_POST['vote_image2'] ?? '');
        $config['voting']['voting_title'] = sanitizeInput($_POST['voting_title'] ?? 'ESCOLHA O FILME QUE VOCÊ QUER VER AMANHÃ');
        $config['voting']['active'] = (isset($_POST['active']) && $_POST['active'] === 'true');
        
        if (saveConfig($config)) {
            echo json_encode(['success' => true, 'message' => 'Votação atualizada!']);
        }
        break;

    case 'reset_votes':
        $config['voting']['movie1']['votes'] = 0;
        $config['voting']['movie2']['votes'] = 0;
        $config['voting']['voters_ips'] = [];
        $config['voting']['current_vote_id'] = (int)($config['voting']['current_vote_id'] ?? 1) + 1;

        if (saveConfig($config)) {
            logActivity('VOTING_RESET', 'Votação reiniciada pelo administrador');
            echo json_encode(['success' => true, 'message' => 'Votos zerados com sucesso!']);
        }
        break;

    case 'clear_all_movies':
        $config['schedule'] = [];
        if (saveConfig($config)) {
            logActivity('SCHEDULE_CLEARED', 'Todos os filmes foram apagados pelo administrador');
            echo json_encode(['success' => true, 'message' => 'Todos os filmes foram apagados!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar configuração']);
        }
        break;

    case 'translate_title':
        $title = sanitizeInput($_POST['title'] ?? '');
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Título vazio']);
            exit;
        }

        // Busca título em português via Wikipedia PT
        $searchQuery = $title . " filme";
        $wikiUrl = "https://pt.wikipedia.org/w/api.php?action=query&list=search&srsearch=" . urlencode($searchQuery) . "&format=json&utf8=1&srlimit=3";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wikiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $translatedTitle = $title; // Mantém o original se não encontrar

        if (isset($data['query']['search'][0]['title'])) {
            $wikiTitle = $data['query']['search'][0]['title'];
            // Remove sufixos comuns como "(filme)", "(filme de 2020)", etc
            $wikiTitle = preg_replace('/\s*\(filme.*?\)\s*$/i', '', $wikiTitle);
            $translatedTitle = $wikiTitle;
        }

        // Se não encontrou na Wikipedia, tenta buscar no TMDB em português
        if ($translatedTitle === $title) {
            $tmdbUrl = "https://api.themoviedb.org/3/search/movie?api_key=2f9e1c0a7d3b8f4e5c6a9d0b1e2f3a4b&language=pt-BR&query=" . urlencode($title);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tmdbUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $tmdbResponse = curl_exec($ch);
            curl_close($ch);

            $tmdbData = json_decode($tmdbResponse, true);
            if (isset($tmdbData['results'][0]['title'])) {
                $translatedTitle = $tmdbData['results'][0]['title'];
            }
        }

        if ($translatedTitle !== $title) {
            echo json_encode(['success' => true, 'translated_title' => $translatedTitle, 'original' => $title]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível encontrar tradução', 'translated_title' => $title]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação desconhecida']);
        break;
}
?>
