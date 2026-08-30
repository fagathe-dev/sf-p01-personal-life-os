<?php

namespace App\Controller\App;

use App\Entity\Tag;
use App\Entity\Task;
use App\Form\App\TaskQuickAddType;
use App\Form\App\TaskType;
use App\Service\TaskService;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/app/task', name: 'app_task_')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService
    ) {
    }

    #[Route(path: '', name: 'manage', methods: ['GET', 'POST'])]
    public function manage(Request $request): Response
    {
        $task = new Task();
        // 🔹 On pré-remplit la date d'échéance à aujourd'hui par défaut pour l'ajout rapide
        $task->setDueDate(new \DateTimeImmutable('today')); 
        
        $quickAddForm = $this->createForm(TaskQuickAddType::class, $task);
        $quickAddForm->handleRequest($request);

        if ($quickAddForm->isSubmitted() && $quickAddForm->isValid()) {
            if ($this->taskService->saveTask($task, true)) {
                $this->addFlash('success', 'Tâche ajoutée rapidement !');
                return $this->redirectToRoute('app_task_manage');
            }
            $this->addFlash('danger', 'Erreur lors de l\'ajout de la tâche.');
        }

        $data = $this->taskService->manage();
        $data['quickAddForm'] = $quickAddForm->createView();

        return $this->render('app/task/index.html.twig', $data);
    }

    #[Route(path: '/tag/{id}', name: 'tag_tasks', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function tagTasks(#[MapEntity(mapping: ['id' => 'id'])] Tag $tag, Request $request): Response
    {
        $task = new Task();
        // 🔹 On pré-remplit également ici
        $task->setDueDate(new \DateTimeImmutable('today')); 

        $quickAddForm = $this->createForm(TaskQuickAddType::class, $task);
        $quickAddForm->handleRequest($request);

        if ($quickAddForm->isSubmitted() && $quickAddForm->isValid()) {
            $task->setTag($tag);

            if ($this->taskService->saveTask($task, true)) {
                $this->addFlash('success', 'Tâche ajoutée avec l\'étiquette !');
                return $this->redirectToRoute('app_task_tag_tasks', ['id' => $tag->getId()]);
            }
            $this->addFlash('danger', 'Erreur lors de l\'ajout de la tâche.');
        }

        $data = $this->taskService->tagTasks($tag);
        $data['quickAddForm'] = $quickAddForm->createView();

        return $this->render('app/task/index.html.twig', $data);
    }

    #[Route(path: '/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        $breadcrumb = $this->taskService->breadcrumb([
            new BreadcrumbItem('Ajouter une Task')
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->taskService->saveTask($task, true)) {
                $this->addFlash('success', 'Tâche créée avec succès.');
                return $this->redirectToRoute('app_task_manage');
            }

            $this->addFlash('danger', 'Une erreur est survenue lors de la création de la tâche.');
        }

        return $this->render('app/task/create.html.twig', compact('form', 'breadcrumb'));
    }

    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Task $task): Response
    {
        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'accéder à cette tâche.');
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->taskService->saveTask($task, false)) {
                $this->addFlash('success', 'Tâche mise à jour avec succès.');
                return $this->redirectToRoute('app_task_manage');
            }

            $this->addFlash('danger', 'Une erreur est survenue lors de la mise à jour de la tâche.');
        }

        $breadcrumb = $this->taskService->breadcrumb([
            new BreadcrumbItem('Modifier une Task')
        ]);

        return $this->render('app/task/edit.html.twig', compact('form', 'task', 'breadcrumb'));
    }

    #[Route(path: '/delete/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, #[MapEntity(mapping: ['id' => 'id'])] Task $task): Response
    {
        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer cette tâche.');
        }

        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $token)) {
            if ($this->taskService->deleteTask($task)) {
                $this->addFlash('success', 'Tâche supprimée avec succès.');
            } else {
                $this->addFlash('danger', 'Une erreur est survenue lors de la suppression.');
            }
        } else {
            $this->addFlash('danger', 'Action non autorisée (Jeton de sécurité invalide).');
        }

        return $this->redirectToRoute('app_task_manage');
    }
}