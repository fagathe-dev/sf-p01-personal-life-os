<?php

namespace App\Controller\App;

use App\Entity\Note;
use App\Entity\Tag;
use App\Enum\Task\TodoDueDateEnum;
use App\Form\App\Note\NoteQuickAddType;
use App\Form\App\Note\NoteType;
use App\Service\NoteService;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/app/note', name: 'app_note_')]
final class NoteController extends AbstractController
{
    public function __construct(
        private readonly NoteService $noteService
    ) {
    }

    # Liste des notes de l'utilisateur connecté
    #[Route(path: '', name: 'manage', methods: ['GET'])]
    public function manage(Request $request): Response
    {
        // Récupération des nouveaux paramètres courts
        $query = $request->query->get('q');
        $tag = $request->query->get('t'); // 't' au lieu de 'tag'
        $color = $request->query->get('c'); // 'c' au lieu de 'color'

        // On passe les filtres au service
        $data = $this->noteService->manage(filters: compact('query', 'tag', 'color'));

        // On injecte les couleurs pour le select Twig
        $data['colors'] = \App\Enum\Note\NoteColorEnum::cases();

        return $this->render('app/note/index.html.twig', $data);
    }

    #[Route(path: '/tag/{id}', name: 'tag_notes', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function tagNotes(#[MapEntity(mapping: ['id' => 'id'])] Tag $tag, Request $request): Response
    {
        $note = new Note();
        $quickAddForm = $this->createForm(NoteQuickAddType::class, $note);
        $quickAddForm->handleRequest($request);

        if ($quickAddForm->isSubmitted() && $quickAddForm->isValid()) {
            $note->setTag($tag); // On pré-associe le tag actif à la nouvelle note

            if ($this->noteService->saveNote($note, true)) {
                $this->addFlash('success', 'Note ajoutée avec l\'étiquette !');
                // Redirection vers la page du tag pour rester dans le contexte
                return $this->redirectToRoute('app_note_tag_notes', ['id' => $tag->getId()]);
            }
            $this->addFlash('danger', 'Erreur lors de l\'ajout de la note.');
        }

        // On récupère les données filtrées
        $data = [...$this->noteService->tagNotes($tag), 'quickAddForm' => $quickAddForm->createView()];

        return $this->render('app/note/index.html.twig', $data);
    }

    # Page de création d'une Note avec NoteType
    #[Route(path: '/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $note = new Note();
        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        $breadcrumb = $this->noteService->breadcrumb([
            new BreadcrumbItem('Ajouter une Note')
        ]);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->noteService->saveNote($note, true)) {
                $this->addFlash('success', 'Note créée avec succès.');
                return $this->redirectToRoute('app_note_manage');
            }

            $this->addFlash('danger', 'Une erreur est survenue lors de la création de la note.');
        }

        return $this->render('app/note/create.html.twig', compact('form', 'breadcrumb'));
    }

    # Page de modification d'une Note avec NoteType
    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Note $note): Response
    {
        // Sécurité : on vérifie que la note appartient bien à l'utilisateur connecté
        if ($note->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'accéder à cette note.');
        }

        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->noteService->saveNote($note, false)) {
                $this->addFlash('success', 'Note mise à jour avec succès.');
                return $this->redirectToRoute('app_note_manage');
            }

            $this->addFlash('danger', 'Une erreur est survenue lors de la mise à jour de la note.');
        }

        $breadcrumb = $this->noteService->breadcrumb([
            new BreadcrumbItem('Modifier une Note')
        ]);

        return $this->render('app/note/edit.html.twig', compact('form', 'note', 'breadcrumb'));
    }

    # Page de suppression d'une Note avec confirmation (méthode POST)
    #[Route(path: '/delete/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, #[MapEntity(mapping: ['id' => 'id'])] Note $note): Response
    {
        // Sécurité : On s'assure que l'utilisateur est bien le propriétaire
        if ($note->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer cette note.');
        }

        // 🔴 Vérification du token CSRF 🔴
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $note->getId(), $token)) {
            // Le jeton est valide, on procède à la suppression
            if ($this->noteService->deleteNote($note)) {
                $this->addFlash('success', 'Note supprimée avec succès.');
            } else {
                $this->addFlash('danger', 'Une erreur est survenue lors de la suppression.');
            }
        } else {
            // Le jeton est invalide (Tentative de hack ou session expirée)
            $this->addFlash('danger', 'Action non autorisée (Jeton de sécurité invalide).');
        }

        return $this->redirectToRoute('app_note_manage');
    }
}