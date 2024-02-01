<?php


namespace App\Normalizer;


use App\Entity\Student;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class StudentNormalizer extends ObjectNormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = []): Student
    {
        $numero = $data[0];
        $name = $data[1];
        $surname = $data[2];
        $birthday = $data[3];
        $mail = empty($data[5]) ? $data[4] : $data[5];
        $type = $data[6] ?? "";
        $code = $data[7] ?? "";

        if (count($data) <= 12) {    // Releves
            $libelle_obj = $data[8] ?? "";
            $code_etape = $data[9] ?? "";
            $libelle = isset($data[11]) && $data[11] != "--" ? $data[11] : "";
        } else {                    // Attests
            $libelle_obj = $data[9];
            $code_etape = $data[10];
            $libelle = isset($data[12]) && $data[12] != "--" ? $data[12] : "";
        }

        return new Student($numero, $name, $surname, $birthday, $mail, $libelle, $code, $code_etape, $type, $libelle_obj);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return Student::class == $type;
    }

}