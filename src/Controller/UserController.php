<?php

namespace App\Controller;

use App\Entity\Filiere;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    // Voir son propre profil
    #[Route('/me', name: 'user_me', methods: ['GET'], priority: 10)]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'country'   => $user->getCountry(),
            'bio'       => $user->getBio(),
            'filieres'  => $user->getFilieres()->map(fn($f) => [
                'id'   => $f->getId(),
                'name' => $f->getName(),
            ])->toArray(),
        ]);
    }

    // Rejoindre une filière (max 2)
    #[Route('/me/filiere/{id}', name: 'user_join_filiere', methods: ['PUT'])]
    public function joinFiliere(
        Filiere $filiere,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $user->addFiliere($filiere);
            $em->flush();
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json([
            'message'  => 'Filière rejointe avec succès',
            'filieres' => $user->getFilieres()->map(fn($f) => [
                'id'   => $f->getId(),
                'name' => $f->getName(),
            ])->toArray(),
        ]);
    }

    // Quitter une filière
    #[Route('/me/filiere/{id}', name: 'user_leave_filiere', methods: ['DELETE'])]
    public function leaveFiliere(
        Filiere $filiere,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $user->removeFiliere($filiere);
        $em->flush();

        return $this->json(['message' => 'Filière quittée avec succès']);
    }
}
