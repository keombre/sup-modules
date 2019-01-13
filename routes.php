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
            })
            ->add(middleware\listID::class)
            ->add(middleware\open_editing::class);

            $this->group('/teacher', function () {

                $this->map(['GET', 'PUT'], '/accept[/{id}]', controller\teacher\accept::class)
                ->setName('lists-teacher-accept')
                ->add(middleware\open_accepting::class);

                $this->get('/view[/{id}]', controller\teacher\view::class)
                ->setName('lists-teacher-view');
            })
            ->add(\middleware\auth\teacher::class);

            $this->group('/admin', function () {
                $this->put('/create', controller\admin\create::class)
                ->setName('lists-admin-create');

                $this->post('/settings', controller\admin\settings::class)
                ->setName('lists-admin-settings');

                $this->map(['GET', 'PUT'], '/manage/{id}', controller\admin\manage::class)
                ->setName('lists-admin-manage');
            })->add(\middleware\auth\admin::class);
            
        })->add(\middleware\layout::class);

        $app->group('/draw', function () {
            $this->get('', controller\draw\select::class)
            ->setName('draw');

            $this->group('/api/v1', function () {
                $this->get('/lists', controller\draw\api::class . ':lists');
                $this->get('/draws', controller\draw\api::class . ':draws');
                $this->get('/{list}', controller\draw\api::class . ':books');
                $this->get('/{list}/draw/{book}', controller\draw\api::class . ':draw');
                $this->get('/{list}/revoke/{book}', controller\draw\api::class . ':revoke');
            });
        })
        ->add(middleware\open_drawing::class);

    }
}
