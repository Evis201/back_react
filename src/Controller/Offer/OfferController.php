<?php

namespace App\Controller\Offer;

use App\Controller\AbstractApiController;
use App\DTO\Offer\OfferCreateDTO;
use App\Entity\User;
use App\Repository\OfferRepository;
use App\Service\Offer\OfferService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/offers', name: 'offer_')]
class OfferController extends AbstractApiController
{
    public function __construct(
        private readonly OfferService $offerService,
        private readonly OfferRepository $offerRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $result = $this->offerService->list($request->query->all());

        return $this->paginated($result['items'], $result['total'], $result['page'], $result['limit']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $data = $this->offerService->getDetail($id);
        if ($data === null) {
            return $this->error('Offer not found.', 404);
        }

        return $this->success($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_COMPANY')]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $company = $user->getCompanyProfile();

        if ($company === null) {
            return $this->error('Company profile not found.', 422);
        }

        $dto = $this->buildDTO($request);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        $offer = $this->offerService->create($company, $dto);

        return $this->success($this->offerService->normalizeDetail($offer), 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user  = $this->getUser();
        $offer = $this->offerRepository->find($id);

        if ($offer === null) {
            return $this->error('Offer not found.', 404);
        }

        if ($offer->getCompany()->getUser()->getId() !== $user->getId()) {
            return $this->error('Access denied.', 403);
        }

        $dto = $this->buildDTO($request);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        $offer = $this->offerService->update($offer, $dto);

        return $this->success($this->offerService->normalizeDetail($offer));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user  = $this->getUser();
        $offer = $this->offerRepository->find($id);

        if ($offer === null) {
            return $this->error('Offer not found.', 404);
        }

        if ($offer->getCompany()->getUser()->getId() !== $user->getId()) {
            return $this->error('Access denied.', 403);
        }

        $this->offerService->delete($offer);

        return $this->success(null, 204);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildDTO(Request $request): OfferCreateDTO
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new OfferCreateDTO();
        $dto->title       = $body['title']       ?? '';
        $dto->description = $body['description'] ?? '';
        $dto->type        = $body['type']        ?? '';
        $dto->status      = $body['status']      ?? 'published';
        $dto->location    = $body['location']    ?? null;
        $dto->isRemote    = (bool) ($body['isRemote'] ?? false);
        $dto->salaryMin   = isset($body['salaryMin']) ? (float) $body['salaryMin'] : null;
        $dto->salaryMax   = isset($body['salaryMax']) ? (float) $body['salaryMax'] : null;
        $dto->startsAt    = $body['startsAt']    ?? null;
        $dto->expiresAt   = $body['expiresAt']   ?? null;
        $dto->skillIds    = $body['skillIds']    ?? [];

        return $dto;
    }
}
