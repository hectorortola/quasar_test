<?php

namespace App\Entity;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DataSerializer extends AbstractController
{

    private $serializer;

    public function __construct()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function serialize($data)
    {
        return $this->serializer->serialize($data, 'json',
            ['circular_reference_handler' => function ($object) {
                return $object->getId();
                }
            ]
        );
    }

}
