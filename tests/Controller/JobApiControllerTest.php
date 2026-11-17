<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Company;
use App\Entity\Job;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JobApiControllerTest extends WebTestCase
{
    public function testPublishedJobsEndpointReturnsMatchingJson(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $job = $this->createPublishedJob();

        try {
            $client->request('GET', '/api/jobs?q=Symfony');

            self::assertResponseIsSuccessful();
            self::assertStringContainsString(
                'application/json',
                (string) $client->getResponse()->headers->get('Content-Type')
            );
            $jobs = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
            self::assertCount(1, $jobs);
            self::assertSame('Symfony API Test Job', $jobs[0]['title']);
        } finally {
            $this->removePublishedJob($job);
        }
    }

    public function testPublishedJobDetailsAreReturnedAsJson(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $job = $this->createPublishedJob();

        try {
            $client->request('GET', '/api/jobs/'.$job->getId());

            self::assertResponseIsSuccessful();
            $details = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('Symfony API Test Job', $details['title']);
            self::assertSame('Test Company', $details['company']);
        } finally {
            $this->removePublishedJob($job);
        }
    }

    public function testUnknownPublishedJobReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/jobs/0');

        self::assertResponseStatusCodeSame(404);
        self::assertStringContainsString(
            'application/json',
            (string) $client->getResponse()->headers->get('Content-Type')
        );
        self::assertSame(
            ['error' => 'Published job not found.'],
            json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    private function createPublishedJob(): Job
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $owner = (new User())
            ->setEmail('api-test-'.bin2hex(random_bytes(8)).'@jobboard.local')
            ->setPassword('test-password')
            ->setFirstName('API')
            ->setLastName('Test');
        $company = (new Company())
            ->setName('Test Company')
            ->setOwner($owner);
        $job = (new Job())
            ->setCompany($company)
            ->setTitle('Symfony API Test Job')
            ->setDescription('A temporary offer created by a functional test.')
            ->setPublished(true);

        $entityManager->persist($owner);
        $entityManager->persist($company);
        $entityManager->persist($job);
        $entityManager->flush();

        return $job;
    }

    private function removePublishedJob(Job $job): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $company = $job->getCompany();
        $owner = $company?->getOwner();

        $entityManager->remove($job);
        if ($company !== null) {
            $entityManager->remove($company);
        }
        if ($owner !== null) {
            $entityManager->remove($owner);
        }
        $entityManager->flush();
    }
}
