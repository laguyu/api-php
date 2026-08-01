<?php

namespace App\Application\Controllers;

use App\Application\DTO\LoginDTO;
use App\Application\DTO\RefreshTokenDTO;
use App\Application\Services\AuthenticationService;
use App\Application\Services\LoginRateLimiter;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use Exception;
use InvalidArgumentException;

class AuthController
{
    public function __construct(
        private AuthenticationService $authenticationService,
        private LoginRateLimiter $loginRateLimiter
    )
    {
    }

    public function login(Request $request): Response
    {
        $clientIp = $this->resolveClientIp();

        if ($this->loginRateLimiter->tooManyAttempts($clientIp)) {
            return Response::json([
                'error' => 'Too Many Requests',
                'message' => 'Too many login attempts. Try again later.'
            ], 429);
        }

        try {
            $dto = LoginDTO::fromArray($request->getBody());
            $tokens = $this->authenticationService->login($dto->email, $dto->password, $clientIp);
            $this->loginRateLimiter->clear($clientIp);

            return Response::json($tokens, 200);
        } catch (InvalidArgumentException $e) {
            $this->loginRateLimiter->hit($clientIp);
            return Response::json(['error' => 'Bad Request', 'message' => $e->getMessage()], 400);
        } catch (HttpException $e) {
            $this->loginRateLimiter->hit($clientIp);
            return Response::json(['error' => 'Unauthorized', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    public function refresh(Request $request): Response
    {
        try {
            $dto = RefreshTokenDTO::fromArray($request->getBody());
            $tokens = $this->authenticationService->refresh($dto->refreshToken, $this->resolveClientIp());

            return Response::json($tokens, 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(['error' => 'Bad Request', 'message' => $e->getMessage()], 400);
        } catch (HttpException $e) {
            return Response::json(['error' => 'Unauthorized', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request): Response
    {
        try {
            $dto = RefreshTokenDTO::fromArray($request->getBody());
            $this->authenticationService->logout($dto->refreshToken);

            return Response::json(['message' => 'Session closed successfully.'], 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(['error' => 'Bad Request', 'message' => $e->getMessage()], 400);
        } catch (HttpException $e) {
            return Response::json(['error' => 'Unauthorized', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    private function resolveClientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
