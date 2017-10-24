<?php

namespace Ypf\Lib;

class Document {
    private $title;
    private $description;
    private $keywords;
    private $globals = array();
    private $links = array();
    private $styles = array();
    private $scripts = array();

    public function reset() {
        $this->title = $this->description = $this->keywords = null;
        $this->globals = $this->links = $this->styles = $this->scripts = array();
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setGlobal($key, $value) {
        $this->globals[$key] = $value;
    }

    public function getGlobal($key) {
        return isset($this->globals[$key]) ? $this->globals[$key] : NULL;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setKeywords($keywords) {
        $this->keywords = $keywords;
    }

    public function getKeywords() {
        return $this->keywords;
    }

    public function addLink($href, $rel) {
        $this->links[$href] = array(
            'href' => $href,
            'rel' => $rel,
        );
    }

    public function getLinks() {
        return $this->links;
    }

    public function addStyle($href, $rel = 'stylesheet', $media = 'screen') {
        $this->styles[$href] = array(
            'href' => $href,
            'rel' => $rel,
            'media' => $media,
        );
    }

    public function getStyles() {
        return $this->styles;
    }

    public function addScript($script) {
        $this->scripts[md5($script)] = $script;
    }

    public function getScripts() {
        return $this->scripts;
    }
}
