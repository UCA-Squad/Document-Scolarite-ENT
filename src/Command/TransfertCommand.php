<?php


namespace App\Command;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Logic\CustomFinder;
use App\Logic\DocapostFast;
use App\Logic\FileAccess;
use App\Repository\ImportedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

class TransfertCommand extends Command
{
	protected static $defaultName = 'transfert';

	private $finder;
	private $docapost;
	private $em;
	private $repo;

	public function __construct(CustomFinder $finder, DocapostFast $docapost, EntityManagerInterface $em, ImportedDataRepository $repo)
	{
		$this->finder = $finder;
		$this->docapost = $docapost;
		$this->em = $em;
		$this->repo = $repo;
		parent::__construct(self::$defaultName);
	}

	protected function configure()
	{
		$this->addArgument('mode', InputArgument::REQUIRED)
			->addArgument('from', InputArgument::REQUIRED)
			->addArgument('to', InputArgument::REQUIRED)
			->addArgument('username', InputArgument::REQUIRED)
			->addArgument('ids', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$mode = $input->getArgument('mode');
		$ids = $input->getArgument('ids');
		$from = $input->getArgument('from');
		$to = $input->getArgument('to');
		$username = $input->getArgument('username');

		$documents = $this->finder->getDirsName($from);
		$docs_count = count($documents);
		if ($docs_count == 0)
			return 1;

		if (!is_dir($to))
			mkdir($to);

		$not_transfered = 0;
		foreach ($documents as $doc) {
			if (isset($ids) && in_array($doc, $ids)) {
				// si pas séléctionné pour transfert mais déjà présent sur le serveur
				if (!file_exists($to . $doc . '/' . $this->finder->getFirstFile($from . $doc)))
					$not_transfered++;
				continue;
			}
			if (!is_dir($to . $doc))
				mkdir($to . $doc);
			$fileFrom = $this->finder->getFirstFile($from . $doc);
			$index = $this->finder->getFileIndex($to . $doc, $fileFrom);
			if ($index != -1) {
				unlink($to . $doc . '/' . $this->finder->getFileByIndex($to . $doc, $index));
			}

			if ($this->docapost->isEnable()) {
				// Génére nom random
				$randName = bin2hex(random_bytes(5)) . '.pdf';
				// Met à jour le nom du fichier
				rename($from . $doc . '/' . $fileFrom, $from . $doc . '/' . $randName);
				// Envoi sur docapost
				$id = $this->docapost->uploadDocument($from . $doc . '/' . $randName, 'test');
				// Récupère le binaire pdf signé
				$docaDoc = $this->docapost->downloadDocument($id);
				// Écris le pdf reçu dans le dossier de destination
				file_put_contents($to . $doc . '/' . $fileFrom, $docaDoc);
				// Supprime le fichier temporaire
				unlink($from . $doc . '/' . $randName);
			} else {
				rename($from . $doc . '/' . $fileFrom, $to . $doc . '/' . $fileFrom);
			}
		}
		$this->finder->deleteDirectory($from);
		$this->update_transfered_files($mode, $docs_count, $not_transfered, $username);

		return 0;
	}

	/**
	 * Update the field 'nbFiles' of the last ImportedData.
	 * @param int $mode
	 * @param int $docs_count
	 * @param int $not_transfered
	 * @param string $username
	 */
	private function update_transfered_files(int $mode, int $docs_count, int $not_transfered, string $username)
	{
		$data = $this->repo->findLastDataByMode($mode, $username);

		$data->getLastHistory()->setNbFiles($docs_count - $not_transfered);
		$data->getLastHistory()->setState(History::Transfered);
		$data->getLastHistory()->setDate();

		$this->em->persist($data);
		$this->em->flush();
	}
}