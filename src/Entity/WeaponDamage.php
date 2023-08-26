<?php

namespace App\Entity;

use App\Entity\Sky\Weapon;
use App\Repository\WeaponDamageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeaponDamageRepository::class)]
class WeaponDamage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $type = null;

    #[ORM\Column]
    private ?float $damage = null;

    #[ORM\ManyToOne(inversedBy: 'weaponDamage')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Weapon $weapon = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDamage(): ?float
    {
        return $this->damage;
    }

    public function setDamage(float $damage): static
    {
        $this->damage = $damage;

        return $this;
    }

    public function getWeapon(): ?Weapon
    {
        return $this->weapon;
    }

    public function setWeapon(?Weapon $weapon): static
    {
        $this->weapon = $weapon;

        return $this;
    }
}
