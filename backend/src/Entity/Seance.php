<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['seance:read', 'seance:public:read', 'coach:read', 'sportif:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    #[Groups(['seance:read', 'seance:public:read', 'coach:read', 'sportif:read'])]
    private ?\DateTimeInterface $date_heure = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["solo", "duo", "trio"], message: "Type de séance invalide. (solo, duo,trio)")]
    #[Groups(['seance:read', 'seance:public:read', 'coach:read', 'sportif:read'])]
    private ?string $type_seance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['seance:read', 'seance:public:read', 'coach:read', 'sportif:read'])]
    private ?string $theme_seance = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["débutant", "intermédiaire", "avancé"], message: "Niveau de séance invalide.")]
    #[Groups(['seance:read', 'seance:public:read', 'coach:read', 'sportif:read'])]
    private ?string $niveau_seance = null;

    #[ORM\ManyToOne(inversedBy: 'seances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['seance:read', 'seance:public:read', 'sportif:read'])]
    private ?Coach $coach = null;

    /**
     * @var Collection<int, Sportif>
     */
    #[ORM\ManyToMany(targetEntity: Sportif::class, inversedBy: 'seances')]
    #[Assert\Count(min: 0, max: 3, exactMessage: "Une séance peut avoir maximum 3 sportifs.")]
    #[Groups(['seance:read'])]
    private Collection $sportifs;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ["prévue", "validée", "annulée"], message: "Statut invalide.")]
    #[Groups(['seance:read', 'coach:read', 'sportif:read'])]
    private ?string $statut = null;

    /**
     * @var Collection<int, Exercice>
     */
    #[ORM\ManyToMany(targetEntity: Exercice::class, inversedBy: 'seances')]
    #[Groups(['seance:read', 'seance:public:read'])]
    private Collection $exercices;

    /**
     * @var Collection<int, Presence>
     */
    #[ORM\OneToMany(targetEntity: Presence::class, mappedBy: 'seance')]
    private Collection $presences;

    /**
     * @var Collection<int, DemandeAnnulation>
     */
    #[ORM\OneToMany(targetEntity: DemandeAnnulation::class, mappedBy: 'seance')]
    private Collection $demandeAnnulations;

    public function __construct()
    {
        $this->sportifs = new ArrayCollection();
        $this->exercices = new ArrayCollection();
        $this->presences = new ArrayCollection();
        $this->demandeAnnulations = new ArrayCollection();
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

    /**
     * 
     * @return int
     */
    public function getRemainingPlaces(): int
    {
        $maxPlaces = match ($this->getTypeSeance()) {
            'solo' => 1,
            'duo' => 2,
            'trio' => 3,
            default => 0,
        };

        return $maxPlaces - $this->sportifs->count();
    }

    #[Assert\Callback]
    public function validateSportifCount(ExecutionContextInterface $context): void
    {
        $typeSeance = $this->getTypeSeance();
        $sportifCount = $this->getSportifs()->count();

        $maxSportifs = match ($typeSeance) {
            'solo' => 1,
            'duo' => 2,
            'trio' => 3,
            default => 0,
        };

        if ($sportifCount > $maxSportifs) {
            $context->buildViolation("Une séance de type '{$typeSeance}' ne peut pas avoir plus de {$maxSportifs} sportif(s).")
                ->atPath('sportifs')
                ->addViolation();
        }
    }

    public function getDureeEstimeeTotal(): int
    {
        $duree = 0;
        foreach ($this->exercices as $exercice) {
            $duree += $exercice->getDureeEstimee();
        }

        return $duree;
    }

    /**
     * @return Collection<int, Presence>
     */
    public function getPresences(): Collection
    {
        return $this->presences;
    }

    public function addPresence(Presence $presence): static
    {
        if (!$this->presences->contains($presence)) {
            $this->presences->add($presence);
            $presence->setSeance($this);
        }

        return $this;
    }

    public function removePresence(Presence $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            if ($presence->getSeance() === $this) {
                $presence->setSeance(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DemandeAnnulation>
     */
    public function getDemandeAnnulations(): Collection
    {
        return $this->demandeAnnulations;
    }

    public function addDemandeAnnulation(DemandeAnnulation $demandeAnnulation): static
    {
        if (!$this->demandeAnnulations->contains($demandeAnnulation)) {
            $this->demandeAnnulations->add($demandeAnnulation);
            $demandeAnnulation->setSeance($this);
        }

        return $this;
    }

    public function removeDemandeAnnulation(DemandeAnnulation $demandeAnnulation): static
    {
        if ($this->demandeAnnulations->removeElement($demandeAnnulation)) {
            if ($demandeAnnulation->getSeance() === $this) {
                $demandeAnnulation->setSeance(null);
            }
        }

        return $this;
    }

    /**
     * @return float Renvoie le taux d'occupation en % de la séance
     */
    public function getTauxOccupation(): float
    {
        $maxPlaces = match ($this->getTypeSeance()) {
            'solo' => 1,
            'duo' => 2,
            'trio' => 3,
            default => 1,
        };

        return $this->sportifs->count() / $maxPlaces * 100;
    }
}
