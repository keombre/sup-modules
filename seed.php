<?php

namespace modules\lists;

class seed {
    
    protected $container;
    protected $db;

    function __construct(\Slim\Container $container) {
        $this->container = $container;
        $this->db = $this->container->db;
        $this->update();
    }

    function update() {
        if (!$this->db->has("sqlite_master", ["AND" => ["type" => "table", "OR" => [
            "name" => ["settings", "versions", 'books', 'regions', 'generes', 'main', 'lists']
        ]]])) {
            $this->seed();
        }
    }

    function seed() {

        $this->db->query("CREATE TABLE IF NOT EXISTS settings (
            active_version INTEGER NULL,
            open_editing INTEGER,
            open_accepting INTEGER,
            open_drawing INTEGER
        );");

        if (!$this->db->count("settings")) {
            $this->db->insert("settings", [
                "open_editing"  => 0,
                "open_accepting" => 0,
                "open_drawing"  => 0
            ]);
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS versions (
            id INTEGER PRIMARY KEY,
            name TEXT
        );");

        $this->db->query("CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY,
            name TEXT,
            author TEXT NULL,
            region INTEGER NULL,
            genere INTEGER NULL,
            version INTEGER
        );");

        $this->db->query("CREATE TABLE IF NOT EXISTS regions (
            id INTEGER PRIMARY KEY,
            name TEXT,
            min INTEGER,
            max INTEGER,
            version INTEGER
        );");

        $this->db->query("CREATE TABLE IF NOT EXISTS generes (
            id INTEGER PRIMARY KEY,
            name TEXT,
            min INTEGER,
            max INTEGER,
            version INTEGER
        );");

        $this->db->query("CREATE TABLE IF NOT EXISTS main (
            id INTEGER PRIMARY KEY,
            user INTEGER,
            created INTEGER,
            state INTEGER DEFAULT 0,
            version INTEGER
        );"); // 0 - editing, 1 - sent, 2 - accepted

        $this->db->query("CREATE TABLE IF NOT EXISTS lists (
            list INTEGER,
            book INTEGER
        );");
        
    }
}
