<?php

namespace App\Controller;

use App\Mail\ContactModel;
use Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class MailerController extends AbstractController
{
    private const string NO_REPLY_EMAIL = 'noreply@mix-link.fr';
    private const string CONTACT_EMAIL = 'contact@mix-link.fr';

    public function __construct(private LoggerInterface $logger)
    {
    }

    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    #[OA\Post(
        path: '/api/contact',
        summary: 'Send an email from the contact form',
        description: 'Sends an email using JSON data from the request body',
        tags: ['Contact'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Contact form data',
            content: new OA\JsonContent(
                required: ['name', 'email', 'subject', 'message'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                    new OA\Property(property: 'subject', type: 'string', example: 'Product inquiry'),
                    new OA\Property(property: 'message', type: 'string', example: 'Hello, I would like to know more about...'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email sent successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request (invalid JSON or incorrect data)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'object', example: ['message' => 'Invalid JSON']),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error when sending the email',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Error sending email: ...'),
                    ]
                )
            ),
        ]
    )]
    public function sendContactEmail(
        Request $request,
        MailerInterface $mailer,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
    ): JsonResponse {
        $content = $request->getContent();
        if (empty($content)) {
            return new JsonResponse(['error' => 'common.error'], Response::HTTP_BAD_REQUEST);
        }

        try {
            json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return new JsonResponse(['error' => 'common.error'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $contactRequest = $serializer->deserialize(
                $content,
                ContactModel::class,
                'json'
            );
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'common.form.error'], Response::HTTP_BAD_REQUEST);
        }

        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'subject' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 150])],
            'message' => [new Assert\NotBlank(), new Assert\Length(['min' => 10, 'max' => 2000])],
        ]);

        $violations = $validator->validate(
            $contactRequest->toArray(),
            $constraints
        );

        if (count($violations) > 0) {
            return new JsonResponse(['error' => 'common.form.error'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $email = (new Email())
                ->from(self::NO_REPLY_EMAIL)
                ->to(self::CONTACT_EMAIL)
                ->replyTo($contactRequest->email)
                ->subject('Formulaire de contact: '.$contactRequest->subject)
                ->html($this->renderView('emails/contact.html.twig', [
                    'name' => $contactRequest->name,
                    'email' => $contactRequest->email,
                    'subject' => $contactRequest->subject,
                    'message' => $contactRequest->message,
                ]));

            $mailer->send($email);

            return new JsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->logger->error('Error sending contact email', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'contact.form.error_message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
