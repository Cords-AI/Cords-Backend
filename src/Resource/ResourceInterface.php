<?php

namespace App\Resource;

interface ResourceInterface
{
    public function getId(): string;
    public function getSource(): string;
    public function getSourceJson(): string;
    public function getPartner(): string;
    public function getNameEn(): string;
    public function getDescriptionEn(): string;
    public function getWebsiteEn(): string;
    public function getEmailEn(): string;
    public function getEmailFr(): string;
    public function getServiceAreas(): array;
    public function getPhoneNumbers(): array;
    public function getStreet1(): string;
    public function getStreet2(): string;
    public function getPostalCode(): string;
    public function getCity(): string;
    public function getProvince(): string;
    public function getCountry(): string;
    public function getLat(): ?float;
    public function getLng(): ?float;
    public function getModifiedDate(): ?int;
    public function getBodyEn();
    public function getBodyFr();
    public function getDescriptionFr(): string;
    public function getNameFr(): string;
    public function getWebsiteFr(): string;
    public function getLocale(): string;
    public function getTaxonomyEn(): ?array;
    public function getTaxonomyFr(): ?array;
    public function getCreatedDate(): int;
    public function getMailingAddressStreet1(): string;
    public function getMailingAddressStreet2(): string;
    public function getMailingAddressPostalCode(): string;
    public function getMailingAddressCity(): string;
    public function getMailingAddressProvince(): string;
    public function getMailingAddressCountry(): string;
    public function getMailingAttentionName(): string;
    public function getHash(): string;
    public function getDelivery(): string;
    public function getDeliveryProvinces(): array;
    public function getTopic(): string;
}
