<?php

namespace App\Controller\Admin;

use App\Controller\AbstractApiController;
use App\Entity\Company;
use App\Entity\Enum\OfferStatus;
use App\Entity\Offer;
use App\Entity\Student;
use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\OfferRepository;
use App\Repository\StudentRepository;
use App\Repository\SwipeActionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'admin_')]
class AdminController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly StudentRepository $studentRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly OfferRepository $offerRepository,
        private readonly SwipeActionRepository $swipeActionRepository,
    ) {
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $conn = $this->em->getConnection();

        $totalUsers     = (int) $conn->fetchOne('SELECT COUNT(*) FROM app_user');
        $totalStudents  = (int) $conn->fetchOne('SELECT COUNT(*) FROM student');
        $totalCompanies = (int) $conn->fetchOne('SELECT COUNT(*) FROM company');
        $staffCount     = (int) $conn->fetchOne("SELECT COUNT(*) FROM app_user WHERE roles LIKE '%ROLE_STAFF%'");
        $visibleStudents = (int) $conn->fetchOne('SELECT COUNT(*) FROM student WHERE is_visible = 1');
        $totalOffers    = (int) $conn->fetchOne('SELECT COUNT(*) FROM offer');
        $draftOffers    = (int) $conn->fetchOne("SELECT COUNT(*) FROM offer WHERE status = 'draft'");
        $publishedOffers = (int) $conn->fetchOne("SELECT COUNT(*) FROM offer WHERE status = 'published'");
        $closedOffers   = (int) $conn->fetchOne("SELECT COUNT(*) FROM offer WHERE status = 'closed'");
        $totalSwipes    = (int) $conn->fetchOne('SELECT COUNT(*) FROM swipe_action');
        $likesCount     = (int) $conn->fetchOne("SELECT COUNT(*) FROM swipe_action WHERE action = 'like'");

        return $this->success([
            'users' => [
                'total'     => $totalUsers,
                'students'  => $totalStudents,
                'companies' => $totalCompanies,
                'staff'     => $staffCount,
            ],
            'students' => [
                'total'   => $totalStudents,
                'visible' => $visibleStudents,
                'hidden'  => $totalStudents - $visibleStudents,
            ],
            'companies' => ['total' => $totalCompanies],
            'offers' => [
                'total'     => $totalOffers,
                'draft'     => $draftOffers,
                'published' => $publishedOffers,
                'closed'    => $closedOffers,
            ],
            'swipes' => [
                'total'  => $totalSwipes,
                'likes'  => $likesCount,
                'passes' => $totalSwipes - $likesCount,
            ],
        ]);
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    #[Route('/users', name: 'users_index', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(50, max(1, (int) $request->query->get('limit', 25)));
        $search = trim((string) $request->query->get('search', ''));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder()
            ->select('u', 'sp', 'cp')
            ->from(User::class, 'u')
            ->leftJoin('u.studentProfile', 'sp')
            ->leftJoin('u.companyProfile', 'cp')
            ->orderBy('u.createdAt', 'DESC');

        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u');

        if ($search !== '') {
            $qb->andWhere('u.email LIKE :search')->setParameter('search', "%{$search}%");
            $countQb->andWhere('u.email LIKE :search')->setParameter('search', "%{$search}%");
        }

        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        $users = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return $this->paginated(
            array_map(fn(User $u) => $this->normalizeUser($u), $users),
            $total, $page, $limit
        );
    }

    #[Route('/users/{id}/role', name: 'users_role', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function patchUserRole(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if ($user === null) {
            return $this->error('User not found.', 404);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $role = $body['role'] ?? '';

        $allowed = ['ROLE_STUDENT', 'ROLE_COMPANY'];
        if (!in_array($role, $allowed, true)) {
            return $this->error('Invalid role. Allowed: ROLE_STUDENT, ROLE_COMPANY.', 422);
        }

        $user->setRoles([$role]);
        $this->em->flush();

        return $this->success($this->normalizeUser($user));
    }

    #[Route('/users/{id}/verify', name: 'users_verify', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function patchUserVerify(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if ($user === null) {
            return $this->error('User not found.', 404);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $user->setIsVerified((bool) ($body['isVerified'] ?? false));
        $this->em->flush();

        return $this->success($this->normalizeUser($user));
    }

    #[Route('/users/{id}', name: 'users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if ($user === null) {
            return $this->error('User not found.', 404);
        }

        /** @var User $me */
        $me = $this->getUser();
        if ($me->getId() === $id) {
            return $this->error('Cannot delete your own account.', 403);
        }

        if (in_array('ROLE_STAFF', $user->getRoles(), true)) {
            return $this->error('Cannot delete staff accounts.', 403);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->success(null, 204);
    }

    // ── Students ──────────────────────────────────────────────────────────────

    #[Route('/students', name: 'students_index', methods: ['GET'])]
    public function listStudents(Request $request): JsonResponse
    {
        $page    = max(1, (int) $request->query->get('page', 1));
        $limit   = min(50, max(1, (int) $request->query->get('limit', 25)));
        $filters = ['search' => trim((string) $request->query->get('search', ''))];

        $total    = $this->studentRepository->countAllWithFilters($filters);
        $students = $this->studentRepository->findAllWithFilters($filters, $page, $limit);

        return $this->paginated(
            array_map(fn(Student $s) => $this->normalizeStudent($s), $students),
            $total, $page, $limit
        );
    }

    #[Route('/students/{id}/visibility', name: 'students_visibility', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function patchStudentVisibility(int $id, Request $request): JsonResponse
    {
        $student = $this->studentRepository->find($id);
        if ($student === null) {
            return $this->error('Student not found.', 404);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $student->setIsVisible((bool) ($body['isVisible'] ?? true));
        $this->em->flush();

        return $this->success(['id' => $student->getId(), 'isVisible' => $student->isVisible()]);
    }

    #[Route('/students/{id}/score', name: 'students_score', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function patchStudentScore(int $id, Request $request): JsonResponse
    {
        $student = $this->studentRepository->find($id);
        if ($student === null) {
            return $this->error('Student not found.', 404);
        }

        $body  = json_decode($request->getContent(), true) ?? [];
        $score = (int) ($body['score'] ?? 0);
        $student->setScore(max(0, $score));
        $this->em->flush();

        return $this->success(['id' => $student->getId(), 'score' => $student->getScore()]);
    }

    #[Route('/students/{id}', name: 'students_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteStudent(int $id): JsonResponse
    {
        $student = $this->studentRepository->find($id);
        if ($student === null) {
            return $this->error('Student not found.', 404);
        }

        $this->em->remove($student);
        $this->em->flush();

        return $this->success(null, 204);
    }

    // ── Companies ─────────────────────────────────────────────────────────────

    #[Route('/companies', name: 'companies_index', methods: ['GET'])]
    public function listCompanies(Request $request): JsonResponse
    {
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(50, max(1, (int) $request->query->get('limit', 25)));
        $search = trim((string) $request->query->get('search', ''));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder()
            ->select('c', 'u', 'COUNT(o.id) AS offersCount')
            ->from(Company::class, 'c')
            ->join('c.user', 'u')
            ->leftJoin('c.offers', 'o')
            ->groupBy('c.id')
            ->orderBy('c.createdAt', 'DESC');

        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Company::class, 'c')
            ->join('c.user', 'u');

        if ($search !== '') {
            $qb->andWhere('c.name LIKE :search OR u.email LIKE :search')->setParameter('search', "%{$search}%");
            $countQb->andWhere('c.name LIKE :search OR u.email LIKE :search')->setParameter('search', "%{$search}%");
        }

        $total   = (int) $countQb->getQuery()->getSingleScalarResult();
        $results = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        $items = array_map(
            fn($row) => $this->normalizeCompany($row[0], (int) $row['offersCount']),
            $results
        );

        return $this->paginated($items, $total, $page, $limit);
    }

    #[Route('/companies/{id}', name: 'companies_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteCompany(int $id): JsonResponse
    {
        $company = $this->companyRepository->find($id);
        if ($company === null) {
            return $this->error('Company not found.', 404);
        }

        $this->em->remove($company);
        $this->em->flush();

        return $this->success(null, 204);
    }

    // ── Offers ────────────────────────────────────────────────────────────────

    #[Route('/offers', name: 'offers_index', methods: ['GET'])]
    public function listOffers(Request $request): JsonResponse
    {
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(50, max(1, (int) $request->query->get('limit', 25)));
        $search = trim((string) $request->query->get('search', ''));
        $status = trim((string) $request->query->get('status', ''));
        $offset = ($page - 1) * $limit;

        $qb = $this->em->createQueryBuilder()
            ->select('o', 'c')
            ->from(Offer::class, 'o')
            ->join('o.company', 'c')
            ->orderBy('o.createdAt', 'DESC');

        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Offer::class, 'o')
            ->join('o.company', 'c');

        if ($search !== '') {
            $qb->andWhere('o.title LIKE :search OR c.name LIKE :search')->setParameter('search', "%{$search}%");
            $countQb->andWhere('o.title LIKE :search OR c.name LIKE :search')->setParameter('search', "%{$search}%");
        }

        $statusEnum = OfferStatus::tryFrom($status);
        if ($statusEnum !== null) {
            $qb->andWhere('o.status = :status')->setParameter('status', $statusEnum);
            $countQb->andWhere('o.status = :status')->setParameter('status', $statusEnum);
        }

        $total  = (int) $countQb->getQuery()->getSingleScalarResult();
        $offers = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return $this->paginated(
            array_map(fn(Offer $o) => $this->normalizeOffer($o), $offers),
            $total, $page, $limit
        );
    }

    #[Route('/offers/{id}/status', name: 'offers_status', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function patchOfferStatus(int $id, Request $request): JsonResponse
    {
        $offer = $this->offerRepository->find($id);
        if ($offer === null) {
            return $this->error('Offer not found.', 404);
        }

        $body   = json_decode($request->getContent(), true) ?? [];
        $status = OfferStatus::tryFrom((string) ($body['status'] ?? ''));
        if ($status === null) {
            return $this->error('Invalid status. Allowed: draft, published, closed.', 422);
        }

        $offer->setStatus($status);
        $this->em->flush();

        return $this->success(['id' => $offer->getId(), 'status' => $offer->getStatus()->value]);
    }

    #[Route('/offers/{id}', name: 'offers_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteOffer(int $id): JsonResponse
    {
        $offer = $this->offerRepository->find($id);
        if ($offer === null) {
            return $this->error('Offer not found.', 404);
        }

        $this->em->remove($offer);
        $this->em->flush();

        return $this->success(null, 204);
    }

    // ── Normalizers ───────────────────────────────────────────────────────────

    private function normalizeUser(User $u): array
    {
        $profileType = 'none';
        if ($u->getStudentProfile() !== null) {
            $profileType = 'student';
        } elseif ($u->getCompanyProfile() !== null) {
            $profileType = 'company';
        } elseif (in_array('ROLE_STAFF', $u->getRoles(), true)) {
            $profileType = 'staff';
        }

        return [
            'id'          => $u->getId(),
            'email'       => $u->getEmail(),
            'roles'       => $u->getRoles(),
            'isVerified'  => $u->isVerified(),
            'profileType' => $profileType,
            'createdAt'   => $u->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function normalizeStudent(Student $s): array
    {
        return [
            'id'            => $s->getId(),
            'userId'        => $s->getUser()->getId(),
            'email'         => $s->getUser()->getEmail(),
            'firstName'     => $s->getFirstName(),
            'lastName'      => $s->getLastName(),
            'school'        => $s->getSchool(),
            'domain'        => $s->getDomain(),
            'promotionYear' => $s->getPromotionYear(),
            'studyYear'     => $s->getStudyYear(),
            'score'         => $s->getScore(),
            'isVisible'     => $s->isVisible(),
            'createdAt'     => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function normalizeCompany(Company $c, int $offersCount): array
    {
        return [
            'id'          => $c->getId(),
            'userId'      => $c->getUser()->getId(),
            'email'       => $c->getUser()->getEmail(),
            'name'        => $c->getName(),
            'website'     => $c->getWebsite(),
            'logoUrl'     => $c->getLogoUrl(),
            'offersCount' => $offersCount,
            'createdAt'   => $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function normalizeOffer(Offer $o): array
    {
        return [
            'id'      => $o->getId(),
            'title'   => $o->getTitle(),
            'type'    => $o->getType()->value,
            'status'  => $o->getStatus()->value,
            'company' => ['id' => $o->getCompany()->getId(), 'name' => $o->getCompany()->getName()],
            'location' => $o->getLocation(),
            'isRemote' => $o->isRemote(),
            'createdAt' => $o->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
