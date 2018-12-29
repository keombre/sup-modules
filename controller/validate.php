<?php

namespace modules\lists\controller;

class validate extends lists {

    public function student($request, &$response, $args) {
        $state = $this->container->db->get('main', 'state', ['id' => $this->listID]);

        if ($state == 0) {
            if (!$this->validate($response))
                return $response;
            else if (!$request->isPut())
                return (new preview($this->container))->withListID($this->listID)->preview($request, $response, $args);
        }
        
        if ($request->isPut()) {
            if ($state == 0) {
                $this->container->db->update("main", ["state" => 1], ["id" => $this->listID]);
                return $this->redirectWithMessage($response, 'lists-preview', "status", ["Kánon odeslán"], ["id" => $this->listID]);
            }
        }

        return $response->withRedirect($this->container->router->pathFor('lists-preview', ["id" => $this->listID]), 301);
    }

    public function teacher($request, &$response, $args) {}
    public function admin($request, &$response, $args) {}

    private function validate(&$response) {
        $list = $this->container->db->select("lists", "book", ["list" => $this->listID]);
        
        if (count($list) != 20) {
            $this->redirectWithMessage($response, 'lists-edit', "error", ["Nezvolili jste 20 knih"], ["id" => $this->listID]);
            return false;
        }
        
        $books = [];
        foreach ($this->container->db->select("books", "*") as $book) {
            $books[$book['id']] = $book;
        }

        $genereCounter = [];
        $regionCounter = [];
        $authorCounter = [];

        $genereInfo = [];
        $regionInfo = [];

        foreach ($this->container->db->select("generes", "*") as $genere) {
            $genereCounter[$genere['id']] = 0;
            $genereInfo[$genere['id']] = $genere;
        }

        foreach ($this->container->db->select("regions", "*") as $region) {
            $regionCounter[$region['id']] = 0;
            $regionInfo[$region['id']] = $region;
        }

        foreach ($list as $book) {
            if (!array_key_exists($book, $books)) {
                $this->container->db->delete("lists", ["list" => $this->listID, "book" => $book]);
            }
            
            if (!array_key_exists($books[$book]['author'], $authorCounter))
                $authorCounter[$books[$book]['author']] = 0;
            
            @$genereCounter[$books[$book]['genere']]++;
            @$regionCounter[$books[$book]['region']]++;
            $authorCounter[$books[$book]['author']]++;
        }

        $message = "";

        foreach ($authorCounter as $author => $count) {
            if ($count > 2 && $author != "") {
                $message .= $author . "<br />";
            }
        }

        if ($message == "") {

            $regionMessage = $this->checkCount($regionInfo, $regionCounter);
            $genereMessage = $this->checkCount($genereInfo, $genereCounter);

            if ($regionMessage != "") {
                $message .= "<h5><b>Období:</b></h5>" . PHP_EOL;
                $message .= $regionMessage;
            }
            if ($genereMessage != "") {
                if ($regionMessage != "")
                    $message .= "<hr>";
                
                $message .= "<h5><b>Žánry:</b></h5>" . PHP_EOL;
                $message .= $genereMessage;
            }
            if ($message != "") {
                $this->redirectWithMessage($response, 'lists-edit', "message", ["title" => "Nezvolili jste dostatečný počet děl", "message" => $message], ["id" => $this->listID]);
                return false;
            } else {
                return true;
            }
        } else {
            $this->redirectWithMessage($response, 'lists-edit', "message", ["title" => "Máte více než dvě díla od následujících autorů", "message" => $message], ["id" => $this->listID]);
            return false;
        }
    }

    private function getListID($args) {
        if (array_key_exists('id', @$args)) {
            $id = filter_var(@$args['id'], FILTER_SANITIZE_STRING);
            $version = $this->container->db->get("settings", "active_version");
            if ($this->container->db->has("main", ["id" => $id, "user" => $this->userID, "version" => $version]))
                $this->listID = $id;
        } else
            $this->listID = true;
    }

    private function checkCount($info, $counter) {
        $ret = true;
        $message = "";
        foreach ($counter as $id => $count) {
            if (!array_key_exists($id, $info))
                continue;
            
            if (!is_null($info[$id]['min']) && $info[$id]['min'] > $count) {
                $message .= "<span class='text-danger'><span class='glyphicon glyphicon-remove'></span> " . $info[$id]['name'] . " (<b>" . $info[$id]['min'] . " ≤</b> " . $count . " ≤ " . (is_numeric($info[$id]['max'])?$info[$id]['max']:'∞') . ")</span><br />" . PHP_EOL;
                $ret = false;
            } elseif (!is_null($info[$id]['max']) && $info[$id]['max'] < $count) {
                $message .= "<span class='text-danger'><span class='glyphicon glyphicon-remove'></span> " . $info[$id]['name'] . " (" . (is_numeric($info[$id]['min'])?$info[$id]['min']:'0') . " ≤ " . $count . " <b>≤ " . $info[$id]['max'] . "</b>)</span><br />" . PHP_EOL;
                $ret = false;
            } else
                $message .= "<span class='text-success'><span class='glyphicon glyphicon-ok'></span> " . $info[$id]['name'] . " (" . (is_numeric($info[$id]['min'])?$info[$id]['min']:'0') . " ≤ " . $count . " ≤ " . (is_numeric($info[$id]['max'])?$info[$id]['max']:'∞') . ")</span><br />" . PHP_EOL;
        }
        return $ret ? "" : $message;
    }
}
