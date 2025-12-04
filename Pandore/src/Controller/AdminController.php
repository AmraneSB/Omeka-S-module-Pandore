<?php
namespace Pandore\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AdminController extends AbstractActionController
{
    public function indexAction()
    {
        $query = $this->params()->fromQuery('q');
        $results = [
            'wikidata' => [],
            'omeka' => [],
            'spotify' => [],
            'jamendo' => [],
        ];

        if ($query) {
            // --- Omeka S ---
            $entityManager = $this->getEvent()->getApplication()
                ->getServiceManager()
                ->get('Omeka\EntityManager');

            $dql = "SELECT i FROM Omeka\Entity\Item i 
                    JOIN i.values v 
                    WHERE v.value LIKE :q";
            $items = $entityManager->createQuery($dql)
                ->setParameter('q', "%$query%")
                ->getResult();

            $results['omeka'] = $items;

            // --- Wikidata ---
            $results['wikidata'] = $this->searchWikidata($query);
            foreach ($results['wikidata'] as &$entity) {
                if (!empty($entity['id'])) {
                    $entity['image'] = $this->getWikidataImage($entity['id']);
                    $entity['audio'] = $this->getWikidataAudio($entity['id']);
                }
            }

            // --- Spotify (extraits officiels) ---
            $results['spotify'] = $this->searchSpotify($query);

            // --- Jamendo (audio libre) ---
            $results['jamendo'] = $this->searchJamendo($query);
        }

        return new ViewModel([
            'results' => $results,
            'query' => $query,
        ]);
    }

    // ======================== WIKIDATA ========================
    private function searchWikidata(string $name): array
    {
        $url = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search="
            . urlencode($name)
            . "&language=fr&format=json";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'PandoreModule/3.0 (http://localhost)',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return [];
        $data = json_decode($response, true);
        return $data['search'] ?? [];
    }

    private function getWikidataImage(string $qid): ?string
    {
        $sparql = "SELECT ?image WHERE { wd:$qid wdt:P18 ?image } LIMIT 1";
        return $this->sparqlQuerySingle($sparql, 'image');
    }

    private function getWikidataAudio(string $qid): ?string
    {
        $sparql = "SELECT ?audio WHERE { wd:$qid wdt:P85 ?audio } LIMIT 1";
        return $this->sparqlQuerySingle($sparql, 'audio');
    }

    private function sparqlQuerySingle(string $sparql, string $var): ?string
    {
        $url = "https://query.wikidata.org/sparql?query=" . urlencode($sparql) . "&format=json";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'PandoreModule/3.0 (http://localhost)',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return null;
        $data = json_decode($response, true);
        return $data['results']['bindings'][0][$var]['value'] ?? null;
    }

    // ======================== SPOTIFY ========================
    private function searchSpotify(string $query): array
    {
        $queryEncoded = urlencode($query);
        $url = "https://open.spotify.com/search/$queryEncoded";
        return ['link' => $url];
    }

    // ======================== JAMENDO (libre) ========================
    private function searchJamendo(string $query): array
    {
        // Utilisation API publique Jamendo : https://developer.jamendo.com/v3.0
        // Limite 5 morceaux
        $client_id = "01fb8f96"; 
        $url = "https://api.jamendo.com/v3.0/tracks/?client_id=$client_id&format=json&limit=5&search=" . urlencode($query);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'PandoreModule/3.0 (http://localhost)',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return [];
        $data = json_decode($response, true);

        $tracks = [];
        if (!empty($data['results'])) {
            foreach ($data['results'] as $track) {
                $tracks[] = [
                    'name' => $track['name'],
                    'artist' => $track['artist_name'],
                    'audio' => $track['audio'],
                    'url' => $track['shareurl'],
                ];
            }
        }

        return $tracks;
    }
}
