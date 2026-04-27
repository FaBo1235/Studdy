<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Filiere;
use App\Entity\User;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/conversations')]
class ConversationController extends AbstractController
{
    // Lister toutes les conversations d'une filière
    #[Route('/filiere/{id}', name: 'conversation_list', methods: ['GET'])]
    public function listByFiliere(Filiere $filiere): JsonResponse
    {
        $conversations = $filiere->getConversations();

        $data = array_map(fn(Conversation $c) => [
            'id'           => $c->getId(),
            'title'        => $c->getTitle(),
            'creator'      => [
                'id'        => $c->getCreator()->getId(),
                'firstName' => $c->getCreator()->getFirstName(),
                'lastName'  => $c->getCreator()->getLastName(),
            ],
            'participants' => count($c->getParticipants()),
            'messages'     => count($c->getMessages()),
            'createdAt'    => $c->getCreatedAt()->format('d/m/Y H:i'),
        ], $conversations->toArray());

        return $this->json($data);
    }

    // Créer une conversation dans une filière
    #[Route('', name: 'conversation_create', methods: ['POST'], priority: 10)]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        // Vérifie que la filière existe
        $filiere = $em->getRepository(Filiere::class)->find($data['filiereId'] ?? 0);
        if (!$filiere) {
            return $this->json(['error' => 'Filière introuvable'], 404);
        }

        $conversation = new Conversation();
        $conversation->setTitle($data['title'] ?? '');
        $conversation->setFiliere($filiere);
        $conversation->setCreator($user);
        $conversation->addParticipant($user); // le créateur rejoint automatiquement

        $errors = $validator->validate($conversation);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($conversation);
        $em->flush();

        return $this->json([
            'message' => 'Conversation créée avec succès',
            'conversation' => [
                'id'    => $conversation->getId(),
                'title' => $conversation->getTitle(),
            ]
        ], 201);
    }

    // Rejoindre une conversation
    #[Route('/{id}/join', name: 'conversation_join', methods: ['PUT'])]
    public function join(
        Conversation $conversation,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $conversation->addParticipant($user);
        $em->flush();

        return $this->json([
            'message'      => 'Conversation rejointe avec succès',
            'participants' => count($conversation->getParticipants()),
        ]);
    }

    // Quitter une conversation
    #[Route('/{id}/leave', name: 'conversation_leave', methods: ['PUT'])]
    public function leave(
        Conversation $conversation,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $conversation->removeParticipant($user);
        $em->flush();

        return $this->json(['message' => 'Conversation quittée avec succès']);
    }

    // Voir les détails d'une conversation
    #[Route('/{id}', name: 'conversation_show', methods: ['GET'])]
    public function show(Conversation $conversation): JsonResponse
    {
        return $this->json([
            'id'           => $conversation->getId(),
            'title'        => $conversation->getTitle(),
            'filiere'      => [
                'id'   => $conversation->getFiliere()->getId(),
                'name' => $conversation->getFiliere()->getName(),
            ],
            'creator'      => [
                'id'        => $conversation->getCreator()->getId(),
                'firstName' => $conversation->getCreator()->getFirstName(),
            ],
            'participants' => $conversation->getParticipants()->map(fn($u) => [
                'id'        => $u->getId(),
                'firstName' => $u->getFirstName(),
                'lastName'  => $u->getLastName(),
                'country'   => $u->getCountry(),
            ])->toArray(),
            'createdAt'    => $conversation->getCreatedAt()->format('d/m/Y H:i'),
        ]);
    }
}
