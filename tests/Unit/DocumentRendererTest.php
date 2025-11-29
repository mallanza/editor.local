<?php

use App\Models\QuillChange;
use App\Models\QuillDocument;
use App\Support\Quill\DocumentRenderer;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

it('renders contiguous tracked inserts without duplication', function () {
    $renderer = app(DocumentRenderer::class);

    $document = new QuillDocument([
        'clean_delta' => [
            'ops' => [
                ['insert' => "Sample document\n\n"],
            ],
        ],
    ]);

    $changeUuid = (string) Str::uuid();

    $change = new QuillChange([
        'change_uuid' => $changeUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => 15,
        'anchor_length' => 8,
        'delta' => [
            'ops' => [
                ['insert' => "\ntest3\n", 'attributes' => ['tc-change-id' => $changeUuid]],
            ],
        ],
    ]);

    $delta = $renderer->buildRedlineDelta($document, [$change]);

    $trackedOps = array_values(array_filter($delta['ops'], function ($op) use ($changeUuid) {
        return ($op['attributes']['tc-change-id'] ?? null) === $changeUuid;
    }));

    expect($trackedOps)->toHaveCount(1);
    expect($trackedOps[0]['insert'])->toBe("\ntest3\n");
});

it('preserves paragraph spacing with sequential inserts', function () {
    $renderer = app(DocumentRenderer::class);

    $document = new QuillDocument([
        'clean_delta' => [
            'ops' => [
                ['insert' => "Sample document\n\n"],
            ],
        ],
    ]);

    $firstUuid = (string) Str::uuid();
    $secondUuid = (string) Str::uuid();

    $firstChange = new QuillChange([
        'change_uuid' => $firstUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => 15,
        'anchor_length' => 8,
        'delta' => [
            'ops' => [
                ['insert' => "\ntest3\n", 'attributes' => ['tc-change-id' => $firstUuid]],
            ],
        ],
    ]);

    $secondChange = new QuillChange([
        'change_uuid' => $secondUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => 22,
        'anchor_length' => 8,
        'delta' => [
            'ops' => [
                ['insert' => "\ntest4\n", 'attributes' => ['tc-change-id' => $secondUuid]],
            ],
        ],
    ]);

    $delta = $renderer->buildRedlineDelta($document, [$firstChange, $secondChange]);

    $ops = array_values($delta['ops']);
    $firstInsert = array_values(array_filter($ops, fn ($op) => ($op['attributes']['tc-change-id'] ?? null) === $firstUuid))[0] ?? null;
    $secondInsert = array_values(array_filter($ops, fn ($op) => ($op['attributes']['tc-change-id'] ?? null) === $secondUuid))[0] ?? null;

    expect($firstInsert['insert'])->toBe("\ntest3\n");
    expect($secondInsert['insert'])->toBe("\ntest4\n");

    $combinedText = implode('', array_map(fn ($op) => $op['insert'] ?? '', $ops));
    expect($combinedText)->toStartWith('Sample document');
    expect($combinedText)->toContain("test3\n");
    expect($combinedText)->toContain("test4\n");
});

it('honors anchor indexes that already include pending inserts', function () {
    $renderer = app(DocumentRenderer::class);

    $document = new QuillDocument([
        'clean_delta' => [
            'ops' => [
                ['insert' => "ABCDEFGHIJ"],
            ],
        ],
    ]);

    $firstUuid = (string) Str::uuid();
    $secondUuid = (string) Str::uuid();

    $firstChange = new QuillChange([
        'change_uuid' => $firstUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => 10,
        'anchor_length' => 1,
        'delta' => [
            'ops' => [
                ['insert' => "\n", 'attributes' => ['tc-change-id' => $firstUuid]],
            ],
        ],
    ]);

    $secondChange = new QuillChange([
        'change_uuid' => $secondUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => 11, // already accounts for the prior newline insert
        'anchor_length' => 8,
        'delta' => [
            'ops' => [
                ['insert' => "XYZ line\n", 'attributes' => ['tc-change-id' => $secondUuid]],
            ],
        ],
    ]);

    $delta = $renderer->buildRedlineDelta($document, [$firstChange, $secondChange]);

    $renderedText = implode('', array_map(fn ($op) => $op['insert'] ?? '', $delta['ops']));

    expect($renderedText)->toBe("ABCDEFGHIJ\nXYZ line\n");
});

