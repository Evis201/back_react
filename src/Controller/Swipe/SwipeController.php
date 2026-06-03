<?php

namespace App\Controller\Swipe;

use App\Controller\AbstractApiController;
use App\Entity\User;
use App\Service\Swipe\SwipeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/swipe', name: 'swipe_')]
#[IsGranted('ROLE_COMPANY')]
class SwipeController extends AbstractApiController
{
    public function __construct(
        private readonly SwipeService $swipeService,
    ) {
    }

    #[Route('/next', name: 'next', methods: ['GET'])]
    public function next(Request $request): JsonResponse
    {
        $company = $this->getCompany();
        if ($company === null) {
            return $this->error('Company profile not found.', 404);
        }

        $limit   = min(20, max(1, (int) ($request->query->get('limit', 10))));
        $students = $this->swipeService->getNextBatch($company, $limit);

        return $this->success($students);
    }

    #[Route('', name: 'swipe', methods: ['POST'])]
    public function swipe(Request $request): JsonResponse
    {
        $company = $this->getCompany();
        if ($company === null) {
            return $this->error('Company profile not found.', 404);
        }

        $body      = json_decode($request->getContent(), true) ?? [];
        $studentId = isset($body['studentId']) ? (int) $body['studentId'] : null;
        $action    = $body['action'] ?? null;

        if ($studentId === null || $studentId <= 0) {
            return $this->error('studentId is required.', 422);
        }

        if (!in_array($action, ['like', 'pass'], true)) {
            return $this->error('action must be "like" or "pass".', 422);
        }

        try {
            $result = $this->swipeService->swipe($company, $studentId, $action);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success($result);
    }

    #[Route('/liked', name: 'liked', methods: ['GET'])]
    public function liked(): JsonResponse
    {
        $company = $this->getCompany();
        if ($company === null) {
            return $this->error('Company profile not found.', 404);
        }

        return $this->success($this->swipeService->getLiked($company));
    }

    #[Route('/{studentId}', name: 'unlike', methods: ['DELETE'], requirements: ['studentId' => '\d+'])]
    public function unlike(int $studentId): JsonResponse
    {
        $company = $this->getCompany();
        if ($company === null) {
            return $this->error('Company profile not found.', 404);
        }

        try {
            $this->swipeService->unlike($company, $studentId);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success(null, 204);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function getCompany(): ?\App\Entity\Company
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user->getCompanyProfile();
    }
}
