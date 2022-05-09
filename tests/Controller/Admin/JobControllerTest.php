<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobExecutionLogs;
use Yokai\Batch\JobParameters;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Summary;

class JobControllerTest extends WebTestCase
{
    private const JOBS = ['job.foo', 'job.bar', 'job.baz'];
    private const STATUSES = [
        BatchStatus::PENDING,
        BatchStatus::RUNNING,
        BatchStatus::COMPLETED,
        BatchStatus::FAILED,
    ];

    /**
     * @before
     */
    public static function clearJobStorage(): void
    {
        (new Filesystem())->remove(__DIR__ . '/../../../var/batch/'); // todo if using another storage, this should be changed
    }

    public function testList(): void
    {
        $http = self::createClient();
        // todo login (or remove)

        self::createManyJobExecution(30);

        $page = $http->request('get', '/admin/jobs/list');

        self::assertResponseIsSuccessful();
        self::assertCount(20, $page->filter('.sonata-ba-list > tbody > tr'));
        $pagination = $page->filter('.pagination a[href]');
        self::assertCount(1, $pagination);

        $page = $http->click($pagination->link());
        self::assertResponseIsSuccessful();
        self::assertCount(10, $page->filter('.sonata-ba-list > tbody > tr'));
    }

    public function testShow(): void
    {
        $http = self::createClient();
        // todo login (or remove)

        $execution = JobExecution::createRoot(
            'job-execution-id',
            'job-name',
            new BatchStatus(BatchStatus::COMPLETED),
            new JobParameters(['parameter-name' => 'parameter-value']),
            new Summary(['summary-name' => 'summary-value']),
        );
        $execution->setStartTime(new DateTimeImmutable('2021-01-01 10:00'));
        $execution->setEndTime(new DateTimeImmutable('2021-01-01 11:00'));
        self::storeJobExecution($execution);

        $http->request('get', '/admin/jobs/job-name/job-execution-id');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.job-show-field-id td', 'job-execution-id');
        self::assertSelectorTextSame('.job-show-field-jobName td', 'job.job_name.job-name');
        self::assertSelectorTextSame('.job-show-field-status td', 'Completedught PHP Exception Symfony\Component\HttpKernel\Exception\NotFoundHttpException: "Not Found" ');
        self::assertSelectorTextSame('.job-show-field-startTime td', 'January 1, 2021 10:00');
        self::assertSelectorTextSame('.job-show-field-endTime td', 'January 1, 2021 11:00');
        self::assertSelectorTextContains('.job-show-parameters code', '"parameter-name": "parameter-value"');
        self::assertSelectorTextContains('.job-show-summary code', '"summary-name": "summary-value"');
    }

    public function testShowNotFound(): void
    {
        $http = self::createClient();
        // todo login (or remove)

        $http->request('get', '/admin/jobs/unknown-name/unknown-id');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDownloadLogs(): void
    {
        $http = self::createClient();
        // todo login (or remove)

        $execution = JobExecution::createRoot(
            'job-execution-id',
            'job-name',
            new BatchStatus(BatchStatus::COMPLETED),
            null,
            null,
            new JobExecutionLogs($logs = <<<LOG
            [2021-01-01T10:00:00.000000+01:00] INFO: Lorem ipsum []
            [2021-01-01T10:30:00.000000+01:00] DEBUG: Dolor sit amet []
            LOG
            )
        );
        self::storeJobExecution($execution);

        $http->request('get', '/admin/jobs/job-name/job-execution-id/download-logs');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=job-name-job-execution-id.log');
        self::assertResponseHeaderSame('Content-Type', 'application/log');
        self::assertSame($logs, $http->getResponse()->getContent());
    }

    public function testDownloadLogsNotFound(): void
    {
        $http = self::createClient();
        // todo login (or remove)

        $http->request('get', '/admin/jobs/unknown-name/unknown-id/download-logs');

        self::assertResponseStatusCodeSame(404);
    }

    private static function createManyJobExecution(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $execution = JobExecution::createRoot(
                \uniqid(),
                self::JOBS[\array_rand(self::JOBS)],
                new BatchStatus(self::STATUSES[\array_rand(self::STATUSES)]),
            );
            if (!$execution->getStatus()->is(BatchStatus::PENDING)) {
                $execution->setStartTime(new DateTimeImmutable('30 minutes ago'));
                if (!$execution->getStatus()->is(BatchStatus::RUNNING)) {
                    $execution->setEndTime(new DateTimeImmutable('25 minutes ago'));
                }
            }
            self::storeJobExecution($execution);
        }
    }

    private static function storeJobExecution(JobExecution $execution): void
    {
        self::getContainer()->get(JobExecutionStorageInterface::class)->store($execution);
    }
}
