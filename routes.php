<?php declare(strict_types=1);

namespace modules\subjects;

use \Slim\App;

class routes
{
    public function __construct(App $app)
    {
        $app->get('', controller\Dispatch::class)->setName('subjects');

        $app->group('', function () {
            $this->group('/student', function () {
                $this->get('', controller\student\dash::class)
                ->setName('subjects-student');
            })->add(\middleware\auth\student::class);

            $this->group('/teacher', function () {
                $this->get('', controller\teacher\dash::class)
                ->setName('subjects-teacher');
            })->add(\middleware\auth\teacher::class);

            $this->group('/admin', function () {
                $this->get('', controller\admin\dash::class)
                ->setName('subjects-admin');

                $this->get('/download', controller\admin\download::class)
                ->setName('subjects-admin-download');

                $this->put('/create', controller\admin\create::class)
                ->setName('subjects-admin-create');

                $this->post('/settings', controller\admin\settings::class)
                ->setName('subjects-admin-settings');

                $this->map(['GET', 'PUT'], '/manage/{id}', controller\admin\manage::class)
                ->setName('subjects-admin-manage');
            })->add(\middleware\auth\admin::class);
        })->add(\middleware\layout::class);
    }
}
