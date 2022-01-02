<?php

namespace App\Entity;

use App\Repository\WaffleDeviceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WaffleDeviceRepository::class)
 */
class WaffleDevice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $waffleName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ipAddress;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWaffleName(): ?string
    {
        return $this->waffleName;
    }

    public function setWaffleName(string $waffleName): self
    {
        $this->waffleName = $waffleName;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }
}
