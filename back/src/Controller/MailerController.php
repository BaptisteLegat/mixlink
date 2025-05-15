<?php

namespace App\Controller;

use App\Mail\ContactModel;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    public function sendContactEmail(
        Request $request,
        MailerInterface $mailer,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
    ): JsonResponse {
        $content = $request->getContent();
        if (empty($content)) {
            return $this->json(['errors' => ['message' => 'Empty request body']], 400);
        }

        try {
            json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $this->json(['errors' => ['message' => 'Invalid JSON']], 400);
        }

        try {
            $contactRequest = $serializer->deserialize(
                $content,
                ContactModel::class,
                'json'
            );
        } catch (Throwable $e) {
            return $this->json(['errors' => ['message' => 'Invalid data format']], 400);
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
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = trim($violation->getPropertyPath(), '[\]');
                $errors[$propertyPath] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], 400);
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

            return $this->json(['success' => true, 'message' => 'Email envoyé avec succès']);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email: '.$e->getMessage()], 500);
        }
    }
}
