<?php

namespace App\Support\Concerns;

trait BuildsCleanQuillDelta
{
    protected function buildCleanDelta(array $content): array
    {
        $ops = $content['ops'] ?? [];

        if (! is_array($ops) || empty($ops)) {
            return ['ops' => []];
        }

        $cleanOps = [];

        foreach ($ops as $op) {
            if (! is_array($op) || ! array_key_exists('insert', $op)) {
                continue;
            }

            $attributes = $op['attributes'] ?? [];

            if (! empty($attributes['tc-change-id'])) {
                continue;
            }

            unset(
                $attributes['comment'],
                $attributes['tc-change-type'],
                $attributes['tc-author-id'],
                $attributes['tc-author-name'],
                $attributes['tc-timestamp'],
            );

            if (! empty($attributes)) {
                $cleanOps[] = [
                    'insert' => $op['insert'],
                    'attributes' => $attributes,
                ];
            } else {
                $cleanOps[] = [
                    'insert' => $op['insert'],
                ];
            }
        }

        return ['ops' => $cleanOps];
    }
}
