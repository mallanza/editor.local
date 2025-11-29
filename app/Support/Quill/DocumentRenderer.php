<?php

namespace App\Support\Quill;

use App\Models\QuillChange;
use App\Models\QuillDocument;
use App\Support\Concerns\BuildsCleanQuillDelta;

class DocumentRenderer
{
    use BuildsCleanQuillDelta;

    private const TRACK_ATTRIBUTES = [
        'tc-change-id',
        'tc-change-type',
        'tc-author-id',
        'tc-author-name',
        'tc-timestamp',
    ];

    /**
     * Build a redline delta composed from the clean snapshot and pending changes.
     */
    public function buildRedlineDelta(QuillDocument $document, $changes): array
    {
        $cleanDelta = $document->clean_delta;

        if (! is_array($cleanDelta) || empty($cleanDelta['ops'])) {
            $cleanDelta = $this->buildCleanDelta($document->content_delta ?? ['ops' => []]);
        }

        $sourceOps = $cleanDelta['ops'] ?? [];

        if (! is_iterable($changes)) {
            $changes = [];
        }

        $pendingChanges = [];
        foreach ($changes as $change) {
            if (! $change instanceof QuillChange) {
                continue;
            }
            if ($change->status !== QuillChange::STATUS_PENDING) {
                continue;
            }
            $pendingChanges[] = $change;
        }

        if (empty($pendingChanges)) {
            return $cleanDelta;
        }

        usort($pendingChanges, function (QuillChange $a, QuillChange $b) {
            return $a->anchor_index <=> $b->anchor_index;
        });

        $insertions = $this->buildInsertionMap($pendingChanges);
        $positions = array_keys($insertions);
        sort($positions, SORT_NUMERIC);

        $finalOps = [];
        $cursor = 0;

        foreach ($sourceOps as $op) {
            $length = $this->opLength($op);

            while ($positions && $positions[0] <= $cursor) {
                $this->appendInsertions($finalOps, $insertions, array_shift($positions));
            }

            if (! isset($op['insert'])) {
                $finalOps[] = $op;
                $cursor += $length;
                continue;
            }

            $insert = $op['insert'];

            if (! is_string($insert)) {
                $finalOps[] = $op;
                $cursor += $length;
                continue;
            }

            $attributes = $op['attributes'] ?? [];
            $offset = 0;

            while ($positions && $positions[0] > $cursor && $positions[0] < ($cursor + $length)) {
                $splitPos = $positions[0] - $cursor - $offset;
                if ($splitPos > 0) {
                    $segment = mb_substr($insert, $offset, $splitPos);
                    $this->pushTextOp($finalOps, $segment, $attributes);
                    $offset += $splitPos;
                }

                $this->appendInsertions($finalOps, $insertions, array_shift($positions));
            }

            $remaining = mb_substr($insert, $offset);
            if ($remaining !== '') {
                $this->pushTextOp($finalOps, $remaining, $attributes);
            }

            $cursor += $length;
        }

        while ($positions) {
            $this->appendInsertions($finalOps, $insertions, array_shift($positions));
        }

        return ['ops' => $finalOps];
    }

    private function decorateInsert(QuillChange $change): array
    {
        $normalizedOps = $this->normalizeChangeOps($change->delta['ops'] ?? []);
        $normalizedOps = $this->explodeParagraphOps($normalizedOps);
        $attributes = $this->trackAttributes($change, 'insert');

        if (empty($normalizedOps)) {
            return [[
                'insert' => '',
                'attributes' => $attributes,
            ]];
        }

        return array_map(function (array $op) use ($attributes) {
            $opAttributes = array_merge($op['attributes'] ?? [], $attributes);
            $result = ['insert' => $op['insert']];
            if (! empty($opAttributes)) {
                $result['attributes'] = $opAttributes;
            }
            return $result;
        }, $normalizedOps);
    }

    private function decorateDelete(QuillChange $change): array
    {
        $normalizedOps = $this->normalizeChangeOps($change->delta['ops'] ?? []);
        $attributes = $this->trackAttributes($change, 'delete');

        if (empty($normalizedOps)) {
            return [[
                'insert' => '',
                'attributes' => $attributes,
            ]];
        }

        return array_map(function (array $op) use ($attributes) {
            return [
                'insert' => $op['insert'],
                'attributes' => $attributes,
            ];
        }, $normalizedOps);
    }

