<?php

namespace App\Controller\Student;

use App\Controller\AbstractApiController;
use App\DTO\Student\StudentCreateDTO;
use App\DTO\Student\StudentUpdateDTO;
use App\Entity\Student;
use App\Entity\User;
use App\Repository\StudentRepository;
use App\Service\Student\StudentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/students', name: 'student_')]
class StudentController extends AbstractApiController
{
    public function __construct(
        private readonly StudentService $studentService,
        private readonly StudentRepository $studentRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $result = $this->studentService->list($request->query->all());

        return $this->paginated($result['items'], $result['total'], $result['page'], $result['limit']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $data = $this->studentService->getDetail($id);
        if ($data === null) {
            return $this->error('Student not found.', 404);
        }

        return $this->success($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = $this->buildCreateDTO($body);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        try {
            $student = $this->studentService->create($user, $dto);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 409);
        }

        return $this->success($this->studentService->normalizeDetail($student), 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_STUDENT')]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $student = $this->studentRepository->find($id);

        if ($student === null) {
            return $this->error('Student not found.', 404);
        }

        if ($student->getUser()->getId() !== $user->getId()) {
            return $this->error('Access denied.', 403);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = $this->buildUpdateDTO($body);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        $student = $this->studentService->update($student, $dto);

        return $this->success($this->studentService->normalizeDetail($student));
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildCreateDTO(array $body): StudentCreateDTO
    {
        $dto = new StudentCreateDTO();
        $dto->firstName    = $body['firstName']    ?? '';
        $dto->lastName     = $body['lastName']     ?? '';
        $dto->bio          = $body['bio']          ?? null;
        $dto->avatarUrl    = $body['avatarUrl']    ?? null;
        $dto->githubUrl    = $body['githubUrl']    ?? null;
        $dto->linkedinUrl  = $body['linkedinUrl']  ?? null;
        $dto->promotionYear = isset($body['promotionYear']) ? (int) $body['promotionYear'] : null;
        $dto->school       = $body['school']       ?? null;
        $dto->domain       = $body['domain']       ?? null;
        $dto->studyYear    = isset($body['studyYear']) ? (int) $body['studyYear'] : null;
        $dto->cvUrl        = $body['cvUrl']        ?? null;
        $dto->skills       = $body['skills']       ?? [];
        $dto->projects     = $body['projects']     ?? [];

        return $dto;
    }

    private function buildUpdateDTO(array $body): StudentUpdateDTO
    {
        $dto = new StudentUpdateDTO();

        if (array_key_exists('firstName', $body)) {
            $dto->firstName = $body['firstName'];
        }
        if (array_key_exists('lastName', $body)) {
            $dto->lastName = $body['lastName'];
        }
        if (array_key_exists('bio', $body)) {
            $dto->bio = $body['bio'];
        }
        if (array_key_exists('avatarUrl', $body)) {
            $dto->avatarUrl = $body['avatarUrl'];
        }
        if (array_key_exists('githubUrl', $body)) {
            $dto->githubUrl = $body['githubUrl'];
        }
        if (array_key_exists('linkedinUrl', $body)) {
            $dto->linkedinUrl = $body['linkedinUrl'];
        }
        if (array_key_exists('promotionYear', $body)) {
            $dto->promotionYear = $body['promotionYear'] !== null ? (int) $body['promotionYear'] : null;
        }
        if (array_key_exists('school', $body)) {
            $dto->school = $body['school'];
        }
        if (array_key_exists('domain', $body)) {
            $dto->domain = $body['domain'];
        }
        if (array_key_exists('studyYear', $body)) {
            $dto->studyYear = $body['studyYear'] !== null ? (int) $body['studyYear'] : null;
        }
        if (array_key_exists('cvUrl', $body)) {
            $dto->cvUrl = $body['cvUrl'];
        }
        if (array_key_exists('isVisible', $body)) {
            $dto->isVisible = (bool) $body['isVisible'];
        }
        if (array_key_exists('skills', $body)) {
            $dto->skills = $body['skills'];
        }
        if (array_key_exists('projects', $body)) {
            $dto->projects = $body['projects'];
        }

        return $dto;
    }
}
