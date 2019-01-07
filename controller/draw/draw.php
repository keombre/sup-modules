<?php

namespace modules\lists\controller\draw;

class draw extends \sup\controller {
    function __invoke($request, $response, $args) {
        return $this->sendResponse($request, $response, "draw/draw.phtml");
    }
}
