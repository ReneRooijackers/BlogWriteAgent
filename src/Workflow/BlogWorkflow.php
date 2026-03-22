<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Agents\ResearchAgent;
use App\Agents\ReviewerAgent;
use App\Agents\SeoAgent;
use App\Agents\WriterAgent;
use App\Storage\BlogRepository;

class BlogWorkflow
{
    public function __construct(
        private ResearchAgent $researchAgent,
        private SeoAgent $seoAgent,
        private WriterAgent $writerAgent,
        private ReviewerAgent $reviewerAgent,
        private BlogRepository $repository
    ) {
    }

    public function generate(string $topic): array
    {
        $research = $this->researchAgent->run($topic);
        $seo = $this->seoAgent->run($research);
        $draft = $this->writerAgent->run($research, $seo);
        $review = $this->reviewerAgent->run($research, $seo, $draft);

        $record = $this->repository->create([
            'topic' => $topic,
            'title' => $review['improved_title'],
            'excerpt' => $review['improved_excerpt'],
            'markdown' => $review['improved_markdown'],
            'research' => $research,
            'seo' => $seo,
            'draft' => $draft,
            'review' => $review,
            'status' => 'pending_human_review',
            'human_feedback' => [],
        ]);

        return $record;
    }

    public function approve(string $id): ?array
    {
        $blog = $this->repository->update($id, [
            'status' => 'approved',
        ]);

        if ($blog !== null) {
            $exportPath = $this->repository->exportApprovedMarkdown($blog);
            $blog = $this->repository->update($id, [
                'export_path' => $exportPath,
            ]);
        }

        return $blog;
    }

    public function reject(string $id, string $reason): ?array
    {
        return $this->repository->update($id, [
            'status' => 'rejected',
            'human_feedback' => [
                'type' => 'reject',
                'reason' => $reason,
                'at' => date('c'),
            ],
        ]);
    }

    public function revise(string $id, string $feedback): ?array
    {
        $blog = $this->repository->find($id);
        if ($blog === null) {
            return null;
        }

        $revisedDraft = $this->writerAgent->run(
            array_merge($blog['research'], ['human_feedback' => $feedback]),
            $blog['seo']
        );

        $review = $this->reviewerAgent->run($blog['research'], $blog['seo'], $revisedDraft);

        return $this->repository->update($id, [
            'title' => $review['improved_title'],
            'excerpt' => $review['improved_excerpt'],
            'markdown' => $review['improved_markdown'],
            'draft' => $revisedDraft,
            'review' => $review,
            'status' => 'pending_human_review',
            'human_feedback' => [
                'type' => 'revise',
                'reason' => $feedback,
                'at' => date('c'),
            ],
        ]);
    }
}