<?php

namespace App\Service\Offer;

use App\DTO\Offer\OfferCreateDTO;
use App\Entity\Company;
use App\Entity\Enum\OfferStatus;
use App\Entity\Enum\OfferType;
use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;

class OfferService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfferRepository $offerRepository,
        private readonly SkillRepository $skillRepository,
    ) {
    }

    public function list(array $queryParams): array
    {
        $page  = max(1, (int) ($queryParams['page']  ?? 1));
        $limit = min(50, max(1, (int) ($queryParams['limit'] ?? 20)));

        $filters = array_filter([
            'type'     => $queryParams['type']     ?? null,
            'skillId'  => $queryParams['skillId']  ?? null,
            'isRemote' => $queryParams['isRemote']  ?? null,
            'search'   => $queryParams['search']   ?? null,
        ]);

        $offers = $this->offerRepository->findPublishedWithFilters($filters, $page, $limit);

        return [
            'items' => array_map($this->normalizeListItem(...), $offers),
            'total' => $this->offerRepository->countPublishedWithFilters($filters),
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function getDetail(int $id): ?array
    {
        $offer = $this->offerRepository->findOneWithDetails($id);

        return $offer !== null ? $this->normalizeDetail($offer) : null;
    }

    public function create(Company $company, OfferCreateDTO $dto): Offer
    {
        $offer = new Offer();
        $offer->setCompany($company);
        $this->applyDtoToOffer($offer, $dto);

        $this->em->persist($offer);
        $this->em->flush();

        return $offer;
    }

    public function update(Offer $offer, OfferCreateDTO $dto): Offer
    {
        $this->applyDtoToOffer($offer, $dto);
        $this->em->flush();

        return $offer;
    }

    public function delete(Offer $offer): void
    {
        $this->em->remove($offer);
        $this->em->flush();
    }

    // ── Normalizers ───────────────────────────────────────────────────────────

    public function normalizeListItem(Offer $offer): array
    {
        return [
            'id'          => $offer->getId(),
            'title'       => $offer->getTitle(),
            'type'        => $offer->getType()->value,
            'status'      => $offer->getStatus()->value,
            'location'    => $offer->getLocation(),
            'isRemote'    => $offer->isRemote(),
            'salaryMin'   => $offer->getSalaryMin(),
            'salaryMax'   => $offer->getSalaryMax(),
            'startsAt'    => $offer->getStartsAt()?->format('Y-m-d'),
            'expiresAt'   => $offer->getExpiresAt()?->format('Y-m-d'),
            'createdAt'   => $offer->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'company' => [
                'id'   => $offer->getCompany()->getId(),
                'name' => $offer->getCompany()->getName(),
            ],
            'requiredSkills' => $offer->getRequiredSkills()
                ->map(fn ($s) => ['id' => $s->getId(), 'name' => $s->getName(), 'category' => $s->getCategory()])
                ->toArray(),
        ];
    }

    public function normalizeDetail(Offer $offer): array
    {
        return array_merge($this->normalizeListItem($offer), [
            'description' => $offer->getDescription(),
            'updatedAt'   => $offer->getUpdatedAt()?->format('Y-m-d\TH:i:s\Z'),
            'company' => [
                'id'      => $offer->getCompany()->getId(),
                'name'    => $offer->getCompany()->getName(),
                'logoUrl' => $offer->getCompany()->getLogoUrl(),
                'website' => $offer->getCompany()->getWebsite(),
            ],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function applyDtoToOffer(Offer $offer, OfferCreateDTO $dto): void
    {
        $offer->setTitle($dto->title);
        $offer->setDescription($dto->description);
        $offer->setType(OfferType::from($dto->type));
        $offer->setStatus(OfferStatus::from($dto->status));
        $offer->setLocation($dto->location);
        $offer->setIsRemote($dto->isRemote);
        $offer->setSalaryMin($dto->salaryMin !== null ? (string) $dto->salaryMin : null);
        $offer->setSalaryMax($dto->salaryMax !== null ? (string) $dto->salaryMax : null);

        if ($dto->startsAt !== null) {
            $offer->setStartsAt(new \DateTimeImmutable($dto->startsAt));
        }
        if ($dto->expiresAt !== null) {
            $offer->setExpiresAt(new \DateTimeImmutable($dto->expiresAt));
        }

        // Replace required skills
        foreach ($offer->getRequiredSkills()->toArray() as $skill) {
            $offer->removeRequiredSkill($skill);
        }

        if (!empty($dto->skillIds)) {
            $skills = $this->skillRepository->findBy(['id' => array_unique($dto->skillIds)]);
            foreach ($skills as $skill) {
                $offer->addRequiredSkill($skill);
            }
        }
    }
}
