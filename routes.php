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

                $this->map(['GET', 'PUT'], '/edit', controller\student\Edit::class)
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

                $this->get('/download/{version}[/{type}]', controller\admin\export\Download::class)
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

                    $this->map(['GET', 'PUT', 'DELETE', 'POST'], '/{id}', controller\admin\entries\Edit::class)
                    ->setName('subjects-admin-entries-edit');
                });

                $this->group('/subjects', function () {
                    $this->map(['GET', 'PUT'], '', controller\admin\subjects\Dash::class)
                    ->setName('subjects-admin-subjects-dash');

                    $this->map(['GET', 'PUT', 'POST'], '/{id}', controller\admin\subjects\Edit::class)
                    ->setName('subjects-admin-subjects-edit');

                    $this->post('/{id}/upload', controller\admin\subjects\Upload::class)
                    ->setName('subjects-admin-subjects-upload');
                });

                $this->group('/stats', function () {
                    $this->map(['GET', 'PUT'], '', controller\admin\stats\Dash::class)
                    ->setName('subjects-admin-stats-dash');

                    $this->map(['PUT', 'DELETE'], '/edit', controller\admin\stats\Edit::class)
                    ->setName('subjects-admin-stats-edit');
                });

                $this->group('/export', function () {
                    $this->get('', controller\admin\export\Dash::class)
                    ->setName('subjects-admin-export-dash');

                    $this->get('/download/{version}[/{type}]', controller\admin\export\Download::class)
                    ->setName('subjects-admin-export-download');
                });

                $this->group('/timers', function () {
                    $this->get('', controller\admin\timers\Dash::class)
                    ->setName('subjects-admin-timers-dash');

                    $this->put('/edit', controller\admin\timers\Edit::class)
                    ->setName('subjects-admin-timers-edit');

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
