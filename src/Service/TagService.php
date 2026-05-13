<?php

namespace App\Service;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

/**
 * Service de gestion des Tags (Pilules de couleur).
 * * Gère la création, l'édition, la suppression et la récupération des tags
 * propres à un utilisateur.
 */
final class TagService
{
    use LoggerTrait, DatetimeTrait;

    public function __construct(
        private readonly TagRepository $repository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Sauvegarde un Tag (Création ou Mise à jour).
     * * Lors d'une création, assigne automatiquement l'utilisateur connecté comme propriétaire.
     * * @param Tag  $tag        Le tag à sauvegarder
     * @param bool $isCreation True si c'est un nouveau tag
     * * @return bool True si l'opération a réussi
     */
    public function saveTag(Tag $tag, bool $isCreation = false): bool
    {
        try {
            if ($isCreation) {
                /** @var User $user */
                $user = $this->security->getUser();
                if ($user) {
                    $tag->setOwner($user);
                }
            }

            // Appel de la méthode du repository (qui gère le flush)
            $this->repository->save(tag: $tag, flush: true, isCreation: $isCreation);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Tag sauvegardé avec succès',
                    'tag_name' => $tag->getName(),
                    'tag_color' => $tag->getColor()->value ?? null
                ],
                ['action' => $isCreation ? 'tag.create.success' : 'tag.update.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la sauvegarde du tag',
                    'error' => $th->getMessage()
                ],
                ['action' => 'tag.save.error']
            );
            return false;
        }
    }

    /**
     * Supprime un Tag.
     * * @param Tag $tag Le tag à supprimer
     * * @return bool True en cas de succès
     */
    public function deleteTag(Tag $tag): bool
    {
        try {
            $tagId = $tag->getId();

            $this->repository->remove($tag, true);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Tag supprimé avec succès',
                    'tag_id' => $tagId
                ],
                ['action' => 'tag.delete.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la suppression du tag',
                    'tag_id' => $tag->getId(),
                    'error' => $th->getMessage()
                ],
                ['action' => 'tag.delete.error']
            );
            return false;
        }
    }

    /**
     * Récupère tous les tags appartenant à un utilisateur précis.
     * * @param User $user Le propriétaire des tags
     * * @return Tag[]
     */
    public function getUserTags(User $user): array
    {
        return $this->repository->findBy(['owner' => $user], ['name' => 'ASC']);
    }

    private function getTagById(int $id): ?Tag
    {
        return $this->repository->find($id);
    }

    /**
     * @return User|null
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Raccourci pour récupérer les tags de l'utilisateur actuellement connecté.
     * * @return Tag[]
     */
    public function getCurrentUserTags(): array
    {
        /** @var User|null $user */
        $user = $this->getCurrentUser();

        if (!$user) {
            return [];
        }

        return $this->getUserTags($user);
    }

    /**
     * Génère le fil d'Ariane pour la gestion des tags.
     * @param BreadcrumbItem[] $items Les éléments du fil d'Ariane
     * * @return Breadcrumb
     */
    public function breadcrumb(array $items = []): Breadcrumb
    {
        return new Breadcrumb([
            new BreadcrumbItem(name: 'Gestion des étiquettes', link: $this->urlGenerator->generate('auth_profile_index', ['t' => 'tags'])),
            ...$items
        ]);
    }

}