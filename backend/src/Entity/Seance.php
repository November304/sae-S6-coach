<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['seance:read', 'seance:write'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    #[Groups(['seance:read', 'seance:write'])]
    private ?\DateTimeInterface $date_heure = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["solo", "duo", "trio"], message: "Type de séance invalide. (solo, duo,trio)")]
    #[Groups(['seance:read', 'seance:write'])]
    private ?string $type_seance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?string $theme_seance = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["débutant", "intermédiaire", "avancé"], message: "Niveau de séance invalide.")]
    #[Groups(['seance:read', 'seance:write'])]
    private ?string $niveau_seance = null;

    #[ORM\ManyToOne(inversedBy: 'seances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coach $coach = null;

    /**
     * @var Collection<int, Sportif>
     */
    #[ORM\ManyToMany(targetEntity: Sportif::class, inversedBy: 'seances')]
    #[Assert\Count(min: 0, max: 3, exactMessage: "Une séance peut avoir maximum 3 sportifs.")]
    #[Groups(['seance:read', 'seance:write'])]
    private Collection $sportifs;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["prévue", "validée", "annulée"], message: "Statut invalide.")]
    #[Groups(['seance:read', 'seance:write'])]
    private ?string $statut = null;

    /**
     * @var Collection<int, Exercice>
     */
    #[ORM\ManyToMany(targetEntity: Exercice::class, inversedBy: 'seances')]
    #[Groups(['seance:read', 'seance:write'])]
    private Collection $exercices;

    public function __construct()
    {
        $this->sportifs = new ArrayCollection();
        $this->exercices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->date_heure;
    }

    public function setDateHeure(\DateTimeInterface $date_heure): static
    {
        $this->date_heure = $date_heure;

        return $this;
    }

    public function getTypeSeance(): ?string
    {
        return $this->type_seance;
    }

    public function setTypeSeance(string $type_seance): static
    {
        $this->type_seance = $type_seance;

        return $this;
    }

    public function getThemeSeance(): ?string
    {
        return $this->theme_seance;
    }

    public function setThemeSeance(string $theme_seance): static
    {
        $this->theme_seance = $theme_seance;

        return $this;
    }

    public function getNiveauSeance(): ?string
    {
        return $this->niveau_seance;
    }

    public function setNiveauSeance(string $niveau_seance): static
    {
        $this->niveau_seance = $niveau_seance;

        return $this;
    }

    public function getCoach(): ?Coach
    {
        return $this->coach;
    }

    public function setCoach(?Coach $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    /**
     * @return Collection<int, Sportif>
     */
    public function getSportifs(): Collection
    {
        return $this->sportifs;
    }

    public function addSportif(Sportif $sportif): static
    {
        if (!$this->sportifs->contains($sportif)) {
            $this->sportifs->add($sportif);
        }

        return $this;
    }

    public function removeSportif(Sportif $sportif): static
    {
        $this->sportifs->removeElement($sportif);

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, Exercice>
     */
    public function getExercices(): Collection
    {
        return $this->exercices;
    }

    public function addExercice(Exercice $exercice): static
    {
        if (!$this->exercices->contains($exercice)) {
            $this->exercices->add($exercice);
        }

        return $this;
    }

    public function removeExercice(Exercice $exercice): static
    {
        $this->exercices->removeElement($exercice);

        return $this;
    }
}
