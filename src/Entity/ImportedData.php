<?php


namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ImportedDataRepository;

#[ORM\Entity(repositoryClass: ImportedDataRepository::class)]
class ImportedData implements \JsonSerializable
{
    public const RN = 0;
    public const ATTEST = 1;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 3, nullable: true)]
    private ?string $semestre;

    #[ORM\Column(type: "string", length: 1, nullable: true)]
    private ?string $session;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $libelle_form;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private string $libelle_obj;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private string $libelle;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private string $pdf_filename;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private string $etu_filename;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $year;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $type;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $code;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $code_obj;

    #[ORM\Column(type: "integer", nullable: false)]
    private $nb_students;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $username;

    #[ORM\OneToMany(mappedBy: "importedData", targetEntity: History::class, cascade: ["all"], fetch: "EAGER")]
    private Collection $history;

    public function __construct()
    {
        $this->history = new ArrayCollection();
    }

    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function getLastHistory(): ?History
    {
        if (!$this->history || $this->history->count() == 0)
            return null;
        return $this->history[$this->history->count() - 1];
    }

    public function addHistory(History $hist): void
    {
        $hist->setImportedData($this);
        $this->history->add($hist);
    }

    public function getSemestre(): string
    {
        return (string)$this->semestre;
    }

    public function setSemestre(?string $semestre): self
    {
        $this->semestre = $semestre;

        return $this;
    }

    public function getSession(): string
    {
        return (string)$this->session;
    }

    public function setSession(?string $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getLibelleForm(): string
    {
        return (string)$this->libelle_form;
    }

    public function isRn(): bool
    {
        return !empty($this->semestre) && !empty($this->session) && !empty($this->libelle_form);
    }

    /**
     * Call by the form
     * @param string $libelle
     */
    public function setLibelleForm(?string $libelle): self
    {
        $this->libelle_form = str_replace('/', ' ', $libelle);

        return $this;
    }

    public function LoadStudentData(Student $stud, string $year, int $nb_students, string $username): void
    {
        $this->year = $year;
        $this->type = $stud->getType();
        $this->code = $stud->getCodeEtape();
        $this->code_obj = $stud->getCode();
        $this->nb_students = $nb_students;
        $this->username = $username;
        $this->libelle_obj = $stud->getLibelleObj();
        $this->libelle = $stud->getLibelle();
    }

    public function setPdfFilename(string $pdf_filename): self
    {
        $this->pdf_filename = $pdf_filename;

        return $this;
    }

    public function setEtuFilename(string $etu_filename): self
    {
        $this->etu_filename = $etu_filename;

        return $this;
    }

    public function getPdfFilename(): string
    {
        return (string)$this->pdf_filename;
    }

    public function getEtuFilename(): string
    {
        return (string)$this->etu_filename;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getYear(): string
    {
        return (string)$this->year;
    }

    public function getType(): string
    {
        return (string)$this->type;
    }

    public function getCode(): string
    {
        return (string)$this->code;
    }

    public function getCodeObj(): string
    {
        return (string)$this->code_obj;
    }

    public function getLibelleObj(): string
    {
        return (string)$this->libelle_obj;
    }

    public function getLibelle(): string
    {
        return (string)$this->libelle;
    }

    public function getNbStudents(): int
    {
        return $this->nb_students;
    }

    public function getUsername(): string
    {
        return (string)$this->username;
    }

    public function setYear(string $year): void
    {
        $this->year = $year;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'libelle_obj' => $this->getLibelleObj(),
            'libelle' => $this->getLibelle(),
            'pdf_filename' => $this->getPdfFilename(),
            'etu_filename' => $this->getEtuFilename(),
            'year' => $this->getYear(),
            'type' => $this->getType(),
            'code' => $this->getCode(),
            'code_obj' => $this->getCodeObj(),
            'nb_students' => $this->getNbStudents(),
            'username' => $this->getUsername(),
            'semestre' => $this->getSemestre(),
            'session' => $this->getSession(),
            'libelle_form' => $this->getLibelleForm(),
            'history' => $this->getHistory()->toArray()
        ];
    }

}