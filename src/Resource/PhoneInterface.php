<?php

namespace App\Resource;

interface PhoneInterface
{
    public function getName(): string;
    public function getPhone(): string;
    public function getType(): string;
}