it('reconstructs multi-paragraph inserts after blank lines', function () {
    $renderer = app(DocumentRenderer::class);

    $baseText = "Sample document\n\ntest1\ntest2 test\n\n";
    $baseLength = mb_strlen($baseText);

    $document = new QuillDocument([
        'clean_delta' => [
            'ops' => [
                ['insert' => $baseText],
            ],
        ],
    ]);

    $firstBreakUuid = (string) Str::uuid();
    $secondBreakUuid = (string) Str::uuid();
    $paragraphUuid = (string) Str::uuid();

    $firstBreak = new QuillChange([
        'change_uuid' => $firstBreakUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => $baseLength,
        'anchor_length' => 1,
        'delta' => [
            'ops' => [
                ['insert' => "\n", 'attributes' => ['tc-change-id' => $firstBreakUuid]],
            ],
        ],
    ]);

    $secondBreak = new QuillChange([
        'change_uuid' => $secondBreakUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => $baseLength + 1,
        'anchor_length' => 1,
        'delta' => [
            'ops' => [
                ['insert' => "\n", 'attributes' => ['tc-change-id' => $secondBreakUuid]],
            ],
        ],
    ]);

    $paragraphInsert = new QuillChange([
        'change_uuid' => $paragraphUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => $baseLength + 2,
        'anchor_length' => 27,
        'delta' => [
            'ops' => [
                ['insert' => "this is test2 line comment\n\n", 'attributes' => ['tc-change-id' => $paragraphUuid]],
            ],
        ],
    ]);

    $delta = $renderer->buildRedlineDelta($document, [$firstBreak, $secondBreak, $paragraphInsert]);

    $renderedText = implode('', array_map(fn ($op) => $op['insert'] ?? '', $delta['ops']));
    $expected = $baseText . "\n\nthis is test2 line comment\n\n";

    expect($renderedText)->toBe($expected);
});

it('splits multi-paragraph insert ops to preserve layout', function () {
    $renderer = app(DocumentRenderer::class);

    $baseText = "Sample document\n\n";
    $document = new QuillDocument([
        'clean_delta' => [
            'ops' => [
                ['insert' => $baseText],
            ],
        ],
    ]);

    $changeUuid = (string) Str::uuid();
    $multiParagraphText = "\nfirst addition\n\nsecond addition\n\nthird addition\n\n";

    $change = new QuillChange([
        'change_uuid' => $changeUuid,
        'change_type' => QuillChange::TYPE_INSERT,
        'status' => QuillChange::STATUS_PENDING,
        'anchor_index' => mb_strlen($baseText),
        'anchor_length' => mb_strlen($multiParagraphText),
        'delta' => [
            'ops' => [
                ['insert' => $multiParagraphText, 'attributes' => ['tc-change-id' => $changeUuid]],
            ],
        ],
    ]);

    $delta = $renderer->buildRedlineDelta($document, [$change]);

    $trackedOps = array_values(array_filter($delta['ops'], fn ($op) => ($op['attributes']['tc-change-id'] ?? null) === $changeUuid));

    expect($trackedOps)->toHaveCount(3);
    expect(implode('', array_map(fn ($op) => $op['insert'] ?? '', $trackedOps)))->toBe($multiParagraphText);

    $renderedText = implode('', array_map(fn ($op) => $op['insert'] ?? '', $delta['ops']));
    expect($renderedText)->toBe($baseText . $multiParagraphText);
});

