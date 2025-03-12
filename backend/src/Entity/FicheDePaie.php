<?php

namespace App\Entity;

use App\Repository\FicheDePaieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FicheDePaieRepository::class)]
class FicheDePaie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['fiche_de_paie:read', 'fiche_de_paie:write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ficheDePaies')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['fiche_de_paie:read', 'fiche_de_paie:write'])]
    private ?Coach $coach_id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["mois", "semaine"], message: "Période invalide.")]
    #[Groups(['fiche_de_paie:read', 'fiche_de_paie:write'])]
    private ?string $periode = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['fiche_de_paie:read', 'fiche_de_paie:write'])]
    private ?int $total_heures = 0;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['fiche_de_paie:read', 'fiche_de_paie:write'])]
    private ?float $montant_total = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoachId(): ?Coach
    {
        return $this->coach_id;
    }

    public function setCoachId(?Coach $coach_id): static
    {
        $this->coach_id = $coach_id;

        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(string $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getTotalHeures(): ?int
    {
        return $this->total_heures;
    }

    public function setTotalHeures(int $total_heures): static
    {
        $this->total_heures = $total_heures;

        return $this;
    }

    public function getMontantTotal(): ?float
    {
        return $this->montant_total;
    }

    public function setMontantTotal(float $montant_total): static
    {
        $this->montant_total = $montant_total;

        return $this;
    }
}
