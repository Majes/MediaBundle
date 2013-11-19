<?php 
namespace Majes\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\Session\Session;

class MediaType extends AbstractType
{


	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
    	    'data_class' => 'Majes\MediaBundle\Entity\Media',
    	    'csrf_protection' => false,
    	));
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('type', 'choice', array(
            'choices' => array(
                'picture' => 'Picture',
                'video' => 'Video',
                'embed' => 'Embed',
                'document' => 'Document'
                ),
            'required' => true));

        $builder->add('folder', 'text', array(
            'required' => true));

        $builder->add('file', 'file', array('required' => false, 'image_path' => 'webPath'));
        $builder->add('embedded', 'textarea', array('required' => false, 'label' => 'If "Embed" type selected'));

        
        $builder->add('title', 'text', array('required' => false));
        $builder->add('author', 'text', array('required' => false));
        $builder->add('is_protected', 'checkbox', array('required' => false));


    }

    public function getName()
    {
        return 'media';
    }
}