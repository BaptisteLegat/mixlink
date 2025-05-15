<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;

class MailerControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private MailerInterface|MockObject $mailerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->mailerMock = $this->createMock(MailerInterface::class);

        $container = $this->client->getContainer();
        $container->set(MailerInterface::class, $this->mailerMock);
    }

    public function testSendContactEmailSuccess(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
        ;

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with sufficient length to pass validation.',
        ];

        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($contactData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email envoyé avec succès', $responseData['message']);
        $this->assertTrue($responseData['success']);
    }

    public function testSendContactEmailWithEmptyBody(): void
    {
        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('message', $responseData['errors']);
    }

    public function testSendContactEmailWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid_json'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testSendContactEmailWithMissingFields(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'subject' => 'Test Subject',
            'message' => 'This is a test message.',
        ];

        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($contactData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testSendContactEmailWithInvalidEmail(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with sufficient length to pass validation.',
        ];

        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($contactData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('email', $responseData['errors']);
    }

    public function testSendContactEmailWithShortMessage(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'subject' => 'Test Subject',
            'message' => 'Too short',
        ];

        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($contactData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('message', $responseData['errors']);
    }

    public function testSendContactEmailWithMailerException(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Erreur de test du mailer'))
        ;

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with sufficient length to pass validation.',
        ];

        $this->client->request(
            'POST',
            '/api/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($contactData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Erreur lors de l\'envoi de l\'email', $responseData['error']);
    }
}
