<?php
namespace App\Twig;

use App\Entity\User;
use App\Security\Enum\RoleEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction("roles", [$this, "getRoles"]),
            new TwigFunction("nice_role", [$this, "niceRole"]),
        ];
    }

    /**
     * @param User $user
     * 
     * @return string
     */
    public function getRoles(User $user): string
    {
        $roles = [];
        foreach ($user->getRoles() as $role) {
            $roles[] = $this->niceRole($role);
        }

        return join(', ', $roles);
    }

    /**
     * @param null|string|RoleEnum $role
     * 
     * @return string
     */
    public function niceRole(null|string|RoleEnum $role): string
    {
        return RoleEnum::getRole($role);
    }
    
}