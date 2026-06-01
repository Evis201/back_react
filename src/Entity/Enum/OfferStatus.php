<?php

namespace App\Entity\Enum;

enum OfferStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Closed    = 'closed';
}
