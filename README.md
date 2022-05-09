# Yokai Batch Sonata Admin UI

This project is a demo UI for [`yokai/batch` suite](https://github.com/yokai-php/batch-src).

It is setup in a [`Symfony`](https://github.com/symfony/symfony) + [`SonataAdmin`](https://github.com/sonata-project/SonataAdminBundle).

Files you should read:

- [`JobController`](src/Controller/Admin/JobController.php): actions to list / filter / show / download job executions
- [`list.html.twig`](templates/admin/job/list.html.twig): template for job execution list / filter (sonata look and feel)
- [`show.html.twig`](templates/admin/job/show.html.twig): template for job execution details (sonata look and feel)
- [`sonata_admin.yaml`](config/packages/sonata_admin.yaml): a menu is added to point to job executions list
- [`RegisterJobsPass`](src/DependencyInjection/RegisterJobsPass.php): collects jobs to inject in form filter
- [`JobFilterType`](src/Form/Type/JobFilterType.php): form filter for executions with job name and status
- [`JobControllerTest`](tests/Controller/Admin/JobControllerTest.php): functional tests for all actions
