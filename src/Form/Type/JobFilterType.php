<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\Model\JobFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yokai\Batch\BatchStatus;

final class JobFilterType extends AbstractType
{
    private const STATUSES = [
        'pending' => BatchStatus::PENDING,
        'running' => BatchStatus::RUNNING,
        'stopped' => BatchStatus::STOPPED,
        'completed' => BatchStatus::COMPLETED,
        'abandoned' => BatchStatus::ABANDONED,
        'failed' => BatchStatus::FAILED,
    ];

    public function __construct(private array $jobs)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $jobChoices = array_combine($this->jobs, $this->jobs);
        $jobChoiceLabel = fn($choice, string $key, string $value) => sprintf('job.job_name.%s', $key);

        $statusesChoices = self::STATUSES;
        $statusesChoiceLabel = fn($choice, string $key, string $value) => sprintf('job.status.%s', $key);

        $builder
            ->add(
                'jobs',
                ChoiceType::class,
                [
                    'label' => 'job.field.job_name',
                    'choice_label' => $jobChoiceLabel,
                    'choices' => $jobChoices,
                    'required' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'statuses',
                ChoiceType::class,
                [
                    'label' => 'job.field.status',
                    'choice_label' => $statusesChoiceLabel,
                    'choices' => $statusesChoices,
                    'required' => false,
                    'multiple' => true,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('translation_domain', 'admin'); // todo or change with your catalog
        $resolver->setDefault('data_class', JobFilter::class);
    }
}
