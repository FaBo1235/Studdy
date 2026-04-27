<?php

namespace App\Controller;

use App\Entity\Filiere;
use App\Repository\FiliereRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/filieres')]
class FiliereController extends AbstractController
{
    // Lister toutes les filières
    #[Route('', name: 'filiere_list', methods: ['GET'])]
    public function list(FiliereRepository $filiereRepository): JsonResponse
    {
        $filieres = $filiereRepository->findAll();

        $data = array_map(fn(Filiere $f) => [
            'id'          => $f->getId(),
            'name'        => $f->getName(),
            'domain'      => $f->getDomain(),
            'description' => $f->getDescription(),
            'students'    => count($f->getUsers()),
        ], $filieres);

        return $this->json($data);
    }

    // Créer une filière (admin seulement plus tard, pour l'instant ouvert)
    #[Route('', name: 'filiere_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $filiere = new Filiere();
        $filiere->setName($data['name'] ?? '');
        $filiere->setDomain($data['domain'] ?? '');
        $filiere->setDescription($data['description'] ?? null);

        $errors = $validator->validate($filiere);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($filiere);
        $em->flush();

        return $this->json([
            'message' => 'Filière créée avec succès',
            'filiere' => [
                'id'     => $filiere->getId(),
                'name'   => $filiere->getName(),
                'domain' => $filiere->getDomain(),
            ]
        ], 201);
    }

    // Voir les étudiants d'une filière (le matching !)
    #[Route('/{id}/students', name: 'filiere_students', methods: ['GET'])]
    public function students(Filiere $filiere): JsonResponse
    {
        $students = array_map(fn($user) => [
            'id'        => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'country'   => $user->getCountry(),
            'bio'       => $user->getBio(),
        ], $filiere->getUsers()->toArray());

        return $this->json([
            'filiere'  => $filiere->getName(),
            'students' => $students,
            'total'    => count($students),
        ]);
    }
}
