<?php

namespace App\Resource;

use App\Features\Import\Import;
use App\Features\Import\TwoEleven\TwoElevenResource;
use App\LocalizedString;
use App\Search\Provinces;
use App\Search\Result;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\ManagerRegistry;
use PDO;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
class Resource implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(type: 'text', unique: true)]
    private ?string $partnerId = null;

    #[ORM\Column(type: 'text', length: 500)]
    private $nameEn = null;

    #[ORM\Column(type: 'text', length: 500, nullable: true)]
    private $nameFr = null;

    #[ORM\Column(type: 'text')]
    private $descriptionEn = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private $descriptionFr = null;

    #[ORM\Column(type: 'text')]
    private ?string $websiteEn = null;

    #[ORM\Column(type: 'text')]
    private ?string $websiteFr = null;

    #[ORM\Column(type: 'text')]
    private ?string $emailEn = null;

    #[ORM\Column(type: 'text')]
    private ?string $emailFr = null;

    #[ORM\Column(type: 'text')]
    private $partner = null;

    #[ORM\Column(type: 'json')]
    private $bodyEn = null;

    #[ORM\Column(type: 'json')]
    private $bodyFr = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $modifiedDate;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $computedCanonicalRecordId;

    #[ORM\Column(type: 'json')]
    private $taxonomyEn = null;

    #[ORM\Column(type: 'json')]
    private $taxonomyFr = null;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: ServiceArea::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $serviceAreas;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: Address::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $addresses;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: PhoneNumber::class, cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    private Collection $phoneNumbers;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: Inquiry::class, cascade: ['persist'])]
    private Collection $inquiries;

    #[ORM\Column(type: 'integer')]
    private int $version;

    #[ORM\Column(type: 'boolean')]
    private $isDeleted;

    #[ORM\Column(type: 'json')]
    private $linkMetaData = null;

    #[ORM\Column(type: 'datetime')]
    private DateTime $linkMetaDataUpdateDate;

    #[ORM\Column(type: 'text')]
    private $delivery = null;

    private Result $result;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: DeliveryProvince::class)]
    private Collection $deliveryProvinces;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $dateOfFirstImport;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $dateOfLatestImport;

    #[ORM\Column(type: 'text')]
    private $topic = null;

    #[ORM\Column(type: 'json')]
    private $similar = null;

    private ?array $hydratedSimilarResources = [];

    public function updateVersion(ManagerRegistry $doctrine)
    {
        $importRepository = $doctrine->getRepository(Import::class);
        $importEntity = $importRepository->findOneBy(['dataPartner' => $this->getPartner()]);
        $latestImportVersion = 0;
        if ($importEntity) {
            $latestImportVersion = $importEntity->getVersion();
        }
        $this->setVersion($latestImportVersion + 1);
    }

    public function populate(ResourceInterface $record): Resource
    {
        $this->serviceAreas->clear();
        $this->addresses->clear();
        $this->phoneNumbers->clear();
        $this->deliveryProvinces->clear();

        if ($record->getPartner() === 'prosper') {
            if ($record->getLocale() !== 'fr-CA') {
                $this->setNameEn($record->getNameEn());
                $this->setBodyEn($record->getBodyEn());
                $this->setDescriptionEn($record->getDescriptionEn());
                $this->setWebsiteEn($record->getWebsiteEn());
                $this->setEmailEn($record->getEmailEn());
                $this->setTaxonomyEn($record->getTaxonomyEn());
            }
            if ($record->getLocale() !== 'en-CA') {
                $this->setNameFr($record->getNameFr());
                $this->setBodyFr($record->getBodyFr());
                $this->setDescriptionFr($record->getDescriptionFr());
                $this->setWebsiteFr($record->getWebsiteFr());
                $this->setEmailFr($record->getEmailFr());
                $this->setTaxonomyFr($record->getTaxonomyFr());
            }
            $this->setTopic($record->getTopic());
        } elseif ($record->getPartner() === '211') {
            if ($record->getLocale() === 'fr') {
                if (!$this->id) {
                    $this->setNameEn($record->getNameFr());
                    $this->setDescriptionEn($record->getDescriptionFr());
                    $this->setWebsiteEn($record->getWebsiteFr());
                    $this->setEmailEn($record->getEmailFr());
                    $this->setTopic($record->getTopic());
                }
                $this->setNameFr($record->getNameFr());
                $this->setBodyFr($record->getBodyFr());
                $this->setDescriptionFr($record->getDescriptionFr());
                $this->setWebsiteFr($record->getWebsiteFr());
                $this->setTaxonomyFr($record->getTaxonomyFr());
                $this->setEmailFr($record->getEmailFr());
            }
            if ($record->getLocale() === 'en') {
                $this->setNameEn($record->getNameEn());
                $this->setBodyEn($record->getBodyEn());
                $this->setDescriptionEn($record->getDescriptionEn());
                $this->setWebsiteEn($record->getWebsiteEn());
                $this->setTaxonomyEn($record->getTaxonomyEn());
                $this->setEmailEn($record->getEmailEn());
                $this->setTopic($record->getTopic());
            }
        } else {
            $this->setNameEn($record->getNameEn());
            $this->setBodyEn($record->getBodyEn());
            $this->setDescriptionEn($record->getDescriptionEn());
            $this->setWebsiteEn($record->getWebsiteEn());
            $this->setNameFr($record->getNameFr());
            $this->setBodyFr($record->getBodyFr());
            $this->setDescriptionFr($record->getDescriptionFr());
            $this->setWebsiteFr($record->getWebsiteFr());
            $this->setTaxonomyEn($record->getTaxonomyEn());
            $this->setTaxonomyFr($record->getTaxonomyFr());
            $this->setEmailEn($record->getEmailEn());
            $this->setEmailFr($record->getEmailFr());
            $this->setTopic($record->getTopic());
        }

        $this->partnerId = $record->getId();
        $this->partner = $record->getPartner();

        foreach ($record->getServiceAreas() as $row) {
            $serviceArea = new ServiceArea();
            $serviceArea->setName($row);
            $this->addServiceArea($serviceArea);
        }

        foreach ($record->getPhoneNumbers() as $row) {
            /** @var PhoneInterface $row */

            $phoneNumber = new PhoneNumber();
            $phoneNumber->setName($row->getName());
            $phoneNumber->setPhone($row->getPhone());
            $phoneNumber->setType($row->getType());
            $this->addPhoneNumber($phoneNumber);
        }

        $address = new Address();
        $address->setStreet1($record->getStreet1());
        $address->setStreet2($record->getStreet2());
        $address->setPostalCode($record->getPostalCode());
        $address->setCity($record->getCity());
        $address->setProvince($record->getProvince());
        $address->setCountry($record->getCountry());
        $address->setLat($record->getLat());
        $address->setLng($record->getLng());
        $address->setType('physical');
        $address->setResource($this);
        $this->addAddress($address);

        if ($record->getPartner() === '211') {
            $mailingAddress = new Address();
            $mailingAddress->setStreet1($record->getMailingAddressStreet1());
            $mailingAddress->setStreet2($record->getMailingAddressStreet2());
            $mailingAddress->setPostalCode($record->getMailingAddressPostalCode());
            $mailingAddress->setCity($record->getMailingAddressCity());
            $mailingAddress->setProvince($record->getMailingAddressProvince());
            $mailingAddress->setCountry($record->getMailingAddressCountry());
            $mailingAddress->setMailingAttentionName($record->getMailingAttentionName());
            $mailingAddress->setType('mailing');
            $mailingAddress->setResource($this);
            $this->addAddress($mailingAddress);
            $this->calculateTwoElevenCombinedDelivery();
        } else {
            $this->setDelivery($record->getDelivery());

            foreach ($record->getDeliveryProvinces() as $abbreviatedProvinceName) {
                $deliveryProvince = new DeliveryProvince();
                $deliveryProvince->setAbbreviatedName($abbreviatedProvinceName);
                $fullProvinceName = Provinces::getNameFromAbbreviation($abbreviatedProvinceName);
                $deliveryProvince->setFullName($fullProvinceName);
                $this->addDeliveryProvince($deliveryProvince);
            }
        }

        if ($record->getModifiedDate()) {
            $this->setModifiedDate($record->getModifiedDate());
        }
        $this->setCreatedDate($record->getCreatedDate());
        $this->setIsDeleted(false);

        return $this;
    }

    private function calculateTwoElevenCombinedDelivery()
    {
        $englishServiceAreas = [];
        $frenchServiceAreas = [];
        if (!empty($this->bodyEn)) {
            $englishServiceAreas = json_decode(json_encode($this->bodyEn))->serviceAreas;
        }
        if (!empty($this->bodyFr)) {
            $frenchServiceAreas = json_decode(json_encode($this->bodyFr))->serviceAreas;
        }

        $allServiceAreas = array_unique(array_merge((array)$englishServiceAreas, (array)$frenchServiceAreas));

        $this->delivery = TwoElevenResource::computeDelivery($allServiceAreas);
        $deliveryProvinces = TwoElevenResource::computeDeliveryProvinces($this->delivery, $allServiceAreas) ?? [];

        foreach ($deliveryProvinces as $abbreviatedProvinceName) {
            $deliveryProvince = new DeliveryProvince();
            $deliveryProvince->setAbbreviatedName($abbreviatedProvinceName);
            $fullProvinceName = Provinces::getNameFromAbbreviation($abbreviatedProvinceName);
            $deliveryProvince->setFullName($fullProvinceName);
            $this->addDeliveryProvince($deliveryProvince);
        }

        $this->setDateOfLatestImport(time());

        return $this;
    }

    public function __construct()
    {
        $this->serviceAreas = new ArrayCollection();
        $this->phoneNumbers = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->deliveryProvinces = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPartnerId(): ?string
    {
        return $this->partnerId;
    }

    public function setPartnerId($partnerId): void
    {
        $this->partnerId = $partnerId;
    }

    public function getName(): LocalizedString
    {
        $name = new LocalizedString();
        $name->en = $this->nameEn;
        $name->fr = $this->nameFr;
        return $name;
    }

    public function setNameEn($nameEn): void
    {
        $this->nameEn = $nameEn;
    }

    public function setNameFr($nameFr): void
    {
        $this->nameFr = $nameFr;
    }

    public function getDescription(): LocalizedString
    {
        $description = new LocalizedString();
        $description->en = $this->descriptionEn;
        $description->fr = $this->descriptionFr;
        return $description;
    }

    public function setDescriptionEn($descriptionEn): void
    {
        $this->descriptionEn = $descriptionEn;
    }

    public function setDescriptionFr($descriptionFr): void
    {
        $this->descriptionFr = $descriptionFr;
    }

    /**
     * @return Collection<int, ServiceArea>
     */
    public function getServiceAreas(): Collection
    {
        return $this->serviceAreas;
    }

    public function addServiceArea(ServiceArea $serviceArea): self
    {
        if (!$this->serviceAreas->contains($serviceArea)) {
            $this->serviceAreas->add($serviceArea);
            $serviceArea->setResource($this);
        }

        return $this;
    }

    public function removeServiceArea(ServiceArea $serviceArea): self
    {
        if ($this->serviceAreas->removeElement($serviceArea)) {
            // set the owning side to null (unless already changed)
            if ($serviceArea->getResource() === $this) {
                $serviceArea->setResource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PhoneNumber>
     */
    public function getPhoneNumbers(): Collection
    {
        return $this->phoneNumbers;
    }

    public function addPhoneNumber(PhoneNumber $phoneNumber): self
    {
        if (!$this->phoneNumbers->contains($phoneNumber)) {
            $this->phoneNumbers->add($phoneNumber);
            $phoneNumber->setResource($this);
        }

        return $this;
    }

    public function removePhoneNumber(PhoneNumber $phoneNumber): self
    {
        if ($this->phoneNumbers->removeElement($phoneNumber)) {
            // set the owning side to null (unless already changed)
            if ($phoneNumber->getResource() === $this) {
                $phoneNumber->setResource(null);
            }
        }

        return $this;
    }

    public function getModifiedDate(): ?\DateTimeInterface
    {
        return $this->modifiedDate ?? null;
    }

    public function setModifiedDate(?int $timestamp): void
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->modifiedDate = $dateTime;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?int $timestamp): void
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->createdDate = $dateTime;
    }

    public function getWebsite(): ?string
    {
        return $this->websiteEn;
    }

    public function getPartner(): ?string
    {
        return $this->partner;
    }

    public function setPartner($partner): void
    {
        $this->partner = $partner;
    }

    public function getBodyEn()
    {
        return $this->bodyEn;
    }

    public function setBodyEn($bodyEn): void
    {
        $this->bodyEn = $bodyEn;
    }

    public function getBodyFr()
    {
        return $this->bodyFr;
    }

    public function setBodyFr($bodyFr): void
    {
        $this->bodyFr = $bodyFr;
    }

    public function setWebsiteEn($websiteEn): void
    {
        $this->websiteEn = $websiteEn;
    }

    public function setWebsiteFr($websiteFr): void
    {
        $this->websiteFr = $websiteFr;
    }

    public function getEmailEn(): ?string
    {
        return $this->emailEn;
    }

    public function setEmailEn(?string $emailEn): self
    {
        $this->emailEn = $emailEn;

        return $this;
    }

    public function getEmailFr(): ?string
    {
        return $this->emailFr;
    }

    public function setEmailFr(?string $emailFr): self
    {
        $this->emailFr = $emailFr;

        return $this;
    }

    public function getComputedCanonicalRecordId(): ?string
    {
        return $this->computedCanonicalRecordId;
    }

    public function setComputedCanonicalRecordId(?string $recordId): self
    {
        $this->computedCanonicalRecordId = $recordId;

        return $this;
    }

    public function getTaxonomyEn()
    {
        return $this->taxonomyEn;
    }

    public function setTaxonomyEn($taxonomyEn): void
    {
        $this->taxonomyEn = $taxonomyEn;
    }

    public function getTaxonomyFr()
    {
        return $this->taxonomyFr;
    }

    public function setTaxonomyFr($taxonomyFr): void
    {
        $this->taxonomyFr = $taxonomyFr;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getLinkMetaData()
    {
        return $this->linkMetaData;
    }

    public function setLinkMetaData($linkMetaData): void
    {
        $this->linkMetaData = $linkMetaData;
    }

    public function getLinkMetaDataUpdateDate(): ?\DateTimeInterface
    {
        return $this->linkMetaDataUpdateDate;
    }

    public function setLinkMetaDataUpdateDate(?int $timestamp): void
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->linkMetaDataUpdateDate = $dateTime;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setResource($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getResource() === $this) {
                $address->setResource(null);
            }
        }

        return $this;
    }

    public function setResult(Result $result)
    {
        $this->result = $result;

        return $this;
    }

    public function getDelivery(): ?string
    {
        return $this->delivery;
    }

    public function setDelivery($delivery): void
    {
        $this->delivery = $delivery;
    }

    /**
     * @return Collection<int, DeliveryProvince>
     */
    public function getDeliveryProvinces(): Collection
    {
        return $this->deliveryProvinces;
    }

    public function getDeliveryProvincesValues(): array
    {
        $deliveryProvinces = $this->deliveryProvinces->toArray();
        return array_map(fn (DeliveryProvince $a) => $a->getAbbreviatedName(), $deliveryProvinces);
    }

    public function addDeliveryProvince(DeliveryProvince $deliveryProvince): self
    {
        if (!$this->deliveryProvinces->contains($deliveryProvince)) {
            $this->deliveryProvinces->add($deliveryProvince);
            $deliveryProvince->setResource($this);
        }

        return $this;
    }

    public function removeDeliveryProvince(DeliveryProvince $deliveryProvince): self
    {
        if ($this->deliveryProvinces->removeElement($deliveryProvince)) {
            if ($deliveryProvince->getResource() === $this) {
                $deliveryProvince->setResource(null);
            }
        }

        return $this;
    }

    public function getDateOfFirstImport(): ?\DateTimeInterface
    {
        return $this->dateOfFirstImport ?? null;
    }

    public function setDateOfFirstImport(?int $timestamp): void
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->dateOfFirstImport = $dateTime;
    }

    public function getDateOfLatestImport(): ?\DateTimeInterface
    {
        return $this->dateOfLatestImport ?? null;
    }

    public function setDateOfLatestImport(?int $timestamp): void
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->dateOfLatestImport = $dateTime;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic($topic): void
    {
        $this->topic = $topic;
    }

    public function getSimilar()
    {
        return $this->similar;
    }

    public function setSimilar($similar): void
    {
        $this->similar = $similar;
    }

    public function getHydratedSimilarResources()
    {
        return $this->hydratedSimilarResources;
    }

    public function setHydratedSimilarResources(?array $hydratedSimilarResources): void
    {
        $this->hydratedSimilarResources = $hydratedSimilarResources;
    }

    public function jsonSerialize(): mixed
    {
        $addresses = $this->getAddresses()->getValues();
        $address = current(array_filter($addresses, fn ($a) => $a->getType() === 'physical'));

        $result = [
            "id" => $this->id,
            "result" => $this->result ?? null,
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "topic" => $this->getTopic(),
            "website" => [
                "en" => $this->websiteEn,
                "fr" => $this->websiteFr
            ],
            "email" => [
                "en" => $this->emailEn,
                "fr" => $this->emailFr
            ],
            "address" => $address,
            "addresses" => $addresses,
            "phoneNumbers" => $this->getPhoneNumbers()->getValues(),
            "partner" => $this->partner,
            "body" => [
                "en" => $this->bodyEn,
                "fr" => $this->bodyFr
            ],
            "taxonomy" => [
                "en" => $this->taxonomyEn,
                "fr" => $this->taxonomyFr,
            ],
            "createdDate" => $this->createdDate,
            "linkMetaData" => $this->linkMetaData,
            'partnerId' => $this->partnerId,
            'updatedDate' => $this->modifiedDate,
            'delivery' => $this->delivery,
            'similar' => $this->similar,
        ];

        if (!empty($this->hydratedSimilarResources)) {
            $result["hydratedSimilarResources"] = $this->hydratedSimilarResources;
        }

        return $result;
    }

    public function getDocument(): string
    {
        $body = [];
        if($this->nameEn) {
            $body[] = $this->nameEn;
        }
        if($this->nameFr) {
            $body[] = $this->nameFr;
        }
        if($this->descriptionEn) {
            $body[] = $this->descriptionEn;
        }
        if($this->descriptionFr) {
            $body[] = $this->descriptionFr;
        }
        if($this->bodyEn) {
            $body[] = json_encode($this->bodyEn);
        }
        if($this->bodyFr) {
            $body[] = json_encode($this->bodyFr);
        }
        if($this->taxonomyEn) {
            $body[] = json_encode($this->taxonomyEn);
        }
        if($this->taxonomyFr) {
            $body[] = json_encode($this->taxonomyFr);
        }

        $body = implode(" ", $body);

        /**
         * The max token length for our model is 8191. In English 1 token is about 4 characters.
         * Setting our max to 7000 tokens (1750 chars) leaves us a buffer.
         */
        if((strlen($body) / 4) > 7000) {
            $body = substr($body, 0, 1750);
        }

        return $body;
    }

    public function getSqlReplaceValues(string $uuid, PDO $connection): string
    {
        $createdDate = $this->createdDate->format("Y-m-d H:i:s");
        $modifiedDate = isset($this->modifiedDate) ? "\"{$this->modifiedDate->format("Y-m-d H:i:s")}\", " : 'null, ';

        if (isset($this->dateOfFirstImport)) {
            $dateOfFirstImport = "\"{$this->dateOfFirstImport->format("Y-m-d H:i:s")}\", ";
        } else {
            $dateOfFirstImport = "\"{$GLOBALS['importTime']}\", ";
        }

        $dateOfLatestImport = "\"{$GLOBALS['importTime']}\"";

        $canonicalRecordId = 'null, ';
        if (isset($this->computedCanonicalRecordId)) {
            $canonicalRecordId = "\"$this->computedCanonicalRecordId\", ";
        }

        $linkMetaDataUpdateDate = 'null, ';
        if (isset($this->linkMetaDataUpdateDate)) {
            $linkMetaDataUpdateDate = "\"{$this->linkMetaDataUpdateDate->format("Y-m-d H:i:s")}\", ";
        }

        $isDeleted = $this->isDeleted ? '1' : '0';

        $valueString = '(';

        $valueString .= "\"{$uuid}\", ";
        $valueString .= "\"{$this->partnerId}\", ";
        $valueString .= ($this->websiteEn ? $connection->quote($this->websiteEn) : "''") . ", ";
        $valueString .= ($this->emailEn ? $connection->quote($this->emailEn) : "''") . ", ";
        $valueString .= $modifiedDate;
        $valueString .= "\"{$createdDate}\", ";
        $valueString .= ($this->nameEn ? $connection->quote($this->nameEn) : "''") . ", ";
        $valueString .= ($this->nameFr ? $connection->quote($this->nameFr) : "''") . ", ";
        $valueString .= ($this->descriptionEn ? $connection->quote($this->descriptionEn) : "''") . ", ";
        $valueString .= ($this->descriptionFr ? $connection->quote($this->descriptionFr) : "''") . ", ";
        $valueString .= $canonicalRecordId;
        $valueString .= "\"{$this->partner}\", ";
        $valueString .= $connection->quote(json_encode($this->bodyEn)) . ", ";
        $valueString .= $connection->quote(json_encode($this->bodyFr)) . ", ";
        $valueString .= ($this->websiteFr ? $connection->quote($this->websiteFr) : "''") . ", ";
        $valueString .= $connection->quote(json_encode($this->taxonomyEn)) . ", ";
        $valueString .= $connection->quote(json_encode($this->taxonomyFr)) . ", ";
        $valueString .= "\"{$this->version}\", ";
        $valueString .= "\"{$isDeleted}\", ";
        $valueString .= $connection->quote(json_encode($this->linkMetaData)) . ", ";
        $valueString .= $linkMetaDataUpdateDate;
        $valueString .= ($this->emailFr ? $connection->quote($this->emailFr) : "''") . ", ";
        $valueString .= "\"{$this->delivery}\", ";
        $valueString .= $connection->quote($this->topic) . ", ";
        $valueString .= $dateOfFirstImport;
        $valueString .= $dateOfLatestImport;

        $valueString .= ')';

        return $valueString;
    }

    public function getAddressTableSqlValues(string $resourceUuid, PDO $connection): RelatedEntitySqlValues
    {
        $addressSqlValues = new RelatedEntitySqlValues();

        $addresses = $this->addresses->getValues();

        if (!count($addresses)) {
            $addressSqlValues->deleteValues[] = "'$resourceUuid'";
            return $addressSqlValues;
        }

        /** @var $address Address */
        foreach ($addresses as $address) {
            $addressId = Uuid::uuid4();

            $lat = $address->getLat() ? "\"{$address->getLat()}\", " : 'null, ';
            $lng = $address->getLng() ? "\"{$address->getLng()}\", " : 'null, ';

            $addressValueString = '(';

            $addressValueString .= "\"{$addressId}\", ";
            $addressValueString .= "\"{$resourceUuid}\", ";
            $addressValueString .= $lat;
            $addressValueString .= $lng;
            $addressValueString .= ($address->getStreet1() ? $connection->quote($address->getStreet1()) : "''") . ", ";
            $addressValueString .= ($address->getStreet2() ? $connection->quote($address->getStreet2()) : "''") . ", ";
            $addressValueString .= ($address->getCity() ? $connection->quote($address->getCity()) : "''") . ", ";
            $addressValueString .= ($address->getPostalCode() ? $connection->quote($address->getPostalCode()) : "''") . ", ";
            $addressValueString .= ($address->getProvince() ? $connection->quote($address->getProvince()) : "''") . ", ";
            $addressValueString .= ($address->getCountry() ? $connection->quote($address->getCountry()) : "''") . ", ";
            $addressValueString .= ($address->getType() ? $connection->quote($address->getType()) : "''") . ", ";
            $addressValueString .= ($address->getMailingAttentionName() ? $connection->quote($address->getMailingAttentionName()) : "''") . "";

            $addressValueString .= ')';

            $addressSqlValues->deleteValues[] = "'$resourceUuid'";
            $addressSqlValues->insertValues[] = $addressValueString;
        }
        return $addressSqlValues;
    }

    public function getPhoneTableSqlValues(string $resourceUuid, PDO $connection): RelatedEntitySqlValues
    {
        $phoneSqlValues = new RelatedEntitySqlValues();

        $phoneNumbers = $this->phoneNumbers->getValues();

        if (!count($phoneNumbers)) {
            $phoneSqlValues->deleteValues[] = "'$resourceUuid'";
            return $phoneSqlValues;
        }

        /** @var $phoneNumber PhoneNumber */
        foreach ($phoneNumbers as $phoneNumber) {
            $phoneValueString = '(';

            $phoneValueString .= "\"{$resourceUuid}\", ";
            $phoneValueString .= ($phoneNumber->getPhone() ? $connection->quote($phoneNumber->getPhone()) : "''") . ", ";
            $phoneValueString .= ($phoneNumber->getName() ? $connection->quote($phoneNumber->getName()) : "''") . ", ";
            $phoneValueString .= ($phoneNumber->getType() ? $connection->quote($phoneNumber->getType()) : "''") . "";

            $phoneValueString .= ')';

            $phoneSqlValues->deleteValues[] = "'$resourceUuid'";
            $phoneSqlValues->insertValues[] = $phoneValueString;
        }

        return $phoneSqlValues;
    }

    public function getDeliveryProvinceSqlValues(string $resourceUuid): RelatedEntitySqlValues
    {

        $deliveryProvinceSqlValues = new RelatedEntitySqlValues();

        $deliveryProvinces = $this->getDeliveryProvinces()->getValues();

        if (!count($deliveryProvinces) || $this->getDelivery() !== 'provincial') {
            $deliveryProvinceSqlValues->deleteValues[] = "'$resourceUuid'";
            return $deliveryProvinceSqlValues;
        }

        /** @var $deliveryProvince DeliveryProvince */
        foreach ($deliveryProvinces as $deliveryProvince) {
            $deliveryProvinceValueString = '(';

            $deliveryProvinceValueString .= "\"{$resourceUuid}\", ";
            $deliveryProvinceValueString .= "\"{$deliveryProvince->getAbbreviatedName()}\", ";
            $deliveryProvinceValueString .= "\"{$deliveryProvince->getFullName()}\"";

            $deliveryProvinceValueString .= ')';

            $deliveryProvinceSqlValues->deleteValues[] = "'$resourceUuid'";
            $deliveryProvinceSqlValues->insertValues[] = $deliveryProvinceValueString;
        }

        return $deliveryProvinceSqlValues;
    }
}
