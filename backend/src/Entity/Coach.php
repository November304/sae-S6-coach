<?php

namespace App\Entity;

use App\Repository\CoachRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: CoachRepository::class)]
#[Vich\Uploadable]
class Coach extends Utilisateur
{
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['coach:read', 'coach:public:read', 'seance:read'])]
    private array $specialites = [];

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?float $tarif_horaire = 0;

    /**
     * @var Collection<int, Seance>
     */
    #[ORM\OneToMany(targetEntity: Seance::class, mappedBy: 'coach')]
    private Collection $seances;

    /**
     * @var Collection<int, FicheDePaie>
     */
    #[ORM\OneToMany(targetEntity: FicheDePaie::class, mappedBy: 'coach')]
    private Collection $ficheDePaies;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['coach:read', 'coach:public:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['coach:read', 'coach:public:read'])]
    private ?string $imageFilename = null;

    #[Vich\UploadableField(mapping: 'coach', fileNameProperty: 'imageFilename')]
    private ?File $imageFile = null;

    public function __construct()
    {
        $this->seances = new ArrayCollection();
        $this->ficheDePaies = new ArrayCollection();

        if ($this->getRoles() === null) {
            $this->setRoles(['ROLE_COACH']);
        } elseif (!in_array('ROLE_COACH', $this->getRoles())) {
            $this->addRole("ROLE_COACH");
        }
    }

    public function getSpecialites(): array
    {
        return $this->specialites;
    }

    public function setSpecialites(array $specialites): static
    {
        $this->specialites = $specialites;

        return $this;
    }

    public function getTarifHoraire(): ?float
    {
        return $this->tarif_horaire;
    }

    public function setTarifHoraire(float $tarif_horaire): static
    {
        $this->tarif_horaire = $tarif_horaire;

        return $this;
    }

    /**
     * @return Collection<int, Seance>
     */
    public function getSeances(): Collection
    {
        return $this->seances;
    }

    public function addSeance(Seance $seance): static
    {
        if (!$this->seances->contains($seance)) {
            $this->seances->add($seance);
            $seance->setCoach($this);
        }

        return $this;
    }

    public function removeSeance(Seance $seance): static
    {
        if ($this->seances->removeElement($seance)) {
            if ($seance->getCoach() === $this) {
                $seance->setCoach(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FicheDePaie>
     */
    public function getFicheDePaies(): Collection
    {
        return $this->ficheDePaies;
    }

    public function addFicheDePaie(FicheDePaie $ficheDePaie): static
    {
        if (!$this->ficheDePaies->contains($ficheDePaie)) {
            $this->ficheDePaies->add($ficheDePaie);
            $ficheDePaie->setCoach($this);
        }

        return $this;
    }

    public function removeFicheDePaie(FicheDePaie $ficheDePaie): static
    {
        if ($this->ficheDePaies->removeElement($ficheDePaie)) {
            if ($ficheDePaie->getCoach() === $this) {
                $ficheDePaie->setCoach(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getNomComplet(): string
    {
        return $this->getPrenom() . ' ' . strtoupper($this->getNom());
    }
}
