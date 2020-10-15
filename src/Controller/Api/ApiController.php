<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Api;

use Buddy\Repman\Query\Api\Model\Error;
use Buddy\Repman\Query\Api\Model\Errors;
use Buddy\Repman\Query\Api\Model\Links;
use Buddy\Repman\Security\Model\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $th) {
            throw new BadRequestHttpException();
        }

        return $data;
    }

    protected function getErrors(FormInterface $form): Errors
    {
        $errors = [];
        foreach ($form as $child) {
            /** @var FormError $error */
            foreach ($child->getErrors(true) as $error) {
                $errors[] = new Error($child->getName(), $error->getMessage());
            }
        }

        return new Errors($errors);
    }

    /**
     * @return array<mixed>
     */
    protected function paginate(callable $listFunction, int $total, int $perPage, int $page, string $baseUrl): array
    {
        $pages = (int) ceil($total / $perPage);
        if ($pages === 0) {
            $pages = 1;
        }
        $page = $page <= 0 ? $page = 1 : $page;
        $page = $page > $pages ? $pages : $page;
        $offset = ($perPage * $page) - $perPage;

        return [
            $listFunction($perPage, $offset < 0 ? 0 : $offset),
            $total,
            new Links($baseUrl, $page, $pages),
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
     * @param mixed $data
     */
    protected function badRequest($data = []): JsonResponse
    {
        return $this->json($data, Response::HTTP_BAD_REQUEST);
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = parent::getUser();

        return $user;
    }
}
