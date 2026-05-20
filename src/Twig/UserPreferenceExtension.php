<?php
namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserPreferenceExtension extends AbstractExtension
{

    public function __construct(private readonly Security $security)
    {
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction("get_preference", [$this, "getPreference"]),
        ];
    }

    /**
     * @param string $preference
     * @param mixed|null $default
     * @param User|null $user
     * 
     * @return mixed
     */
    public function getPreference(string $preference, mixed $default = null, ?User $user = null): mixed
    {
        if ($user === null) {
            $user = $this->getUser();
        }

        if ($user === null) {
            return $default;
        }

        return $user->getPreference($preference, $default);
    }

    /**
     * @return User|null
     */
    private function getUser(): ?User
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

}