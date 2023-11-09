<?php

namespace App\Resource;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

#[ORM\Entity(repositoryClass: InquiryRepository::class)]
class Inquiry implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionId = null;

    #[ORM\ManyToOne(cascade: ['remove'], fetch: 'EAGER', inversedBy: 'inquiries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $resource = null;

    #[ORM\Column(length: 255)]
    private ?string $resourceId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdDate = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setResource(Resource $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?int $timestamp): void
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->createdDate = $dateTime;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'createdDate' => $this->getCreatedDate()->getTimestamp(),
            'resource' => $this->resource
        ];
    }
}