    private function trackAttributes(QuillChange $change, string $type): array
    {
        return [
            'tc-change-id' => $change->change_uuid,
            'tc-change-type' => $type,
            'tc-author-id' => $change->user_id,
            'tc-author-name' => $change->user_name ?? 'Unknown User',
            'tc-timestamp' => optional($change->created_at)->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    private function opLength(array $op): int
    {
        $insert = $op['insert'] ?? '';

        if (is_string($insert)) {
            return mb_strlen($insert);
        }

        return 1;
    }

    private function buildInsertionMap(array $changes): array
    {
        $insertions = [];

        foreach ($changes as $change) {
            $position = max(0, (int) ($change->anchor_index ?? 0));
            $ops = $change->change_type === QuillChange::TYPE_DELETE
                ? $this->decorateDelete($change)
                : $this->decorateInsert($change);

            if (empty($ops)) {
                continue;
            }

            if (! isset($insertions[$position])) {
                $insertions[$position] = [];
            }

            $insertions[$position] = array_merge($insertions[$position], $ops);
        }

        return $insertions;
    }

    private function explodeParagraphOps(array $ops): array
    {
        $expanded = [];

        foreach ($ops as $op) {
            $insert = $op['insert'] ?? null;

            if (! is_string($insert) || ! str_contains($insert, "\n\n")) {
                $expanded[] = $op;
                continue;
            }

            foreach ($this->splitParagraphSegments($insert) as $segment) {
                if ($segment === '') {
                    continue;
                }

                $piece = ['insert' => $segment];
                if (! empty($op['attributes'])) {
                    $piece['attributes'] = $op['attributes'];
                }
                $expanded[] = $piece;
            }
        }

        return $expanded;
    }

    private function splitParagraphSegments(string $text): array
    {
        if (! str_contains($text, "\n\n")) {
            return [$text];
        }

        $segments = [];
        $length = mb_strlen($text);
        $buffer = '';

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $buffer .= $char;

            if ($char !== "\n") {
                continue;
            }

            $runLength = 1;
            while (($i + $runLength) < $length && mb_substr($text, $i + $runLength, 1) === "\n") {
                $buffer .= "\n";
                $runLength++;
            }

            if ($runLength >= 2) {
                $segments[] = $buffer;
                $buffer = '';
            }

            $i += $runLength - 1;
        }

        if ($buffer !== '') {
            $segments[] = $buffer;
        }

        return $segments;
    }

    private function appendInsertions(array &$target, array &$insertions, int $position): void
    {
        $ops = $insertions[$position] ?? [];

        foreach ($ops as $op) {
            $target[] = $op;
        }

        unset($insertions[$position]);
    }

    private function pushTextOp(array &$target, string $text, array $attributes = []): void
    {
        if ($text === '') {
            return;
        }

        $op = ['insert' => $text];

        if (! empty($attributes)) {
            $op['attributes'] = $attributes;
        }

        if (! empty($target)) {
            $lastIndex = count($target) - 1;
            $last = $target[$lastIndex];
            if ($this->canMergeOps($last, $op)) {
                $target[$lastIndex]['insert'] .= $text;
                return;
            }
        }

        $target[] = $op;
    }

    private function normalizeChangeOps(array $ops): array
    {
        $normalized = [];

        foreach ($ops as $op) {
            if (! array_key_exists('insert', $op)) {
                continue;
            }

            $insert = $op['insert'];
            if ($insert === '' || $insert === null) {
                continue;
            }

            $attributes = $op['attributes'] ?? [];
            foreach (self::TRACK_ATTRIBUTES as $attr) {
                unset($attributes[$attr]);
            }

            $candidate = ['insert' => $insert];
            if (! empty($attributes)) {
                ksort($attributes);
                $candidate['attributes'] = $attributes;
            }

            if (! empty($normalized)) {
                $lastIndex = count($normalized) - 1;
                $lastOp = $normalized[$lastIndex];

                if ($this->canMergeOps($lastOp, $candidate)) {
                    $lastText = $lastOp['insert'];
                    $nextText = $candidate['insert'];

                    if (str_contains($nextText, $lastText)) {
                        $normalized[$lastIndex] = $candidate;
                        continue;
                    }

                    if (str_contains($lastText, $nextText)) {
                        continue;
                    }

                    $normalized[$lastIndex]['insert'] .= $insert;
                    continue;
                }
            }

            $normalized[] = $candidate;
        }

        return $normalized;
    }

    private function canMergeOps(array $a, array $b): bool
    {
        if (! is_string($a['insert'] ?? null) || ! is_string($b['insert'] ?? null)) {
            return false;
        }

        $aAttr = $a['attributes'] ?? [];
        $bAttr = $b['attributes'] ?? [];

        ksort($aAttr);
        ksort($bAttr);

        return $aAttr === $bAttr;
    }

}
