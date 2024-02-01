<?php


namespace App\Parser;


use App\Entity\ImportedData;
use App\Entity\Student;
use App\Normalizer\StudentNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class EtuParser implements IEtuParser
{
    protected $num_regexes;

    protected $name_regexes;

    protected $nb_doublons;

    public function getNbDoublons(): int
    {
        return $this->nb_doublons;
    }

    public function __construct()
    {
        $this->nb_doublons = 0;

        try {
            $this->name_regexes = json_decode(file_get_contents("../src/Parser/NameRegexes.json"));
            $this->num_regexes = json_decode(file_get_contents("../src/Parser/NumRegexes.json"));
        } catch (\Exception $e) {
            throw new \Exception("L'un des fichiers regex json n'est pas valide");
        }

        if ($this->name_regexes == null || $this->num_regexes == null)
            throw new \Exception("L'un des fichiers regex json n'est pas valide");
    }

    /**
     * Parse etu file into Students array
     * @param string $filename
     * @return array
     * @throws ExceptionInterface
     */
    public function parseETU(string $filename): array
    {
        $content = file_get_contents($filename);

        $content = utf8_encode($content);
        $content = mb_convert_encoding($content, "UTF-8", mb_list_encodings());

        $content = preg_replace('/^\h*\v+/m', '', $content);

        $serializer = new Serializer([new StudentNormalizer()],
            [new CsvEncoder(array(CsvEncoder::DELIMITER_KEY => ";", CsvEncoder::NO_HEADERS_KEY => true))]);

        $students = $serializer->decode($content, 'csv', array(CsvEncoder::DELIMITER_KEY => ";", CsvEncoder::NO_HEADERS_KEY => true));
        $studs = [];
        foreach ($students as $student) {
            $stud = $serializer->denormalize($student, Student::class);
            if (!(in_array($stud, $studs)))
                $studs[] = $stud;
            else
                $this->nb_doublons++;
        }
        return $studs;
    }

    public function getReleveFileName(string $date, Student $stud, ImportedData $data = null): string
    {
        $code = $stud->getCodeEtape() != "--" && $stud->getCodeEtape() != "" ? $stud->getCodeEtape() : $stud->getCode();

        return $stud->getNumero() . '_' . $date . '_' . $code . '_' . "sess" . $data->getSession() . "_sem" . $data->getSemestre() .
            "_" . ($stud->getLibelle() == "" ? $data->getLibelleForm() : $stud->getLibelle()) . '.pdf';
    }

    public function getAttestFileName(string $date, Student $stud, ImportedData $data = null): string
    {
        $separator = "AR";
        $libelle = $stud->getLibelle();
        $code = $stud->getCodeEtape();
        if ($stud->getType() == "ELP") {
            $separator = "ARM";
            $code = $stud->getCode();
        } else if ($stud->getType() == "VET")
            $separator = "ARN";
        else if ($stud->getType() == "VDI") {
            $libelle = $stud->getLibelleObj();
            $separator = "ARD";
        }

        return $stud->getNumero() . '_' . $date . '_' . $separator . '_' . $code . '_' . $libelle . '.pdf';
    }

    /**
     * Return the Student with the given numero
     * @param int $num Student numero
     * @param array $students
     * @return mixed
     */
    public function getEtuByNum(int $num, array $students): ?Student
    {
        for ($i = 0; $i < count($students); $i++) {
            if ($students[$i]->getNumero() == $num)
                return $students[$i];
        }
        return null;
    }

    /**
     * Return the Student with the given name
     * @param string $name Concatenation of name. ' '.surname
     * @param array $students
     * @param string|null $date
     * @return mixed
     */
    public function getEtuByName(string $name, array $students, string $date = null): ?Student
    {
        for ($i = 0; $i < count($students); $i++) {
            $id = strtolower($students[$i]->getName() . ' ' . $students[$i]->getSurname());
            if ($id == strtolower($name) || $id == strtolower($this->stripAccents($name)))
                if ($date == null || ($date == $students[$i]->getBirthday()))
                    return $students[$i];
        }
        return null;
    }

    function stripAccents($str): string
    {
//        return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'),
//            'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        return strtr($str, 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
//        return strtr(mb_convert_encoding($str, 'UTF-8', mb_list_encodings()),
//            mb_convert_encoding('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'UTF-8', mb_list_encodings()),
//            'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }

    public function findStudentByNum(string $content, array $students)
    {
        foreach ($this->num_regexes as $regex) {
            if (preg_match($regex, $content, $matches)) {
                for ($i = 1; $i < count($matches); $i++) {
                    $res = array_search($this->getEtuByNum(str_replace(' ', '', $matches[$i]), $students), $students);
                    if ($res !== false)
                        return $res;
                }
            }
        }
        return false;
    }

    public function findStudentByName(string $content, array $students)
    {
        foreach ($this->name_regexes as $regex_info) {
            if (preg_match($regex_info->regex, $content, $matches)) {
                $date = $regex_info->indexDate > 0 && isset($matches[$regex_info->indexDate]) ? $matches[$regex_info->indexDate] : null;
                if ($regex_info->indexNom == $regex_info->indexPrenom && isset($matches[$regex_info->indexPrenom]))
                    $id = preg_replace('/[ ]+/', ' ', trim($matches[$regex_info->indexPrenom]));
                else {
                    $first = preg_replace('/[ ]+/', ' ', trim($matches[$regex_info->indexPrenom]));
                    $second = preg_replace('/[ ]+/', ' ', trim($matches[$regex_info->indexNom]));
                    $id = $first . " " . $second;
                }
                $index = array_search($this->getEtuByName($id, $students, $date), $students);
                if ($index !== false)
                    return $index;
            }
        }
        return false;
    }
}
