<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Session\Manager\SessionManager;
use App\Session\Manager\SessionParticipantManager;
use App\Session\Mapper\SessionMapper;
use App\Session\Model\Request\CreateSessionRequest;
use App\Voter\AuthenticationVoter;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/session', name: 'api_session_')]
class SessionController extends AbstractController
{
    public function __construct(
        private SessionManager $sessionManager,
        private SessionMapper $sessionMapper,
        private SessionParticipantManager $participantManager,
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
        private ProviderManager $providerManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Post(
        path: '/api/session',
        summary: 'Create a new session',
        description: 'Creates a new collaborative session for the authenticated user',
        requestBody: new OA\RequestBody(
            description: 'Session creation data',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Session description', example: 'Une session pour créer une playlist ensemble'),
                    new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 10, minimum: 1, maximum: 10),
                ]
            )
        ),
        tags: ['Session'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Session created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', description: 'Session ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
                        new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
                        new OA\Property(property: 'code', type: 'string', description: 'Session code', example: 'ABC12345'),
                        new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 10),
                        new OA\Property(property: 'host', type: 'object', description: 'Session host'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Creation date'),
                        new OA\Property(property: 'endedAt', type: 'string', format: 'date-time', nullable: true, description: 'End date'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request data'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function createSession(Request $request): JsonResponse
    {
        try {
            /** @var string $accessToken */
            $accessToken = $request->cookies->get('AUTH_TOKEN');
            /** @var User $user */
            $user = $this->providerManager->findByAccessToken($accessToken);

            $createSessionRequest = $this->serializer->deserialize(
                $request->getContent(),
                CreateSessionRequest::class,
                'json'
            );

            $session = $this->sessionManager->createSession($user, $createSessionRequest);
            $sessionModel = $this->sessionMapper->mapModel($session);

            return new JsonResponse($sessionModel->toArray(), Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logger->error('Error creating session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'Unable to create session'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/my-sessions', name: 'my_sessions', methods: ['GET'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Get(
        path: '/api/session/my-sessions',
        summary: 'Get my sessions',
        description: 'Get all active sessions for the authenticated user',
        tags: ['Session'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sessions retrieved successfully',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'string', description: 'Session ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
                            new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
                            new OA\Property(property: 'code', type: 'string', description: 'Session code', example: 'ABC12345'),
                            new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 10),
                            new OA\Property(property: 'host', type: 'object', description: 'Session host'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Creation date'),
                            new OA\Property(property: 'endedAt', type: 'string', format: 'date-time', nullable: true, description: 'End date'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function getMySessions(Request $request): JsonResponse
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $sessions = $this->sessionManager->getActiveSessionsByHost($user);
        $sessionModels = $this->sessionMapper->mapModels($sessions);

        return new JsonResponse(array_map(fn ($model) => $model->toArray(), $sessionModels), Response::HTTP_OK);
    }

    #[Route('/{code}/end', name: 'end', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Post(
        path: '/api/session/{code}/end',
        summary: 'End a session',
        description: 'End a session (only the host can end it)',
        tags: ['Session'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Session ended successfully'
            ),
            new OA\Response(
                response: 403,
                description: 'Only the host can end the session'
            ),
            new OA\Response(
                response: 404,
                description: 'Session not found'
            ),
        ]
    )]
    public function endSession(string $code, Request $request): JsonResponse
    {
        $user = null;
        try {
            /** @var string $accessToken */
            $accessToken = $request->cookies->get('AUTH_TOKEN');
            /** @var User $user */
            $user = $this->providerManager->findByAccessToken($accessToken);

            $session = $this->sessionManager->findSessionByCode($code);

            if (!$session) {
                return new JsonResponse(['error' => 'session.end.error_session_not_found'], Response::HTTP_NOT_FOUND);
            }

            $this->sessionManager->endSession($session, $user);

            return new JsonResponse(['message' => 'session.end.success']);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'session.end.error_forbidden'], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            $this->logger->error('Error ending session', [
                'error' => $e->getMessage(),
                'sessionCode' => $code,
                'userId' => $user?->getId()?->toRfc4122(),
            ]);

            return new JsonResponse(['error' => 'session.end.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{code}/join', name: 'join', methods: ['POST'])]
    #[OA\Post(
        path: '/api/session/{code}/join',
        summary: 'Join a session as guest',
        description: 'Join a session as a guest with a pseudo',
        requestBody: new OA\RequestBody(
            description: 'Join session data',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    'pseudo' => new OA\Property(
                        property: 'pseudo',
                        type: 'string',
                        example: 'MonPseudo'
                    ),
                ]
            )
        ),
        tags: ['Session'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Joined session successfully'
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request or pseudo already taken'
            ),
            new OA\Response(
                response: 404,
                description: 'Session not found'
            ),
        ]
    )]
    public function joinSession(string $code, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['pseudo']) || !is_string($data['pseudo']) || empty(trim((string) $data['pseudo']))) {
                return new JsonResponse(['error' => 'session.join.error.pseudo_required'], Response::HTTP_BAD_REQUEST);
            }

            $session = $this->sessionManager->findSessionByCode($code);
            if (!$session) {
                return new JsonResponse(['error' => 'session.join.error.session_not_found'], Response::HTTP_NOT_FOUND);
            }

            try {
                $participant = $this->participantManager->joinSession($session, trim((string) $data['pseudo']));
            } catch (InvalidArgumentException $e) {
                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'participant' => [
                    'id' => $participant->getId()?->toRfc4122(),
                    'pseudo' => $participant->getPseudo(),
                    'joinedAt' => $participant->getCreatedAt()?->format('c'),
                ],
                'success' => true,
                'message' => 'session.join.success',
            ]);
        } catch (Exception $e) {
            $this->logger->error('Error joining session', [
                'error' => $e->getMessage(),
                'sessionCode' => $code,
                'pseudo' => $data['pseudo'] ?? 'unknown',
            ]);

            return new JsonResponse(['error' => 'session.join.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{code}/participants', name: 'participants', methods: ['GET'])]
    #[OA\Get(
        path: '/api/session/{code}/participants',
        summary: 'Get session participants',
        description: 'Get all active participants in a session',
        tags: ['Session'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Participants retrieved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Session not found'
            ),
        ]
    )]
    public function getParticipants(string $code): JsonResponse
    {
        $session = $this->sessionManager->findSessionByCode($code);
        if (!$session) {
            return new JsonResponse(['error' => 'session.participants.error_session_not_found'], Response::HTTP_NOT_FOUND);
        }

        $participants = $this->participantManager->getActiveParticipants($session);

        $this->logger->info('Getting participants for session', [
            'sessionCode' => $code,
            'sessionId' => $session->getId()?->toRfc4122(),
            'participantCount' => count($participants),
        ]);

        $participantData = array_map(function ($participant) {
            return [
                'id' => $participant->getId()?->toRfc4122(),
                'pseudo' => $participant->getPseudo(),
                'joinedAt' => $participant->getCreatedAt()?->format('c'),
            ];
        }, $participants);

        return new JsonResponse([
            'participants' => $participantData,
            'count' => count($participantData),
        ]);
    }

    #[Route('/{code}/remove', name: 'remove', methods: ['POST'])]
    #[OA\Post(
        path: '/api/session/{code}/remove',
        summary: 'Remove a participant from a session',
        description: 'Remove a participant from a session',
        tags: ['Session'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'pseudo',
                            type: 'string',
                            example: 'JohnDoe'
                        ),
                        new OA\Property(
                            property: 'reason',
                            type: 'string',
                            example: 'leave'
                        ),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Participant removed successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Session or participant not found'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden'
            ),
        ]
    )]
    public function removeParticipant(string $code, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['pseudo']) || !is_string($data['pseudo']) || empty(trim((string) $data['pseudo']))) {
                return new JsonResponse(['error' => 'session.remove.errors.pseudo_required'], Response::HTTP_BAD_REQUEST);
            }

            $reason = isset($data['reason']) && is_string($data['reason']) ? $data['reason'] : 'leave';
            $session = $this->sessionManager->findSessionByCode($code);
            if (!$session) {
                return new JsonResponse(['error' => 'session.remove.errors.session_not_found'], Response::HTTP_NOT_FOUND);
            }

            $participant = $this->participantManager->getParticipantBySessionAndPseudo($session, trim((string) $data['pseudo']));
            if (!$participant) {
                return new JsonResponse(['error' => 'session.remove.errors.participant_not_found'], Response::HTTP_NOT_FOUND);
            }

            if ('kick' === $reason) {
                /** @var string $accessToken */
                $accessToken = $request->cookies->get('AUTH_TOKEN');
                /** @var User $user */
                $user = $this->providerManager->findByAccessToken($accessToken);

                if ($session->getHost()?->getId() !== $user->getId()) {
                    return new JsonResponse(['error' => 'session.remove.errors.only_host_can_kick'], Response::HTTP_FORBIDDEN);
                }
            }

            $this->participantManager->removeParticipant($participant, $reason);

            return new JsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->logger->error('Error removing participant', [
                'error' => $e->getMessage(),
                'sessionCode' => $code,
                'pseudo' => $data['pseudo'] ?? 'unknown',
            ]);

            return new JsonResponse(['error' => 'session.remove.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{code}', name: 'get_by_code', methods: ['GET'])]
    #[OA\Get(
        path: '/api/session/{code}',
        summary: 'Get session by code',
        description: 'Retrieve a session by its code',
        tags: ['Session'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Session found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', description: 'Session ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
                        new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
                        new OA\Property(property: 'code', type: 'string', description: 'Session code', example: 'ABC12345'),
                        new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 10),
                        new OA\Property(property: 'host', type: 'object', description: 'Session host'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Creation date'),
                        new OA\Property(property: 'endedAt', type: 'string', format: 'date-time', nullable: true, description: 'End date'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Session not found'
            ),
        ]
    )]
    public function getSessionByCode(string $code): JsonResponse
    {
        $session = $this->sessionManager->findSessionByCode($code);
        if (!$session instanceof Session) {
            return new JsonResponse(['error' => 'session.get_by_code.error_session_not_found'], Response::HTTP_NOT_FOUND);
        }

        $sessionModel = $this->sessionMapper->mapModel($session);

        return new JsonResponse($sessionModel->toArray());
    }
}
