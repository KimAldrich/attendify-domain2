<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use RuntimeException;

class FirestoreRest
{
    private string $projectId;
    private string $base;
    private Client $http;

    /** @var ServiceAccountCredentials */
    private $creds;

    private ?string $token = null;
    private ?int $tokenExpiresAt = null; // unix ts

    public function __construct()
    {
        $project = config('services.firebase.project_id');
        $credCfg = config('services.firebase.credentials'); // array OR string path

        if (blank($project)) {
            throw new RuntimeException('Firebase project_id is missing (services.firebase.project_id).');
        }

        // Build creds
        $scope = ['https://www.googleapis.com/auth/datastore'];
        if (is_array($credCfg)) {
            $this->creds = new ServiceAccountCredentials($scope, $credCfg);
        } elseif (is_string($credCfg) && $credCfg !== '') {
            $path = $this->resolvePath($credCfg);
            if (!is_readable($path)) {
                throw new RuntimeException("FIREBASE_CREDENTIALS not readable: {$credCfg}");
            }
            $this->creds = new ServiceAccountCredentials($scope, $path);
        } else {
            throw new RuntimeException('Firebase credentials missing (services.firebase.credentials).');
        }

        $this->projectId = $project;

        $this->http = new Client([
            'base_uri'        => 'https://firestore.googleapis.com/',
            'timeout'         => 10,
            'connect_timeout' => 5,
        ]);

        $this->base = "v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    /* ---------- Auth helpers ---------- */

    private function resolvePath(string $maybeRelative): string
    {
        $isAbsolute =
            Str::startsWith($maybeRelative, ['/']) ||
            preg_match('/^[A-Za-z]:[\\\\\\/]/', $maybeRelative) === 1;

        return $isAbsolute ? $maybeRelative : base_path($maybeRelative);
    }

    private function authHeader(): array
    {
        $now = time();
        if (!$this->token || !$this->tokenExpiresAt || ($this->tokenExpiresAt - $now) <= 60) {
            $resp = $this->creds->fetchAuthToken();
            $this->token = $resp['access_token'] ?? null;

            if (isset($resp['expires_at']) && is_numeric($resp['expires_at'])) {
                $this->tokenExpiresAt = (int) $resp['expires_at'];
            } elseif (isset($resp['expires_in']) && is_numeric($resp['expires_in'])) {
                $this->tokenExpiresAt = $now + (int) $resp['expires_in'];
            } else {
                $this->tokenExpiresAt = $now + 55 * 60;
            }
        }

        if (!$this->token) {
            throw new RuntimeException('Unable to fetch Google access token.');
        }

        return ['Authorization' => "Bearer {$this->token}"];
    }

    /* ---------- Firestore value encoding ---------- */

    private function isAssoc(array $arr): bool
    {
        if ($arr === []) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /** Minimal mapper: PHP scalar/array => Firestore Value */
    private function value($v): array
    {
        return match (true) {
            is_string($v)                    => ['stringValue' => $v],
            is_int($v)                       => ['integerValue' => (string) $v],
            is_float($v)                     => ['doubleValue' => $v],
            is_bool($v)                      => ['booleanValue' => $v],
            $v instanceof \DateTimeInterface => ['timestampValue' => $v->format('c')],
            is_array($v) && $this->isAssoc($v)
                                            => ['mapValue' => ['fields' => $this->fields($v)]],
            is_array($v)                     => ['listValue' => ['values' => array_map(fn($item) => $this->value($item), $v)]],
            $v === null                      => ['nullValue' => null],
            default                          => ['stringValue' => (string) $v],
        };
    }

    private function fields(array $map): array
    {
        $out = [];
        foreach ($map as $k => $v) {
            $out[$k] = $this->value($v);
        }
        return $out;
    }

    /** For updateMask with simple field names. If you need dotted/nested paths, extend this. */
    private function maskPaths(array $data): array
    {
        // Simple: top-level keys only. (Extend to dotted paths if you need nested partial updates.)
        return array_keys($data);
    }

    private function docPath(string $collection, string $docId): string
    {
        return "{$this->base}/{$collection}/{$docId}";
    }

    /* ---------- CRUD ---------- */

    /** Create a document in a collection (auto ID) */
    public function add(string $collection, array $data): array
    {
        $resp = $this->http->post("{$this->base}/{$collection}", [
            'headers'     => $this->authHeader(),
            'json'        => ['fields' => $this->fields($data)],
            'http_errors' => true,
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /** Create a document with a fixed ID (fails if exists) */
    public function create(string $collection, string $docId, array $data): array
    {
        $resp = $this->http->post("{$this->base}/{$collection}", [
            'headers'     => $this->authHeader(),
            'query'       => ['documentId' => $docId],
            'json'        => ['fields' => $this->fields($data)],
            'http_errors' => true,
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /** Get a document by path: "collection/docId" */
    public function get(string $docPath): array
    {
        $resp = $this->http->get("{$this->base}/{$docPath}", [
            'headers'     => $this->authHeader(),
            'http_errors' => true,
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /** List documents in a collection (simple wrapper) */
    public function list(string $collection, array $opts = []): array
    {
        $query = [];
        if (isset($opts['pageSize']))  $query['pageSize']  = (int) $opts['pageSize'];
        if (isset($opts['pageToken'])) $query['pageToken'] = $opts['pageToken'];
        if (isset($opts['orderBy']))   $query['orderBy']   = $opts['orderBy']; // e.g., "field asc"

        $resp = $this->http->get("{$this->base}/{$collection}", [
            'headers'     => $this->authHeader(),
            'query'       => $query,
            'http_errors' => true,
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /**
     * Upsert a document with a fixed id: users/{docId}
     * - If $options['merge'] === true, uses PATCH with updateMask (partial update).
     * - Else, replaces provided fields (PATCH w/o updateMask) and creates if missing.
     */
    public function set(string $collection, string $docId, array $data, array $options = []): array
    {
        $merge = (bool)($options['merge'] ?? false);
        $docPath = $this->docPath($collection, $docId);

        try {
            if ($merge) {
                // Partial update with updateMask so unspecified fields are preserved
                $pairs = [];
                foreach ($this->maskPaths($data) as $fieldPath) {
                    $pairs[] = 'updateMask.fieldPaths=' . rawurlencode($fieldPath);
                }
                $query = implode('&', $pairs);

                $resp = $this->http->patch($docPath, [
                    'headers' => $this->authHeader(),
                    'query'   => $query,               // ← string
                    'json'    => ['fields' => $this->fields($data)],
                    'http_errors' => true,
                ]);

            } else {
                // Replace provided fields (fields not in body are removed). Creates if not found (handled below).
                $resp = $this->http->patch($docPath, [
                    'headers'     => $this->authHeader(),
                    'json'        => ['fields' => $this->fields($data)],
                    'http_errors' => true,
                ]);
            }
            return json_decode((string) $resp->getBody(), true);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $code = $e->getResponse()?->getStatusCode();
            if ($code !== 404) {
                $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : null;
                throw new RuntimeException("Firestore PATCH failed ({$code}): {$body}", $code, $e);
            }

            // Create with fixed id when not found
            $resp = $this->http->post("{$this->base}/{$collection}", [
                'headers'     => $this->authHeader(),
                'query'       => ['documentId' => $docId],
                'json'        => ['fields' => $this->fields($data)],
                'http_errors' => true,
            ]);
            return json_decode((string) $resp->getBody(), true);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new RuntimeException('Firestore connection error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Update (partial) — safe merge semantics using updateMask.
     * Only the provided fields will be changed; the rest of the doc is preserved.
     */
    public function update(string $collection, string $docId, array $data): array
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Firestore update() requires at least one field.');
        }

        $docPath = "{$this->base}/{$collection}/{$docId}";

        // Build query manually to avoid "fieldPaths[0]" encoding
        $pairs = [];
        foreach (array_keys($data) as $fieldPath) {
            // Allow dotted paths if you ever pass nested fields (e.g. "profile.displayName")
            $pairs[] = 'updateMask.fieldPaths=' . rawurlencode($fieldPath);
        }
        $query = implode('&', $pairs);

        $resp = $this->http->patch($docPath, [
            'headers'     => $this->authHeader(),
            'query'       => $query, // ← string, not array
            'json'        => ['fields' => $this->fields($data)],
            'http_errors' => true,
        ]);

        return json_decode((string) $resp->getBody(), true);
    }


    /** Delete document */
    public function delete(string $collection, string $docId): bool
    {
        $docPath = $this->docPath($collection, $docId);

        $this->http->delete($docPath, [
            'headers'     => $this->authHeader(),
            'http_errors' => true,
        ]);

        return true;
    }

    /** Exists helper */
    public function exists(string $collection, string $docId): bool
    {
        try {
            $this->get("{$collection}/{$docId}");
            return true;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse()?->getStatusCode() !== 404 ? throw $e : false;
        }
    }
}
