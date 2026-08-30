<?php
namespace App\Controller\Ajax;

use App\Entity\Todo;
use App\Service\TodoService;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/ajax/todo', name: 'ajax_todo_')]
final class TodoQuickActionsController extends AbstractController
{

    use DatetimeTrait;

    public function __construct(private readonly TodoService $todoService)
    {
    }

    #[Route(path: '/quick-add', name: 'quick_add', methods: ['POST'])]
    public function quickAdd(Request $request): JsonResponse
    {
        // On récupère le payload JSON envoyé par ton JS
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? null;

        if (empty($title)) {
            return new JsonResponse(['success' => false, 'error' => 'Titre vide'], 400);
        }

        $task = new Todo();
        $task->setTitle(trim($title));

        if ($this->todoService->saveTodo($task, true)) {
            // 🔥 L'ASTUCE : On demande à Symfony de générer le HTML de la petite carte !
            $html = $this->renderView('app/todo/_component.html.twig', [
                'task' => $task
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html // Le JS n'aura qu'à faire un insertAdjacentHTML
            ]);
        }

        return new JsonResponse(['success' => false], 500);
    }

    #[Route(path: '/{id}/toggle-completed', name: 'toggle_completed', methods: ['POST'])]
    public function toggleCompleted(#[MapEntity(mapping: ['id' => 'id'])] Todo $task): JsonResponse
    {
        // Sécurité métier
        if ($task->getOwner() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        // 1. On bascule le statut booléen natif
        $task->toggleCompleted();

        if ($task->isCompleted()) {
            $task->setCompletedAt($this->now());
        } else {
            $task->setCompletedAt(null);
        }

        $this->todoService->saveTodo($task, false);

        return new JsonResponse([
            'success' => true,
            'is_completed' => $task->isCompleted()
        ]);
    }

}