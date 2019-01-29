<?php declare(strict_types=1);

namespace modules\subjects;

use \Slim\App;

class Routes
{
    public function __construct(App $app)
    {
        $app->get('', controller\Dispatch::class)->setName('subjects');

        $app->group('', function () {
            // ------- student -------
            $this->group('/student', function () {
                $this->get('', controller\student\Dash::class)
                ->setName('subjects-student');

                $this->map(['GET', 'PUT'], '/edit[/[{id}]]', controller\student\Edit::class)
                ->setName('subjects-student-edit');
                
                $this->map(['GET', 'PUT'], '/preview/{id}', controller\student\Preview::class)
                ->add(middleware\student\Validate::class)
                ->setName('subjects-student-preview');

                $this->get('/generate/{id}', controller\student\generatePDF::class)
                ->setName('subjects-student-generate');
            })
            ->add(\middleware\auth\Student::class)
            ->add(middleware\student\OpenEditing::class);

            // ------- teacher -------
            $this->group('/teacher', function () {
                $this->get('', controller\teacher\Dash::class)
                ->setName('subjects-teacher');

                $this->map(['GET', 'PUT'], '/accept[/{id}]', controller\teacher\Accept::class)
                ->setName('subjects-teacher-accept')
                ->add(middleware\teacher\OpenAccepting::class);
                
                $this->get('/view', controller\teacher\View::class)
                ->setName('subjects-teacher-view');

                $this->get('/preview/{id}', controller\student\Preview::class)
                ->setName('subjects-teacher-preview');
            })
            ->add(\middleware\auth\Teacher::class);

            // ------- admin -------
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
            })->add(\middleware\auth\Admin::class);
        })->add(\middleware\layout::class);
    }
}
