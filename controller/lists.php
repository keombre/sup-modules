<?php

namespace modules\lists\controller;

abstract class lists extends \sup\controller implements \sup\roleActions {

    protected $userID;
    protected $listID = null;
    protected $settings;

    function __construct(\Slim\Container $container) {
        parent::__construct($container);
        
        $this->settings = $this->db->get("settings", "*");
        $this->userID = $this->container->auth->getUser()->getID();
    }

    function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
        if (array_key_exists('id', $args))
            $this->listID = filter_var($args['id'], FILTER_SANITIZE_STRING);
            
        $userLevel = $this->container->auth->getUser()->getAttribute('activeRole');
        switch ($userLevel) {
            case ROLE_STUDENT: return $this->student($request, $response, $args);
            case ROLE_TEACHER: return $this->teacher($request, $response, $args);
            case ROLE_ADMIN:   return $this->admin  ($request, $response, $args);
            
            default: $response->withRedirect($this->container->router->pathFor('dashboard'), 301); break;
        }
        return $response;
    }

    function withListID($listID) {
        $clone = clone $this;
        $clone->listID = $listID;
        return $clone;
    }
}
