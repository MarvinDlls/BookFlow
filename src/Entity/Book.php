<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 120)]
    private ?string $author = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $cover = null;

    #[ORM\Column]
    private ?int $popularity = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $is_restricted = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(options: ["default" => false])]
    private bool $isReserved = false;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'book')]
    private Collection $reservations;

    /**
     * @var Collection<int, UserHistory>
     */
    #[ORM\OneToMany(targetEntity: UserHistory::class, mappedBy: 'book')]
    private Collection $userHistories;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_tag')]
    private Collection $tags;

    #[ORM\Column(length: 255)]
    private ?string $pdfFile = null;

    /**
     * Vérifie si le livre est réservé (statut actif).
     *
     * @return bool
     */
    public function isReserved(): bool
    {
        // Vérifie si le livre a une réservation active (status true)
        foreach ($this->getReservations() as $reservation) {
            if ($reservation->getStatus()) {
                return true; // Le livre est réservé
            }
        }
        
        return false; // Le livre n'est pas réservé
    }

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->userHistories = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue()
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(string $cover): static
    {
        $this->cover = $cover;

        return $this;
    }

    public function getPopularity(): ?int
    {
        return $this->popularity;
    }

    public function setPopularity(int $popularity): static
    {
        $this->popularity = $popularity;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isRestricted(): ?bool
    {
        return $this->is_restricted;
    }

    public function setIsRestricted(bool $is_restricted): static
    {
        $this->is_restricted = $is_restricted;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getIsReserved(): bool
    {
        return $this->isReserved;
    }

    public function setIsReserved(bool $isReserved): self
    {
        $this->isReserved = $isReserved;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setBook($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getBook() === $this) {
                $reservation->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserHistory>
     */
    public function getUserHistories(): Collection
    {
        return $this->userHistories;
    }

    public function addUserHistory(UserHistory $userHistory): static
    {
        if (!$this->userHistories->contains($userHistory)) {
            $this->userHistories->add($userHistory);
            $userHistory->setBook($this);
        }

        return $this;
    }

    public function removeUserHistory(UserHistory $userHistory): static
    {
        if ($this->userHistories->removeElement($userHistory)) {
            // set the owning side to null (unless already changed)
            if ($userHistory->getBook() === $this) {
                $userHistory->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addBook($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeBook($this);
        }

        return $this;
    }

    public function getPdfFile(): ?string
    {
        return $this->pdfFile;
    }

    public function setPdfFile(string $pdfFile): self
    {
        $this->pdfFile = $pdfFile;

        return $this;
    }
}
