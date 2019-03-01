<?php

namespace modules\lists;

class routes {
    function __construct(\Slim\App $app) {
        $app->group('', function () {
            $this->get('', controller\view::class)
            ->setName('lists');

            $this->group('', function () {
                $this->get('/view[/{id}]', controller\view::class)
                ->setName('lists-view');

                $this->map(['GET', 'PUT', 'DELETE'], '/edit[/{id}]', controller\edit::class)
                ->setName('lists-edit')
                ->add(\middleware\auth\student::class);

                $this->map(['GET', 'PUT'], '/validate/{id}', controller\validate::class)
                ->setName('lists-validate')
                ->add(\middleware\auth\student::class);

                $this->map(['GET', 'PUT'], '/preview/{id}', controller\preview::class)
                ->setName('lists-preview');

                $this->get('/generate/{id}', controller\student\generate::class)
                ->setName('lists-generate');
            })
            ->add(middleware\listID::class)
            ->add(middleware\open_editing::class);

            $this->group('/teacher', function () {

                $this->map(['GET', 'PUT'], '/accept[/{id}]', controller\teacher\accept::class)
                ->setName('lists-teacher-accept')
                ->add(middleware\open_accepting::class);

                $this->get('/view', controller\teacher\view::class)
                ->setName('lists-teacher-view');

                $this->get('/generate/{id}', controller\teacher\generate::class)
                ->setName('lists-teacher-generate');
            })
            ->add(\middleware\auth\teacher::class);

            $this->group('/admin', function () {
                $this->get('/download', controller\download\generate::class)
                ->setName('lists-admin-download');

                $this->put('/create', controller\admin\create::class)
                ->setName('lists-admin-create');

                $this->post('/settings', controller\admin\settings::class)
                ->setName('lists-admin-settings');

                $this->map(['GET', 'PUT'], '/manage/{id}', controller\admin\manage::class)
                ->setName('lists-admin-manage');
            })->add(\middleware\auth\admin::class);
            
        })->add(\middleware\layout::class);

    }
}
