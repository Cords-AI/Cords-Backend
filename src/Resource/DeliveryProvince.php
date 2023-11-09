<?php

namespace App\Resource;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryProvinceRepository::class)]
class DeliveryProvince
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $abbreviatedName = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\ManyToOne(inversedBy: 'deliveryProvinces')]
    private ?Resource $resource = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAbbreviatedName(): ?string
    {
        return $this->abbreviatedName;
    }

    public function setAbbreviatedName(string $abbreviatedName): self
    {
        $this->abbreviatedName = $abbreviatedName;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): self
    {
        $this->resource = $resource;

        return $this;
    }
}
