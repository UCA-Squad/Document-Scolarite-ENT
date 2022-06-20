<?php


namespace App\Form;


use App\Entity\ImportedData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ImportType extends AbstractType
{
	public const IMPORT = "import";
	public const DELETE = "delete";

	public const RELEVE = "releve";
	public const ATTEST = "attest";

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$type = $options["type"];
		$act = $options["act"];

		if ($act == self::IMPORT) {
			if ($type == self::RELEVE) {
				$builder->add('pdf', FileType::class, ['label' => "Relevé de notes", 'help' => '', 'attr' => ['accept' => '.pdf']])
					->add('etu', FileType::class, ['label' => "Fichier ETU", 'attr' => ['accept' => '.etu']])
					->add('semestre', ChoiceType::class, ['choices' =>
						['1' => 1, '2' => 2, 'A' => 'A', '1p1' => '1p1', '1p2' => '1p2', '2p1' => '2p1', '2p2' => '2p2', 'Ap1' => 'Ap1', 'Ap2' => 'Ap2']
					])
					->add('session', ChoiceType::class, ['choices' => ['1' => 1, '2' => 2, 'U' => 'U']])
					->add('libelle_form', TextType::class, ['label' => 'Libellé', 'attr' => ['maxlength' => 25]])
					->add('tampon', FileType::class, ['label' => 'Tampon', 'required' => false, 'mapped' => false, 'attr' => ['accept' => '.png']])
					->add('num_page', IntegerType::class, ['label' => "Numéro de page d'exemple pour tampon", 'data' => 1, 'mapped' => false, 'required' => false, 'attr' => ['min' => 1]])
					->add('submit', SubmitType::class, ['label' => 'Charger']);
			} else if ($type == self::ATTEST) {
				$builder->add('pdf', FileType::class, ['label' => 'Attestation de réussite PDF', 'attr' => ['accept' => '.pdf']])
					->add('etu', FileType::class, ['label' => 'Fichier ETU', 'attr' => ['accept' => '.etu']])
					->add('tampon', FileType::class, ['label' => 'Tampon', 'required' => false, 'mapped' => false, 'attr' => ['accept' => '.png']])
					->add('num_page', IntegerType::class, ['label' => "Numéro de page d'exemple pour tampon", 'data' => 1, 'mapped' => false, 'required' => false, 'attr' => ['min' => 1]])
					->add('submit', SubmitType::class, ['label' => 'Charger']);
			}
		} else if ($act == self::DELETE) {
			$year = new \DateTime();
			$year = $year->format('Y');
			if ($type == self::RELEVE) {
				$builder->add('etu', FileType::class, ['label' => 'Fichier ETU', 'attr' => ['accept' => '.etu']])
					->add('semestre', ChoiceType::class, ['choices' =>
						['1' => 1, '2' => 2, 'A' => 'A', '1p1' => '1p1', '1p2' => '1p2', '2p1' => '2p1', '2p2' => '2p2', 'Ap1' => 'Ap1', 'Ap2' => 'Ap2']
					])
					->add('session', ChoiceType::class, ['choices' => ['1' => 1, '2' => 2, 'U' => 'U']])
					->add('year', IntegerType::class, ['label' => 'Année universitaire (yyyy)', 'data' => $year, 'constraints' => [new Length(['min' => 4, 'max' => 4])]])
					->add('submit', SubmitType::class, ['label' => 'Charger']);
			} else if ($type == self::ATTEST) {
				$builder->add('etu', FileType::class, ['label' => 'Fichier ETU', 'attr' => ['accept' => '.etu']])
					->add('year', IntegerType::class, ['label' => 'Année universitaire (yyyy)', 'data' => $year, 'constraints' => [new Length(['min' => 4, 'max' => 4])]])
					->add('submit', SubmitType::class, ['label' => 'Charger']);
			}
		}
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => ImportedData::class,
			'type' => self::RELEVE,
			'act' => self::IMPORT,
		]);
	}


}