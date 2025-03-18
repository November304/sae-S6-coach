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
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ficheDePaies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coach $coach = null;
  
    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["mois", "semaine"], message: "Période invalide.")]
    private ?string $periode = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $total_heures = 0;

    #[ORM\Column]
    #[Assert\Positive]
    private ?float $montant_total = 0;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateMontantTotal(): void
    {
        if ($this->coach && $this->total_heures !== null) {
            $this->montant_total = $this->total_heures * $this->coach->getTarifHoraire();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoach(): ?Coach
    {
        return $this->coach;
    }

    public function setCoach(?Coach $coach): static
    {
        $this->coach = $coach;
        $this->calculateMontantTotal();
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

    /**
     * Le set est inutile car le montant est calculé automatiquement
     */
    public function setMontantTotal(float $montant_total): static
    {
        $this->montant_total = $montant_total;
        $this->calculateMontantTotal();
        return $this;
    }
}
