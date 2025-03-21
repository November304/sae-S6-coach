<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: "utilisateur")]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "user_type", type: "string")]
#[ORM\DiscriminatorMap(["utilisateur" => Utilisateur::class, "coach" => Coach::class, "sportif" => Sportif::class])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], entityClass: Utilisateur::class, message: 'Cet email est déjà utilisé.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['coach:read', 'sportif:read', 'sportif:write', 'seance:read', 'coach:public:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['coach:read', 'sportif:read', 'sportif:write', 'seance:read'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Assert\All([
        new Assert\Choice(choices: ["ROLE_SPORTIF", "ROLE_COACH", "ROLE_RESPONSABLE"], message: "Rôle invalide.")
    ])]
    #[Groups(['sportif:read', 'sportif:write'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Groups(['sportif:write'])]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['coach:read', 'sportif:read', 'sportif:write', 'seance:read', 'seance:public:read', 'coach:public:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['coach:read', 'sportif:read', 'sportif:write', 'seance:read', 'seance:public:read', 'coach:public:read'])]
    private ?string $prenom = null;

    /**
     * @var Collection<int, DemandeAnnulation>
     */
    #[ORM\OneToMany(targetEntity: DemandeAnnulation::class, mappedBy: 'responsable')]
    private Collection $demandeAnnulations;

    public function __construct()
    {
        $this->demandeAnnulations = new ArrayCollection();
    }

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordChangedAt = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): static
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . strtoupper($this->nom);
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
            $demandeAnnulation->setResponsable($this);
        }

        return $this;
    }

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(?\DateTimeImmutable $passwordChangedAt): static
    {
        $this->passwordChangedAt = $passwordChangedAt;


        return $this;
    }

    public function removeDemandeAnnulation(DemandeAnnulation $demandeAnnulation): static
    {
        if ($this->demandeAnnulations->removeElement($demandeAnnulation)) {
            if ($demandeAnnulation->getResponsable() === $this) {
                $demandeAnnulation->setResponsable(null);
            }
        }

        return $this;
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'password_changed_at' => $this->passwordChangedAt ? $this->passwordChangedAt->getTimestamp() : null,
        ];
    }
}
