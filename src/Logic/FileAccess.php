<?php


namespace App\Logic;


use App\Entity\ImportedData;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

class FileAccess
{
	private $params;
	private $security;

	public function __construct(ParameterBagInterface $params, Security $security)
	{
		$this->params = $params;
		$this->security = $security;
	}

	/**
	 * Retourne le nom de l'utilisateur
	 */
	private function getUsername(): string
	{
		return $this->security->getUser()->getUsername();
	}

	/**
	 * Retourne le chemin du fichier ETU pour les relevés.
	 * @param string $filter
	 * @return string
	 */
	public function getRnEtu(string $filter = ''): string
	{
		return $this->filter($this->params->get('output_etu_rn'), $this->getUsername() . '.etu', $filter);
	}

	/**
	 * @param string $dir
	 * @param string $filename
	 * @param string $filter
	 * @return string
	 */
	private function filter(string $dir, string $filename, string $filter = '')
	{
		if ($filter == 'd')
			return $dir;
		if ($filter == 'f')
			return $filename;

		return $dir . $filename;
	}

	/**
	 * Retourne le chemin du fichier ETU pour les attestations.
	 * @param string $filter
	 * @return string
	 */
	public function getAttestEtu(string $filter = ''): string
	{
		return $this->filter($this->params->get('output_etu_attest'), $this->getUsername() . '.etu', $filter);
	}

	/**
	 * Retourne le chemin du dossier contenant les relevés temporaires avant transfert.
	 */
	public function getTmpRn(): string
	{
		return $this->params->get('output_tmp_rn') . $this->getUsername() . '/';
	}

	/**
	 * Retourne le chemin du dossier contenant les attestations temporaires avant transfert.
	 */
	public function getTmpAttest(): string
	{
		return $this->params->get('output_tmp_attest') . $this->getUsername() . '/';
	}

	/**
	 * Retourne le chemin du dossier contenant les réléves.
	 */
	public function getRn(): string
	{
		return $this->params->get('output_dir_rn');
	}

	/**
	 * Retourne le chemin du dossier contenant les attestations.
	 */
	public function getAttest(): string
	{
		return $this->params->get('output_dir_attest');
	}

	public function getDirByMode(int $mode): string
	{
		return $mode == ImportedData::RN ? $this->getRn() : $this->getAttest();
	}

	/**
	 * Retourne le chemin du fichier ETU en fonction du mode
	 * @param int $mode 0 = RN / 1 = ATTEST
	 * @param string $filter
	 * @return string
	 */
	public function getEtuByMode(int $mode, string $filter = ''): string
	{
		return $mode == ImportedData::RN ? $this->getRnEtu($filter) : $this->getAttestEtu($filter);
	}

	/**
	 * Retourne le chemin du dossier contenant les pdf temporaires en fonction du mode
	 * @param int $mode 0 = RN / 1 = ATTEST
	 */
	public function getTmpByMode(int $mode): string
	{
		return $mode == ImportedData::RN ? $this->getTmpRn() : $this->getTmpAttest();
	}

    /**
     * Retourne le chemin du fichier pdf complet de relevés.
     * @param string $filter
     * @return string
     */
	public function getPdfRn(string $filter = ''): string
	{
        return $this->filter($this->params->get('output_tmp_pdf'), 'rn_' . $this->security->getUser()->getUsername() . '.pdf', $filter);
//		return $this->params->get('output_tmp_pdf') . 'rn_' . $this->security->getUser()->getUsername() . '.pdf';
	}

	/**
	 * Retourne le chemin du fichier pdf complet d'attestations.
	 * @return string
	 */
	public function getPdfAttest(string $filter = ''): string
	{
		return $this->filter($this->params->get('output_tmp_pdf'), 'attest_' . $this->security->getUser()->getUsername() . '.pdf', $filter);
	}

	/**
	 * Retourne le chemin du fichier pdf complet en fonction du mode.
	 * @param int $mode 0 = RN / 1 = ATTEST
	 * @return string
	 */
	public function getPdfByMode(int $mode, string $filter = ''): string
	{
		return $mode == ImportedData::RN ? $this->getPdfRn($filter) : $this->getPdfAttest($filter);
	}

	public function getTamponFolder(): string
	{
		return $this->params->get('output_tmp_tampon') . $this->security->getUser()->getUsername() . '/';
	}

	public function getTamponRn(string $filter = ''): string
	{
		return $this->filter($this->getTamponFolder(), 'tampon_rn.png', $filter);
	}

	public function getTamponAttest(string $filter = ''): string
	{
		return $this->filter($this->getTamponFolder(), 'tampon_attest.png', $filter);
	}

	public function getTamponByMode(int $mode, string $filter = ''): string
	{
		return $mode == ImportedData::RN ? $this->getTamponRn($filter) : $this->getTamponAttest($filter);
	}

	public function getPdfTamponRn(string $filter = ''): string
	{
		return $this->filter($this->getTamponFolder(), 'pdf_rn.pdf', $filter);
	}

	public function getPdfTamponAttest(string $filter = ''): string
	{
		return $this->filter($this->getTamponFolder(), 'pdf_attest.pdf', $filter);
	}

	public function getPdfTamponByMode(int $mode, string $filter = ''): string
	{
		return $mode == ImportedData::RN ? $this->getPdfTamponRn($filter) : $this->getPdfTamponAttest($filter);
	}
}