<?php


namespace App\Entity;


use App\Logic\CustomFinder;

class Student implements \JsonSerializable
{
    protected $numero;
    protected $name;
    protected $surname;
    protected $mail;
    protected $libelle;
    protected $type;
    protected $code;
    protected $code_etape;
    protected $birthday;
    protected $libelle_obj;


    // The index of the last pdf file
    protected $index;
    // The new pdf file link to this student
    protected $file;

    public function __construct(int $numero, string $name, string $surname, string $birthday, string $mail, string $libelle, string $code, string $code_etape, string $type, string $libelle_obj)
    {
        $this->numero = $numero;
        $this->name = $name;
        $this->surname = $surname;
        $this->birthday = $birthday;
        $this->mail = $mail;
        $this->code = $code;
        $this->setLibelle($libelle);
        $this->index = -1;
        $this->code_etape = $code_etape;
        $this->type = $type;
        $this->setLibelleObj($libelle_obj);
    }

    public function LoadFile(string $tmp_dir, string $output_dir)
    {
        if (is_dir($tmp_dir . $this->getNumero())) {
            $finder = new CustomFinder();
            $this->setFile($finder->getFirstFile($tmp_dir . $this->getNumero()));
            $this->setIndex($finder->getFileIndex($output_dir . $this->getNumero(), $this->file));
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCodeEtape(): string
    {
        return $this->code_etape;
    }

    public function getLibelleObj(): string
    {
        return (string)$this->libelle_obj;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    protected function setNumero(int $numero): void
    {
        $this->numero = $numero;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    protected function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getMail(): string
    {
        return $this->mail;
    }

    protected function setMail(string $mail): void
    {
        $this->mail = $mail;
    }

    public function getLibelle(): string
    {
        return (string)$this->libelle;
    }

    protected function setLibelle(string $libelle): void
    {
        $this->libelle = str_replace('/', ' ', $libelle);
    }

    protected function setLibelleObj(string $libelle): void
    {
        $this->libelle_obj = str_replace('/', ' ', $libelle);
    }

    public function getCode(): string
    {
        return $this->code ?? "";
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): void
    {
        $this->file = $file;
    }

    public function getBirthday(): string
    {
        return $this->birthday;
    }

    protected function setBirthday($birthday): void
    {
        $this->birthday = $birthday;
    }

    public function jsonSerialize(): array
    {
        return [
            'numero' => $this->getNumero(),
            'name' => $this->getName(),
            'surname' => $this->getSurname(),
            'mail' => $this->getMail(),
            'libelle' => $this->getLibelle(),
            'code' => $this->getCode(),
            'code_etape' => $this->getCodeEtape(),
            'birthday' => $this->getBirthday(),
            'libelle_obj' => $this->getLibelleObj(),
            'index' => $this->getIndex(),
            'file' => $this->getFile(),
            'type' => $this->getType()
        ];
    }
}