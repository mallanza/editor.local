<?php

namespace App\Console\Commands;

use App\Models\QuillChange;
use App\Models\QuillComment;
use App\Models\QuillDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetQuillDocument extends Command
{
    protected $signature = 'quill:reset {--text=Sample document}'
        . ' {--title=Quill Proof of Concept}';

    protected $description = 'Truncate Quill documents/changes/comments and seed a fresh baseline snapshot.';

    public function handle(): int
    {
        $seedText = (string) $this->option('text');
        $seedTitle = (string) $this->option('title');
        $seedText = $seedText === '' ? "Sample document" : $seedText;

        $document = null;

        DB::transaction(function () use ($seedText, $seedTitle, &$document) {
            QuillChange::query()->delete();
            QuillComment::query()->delete();
            QuillDocument::query()->delete();

            $normalizedText = rtrim($seedText, "\n") . "\n";
            $seedDelta = [
                'ops' => [
                    ['insert' => $normalizedText],
                ],
            ];

            $document = QuillDocument::create([
                'title' => $seedTitle,
                'content_delta' => $seedDelta,
                'clean_delta' => $seedDelta,
                'base_delta' => $seedDelta,
                'version' => 1,
            ]);
        });

        $documentId = $document?->id ?? 'n/a';
        $this->info('Quill storage reset. Document ID: ' . $documentId);

        return self::SUCCESS;
    }
}
