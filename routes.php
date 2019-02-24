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

                $this->get('/generate/{id}', controller\student\Generate::class)
                ->setName('subjects-student-generate');
            })
            ->add(\middleware\auth\student::class)
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

                $this->get('/generate/{id}', controller\teacher\Generate::class)
                ->setName('subjects-teacher-generate');
            })
            ->add(\middleware\auth\teacher::class);

            // ------- admin -------
            $this->group('/admin', function () {

                $this->get('', controller\admin\Dash::class)
                ->setName('subjects-admin');

                $this->group('/entries', function () {
                    $this->get('', controller\admin\entries\Dash::class)
                    ->setName('subjects-admin-entries-dash');

                    $this->map(['GET', 'PUT'], '/{id}', controller\admin\entries\Edit::class)
                    ->setName('subjects-admin-entries-edit');
                });
                
                $this->group('/subjects', function () {
                    $this->get('', controller\admin\subjects\Dash::class)
                    ->setName('subjects-admin-subjects-dash');

                    $this->map(['GET', 'PUT', 'POST'], '/{id}', controller\admin\subjects\Edit::class)
                    ->setName('subjects-admin-subjects-edit');

                    $this->post('/{id}/upload', controller\admin\subjects\Upload::class)
                    ->setName('subjects-admin-subjects-upload');
                });

                $this->group('/stats', function () {
                    $this->get('', controller\admin\stats\Dash::class)
                    ->setName('subjects-admin-stats-dash');

                    $this->map(['PUT', 'DELETE'], '/edit', controller\admin\stats\Edit::class)
                    ->setName('subjects-admin-stats-edit');
                });

                /*
                $this->get('/download', controller\admin\Download::class)
                ->setName('subjects-admin-download');

                $this->put('/create', controller\admin\Create::class)
                ->setName('subjects-admin-create');

                $this->post('/settings', controller\admin\Settings::class)
                ->setName('subjects-admin-settings');

                $this->map(['GET', 'PUT'], '/manage/{id}', controller\admin\Manage::class)
                ->setName('subjects-admin-manage');

                */
            })->add(\middleware\auth\admin::class);
        })->add(\middleware\layout::class);
    }
}
