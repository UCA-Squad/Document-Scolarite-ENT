<?php

namespace App\Command;

use App\Logic\FileAccess;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sinchro:nb_files', description: 'Synchronise le nombre de fichiers sur le serveur')]
class SinchroNbFilesCommand extends Command
{
    public function __construct(private ImportedDataRepository $repo, private FileAccess $file_access, private IEtuParser $parser, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $imports = $this->repo->findAll();
        $pb = new ProgressBar($output, count($imports));
        $pb->start();

        foreach ($imports as $import) {

            if (empty($import->getSemestre()) && empty($import->getSession())) {
                $folder = $this->file_access->getAttest();
                $pattern = "*/" . $this->parser->getAttestFileName($import, '*');
            } else {
                $folder = $this->file_access->getRn();
                $pattern = "*/" . $this->parser->getReleveFileName($import, '*');
            }

            $files = glob($folder . $pattern);

            if (!empty($files)) {
                $import->setNbStudents(count($files));
                $import->getLastHistory()->setNbFiles(count($files));
            } else {

            }

            $pb->advance();

        }

        $pb->finish();
        $this->em->flush();

        return Command::SUCCESS;
    }
}