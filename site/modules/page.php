<?php

class Page {
    private $template;

    public function __construct($template) {
        if (!file_exists($template)) {
            throw new Exception("Template file not found: $template");
        }
        $this->template = $template;
    }

    public function Render($data) {
        $output = file_get_contents($this->template);

        foreach ($data as $key => $value) {
            $output = str_replace("{{ $key }}", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $output);
        }

        echo $output;
    }
}