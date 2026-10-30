<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case Experience = 'experience';
    case Project = 'project';
    case Education = 'education';
    case Certification = 'certification';
    case Achievement = 'achievement';
    case Skill = 'skill';

    public function label(): string
    {
        return match ($this) {
            self::Experience => __('Work experience'),
            self::Project => __('Project'),
            self::Education => __('Education'),
            self::Certification => __('Certification'),
            self::Achievement => __('Achievement'),
            self::Skill => __('Skill'),
        };
    }
}
