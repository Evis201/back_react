<?php

namespace App\Entity\Enum;

enum OfferType: string
{
    case Job        = 'job';
    case Internship = 'internship';
    case Alternance = 'alternance';
}
