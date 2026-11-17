<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Job;
use App\Entity\Skill;
use App\Repository\JobRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JobApiController extends AbstractController
{
    #[Route('/api/jobs', name: 'api_jobs_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/jobs',
        summary: 'Lister et rechercher les offres publiées',
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                description: 'Recherche dans le titre et la description',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des offres publiées',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'object')
                )
            ),
        ]
    )]
    public function index(Request $request, JobRepository $jobs): JsonResponse
    {
        $results = array_map(
            self::serializeJob(...),
            $jobs->findPublished(trim($request->query->getString('q')))
        );

        return $this->json($results);
    }

    #[Route('/api/jobs/{id}', name: 'api_jobs_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/jobs/{id}',
        summary: 'Consulter une offre publiée',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Identifiant de l’offre',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail de l’offre',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 404,
                description: 'Offre publiée introuvable',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Published job not found.'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function show(int $id, JobRepository $jobs): JsonResponse
    {
        $job = $jobs->findOneBy(['id' => $id, 'published' => true]);

        if (!$job instanceof Job) {
            return $this->json(
                ['error' => 'Published job not found.'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json(self::serializeJob($job));
    }

    private static function serializeJob(Job $job): array
    {
        return [
            'id' => $job->getId(),
            'title' => $job->getTitle(),
            'description' => $job->getDescription(),
            'contractType' => $job->getContractType(),
            'location' => $job->getLocation(),
            'remote' => $job->isRemote(),
            'createdAt' => $job->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'company' => $job->getCompany()?->getName(),
            'skills' => array_map(
                static fn (Skill $skill): string => $skill->getName(),
                $job->getSkills()->toArray()
            ),
        ];
    }
}
