<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class AbstractApiController extends AbstractController
{
    protected function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data], $status);
    }

    protected function paginated(array $items, int $total, int $page, int $limit): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total'  => $total,
                'page'   => $page,
                'limit'  => $limit,
                'pages'  => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    protected function error(string $message, int $status = 400, array $details = []): JsonResponse
    {
        $body = ['success' => false, 'error' => $message];
        if (!empty($details)) {
            $body['details'] = $details;
        }

        return new JsonResponse($body, $status);
    }

    protected function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field'   => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $this->error('Validation failed.', 422, $errors);
    }
}
