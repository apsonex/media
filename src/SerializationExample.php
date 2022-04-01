<?php

namespace Apsonex\Media;

use Illuminate\Queue\SerializesModels;

class SerializationExample
{
    public string $firstName;

    public string $lastName;

    protected string $phone;

    private string $email;

    public function __construct($name)
    {
        $this->firstName = $name;
    }


    public function handle($data)
    {
        dd(
            $this->firstName . PHP_EOL,

            $data,
        );
    }
}