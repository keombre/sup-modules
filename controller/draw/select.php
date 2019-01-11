<?php

namespace modules\lists\controller\draw;

class select extends \sup\controller {
    function __invoke($request, $response, $args) {
        return $this->sendResponse($request, $response, "draw/select.phtml");
    }
}
