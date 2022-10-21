<?php

namespace src;

class Promise
{
    public function __construct(private string $fileName) {}

    public function wait() {
        while (file_exists($this->fileName)) {
        }
    }
}
