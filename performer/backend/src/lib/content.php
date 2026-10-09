<?php

declare(strict_types=1);

/** @return mixed */
function decodeContentInput(array $content)
{
    switch ($content['kind']) {
        case 'json':
            // Object mode so {} stays {} rather than becoming [].
            return json_decode($content['json'], false, 512, JSON_THROW_ON_ERROR);
        case 'passthrough_string':
            return $content['value'];
        case 'byte_array':
            return base64_decode($content['value']);
        case 'null':
            return null;
        default:
            throw new \RuntimeException("unknown content kind {$content['kind']}");
    }
}

/** @param mixed $content */
function encodeContentAs($content, string $contentAs): array
{
    if ($content === null) {
        return ['kind' => 'null'];
    }

    switch ($contentAs) {
        case 'boolean':
            return ['kind' => 'bool', 'bool' => (bool) $content];
        case 'integer':
            return ['kind' => 'int64', 'int64' => (int) $content];
        case 'floating_point':
            return ['kind' => 'double', 'double' => (float) $content];
        case 'string':
            return ['kind' => 'string', 'string' => is_string($content) ? $content : json_encode($content)];
        case 'json_object':
        case 'json_array':
        case 'byte_array':
        default:
            $encoded = is_string($content) ? $content : json_encode($content);
            return ['kind' => 'bytes', 'bytes' => base64_encode((string) $encoded)];
    }
}
