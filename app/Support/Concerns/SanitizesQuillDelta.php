<?php

namespace App\Support\Concerns;

trait SanitizesQuillDelta
{
    protected function sanitizeDelta($payload): ?array
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $ops = $payload['ops'] ?? null;

        if (! is_array($ops)) {
            return ['ops' => []];
        }

        $cleanOps = [];

        foreach ($ops as $op) {
            if (! is_array($op) || ! array_key_exists('insert', $op)) {
                continue;
            }

            if ($op['insert'] === null) {
                $op['insert'] = "\n";
            }

            $attributes = $op['attributes'] ?? null;

            if (is_array($attributes) && count($attributes) === 0) {
                unset($op['attributes']);
            } elseif ($attributes !== null) {
                $op['attributes'] = $attributes;
            }

            $cleanOps[] = $op;
        }

        return ['ops' => $cleanOps];
    }
}
