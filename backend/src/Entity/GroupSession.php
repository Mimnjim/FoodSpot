<?php

namespace App\Entity;

use App\Repository\GroupSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupSessionRepository::class)]
class GroupSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 12, unique: true)]
    private string $code;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 120)]
    private string $city;

    #[ORM\Column(length: 20)]
    private string $status = 'collecting';

    #[ORM\Column(length: 24)]
    private string $creatorParticipantId;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'text')]
    private string $participants = '[]';

    #[ORM\Column(type: 'text')]
    private string $preferences = '[]';

    #[ORM\Column(type: 'text')]
    private string $votes = '[]';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $result = null;

    public function __construct()
    {
        $this->expiresAt = new \DateTimeImmutable('+24 hours');
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getCity(): string { return $this->city; }
    public function setCity(string $city): static { $this->city = $city; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getCreatorParticipantId(): string { return $this->creatorParticipantId; }
    public function setCreatorParticipantId(string $value): static { $this->creatorParticipantId = $value; return $this; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getParticipants(): array { return json_decode($this->participants, true) ?: []; }
    public function setParticipants(array $value): static { $this->participants = json_encode($value, JSON_THROW_ON_ERROR); return $this; }
    public function getPreferences(): array { return json_decode($this->preferences, true) ?: []; }
    public function setPreferences(array $value): static { $this->preferences = json_encode($value, JSON_THROW_ON_ERROR); return $this; }
    public function getVotes(): array { return json_decode($this->votes, true) ?: []; }
    public function setVotes(array $value): static { $this->votes = json_encode($value, JSON_THROW_ON_ERROR); return $this; }
    public function getResult(): ?array { return $this->result === null ? null : (json_decode($this->result, true) ?: []); }
    public function setResult(?array $value): static { $this->result = $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR); return $this; }
}
