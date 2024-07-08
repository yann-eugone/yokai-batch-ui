# Yokai Batch Sonata Admin UI

> [!WARNING]  
> Since https://github.com/yokai-php/batch-src/pull/104 the `yokai/batch` bundle has everything demoed in this repository.
> You can still have a look to the code here, but you will find most of it in the bundle anyway.

This project is a demo UI for [`yokai/batch` suite](https://github.com/yokai-php/batch-src).

![image](https://github.com/yann-eugone/yokai-batch-ui/assets/1303838/af320128-15f3-4abf-a531-ffab98e3e57f)
![image](https://github.com/yann-eugone/yokai-batch-ui/assets/1303838/1485c4b6-78b5-415d-b64a-6bcf0d0a2769)

It is living in a [`Symfony`](https://github.com/symfony/symfony) + [`SonataAdmin`](https://github.com/sonata-project/SonataAdminBundle) project.

Files you should read:

- [`JobController`](src/Controller/Admin/JobController.php): actions to list / filter / show / download job executions
- [`list.html.twig`](templates/admin/job/list.html.twig): template for job execution list / filter (sonata look and feel)
- [`show.html.twig`](templates/admin/job/show.html.twig): template for job execution details (sonata look and feel)
- [`sonata_admin.yaml`](config/packages/sonata_admin.yaml): a menu is added to point to job executions list
- [`RegisterJobsPass`](src/DependencyInjection/RegisterJobsPass.php): collects jobs to inject in form filter
- [`JobFilterType`](src/Form/Type/JobFilterType.php): form filter for executions with job name and status
- [`JobControllerTest`](tests/Controller/Admin/JobControllerTest.php): functional tests for all actions
