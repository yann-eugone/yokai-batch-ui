<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Model\JobFilter;
use App\Form\Type\JobFilterType;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;
use Yokai\Batch\Storage\QueryBuilder;

#[Route(path: '/admin/jobs')]
final class JobController extends AbstractController
{
    private const LIMIT = 20;

    public function __construct(
        private QueryableJobExecutionStorageInterface $jobExecutionStorage,
        private TemplateRegistryInterface $sonataTemplates,
        private FormFactoryInterface $formFactory,
    ) {
    }

    #[Route(path: '/list', name: 'admin_job_list')]
    public function list(Request $request): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_***'); todo secure with role (or remove)

        $page = $request->query->getInt('page', 1);
        $sort = (string)$request->query->get('sort', Query::SORT_BY_START_DESC);

        $query = new QueryBuilder();

        $filter = $this->formFactory->createNamed(
            'filter',
            JobFilterType::class,
            $data = new JobFilter(),
            [
                'method' => Request::METHOD_GET,
                'csrf_protection' => false,
            ]
        );
        $filter->handleRequest($request);

        try {
            $query->jobs($data->jobs);
            $query->statuses($data->statuses);
            $query->limit(self::LIMIT, self::LIMIT * ($page - 1));
            $query->sort($sort);
        } catch (Throwable $exception) {
            throw new BadRequestHttpException(null, $exception);
        }

        // transform iterable executions to array
        $executions = [];
        foreach ($this->jobExecutionStorage->query($query->getQuery()) as $execution) {
            $executions[] = $execution;
        }

        // find out which filters were used
        $filters = [];
        foreach (\get_object_vars($data) as $field => $value) {
            if (!empty($value)) {
                $filters[] = $field;
            }
        }
        // prepare sort variable for view
        $sort = [
            'parameter' => 'sort',
            'current' => $sort,
            'desc' => \in_array($sort, [Query::SORT_BY_START_DESC, Query::SORT_BY_END_DESC], true),
            // sort by execution start info
            'start' => [
                'switch' => $sort === Query::SORT_BY_START_DESC ? Query::SORT_BY_START_ASC : Query::SORT_BY_START_DESC,
                'sorted' => \in_array($sort, [Query::SORT_BY_START_ASC, Query::SORT_BY_START_DESC], true),
            ],
            // sort by execution end info
            'end' => [
                'switch' => $sort === Query::SORT_BY_END_DESC ? Query::SORT_BY_END_ASC : Query::SORT_BY_END_DESC,
                'sorted' => \in_array($sort, [Query::SORT_BY_END_ASC, Query::SORT_BY_END_DESC], true),
            ],
        ];
        // prepare pagination variable for view
        $pagination = [
            'parameter' => 'page',
            'per_page' => self::LIMIT,
            'results' => \count($executions),
            'current' => $page,
            'is' => [
                'first' => $page === 1,
                'last' => \count($executions) !== self::LIMIT,
            ],
            'prev' => ['enabled' => $page !== 1, 'value' => $page - 1],
            'next' => ['enabled' => \count($executions) === self::LIMIT, 'value' => $page + 1],
        ];

        return $this->render(
            'admin/job/list.html.twig',
            [
                'base_template' => $this->sonataTemplates->getTemplate('layout'),
                'filter_template' => $this->sonataTemplates->getTemplate('filter'),
                'executions' => $executions,
                'form' => $filter->createView(),
                'sort' => $sort,
                'filters' => $filters,
                'pagination' => $pagination,
            ]
        );
    }

    #[Route(path: '/{job}/{id}', name: 'admin_job_show')]
    public function show(string $job, string $id): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_***'); todo secure with role (or remove)

        try {
            $execution = $this->jobExecutionStorage->retrieve($job, $id);
        } catch (JobExecutionNotFoundException $exception) {
            throw $this->createNotFoundException('Not Found', $exception);
        }

        return $this->render(
            'admin/job/show.html.twig',
            [
                'base_template' => $this->sonataTemplates->getTemplate('layout'),
                'execution' => $execution,
            ]
        );
    }

    #[Route(path: '/{job}/{id}/download-logs', name: 'admin_job_download_logs')]
    public function downloadLogs(string $job, string $id): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_***'); todo secure with role (or remove)

        try {
            $execution = $this->jobExecutionStorage->retrieve($job, $id);
        } catch (JobExecutionNotFoundException $exception) {
            throw $this->createNotFoundException('Not Found', $exception);
        }

        $filename = \sprintf('%s-%s.log', $execution->getJobName(), $execution->getId());
        $response = new Response((string)$execution->getLogs());
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );
        $response->headers->set('Content-Type', 'application/log');

        return $response;
    }
}
