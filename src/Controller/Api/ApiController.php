<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ApiController extends AbstractController
{
    protected function createApiForm(string $class): FormInterface
    {
        return $this->createForm(
            $class, null, [
                'allow_extra_fields' => true,
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseJson(Request $request): array
    {
        if ($request->getContent() === '') {
            return [];
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }

        return $data;
    }

    protected function renderFormErrors(FormInterface $form): JsonResponse
    {
        $errors = [];
        foreach ($form as $child) {
            /** @var FormError $error */
            foreach ($child->getErrors(true) as $error) {
                $errors[$child->getName()][] = $error->getMessage();
            }
        }

        return $this->errors($errors);
    }

    /**
     * @param callable $listFunction
     *
     * @return array<string,mixed>
     */
    protected function paginate($listFunction, int $total, int $perPage, int $page, string $baseUrl): array
    {
        $pages = (int) ceil($total / $perPage);
        if ($pages === 0) {
            $pages = 1;
        }
        $page = $page <= 0 ? $page = 1 : $page;
        $page = $page > $pages ? $pages : $page;
        $offset = ($perPage * $page) - $perPage;

        return [
            'data' => $listFunction($perPage, $offset < 0 ? 0 : $offset),
            'total' => $total,
            'links' => [
                'first' => $this->generatePaginationUrl($baseUrl, 1),
                'prev' => $page <= 1 ? null : $this->generatePaginationUrl($baseUrl, $page - 1),
                'next' => $page === $pages ? null : $this->generatePaginationUrl($baseUrl, $page + 1),
                'last' => $this->generatePaginationUrl($baseUrl, $pages),
            ],
        ];
    }

    /**
     * @param mixed $data
     */
    protected function created($data = []): JsonResponse
    {
        return $this->json($data, Response::HTTP_CREATED);
    }

    /**
     * @param array<string,mixed> $errors
     */
    protected function errors(array $errors): JsonResponse
    {
        return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
    }

    private function generatePaginationUrl(string $baseUrl, int $page): string
    {
        return "$baseUrl?page=$page";
    }
}
