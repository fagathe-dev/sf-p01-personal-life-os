<?php

namespace App\Controller\App;

use App\Entity\Task;
use App\Enum\Task\TodoDueDateEnum;
use App\Form\App\TodoType;
use App\Service\TodoService;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/app/todo', name: 'app_todo_')]
final class TodoController extends AbstractController
{
    public function __construct(
        private readonly TodoService $todoService
    ) {
    }

    # Liste des tâches de l'utilisateur connecté
    #[Route(path: '', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        // On utilise la méthode de ton service qui filtre déjà par l'utilisateur connecté
        $tasks = $this->todoService->getCurrentUserTasks();

        $breadcrumb = $this->todoService->breadcrumb();

        return $this->render('app/todo/index.html.twig', compact('tasks', 'breadcrumb'));
    }

    # Page de création d'une Todo avec TodoType
    #[Route(path: '/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TodoType::class, $task);
        $form->handleRequest($request);

        $breadcrumb = $this->todoService->breadcrumb([
            new BreadcrumbItem('Ajouter une Todo')
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement manuel de l'Enum (Puisqu'à la création, c'est toujours l'Enum qui s'affiche)
            if ($form->has('due_date') && $form->get('due_date')->getData()) {
                $dueDate = TodoDueDateEnum::generateDatetimeFromEnum($form->get('due_date')->getData());
                $task->setDueDate($dueDate);
            }

            if ($this->todoService->saveTask($task, true)) {
                $this->addFlash('success', 'Tâche créée avec succès.');
                return $this->redirectToRoute('app_todo_index');
            }

            $this->addFlash('danger', 'Une erreur est survenue lors de la création de la tâche.');
        }

        return $this->render('app/todo/create.html.twig', compact('form', 'breadcrumb'));
    }

    # Page de modification d'une Todo avec TodoType
    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Task $task): Response
    {
        // Sécurité : on vérifie que la tâche appartient bien à l'utilisateur connecté
        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'accéder à cette tâche.');
        }

        $form = $this->createForm(TodoType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On vérifie si le champ due_date rendu était notre Enum non-mappé
            // Si c'était un DateType, Symfony a déjà hydraté l'objet $task automatiquement !
            if ($form->has('due_date') && !$form->get('due_date')->getConfig()->getMapped()) {
                $enumData = $form->get('due_date')->getData();
                if ($enumData) {
                    $dueDate = TodoDueDateEnum::generateDatetimeFromEnum($enumData);
                    $task->setDueDate($dueDate);
                }
            }

            if ($this->todoService->saveTask($task, false)) {
                $this->addFlash('success', 'Tâche mise à jour avec succès.');
                return $this->redirectToRoute('app_todo_index');
            }

            $this->addFlash('danger', 'Une erreur est survenue lors de la mise à jour de la tâche.');
        }

        $breadcrumb = $this->todoService->breadcrumb([
            new BreadcrumbItem('Modifier une Todo')
        ]);

        return $this->render('app/todo/edit.html.twig', compact('form', 'task', 'breadcrumb'));
    }

    # Page de suppression d'une Todo avec confirmation (méthode POST)
    #[Route(path: '/delete/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Task $task): Response
    {
        // Sécurité
        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer cette tâche.');
        }

        // Optionnel mais recommandé en production : vérifier le token CSRF
        // $token = $request->request->get('_token');
        // if ($this->isCsrfTokenValid('delete'.$task->getId(), $token)) { ... }

        if ($this->todoService->deleteTask($task)) {
            $this->addFlash('success', 'Tâche supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Une erreur est survenue lors de la suppression.');
        }

        return $this->redirectToRoute('app_todo_index');
    }
}