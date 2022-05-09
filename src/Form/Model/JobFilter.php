<?php

declare(strict_types=1);

namespace App\Form\Model;

final class JobFilter
{
    /**
     * @var string[]
     */
    public array $jobs = [];

    /**
     * @var int[]
     */
    public array $statuses = [];
}
