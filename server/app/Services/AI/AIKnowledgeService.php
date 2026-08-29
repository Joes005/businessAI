<?php

namespace App\Services\AI;

use App\Models\AiKnowledgeDocument;
use App\Models\AiKnowledgeChunk;
use Illuminate\Support\Str;

class AIKnowledgeService
{
    /**
     * Index a business document into searchable chunks.
     */
    public function indexDocument(int $businessId, string $title, string $content, string $category = 'general'): AiKnowledgeDocument
    {
        $doc = AiKnowledgeDocument::create([
            'business_id' => $businessId,
            'title'       => $title,
            'category'    => $category,
        ]);

        // Simple sentence / paragraph chunking
        $paragraphs = array_filter(explode("\n\n", $content));
        foreach ($paragraphs as $index => $paragraph) {
            if (empty(trim($paragraph))) continue;

            AiKnowledgeChunk::create([
                'document_id' => $doc->id,
                'business_id' => $businessId,
                'content'     => trim($paragraph),
                'metadata'    => ['chunk_index' => $index, 'title' => $title],
            ]);
        }

        return $doc;
    }

    /**
     * Query knowledge base for relevant chunks.
     */
    public function search(int $businessId, string $query, int $limit = 3): array
    {
        $keywords = array_filter(explode(' ', Str::lower($query)));
        if (empty($keywords)) return [];

        $chunks = AiKnowledgeChunk::where('business_id', $businessId)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    if (strlen($kw) > 2) {
                        $q->orWhere('content', 'LIKE', "%{$kw}%");
                    }
                }
            })
            ->limit($limit)
            ->get();

        return $chunks->map(function ($chunk) {
            return [
                'document' => $chunk->document?->title ?? 'Document',
                'content'  => $chunk->content,
            ];
        })->toArray();
    }
}
