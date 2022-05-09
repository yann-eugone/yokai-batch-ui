<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Form\Type\JobFilterType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterJobsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $jobs = [];
        foreach ($container->findTaggedServiceIds('yokai_batch.job') as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $jobs[$attributes['job'] ?? $serviceId] = true;
            }
        }

        $container->getDefinition(JobFilterType::class)
            ->setArgument('$jobs', \array_keys($jobs));
    }
}
