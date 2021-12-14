<?php


namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImportedDataRepository")
 */
class ImportedData
{
	public const RN = 0;
	public const ATTEST = 1;

	////////////////////Form Field //////////////////////
	private $pdf;
	private $etu;
	/**
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	private $semestre;
	/**
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	private $session;
	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $libelle_form;
	/////////////////////////////////////////////////////

	/**
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 */
	private $libelle_obj;

	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 */
	private $libelle;

	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 */
	private $pdf_filename;

	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 */
	private $etu_filename;

	/**
	 * @ORM\Column(type="string", length=10, nullable=false)
	 */
	private $year;

	/**
	 * @ORM\Column(type="string", length=10, nullable=false)
	 */
	private $type;

	/**
	 * @ORM\Column(type="string", length=10, nullable=false)
	 */
	private $code;

	/**
	 * @ORM\Column(type="string", length=10, nullable=false)
	 */
	private $code_obj;

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	private $nb_students;

	/**
	 * @ORM\Column(type="string", length=10, nullable=false)
	 */
	private $username;

	/**
	 * @ORM\OneToMany(targetEntity="History", mappedBy="importedData", cascade={"all"})
	 */
	private $history;

	public function __construct()
	{
		$this->history = new ArrayCollection();
		$this->addHistory(new History(0));
	}

	public function getPdf()
	{
		return $this->pdf;
	}

	public function setPdf(UploadedFile $pdf): void
	{
		$this->pdf = $pdf;
	}

	public function getHistory(): Collection
	{
		return $this->history;
	}

	public function getLastHistory(): ?History
	{
		if (!$this->history || count($this->history) == 0)
			return null;
		return $this->history[count($this->history) - 1];
	}

	public function addHistory(History $hist)
	{
		$hist->setImportedData($this);
		$this->history->add($hist);
	}

	public function getEtu()
	{
		return $this->etu;
	}

	public function setEtu(UploadedFile $etu): void
	{
		$this->etu = $etu;
	}

	public function getSemestre(): string
	{
		return (string)$this->semestre;
	}

	public function setSemestre(string $semestre): void
	{
		$this->semestre = $semestre;
	}

	public function getSession(): string
	{
		return (string)$this->session;
	}

	public function setSession(string $session): void
	{
		$this->session = $session;
	}

	public function getLibelleForm(): string
	{
		return (string)$this->libelle_form;
	}

	/**
	 * Call by the form
	 * @param string $libelle
	 */
	public function setLibelleForm(string $libelle): void
	{
		$this->libelle_form = str_replace('/', ' ', $libelle);
	}

	public function LoadStudentData(Student $stud, string $year, int $nb_students, string $username)
	{
		if (!(isset($stud)))
			return;
		$this->year = $year;
		$this->type = $stud->getType();
		$this->code = $stud->getCodeEtape();
		$this->code_obj = $stud->getCode();
		$this->nb_students = $nb_students;
		$this->pdf_filename = $this->pdf->getClientOriginalName();
		$this->etu_filename = $this->etu->getClientOriginalName();
		$this->username = $username;
		$this->libelle_obj = $stud->getLibelleObj();
		$this->libelle = $stud->getLibelle();
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
		return (int)$this->nb_students;
	}

	public function getUsername(): string
	{
		return (string)$this->username;
	}

	public function setYear(string $year): void
	{
		$this->year = $year;
	}

}