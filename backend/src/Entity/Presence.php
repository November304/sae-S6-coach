<?php

namespace App\Entity;

use App\Repository\PresenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PresenceRepository::class)]
class Presence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Seance $seance = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sportif $sportif = null;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: ['Présent', 'Absent', 'Annulé'], message: 'Choose a valid presence status.')]
    private ?string $present = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;

        return $this;
    }

    public function getSportif(): ?Sportif
    {
        return $this->sportif;
    }

    public function setSportif(?Sportif $sportif): static
    {
        $this->sportif = $sportif;

        return $this;
    }

    public function getPresent(): ?string
    {
        return $this->present;
    }

    public function setPresent(string $present): static
    {
        $this->present = $present;

        return $this;
    }
}
