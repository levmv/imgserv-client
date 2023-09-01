<?php declare(strict_types=1);

namespace levmv\imgserv;

use mii\core\Component;
use mii\log\Log;
use mii\rest\Client;
use mii\rest\Response;
use function http_build_query;

class Imgserv extends Component
{
    protected string $api = 'http://127.0.0.1:8081';
    protected Client $client;


    public function init(array $config = []): void
    {
        parent::init($config);
        $this->client = new Client([
            'base_uri' => $this->api
        ]);
    }


    /**
     * Calls imgserv/upload_file endpoint, to upload to storage file from local disk
     * File must be accessible to imgserv
     * If optional $key is omitted then imgserv will generate uuid on its own
     * @return null|array<$key string, $width int, $height int>
     */
    public function uploadLocalFile(string $filename, string $key = ""): ?array
    {
        $resp = $this->sendRequest("/upload_file", [
            "key" => $key,
            "filename" => $filename
        ]);
        if (!$resp->isOk()) {
            return null;
        }

        return $resp->asArray();
    }

    /**
     * Uploads file to storage
     * If optional $key is omitted then imgserv will generate uuid on its own
     * @return null|array<$key string, $width int, $height int>
     */
    public function upload(string $body, string $key = ""): ?array
    {
        $resp = $this->sendRequest("/upload", [
            "key" => $key
        ], $body);
        if (!$resp->isOk()) {
            return null;
        }

        return $resp->asArray();
    }


    public function delete(string $key): bool
    {
        return $this->sendRequest("/delete", ['key' => $key])->isOk();
    }


    public function share(string $key, string $text, bool $preview = null): ?string
    {
        $resp = $this->sendRequest("/share", [
            'key' => $key,
            'text' => $text,
            'preview' => $preview
        ]);

        if (!$resp->isOk()) {
            return null;
        }
        return $resp->body;
    }


    private function sendRequest($url, array $args, string $raw = null): Response
    {
        $url = $url . '?' . http_build_query($args);

        $headers = [];
        if ($raw !== null) {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        $resp = $this->client->post($url, $raw, $headers);

        if (!$resp->isOk()) {
            Log::error($url, $resp->statusCode(), $this->client->lastError());
            return $resp;
        }

        return $resp;
    }
}