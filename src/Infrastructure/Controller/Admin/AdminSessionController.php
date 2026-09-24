<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\DTO\Output\Session\SessionDataOutput;
use App\Domain\Registry\Session\SessionAudienceRegistry;
use App\Infrastructure\Factory\RefreshTokenCookieFactory;
use App\Infrastructure\HttpKernel\Attribute\MapDataInput;
use App\UseCase\Session\LoginUseCase;
use App\UseCase\Session\LogoutUseCase;
use App\UseCase\Session\RefreshSessionUseCase;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The back-office's sessions: the same use cases as the website's, for the admin audience, under
 * a path and a cookie of their own. Public despite the /api/admin prefix — see security.yaml.
 */
#[OA\Tag(name: 'Admin — sessions')]
final class AdminSessionController extends AbstractController
{
    public function __construct(private readonly RefreshTokenCookieFactory $refreshTokenCookieFactory)
    {
    }

    #[Route('/api/admin/auth/login', methods: Request::METHOD_POST)]
    #[OA\RequestBody(required: true, content: new Model(type: LoginDataInput::class))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Signed in. The refresh token is returned as an httpOnly cookie, not in the body.',
        content: new OA\JsonContent(ref: new Model(type: SessionDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Invalid credentials.')]
    #[OA\Response(response: Response::HTTP_FORBIDDEN, description: 'The account is deactivated, or is not an administrator (wrong_audience).')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid payload.')]
    public function login(#[MapDataInput] LoginDataInput $input, LoginUseCase $useCase): JsonResponse
    {
        return $this->respondWithSession($useCase->execute($input, SessionAudienceRegistry::ADMIN));
    }

    #[Route('/api/admin/auth/refresh', methods: Request::METHOD_POST)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Session renewed. The previous refresh token is spent and replaced.',
        content: new OA\JsonContent(ref: new Model(type: SessionDataOutput::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Missing, unknown, expired or already spent refresh token.')]
    #[OA\Response(response: Response::HTTP_FORBIDDEN, description: 'The account is deactivated, or is not an administrator (wrong_audience).')]
    public function refreshSession(Request $request, RefreshSessionUseCase $useCase): JsonResponse
    {
        return $this->respondWithSession($useCase->execute($this->readRefreshToken($request), SessionAudienceRegistry::ADMIN));
    }

    #[Route('/api/admin/auth/logout', methods: Request::METHOD_POST)]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'Signed out of every device of the account. Idempotent. The website session, if any, is untouched.',
    )]
    public function logout(Request $request, LogoutUseCase $useCase): JsonResponse
    {
        $useCase->execute($this->readRefreshToken($request));

        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(
            $this->refreshTokenCookieFactory->buildCleared(SessionAudienceRegistry::ADMIN),
        );

        return $response;
    }

    private function respondWithSession(SessionDataOutput $output): JsonResponse
    {
        $response = new JsonResponse($output);
        $response->headers->setCookie(
            $this->refreshTokenCookieFactory->buildOne(
                SessionAudienceRegistry::ADMIN,
                $output->refreshToken,
                $output->refreshTokenExpiresAt,
            ),
        );

        return $response;
    }

    /**
     * An absent cookie is passed on as an empty token: no session matches it, and the use case
     * answers exactly as it does for an unknown one.
     */
    private function readRefreshToken(Request $request): string
    {
        $cookieName = $this->refreshTokenCookieFactory->getName(SessionAudienceRegistry::ADMIN);

        return (string) $request->cookies->get($cookieName, '');
    }
}
