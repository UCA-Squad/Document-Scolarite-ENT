<?php


namespace App\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HistoryRepository")
 */
class History
{
	// Imported before Selection
	public const Imported = 1;
	// Transfered after selection
	public const Transfered = 2;
	// Modified after suppression
	public const Modified = 3;

	/**
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	private $date;

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	private $state;

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	private $nb_files;

	/**
	 * @ORM\ManyToOne(targetEntity="ImportedData", inversedBy="history")
	 */
	private $importedData;

	public function __construct(int $nb_files, int $state = self::Imported)
	{
		$this->setDate();
		$this->setNbFiles($nb_files);
		$this->setState($state);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getDate(): DateTime
	{
		return $this->date;
	}

	public function getState(): int
	{
		return $this->state;
	}

	public function getNbFiles(): int
	{
		return $this->nb_files;
	}

	public function setDate(): void
	{
		$this->date = new DateTime('now', new \DateTimeZone('Europe/Paris'));
	}

	public function setState(int $state): void
	{
		if ($state != self::Imported && $state != self::Transfered && $state != self::Modified)
			return;
		$this->state = $state;
	}

	public function setNbFiles(int $nb_files): void
	{
		if ($nb_files < 0)
			$nb_files = 0;
		$this->nb_files = $nb_files;
	}

	public function getImportedData(): ImportedData
	{
		return $this->importedData;
	}

	public function setImportedData(ImportedData $importedData): void
	{
		$this->importedData = $importedData;
	}

}