<?php

namespace modules\lists\controller;

class preview extends lists {

    protected $state = null;

    public function student($request, &$response, $args) {
        $state = $this->db->get('main', 'state', ['id' => $this->listID]);

        if ($state == 0)
            return $response->withRedirect($this->container->router->pathFor('lists-edit', ["id" => $this->listID]), 301);

        return $this->preview($request, $response, $args);
    }

    public function teacher($request, &$response, $args) {
        $response->getBody()->write("kitten");
    }

    public function admin($request, &$response, $args) {
        $response->getBody()->write("kitten");
    }

    public function preview($request, &$response, $args) {
        
        $versionName = $this->db->get('versions', 'name [String]', ['id' => $this->settings['active_version']]);
        
        $listInfo = $this->db->get('main', ['state', 'user'], ['id' => $this->listID]);

        $list = [
            //'user' => (new \sup\User($this->container->base))->createFromDB($listInfo['user']),
            'user' => $this->container->factory->userFromID($listInfo['user']),
            'state' => $listInfo['state'],
            'id' => $this->listID,
            'books' => $this->db->select('lists', ['[>]books' => ['book' => 'id']], [
                'books.id [Int]',
                'books.name [String]',
                'books.author [String]'
            ], ['lists.list' => $this->listID, 'ORDER' => 'books.id'])
        ];

        /*
        $qrURL = (string) $request
                ->getUri()
                ->withPath($this->container->router->pathFor("lists-teacher-accept", ["id" => $this->formatBarcode($list)]))
                ->withQuery(http_build_query(['b' => base64_encode(implode('-', array_column($list['books'], 'id')))]))
                ->withFragment("");
        
        $qrcode = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        ])))->render($qrURL);*/
        
        //$generatorPNG = new \Picqer\Barcode\BarcodeGeneratorPNG();
        //$barcode = base64_encode($generatorPNG->getBarcode($this->formatBarcode($list), $generatorPNG::TYPE_CODE_39E, 1.5));

        $this->sendResponse($request, $response, "preview.phtml", [
            /*"barcode" => $barcode,
            "qrcode" => $qrcode,*/
            "versionName" => $versionName,
            "list" => $list
        ]);
        return $response;
    }

    private function formatBarcode($list) {
        return 'C' . $list['id'] . "-U" . $list['user']->getUname();
    }
}
