<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    // Lister les messages d'une conversation
    #[Route('/conversation/{id}', name: 'message_list', methods: ['GET'])]
    public function listByConversation(Conversation $conversation): JsonResponse
    {
        // Vérifie que l'utilisateur est participant
        /** @var User $user */
        $user = $this->getUser();

        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $messages = $conversation->getMessages()->map(fn(Message $m) => [
            'id'        => $m->getId(),
            'content'   => $m->getContent(),
            'author'    => [
                'id'        => $m->getAuthor()->getId(),
                'firstName' => $m->getAuthor()->getFirstName(),
                'lastName'  => $m->getAuthor()->getLastName(),
                'country'   => $m->getAuthor()->getCountry(),
            ],
            'createdAt' => $m->getCreatedAt()->format('d/m/Y H:i'),
        ])->toArray();

        return $this->json([
            'conversation' => $conversation->getTitle(),
            'messages'     => $messages,
            'total'        => count($messages),
        ]);
    }

    // Envoyer un message
    #[Route('', name: 'message_create', methods: ['POST'])]
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

        $conversation = $em->getRepository(Conversation::class)->find($data['conversationId'] ?? 0);
        if (!$conversation) {
            return $this->json(['error' => 'Conversation introuvable'], 404);
        }

        // Vérifie que l'utilisateur est participant
        if (!$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Tu dois rejoindre la conversation pour envoyer un message'], 403);
        }

        $message = new Message();
        $message->setContent($data['content'] ?? '');
        $message->setAuthor($user);
        $message->setConversation($conversation);

        $errors = $validator->validate($message);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($message);
        $em->flush();

        return $this->json([
            'message'   => 'Message envoyé avec succès',
            'id'        => $message->getId(),
            'content'   => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('d/m/Y H:i'),
        ], 201);
    }

    // Supprimer son propre message
    #[Route('/{id}', name: 'message_delete', methods: ['DELETE'])]
    public function delete(
        Message $message,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Seul l'auteur peut supprimer son message
        if ($message->getAuthor() !== $user) {
            return $this->json(['error' => 'Tu ne peux supprimer que tes propres messages'], 403);
        }

        $em->remove($message);
        $em->flush();

        return $this->json(['message' => 'Message supprimé avec succès']);
    }
}
