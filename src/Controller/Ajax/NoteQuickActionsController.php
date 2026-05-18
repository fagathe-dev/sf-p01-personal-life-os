<?php
namespace App\Controller\Ajax;

use App\Entity\Note;
use App\Service\NoteService;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/ajax/note', name: 'ajax_note_')]
final class NoteQuickActionsController extends AbstractController
{

    use DatetimeTrait;

    public function __construct(private readonly NoteService $noteService)
    {
    }

    #[Route(path: '/quick-add', name: 'quick_add', methods: ['POST'])]
    public function quickAdd(Request $request): JsonResponse
    {
        # TODO: Adapter ce code au contexte des notes (et pas des tâches)
        // On récupère le payload JSON envoyé par ton JS
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? null;

        if (empty($title)) {
            return new JsonResponse(['success' => false, 'error' => 'Titre vide'], 400);
        }

        $note = new Note();
        $note->setTitle(trim($title));

        if ($this->noteService->saveNote($note, true)) {
            // 🔥 L'ASTUCE : On demande à Symfony de générer le HTML de la petite carte !
            $html = $this->renderView('app/note/_component.html.twig', [
                'note' => $note,
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html // Le JS n'aura qu'à faire un insertAdjacentHTML
            ]);
        }

        return new JsonResponse(['success' => false], 500);
    }

    #[Route(path: '/{id}/toggle-pinned', name: 'toggle_pinned', methods: ['POST'])]
    public function togglePinned(#[MapEntity(mapping: ['id' => 'id'])] Note $note): JsonResponse
    {
        # TODO: Adapter ce code au contexte des notes (et pas des tâches)
        if ($note->getOwner() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $task->togglePinned();
        $this->todoService->saveTask($task, false);

        return new JsonResponse([
            'success' => true,
            'is_pinned' => $task->isPinned()
        ]);
    }

    #[Route(path: '/archive/{id}', name: 'archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archive(#[MapEntity(mapping: ['id' => 'id'])] Note $note): JsonResponse
    {
        if ($note->getOwner() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $note->toggleArchived();
        $this->noteService->saveNote($note, false);

        return new JsonResponse([
            'success' => true,
            'is_archived' => $note->isArchived()
        ]);
    }

    #[Route(path: '/trash/{id}', name: 'trash', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function trash(#[MapEntity(mapping: ['id' => 'id'])] Note $note): JsonResponse
    {
        if ($note->getOwner() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $note->toggleArchived();
        $this->noteService->saveNote($note, false);

        return new JsonResponse([
            'success' => true,
            'is_archived' => $note->isArchived()
        ]);
    }
}
