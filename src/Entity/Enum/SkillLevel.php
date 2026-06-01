<?php

namespace App\Entity\Enum;

enum SkillLevel: string
{
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced     = 'advanced';
    case Expert       = 'expert';
}
