<?php

declare(strict_types=1);

namespace Buddy\Repman\Controller\Organization;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Form\Type\Organization\AddPackageType;
use Buddy\Repman\Form\Type\Organization\EditPackageType;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\Package\Update;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Query\User\HooksQuery;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Service\IntegrationRegister;
use Buddy\Repman\Service\User\UserOAuthTokenProvider;
use Http\Client\Exception as HttpException;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class HookController extends AbstractController
{
    private HooksQuery $hooksQuery;

    public function __construct(
        HooksQuery $hooksQuery
    ) {
        $this->hooksQuery = $hooksQuery;
    }

    /**
     * @IsGranted("ROLE_ORGANIZATION_MEMBER", subject="organization")
     * @Route("/organization/{organization}/hooks", name="organization_hooks", methods={"GET"}, requirements={"organization"="%organization_pattern%"})
     */
    public function packageNew(Organization $organization): Response
    {
        return $this->render('organization/hooks/overview.html.twig', [
            'organization' => $organization,
            'hooks' => $this->hooksQuery->findAll($organization->id())
        ]);
    }
}
