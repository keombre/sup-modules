<?php declare(strict_types=1);

namespace modules\subjects;

use \Slim\App;

class Routes
{
    public function __construct(App $app)
    {
        $app->get('', controller\Dispatch::class)->setName('subjects');

        $app->group('', function () {
            $this->group('/student', function () {
                $this->get('', controller\student\Dash::class)
                ->setName('subjects-student');
            })->add(\middleware\auth\Student::class);

            $this->group('/teacher', function () {
                $this->get('', controller\teacher\Dash::class)
                ->setName('subjects-teacher');
            })->add(\middleware\auth\Teacher::class);

            $this->group('/admin', function () {
                $this->get('', controller\admin\Dash::class)
                ->setName('subjects-admin');

                $this->get('/download', controller\admin\Download::class)
                ->setName('subjects-admin-download');

                $this->put('/create', controller\admin\Create::class)
                ->setName('subjects-admin-create');

                $this->post('/settings', controller\admin\Settings::class)
                ->setName('subjects-admin-settings');

                $this->map(['GET', 'PUT'], '/manage/{id}', controller\admin\Manage::class)
                ->setName('subjects-admin-manage');
            })->add(\middleware\auth\admin::class);
        })->add(\middleware\layout::class);
    }
}
