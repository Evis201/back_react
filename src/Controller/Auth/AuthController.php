<?php

namespace App\Controller\Auth;

use App\Controller\AbstractApiController;
use App\DTO\Auth\RegisterDTO;
use App\Service\Auth\RegistrationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'auth_')]
class AuthController extends AbstractApiController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        RegistrationService $registrationService,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new RegisterDTO();
        $dto->email       = $body['email'] ?? '';
        $dto->password    = $body['password'] ?? '';
        $dto->role        = $body['role'] ?? '';
        $dto->firstName   = $body['firstName'] ?? null;
        $dto->lastName    = $body['lastName'] ?? null;
        $dto->companyName = $body['companyName'] ?? null;

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            return $this->validationError($violations);
        }

        try {
            $user = $registrationService->register($dto);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], 201);
    }
}
